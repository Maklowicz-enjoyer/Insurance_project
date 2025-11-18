# Wyjaśnienie Działania Haseł Bcrypt i Persystencji

## 📋 Odpowiedzi na Pytania

### ✅ 1. Czy logowanie User działa?

**TAK, DZIAŁA!** ✓

```bash
$ curl -i -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=user@example.pl' \
  -d 'password=User123!@#'

HTTP/1.1 302 Found
Location: ../html/main.html
Set-Cookie: PHPSESSID=e8757dd29060c717469ea98a4bc7063d; ...
```

**Weryfikacja w bazie:**
```
Users_ID: 2
email:    user@example.pl
SUser:    0 (regular user)
hash:     $2y$12$NdOgRUQ.9wNb5W0McX3rFenGDnUvMcD.D5zeb57TnafDDhD7oXosu
```

---

### ✅ 2. Jak zalogować się do PhpMyAdmin?

**PhpMyAdmin jest skonfigurowany z automatycznym logowaniem!**

**Krok 1:** Uruchom PhpMyAdmin (profil dev):
```bash
docker-compose --profile dev up -d phpmyadmin
```

**Krok 2:** Otwórz przeglądarkę:
```
http://localhost:8081
```

**Dane logowania** (już skonfigurowane w docker-compose.yml):
```yaml
PMA_USER: insurance_user
PMA_PASSWORD: qaBDlRM+qLLDSgqTlICBzvk7D+zhY8H3kO01kSCNGiA=
MYSQL_ROOT_PASSWORD: qaBDlRM+qLLDSgqTlICBzvk7D+zhY8H3kO01kSCNGiA=
```

PhpMyAdmin automatycznie loguje się jako `insurance_user` bez potrzeby wpisywania hasła!

**Jeśli chcesz zalogować się ręcznie jako root:**
- Serwer: `mysql`
- Użytkownik: `root`
- Hasło: `qaBDlRM+qLLDSgqTlICBzvk7D+zhY8H3kO01kSCNGiA=` (z .env)

---

### 🔐 3. Dlaczego hashe zostały zmienione?

## Historia Problemu

### Pierwotny Problem (przed naprawą)
W `docker/mysql/init.sql` były nieprawidłowe hashe:
```sql
-- ❌ STARY, NIEPRAWIDŁOWY HASH
'$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
```

**Ten hash NIE pasował do hasła `Admin123!@#`!**

### Test Weryfikacji
```php
$password = 'Admin123!@#';
$oldHash = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

password_verify($password, $oldHash); // ✗ FALSE - NIE PASUJE!
```

**Skutek:** Logowanie zawsze zwracało "Invalid email or password"

### Rozwiązanie
Wygenerowano nowe, poprawne hashe:
```bash
$ docker exec skanpolis_php php -r "
  echo password_hash('Admin123!@#', PASSWORD_BCRYPT, ['cost' => 12]);
"
# Wynik: $2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0xCyy0D.77I1DLi3t8zFm
```

**Test:**
```php
$newHash = '$2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0xCyy0D.77I1DLi3t8zFm';
password_verify('Admin123!@#', $newHash); // ✓ TRUE - PASUJE!
```

---

### 🧪 4. Jak działają hashe bcrypt?

## Struktura Bcrypt Hash

```
$2y$12$9hOMWvaNUN3jGjD7Avy1weSq7mSB1p5dfGuwJ8B7YaQDdwY1mnYfC
│  │  │                                                      │
│  │  │                                                      └─ 31 znaków: Hash
│  │  └─ 22 znaki: Salt (losowy!)
│  └─ Cost factor (12 = 2^12 = 4096 iteracji)
└─ Algorytm (2y = bcrypt)
```

## Kluczowe Właściwości Bcrypt

### 1. **Każde wywołanie generuje INNY hash**
```php
$password = 'Admin123!@#';

$hash1 = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
// $2y$12$9hOMWvaNUN3jGjD7Avy1weSq7mSB1p5dfGuwJ8B7YaQDdwY1mnYfC

$hash2 = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
// $2y$12$92qOboqr1yLNaCRaNK16JeHXDJrZCg./ostJPJxa.9aDXZf/YeC9m

$hash3 = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
// $2y$12$ZlspmhNN8u5AHftwr6PbauyFIC.IKasFbR382RCqqBcoebUBDUBfS
```

**WSZYSTKIE 3 HASHE SĄ RÓŻNE!** - To jest normalne i bezpieczne!

### 2. **Wszystkie pasują do tego samego hasła**
```php
password_verify('Admin123!@#', $hash1); // ✓ TRUE
password_verify('Admin123!@#', $hash2); // ✓ TRUE
password_verify('Admin123!@#', $hash3); // ✓ TRUE
```

### 3. **Dlaczego każdy hash jest inny?**

**Odpowiedź: SALT (sól)**

- Bcrypt automatycznie generuje **losowy 22-znakowy salt**
- Salt jest częścią hash (wbudowany)
- Salt zapobiega atakom rainbow table
- `password_verify()` ekstraktuje salt z hash i używa go do weryfikacji

### Jak to działa?

```
Hashowanie:
1. Generuj losowy salt (22 znaki)
2. Hashuj: bcrypt(hasło + salt, cost=12)
3. Zapisz: $2y$12$[salt][hash]

Weryfikacja:
1. Wyciągnij salt z zapisanego hash
2. Hashuj wprowadzone hasło z tym samym salt
3. Porównaj wynik z zapisanym hash
```

---

### 💾 5. Persystencja - Czy problem się powtórzy?

## Test 1: Restart Kontenera MySQL

```bash
# Przed restartem
admin@skanpolis.pl: $2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0xCyy0D.77I1DLi3t8zFm

$ docker-compose restart mysql
# Czekam 8 sekund...

# Po restarcie
admin@skanpolis.pl: $2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0xCyy0D.77I1DLi3t8zFm
```

✅ **HASH JEST TEN SAM** - MySQL używa persystentnego wolumenu

---

## Test 2: Usunięcie Wolumenu i Pełny Rebuild

```bash
# Przed
admin@skanpolis.pl: $2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0xCyy0D.77I1DLi3t8zFm

$ docker-compose down
$ docker volume rm project_mysql_data  # ⚠️ Usuń całą bazę!
$ docker-compose up -d
# Czekam 15 sekund na inicjalizację...

# Po rebuildie
admin@skanpolis.pl: $2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0xCyy0D.77I1DLi3t8zFm
```

✅ **HASH JEST TEN SAM** - Baza jest reinicjalizowana z `docker/mysql/init.sql`

---

## Test 3: Logowanie Po Pełnym Rebuildie

```bash
$ curl -i -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=admin@skanpolis.pl' \
  -d 'password=Admin123!@#'

HTTP/1.1 302 Found
Location: admin.php
```

✅ **LOGOWANIE DZIAŁA** - Problem NIE POWRÓCI!

---

## 🎯 Podsumowanie

### Dlaczego problem się nie powtórzy?

1. **Hashe są zapisane w `init.sql`**
   - Plik: `docker/mysql/init.sql:168-173`
   - MySQL uruchamia ten skrypt przy pierwszym starcie
   - Każdy rebuild wolumenu = ponowna inicjalizacja z tego samego pliku

2. **Hashe są deterministyczne** (raz wygenerowane)
   - Hash wygenerowany raz pozostaje stały
   - Salt jest częścią hash, więc jest zachowany
   - `password_verify()` zawsze będzie działać z tym samym hash

3. **Wolumen MySQL jest persystentny**
   - Dane: `/var/lib/mysql` w kontenerze
   - Volume: `project_mysql_data` na hoście
   - Restart kontenera = dane pozostają

---

## 📊 Przepływ Danych

### Pierwsza Inicjalizacja
```
1. docker-compose up -d
   ↓
2. MySQL sprawdza: czy /var/lib/mysql jest puste?
   ↓ TAK
3. MySQL uruchamia: /docker-entrypoint-initdb.d/01-schema.sql
   ↓
4. INSERT INTO User... (z poprawnym hash)
   ↓
5. Dane zapisane w wolumenie: project_mysql_data
```

### Kolejne Starty
```
1. docker-compose up -d
   ↓
2. MySQL sprawdza: czy /var/lib/mysql jest puste?
   ↓ NIE - wolumen istnieje
3. MySQL pomija init.sql
   ↓
4. Ładuje dane z wolumenu (z poprawnym hash)
```

### Rebuild (usunięcie wolumenu)
```
1. docker volume rm project_mysql_data
   ↓
2. docker-compose up -d
   ↓
3. MySQL: /var/lib/mysql jest puste!
   ↓
4. Uruchamia init.sql PONOWNIE
   ↓
5. INSERT INTO User... (z poprawnym hash z pliku)
```

---

## 🔧 Zmienne w Systemie

### Zmienne Środowiskowe (.env)
```bash
# Database
DB_HOST=mysql
DB_NAME=insurance_db
DB_USER=insurance_user
DB_PASSWORD=qaBDlRM+qLLDSgqTlICBzvk7D+zhY8H3kO01kSCNGiA=

# Redis
REDIS_PASSWORD=changeme_redis_password_here

# Session
SESSION_SECRET=changeme_session_secret_minimum_64_characters_random_string_here
```

### Hasła Użytkowników (w bazie)
```sql
-- Admin (hasło: Admin123!@#)
haslo = '$2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0xCyy0D.77I1DLi3t8zFm'

-- User (hasło: User123!@#)
haslo = '$2y$12$NdOgRUQ.9wNb5W0McX3rFenGDnUvMcD.D5zeb57TnafDDhD7oXosu'
```

### Flow Logowania
```php
// 1. Użytkownik wprowadza: Admin123!@#
$inputPassword = $_POST['password']; // 'Admin123!@#'

// 2. Pobieramy hash z bazy
$storedHash = '$2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0xCyy0D.77I1DLi3t8zFm';

// 3. password_verify():
//    - Ekstraktuje salt z $storedHash
//    - Hashuje $inputPassword z tym salt
//    - Porównuje wynik z $storedHash
if (password_verify($inputPassword, $storedHash)) {
    // ✓ SUKCES - hasła pasują!
    $_SESSION['user_email'] = $email;
    header("Location: admin.php");
}
```

---

## ⚠️ Ważne Uwagi

### 1. Nigdy nie zmieniaj hashy ręcznie!
```sql
-- ❌ ŹLE - losowy hash nie będzie pasował
UPDATE User SET haslo = '$2y$12$RANDOM_HASH_HERE';

-- ✓ DOBRZE - generuj przez PHP
-- W PHP: password_hash('NoweHaslo123', PASSWORD_BCRYPT, ['cost' => 12])
UPDATE User SET haslo = '$2y$12$[wygenerowany_przez_PHP]';
```

### 2. Koszt (cost) musi być ten sam
```php
// ✓ Zawsze używaj cost=12 w tym projekcie
password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
```

### 3. Hashe są case-sensitive
```php
// Te hashe są RÓŻNE (mimo że wyglądają podobnie):
$hash1 = '$2y$12$Abc...';
$hash2 = '$2y$12$abc...';  // małe 'a' zamiast wielkiego 'A'
```

---

## 📝 Checklist Weryfikacji

Po każdym rebuildie sprawdź:

```bash
# 1. Sprawdź hashe w bazie
docker exec skanpolis_mysql mysql -u root -p"..." insurance_db \
  -e "SELECT email, LEFT(haslo, 40) FROM User;"

# Oczekiwane:
# admin@skanpolis.pl: $2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0
# user@example.pl:    $2y$12$NdOgRUQ.9wNb5W0McX3rFenGDnUvMcD.D

# 2. Test logowania admin
curl -i -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=admin@skanpolis.pl' \
  -d 'password=Admin123!@#'
# Oczekiwane: HTTP/1.1 302 Found, Location: admin.php

# 3. Test logowania user
curl -i -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=user@example.pl' \
  -d 'password=User123!@#'
# Oczekiwane: HTTP/1.1 302 Found, Location: ../html/main.html

# 4. Sprawdź sesje Redis
docker exec skanpolis_redis redis-cli -a "changeme_redis_password_here" \
  KEYS "PHPREDIS_SESSION:*"
# Oczekiwane: Lista session ID
```

---

## 🎓 Edukacyjne Przykłady

### Przykład 1: Generowanie i Weryfikacja
```php
<?php
$password = 'MojeSuperHaslo123!';

// Krok 1: Generowanie (np. podczas rejestracji)
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo "Hash: $hash\n";
// Wynik: $2y$12$AbCdEf...XyZ (60 znaków)

// Zapisz $hash do bazy danych
// INSERT INTO User (email, haslo) VALUES ('user@example.com', '$hash');

// Krok 2: Weryfikacja (np. podczas logowania)
$userInput = 'MojeSuperHaslo123!';
if (password_verify($userInput, $hash)) {
    echo "Logowanie udane!\n";
} else {
    echo "Błędne hasło!\n";
}
?>
```

### Przykład 2: Dlaczego Salt Jest Ważny?
```php
<?php
// Bez salt (NIEBEZPIECZNE - nie rób tego!):
$hash1 = md5('password123');  // 482c811da5d5b4bc6d497ffa98491e38
$hash2 = md5('password123');  // 482c811da5d5b4bc6d497ffa98491e38
// ❌ Ten sam hash! Atakujący może użyć rainbow table!

// Z salt (bcrypt - BEZPIECZNE):
$hash1 = password_hash('password123', PASSWORD_BCRYPT);
// $2y$12$9hOMWvaNUN3jGjD7Avy1weSq7mSB1p5dfGuwJ8B7YaQDdwY1mnYfC

$hash2 = password_hash('password123', PASSWORD_BCRYPT);
// $2y$12$ZlspmhNN8u5AHftwr6PbauyFIC.IKasFbR382RCqqBcoebUBDUBfS

// ✓ Różne hashe dla tego samego hasła! Rainbow table bezużyteczny!
?>
```

---

## 🔒 Bezpieczeństwo

### Dlaczego bcrypt jest bezpieczny?

1. **Losowy salt** - każdy hash jest unikalny
2. **Wolny algorytm** - cost=12 = 4096 iteracji (trudny do bruteforce)
3. **Salt w hash** - nie trzeba osobno przechowywać salt
4. **Resistant to rainbow tables** - różne hashe dla tego samego hasła

### Porównanie algorytmów
```
MD5:     ❌ Złamany, niezalecany
SHA1:    ❌ Złamany, niezalecany
SHA256:  ⚠️ Szybki (podatny na bruteforce)
bcrypt:  ✓ Wolny (odporny), zalecany
argon2:  ✓ Nowoczesny, najlepszy (wymaga PHP 7.2+)
```

---

## ✅ Wszystko Działa!

**Podsumowanie:**
1. ✅ Admin login działa
2. ✅ User login działa
3. ✅ PhpMyAdmin działa (http://localhost:8081)
4. ✅ Hashe są persystentne
5. ✅ Problem NIE POWRÓCI przy kolejnych startach
6. ✅ Bcrypt działa prawidłowo (różne hashe = normalne!)

**Dane testowe:**
- Admin: admin@skanpolis.pl / Admin123!@#
- User: user@example.pl / User123!@#
- PhpMyAdmin: http://localhost:8081 (automatyczne logowanie)
