# 🚀 SkanPolis - Status Infrastruktury

**Data:** 2025-11-16  
**Status:** ✅ DZIAŁA

---

## ✅ Zrealizowane Zadania

### 1. Konteneryzacja Docker
- [x] Docker Compose z 4 serwisami (nginx, php-fpm, mysql, redis)
- [x] Nginx jako reverse proxy + static files
- [x] PHP 8.2-FPM z rozszerzeniami (PDO, Redis, GD, etc.)
- [x] MySQL 8.0 z nowym schematem bazy
- [x] Redis dla sesji i cache

### 2. Bezpieczeństwo
- [x] Secrets management (poza git)
- [x] Hasła generowane automatycznie (openssl)
- [x] Połączenie przez zmienne środowiskowe
- [x] Rate limiting w Nginx (5 req/min login)
- [x] Security headers (X-Frame-Options, CSP, etc.)
- [x] Bcrypt password hashing (cost 12)

### 3. Baza Danych
- [x] Nowy schemat z obsługą motocykli
- [x] Tabele: User, Vehicle, CarInsurance, MotorcycleInsurance
- [x] Foreign keys i indeksy
- [x] Dane testowe załadowane

### 4. Aplikacja
- [x] Legacy kod działa w kontenerach
- [x] Logowanie admin/user weryfikowane
- [x] Połączenie PHP ↔ MySQL działa
- [x] Ścieżki zaktualizowane (absolute paths)

---

## 🌐 Dostępne Serwisy

| Serwis | URL | Status | Credentials |
|--------|-----|--------|-------------|
| **Aplikacja** | http://localhost:8080 | ✅ UP | - |
| **Nginx Health** | http://localhost:8080/health | ✅ healthy | - |
| **PhpMyAdmin** | http://localhost:8081 | ⏸️  (--profile dev) | root/[auto] |
| **MySQL** | localhost:3306 | ✅ UP | insurance_user/[auto] |
| **Redis** | localhost:6379 | ✅ UP | [auto password] |

---

## 👤 Konta Testowe

### Administrator
- **Email:** admin@skanpolis.pl
- **Hasło:** Admin123!@#
- **Redirect:** /scripts/admin.php

### Użytkownik
- **Email:** user@example.pl
- **Hasło:** User123!@#
- **Redirect:** /html/main.html

**Status logowania:** ✅ Zweryfikowane (HTTP 302 redirect)

---

## 📊 Baza Danych

### Tabele utworzone:
1. **User** (Users_ID, email, haslo, SUser, Wiek)
2. **Vehicle** (Vehicle_ID, Vehicle_type: CAR/MOTORCYCLE)
3. **CarInsurance** (Body_type, Planned_mileage, Price)
4. **MotorcycleInsurance** (Engine_capacity, Power_HP, Price) ← NOWE!
5. **SearchHistory** (audyt wyszukiwań)
6. **Sessions** (opcjonalna, Redis jest głównym)

### Dane testowe:
- ✅ 2 użytkowników (admin + user)
- ✅ 2 pojazdy (Toyota Corolla + Yamaha MT-07)
- ✅ 3 polisy samochodowe (PZU, Warta, Allianz)
- ✅ 3 polisy motocyklowe (PZU, Warta, Allianz)

---

## 🐳 Stan Kontenerów

```bash
$ docker-compose ps
NAME                COMMAND                  STATUS    PORTS
skanpolis_nginx     nginx -g daemon off      Up        0.0.0.0:8080->80/tcp
skanpolis_php       php-fpm                  Up        9000/tcp
skanpolis_mysql     mysqld --default-auth... Up        0.0.0.0:3306->3306/tcp
skanpolis_redis     redis-server             Up        0.0.0.0:6379->6379/tcp
```

---

## 🔧 Zmiany w Legacy Code

### Zaktualizowane pliki:
1. **scripts/db_connect.php**
   - Odczyt z ENV variables
   - Wsparcie Docker secrets
   - Fallback na domyślne wartości

2. **scripts/login.php**
   - Dodano output buffering (ob_start)
   - Naprawiono redirect (headers already sent)
   - Zaktualizowano ścieżki CSS

3. **index.html**
   - Ścieżki relative → absolute (/css/, /scripts/)

---

## 📁 Struktura Projektu

```
project/
├── docker/
│   ├── nginx/       (Dockerfile, nginx.conf, skanpolis.conf)
│   ├── php/         (Dockerfile, php.ini)
│   └── mysql/       (init.sql, my.cnf)
├── secrets/         (db_password.txt, session_secret.txt, redis_password.txt)
├── Insurance_project/ (legacy code - działa!)
├── docker-compose.yml
├── .env             (wygenerowane z .env.example)
├── .gitignore
├── setup-dev.sh     (automatyczne uruchomienie)
└── README.md
```

---

## ⚡ Użyteczne Komendy

```bash
# Sprawdź status
docker-compose ps

# Logi wszystkich serwisów
docker-compose logs -f

# Restart serwisu
docker-compose restart nginx

# Shell w kontenerze
docker-compose exec php bash
docker-compose exec mysql mysql -u root -p

# Zatrzymaj wszystko
docker-compose down

# Zatrzymaj + usuń woluminy (UWAGA: kasuje dane!)
docker-compose down -v
```

---

## 🎯 Następne Kroki

### Opcja A: Security & MVC (zalecane)
1. Implementacja CSRF middleware
2. Authorization middleware (admin check)
3. Slim Framework + Controllers/Models
4. Session management w Redis

### Opcja B: Feature - Motocykle
1. Formularz wyboru typu pojazdu (CAR/MOTORCYCLE)
2. Osobny formularz dla motocykli
3. Wyszukiwarka dla MotorcycleInsurance
4. Widoki wyników

### Opcja C: Testing & CI/CD
1. PHPUnit tests
2. Integration tests
3. GitHub Actions
4. Automated deployments

---

## ⚠️ Znane Problemy (do naprawy w Fazie 2)

1. ❌ Brak sesji - użytkownicy mogą wchodzić na strony bez logowania
2. ❌ Brak CSRF protection
3. ❌ Weak password policy (powinno być 12+ chars + special)
4. ❌ Debug code (var_dump w register.php)
5. ❌ Mixed HTML/PHP (trzeba rozdzielić na MVC)
6. ❌ Random prices (brak rzeczywistego pricing logic)

---

## ✅ Weryfikacja

```bash
# Test 1: Nginx health
curl http://localhost:8080/health
# Expected: healthy

# Test 2: MySQL connection
docker-compose exec mysql mysqladmin ping -h localhost
# Expected: mysqld is alive

# Test 3: Tabele w bazie
docker-compose exec mysql mysql -u root -p[PASSWORD] insurance_db -e "SHOW TABLES;"
# Expected: 6 tabel

# Test 4: Logowanie admin
curl -X POST http://localhost:8080/scripts/login.php \
  -d "email=admin@skanpolis.pl" \
  -d "password=Admin123!@#" -i
# Expected: HTTP/1.1 302 Found + Location: admin.php
```

---

**Status:** 🚀 Infrastruktura gotowa do dalszego rozwoju!

