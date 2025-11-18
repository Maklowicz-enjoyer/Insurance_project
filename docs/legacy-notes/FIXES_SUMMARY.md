# SkanPolis - Podsumowanie Napraw Błędów Logowania

**Data:** 2025-11-17
**Status:** ✅ NAPRAWIONO

## 🔍 Problemy Zidentyfikowane

### 1. ❌ Nieprawidłowe Hashe Haseł w Bazie Danych
**Lokalizacja:** `docker/mysql/init.sql:168-173`

**Problem:**
- Hasła testowe w bazie danych miały nieprawidłowe hashe
- Hash w bazie: `$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`
- Ten hash NIE pasował do haseł `Admin123!@#` ani `User123!@#`
- `password_verify()` w `login.php:35` zawsze zwracał `false`

**Przyczyna:**
- Hashe zostały skopiowane z innego projektu/przykładu
- Nie były wygenerowane dla rzeczywistych haseł testowych

**Rozwiązanie:**
```sql
-- Wygenerowano nowe hashe używając PHP 8.2 w kontenerze
Admin: $2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0xCyy0D.77I1DLi3t8zFm
User:  $2y$12$NdOgRUQ.9wNb5W0McX3rFenGDnUvMcD.D5zeb57TnafDDhD7oXosu
```

---

### 2. ❌ Sesje Redis Nie Działały
**Lokalizacja:** `docker/php/php.ini:18-20`

**Problem:**
```
PHP Warning: session_start(): Failed to read session data: redis
(path: tcp://redis:6379?auth=)
```

**Przyczyna:**
- `php.ini` używał `${REDIS_PASSWORD}` w session.save_path
- PHP.ini **nie wspiera** podstawiania zmiennych środowiskowych
- Hasło było pustym stringiem, więc Redis odrzucał połączenie

**Rozwiązanie:**
Utworzono `docker/php/session_config.php` z dynamiczną konfiguracją:
```php
<?php
$redisPassword = getenv('REDIS_PASSWORD') ?: 'changeme_redis_password_here';
ini_set('session.save_path', "tcp://redis:6379?auth={$redisPassword}");
```

Dodano `auto_prepend_file` w `php.ini`:
```ini
auto_prepend_file = /usr/local/etc/php/session_config.php
```

**Weryfikacja:**
```bash
$ docker exec skanpolis_redis redis-cli -a "..." KEYS "PHPREDIS_SESSION:*"
PHPREDIS_SESSION:ca0cd036f4994d0d39defc0cfdd87beb
PHPREDIS_SESSION:8859b878cd43becf8b3e1560f92e75ef
```
✅ Sesje są teraz przechowywane w Redis

---

### 3. ⚠️ Błąd HTML w Formularzu Rejestracji
**Lokalizacja:** `Insurance_project/scripts/register.php:117`

**Problem:**
```html
<input type="password" id="confirm_ password" name="confirm_password"...
                              ^
                              | spacja w id!
```

**Wpływ:** Minimalny (nie wpływa na funkcjonalność, tylko na walidację HTML)

**Rozwiązanie:**
```html
<input type="password" id="confirm_password" name="confirm_password"...
```

---

## ✅ Weryfikacja Napraw

### Test 1: Logowanie Admina
```bash
$ curl -i -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=admin@skanpolis.pl' \
  -d 'password=Admin123!@#'

HTTP/1.1 302 Found
Location: admin.php
Set-Cookie: PHPSESSID=8859b878cd43becf8b3e1560f92e75ef; path=/; HttpOnly; SameSite=Strict
```
✅ **DZIAŁA** - Redirect do admin.php

### Test 2: Logowanie Użytkownika
```bash
$ curl -i -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=user@example.pl' \
  -d 'password=User123!@#'

HTTP/1.1 302 Found
Location: ../html/main.html
Set-Cookie: PHPSESSID=ca0cd036f4994d0d39defc0cfdd87beb; path=/; HttpOnly; SameSite=Strict
```
✅ **DZIAŁA** - Redirect do main.html

### Test 3: Sesje Redis
```bash
$ docker exec skanpolis_redis redis-cli -a "..." KEYS "PHPREDIS_SESSION:*"
PHPREDIS_SESSION:ca0cd036f4994d0d39defc0cfdd87beb
PHPREDIS_SESSION:8859b878cd43becf8b3e1560f92e75ef
```
✅ **DZIAŁA** - Sesje są przechowywane w Redis

---

## 📋 Dane Testowe

### Użytkownicy Testowi
```
Administrator:
  Email:    admin@skanpolis.pl
  Hasło:    Admin123!@#
  SUser:    1 (admin)

Użytkownik:
  Email:    user@example.pl
  Hasło:    User123!@#
  SUser:    0 (regular user)
```

---

## 🔧 Pliki Zmodyfikowane

1. **docker/mysql/init.sql** - Zaktualizowano hashe haseł
2. **docker/php/session_config.php** - NOWY PLIK - Konfiguracja sesji Redis
3. **docker/php/php.ini** - Dodano auto_prepend_file
4. **docker/php/Dockerfile** - Dodano kopiowanie session_config.php
5. **Insurance_project/scripts/register.php** - Poprawiono id pola formularza
6. **rebuild-and-test.sh** - NOWY PLIK - Skrypt do rebuildu i testowania

---

## 🚀 Jak Uruchomić Aplikację

### Pierwsza Konfiguracja
```bash
# 1. Upewnij się że secrets są utworzone
cat secrets/db_password.txt
cat secrets/session_secret.txt

# 2. Zbuduj i uruchom kontenery
docker-compose down
docker volume rm project_mysql_data  # Usuń starą bazę
docker-compose build php
docker-compose up -d

# 3. Poczekaj na inicjalizację MySQL (10-15 sekund)
sleep 15

# 4. Sprawdź status
docker-compose ps
```

### Szybki Restart
```bash
bash rebuild-and-test.sh
```

---

## 📊 Status Infrastruktury

| Serwis | Status | Uwagi |
|--------|--------|-------|
| ✅ Nginx | Działa | Port 8080 (HTTP) |
| ✅ PHP-FPM | Działa | PHP 8.2 z Redis extension |
| ✅ MySQL | Działa | Port 3306, insurance_db |
| ✅ Redis | Działa | Port 6379, sesje działają |
| ✅ Routing | Działa | Nginx poprawnie przekazuje requesty |
| ✅ Autentykacja | Działa | Login + hasła OK |
| ✅ Sesje | Działają | Redis backend, secure cookies |

---

## 🔐 Bezpieczeństwo

### Wdrożone Zabezpieczenia
- ✅ Hasła hashowane z bcrypt (cost=12)
- ✅ Prepared statements (SQL injection safe)
- ✅ Sesje w Redis (nie w plikach)
- ✅ HttpOnly cookies
- ✅ SameSite=Strict cookies
- ✅ XSS protection headers
- ✅ CSRF protection headers
- ✅ Rate limiting (5 req/min dla login)

### Do Poprawy (Przyszłe Zadania)
- ⚠️ Brak middleware sprawdzającego sesję na chronionych stronach
- ⚠️ admin.php nie weryfikuje czy użytkownik jest zalogowany
- ⚠️ main.html nie weryfikuje sesji
- ⚠️ Brak CSRF tokenów w formularzach
- ⚠️ Debug code (var_dump) w register.php:17,20

---

## 📝 Następne Kroki

### Krótkoterminowe (Security Critical)
1. Dodać middleware sprawdzające sesję w admin.php
2. Dodać middleware sprawdzające sesję w main.html
3. Usunąć debug code (var_dump) z register.php
4. Dodać CSRF tokeny do wszystkich formularzy

### Średnioterminowe (Architecture)
1. Migracja do MVC (Slim Framework)
2. Utworzenie warstwy Services
3. Utworzenie warstwy Models
4. Utworzenie warstwy Controllers

### Długoterminowe (Features)
1. Email verification
2. Password reset flow
3. Two-factor authentication
4. Audit logging

---

## 🎯 Podsumowanie

**Wszystkie problemy z logowaniem zostały rozwiązane:**

1. ✅ Hasła w bazie działają poprawnie
2. ✅ Redis sesje działają poprawnie
3. ✅ Routing działa poprawnie
4. ✅ Admin login działa (redirect do admin.php)
5. ✅ User login działa (redirect do main.html)

**Aplikacja jest gotowa do użycia!**

URL: http://localhost:8080
