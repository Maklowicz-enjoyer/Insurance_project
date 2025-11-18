# 🚀 SkanPolis - Quick Start Guide

## ⚡ Szybkie Uruchomienie (3 kroki)

```bash
# 1. Uruchom aplikację
./setup-dev.sh

# 2. Otwórz w przeglądarce
# http://localhost:8080/test_login.html

# 3. Zaloguj się!
# Admin: admin@skanpolis.pl / Admin123!@#
# User:  user@example.pl / User123!@#
```

---

## 🌐 Dostęp do Serwisów

| Serwis | URL | Opis |
|--------|-----|------|
| **🏠 Strona główna** | http://localhost:8080 | Aplikacja SkanPolis |
| **🧪 Strona testowa** | http://localhost:8080/test_login.html | Przyciski logowania + credentials |
| **🔐 Logowanie** | http://localhost:8080/scripts/login.php | Formularz logowania |
| **🗄️ PhpMyAdmin** | http://localhost:8081 | Zarządzanie bazą |
| **💚 Health Check** | http://localhost:8080/health | Status Nginx |

---

## 👤 Konta Testowe

### Administrator
```
Email:    admin@skanpolis.pl
Hasło:    Admin123!@#
Redirect: /scripts/admin.php (panel admina)
```

### Użytkownik
```
Email:    user@example.pl
Hasło:    User123!@#
Redirect: /html/main.html (formularz wyszukiwania)
```

---

## 🗄️ PhpMyAdmin

```
URL:      http://localhost:8081
Server:   mysql
Username: root
Password: (zobacz poniżej)
```

**Hasło do PhpMyAdmin:**
```bash
cat secrets/db_password.txt
```

Aktualne hasło:
```
qaBDlRM+qLLDSgqTlICBzvk7D+zhY8H3kO01kSCNGiA=
```

---

## 🧪 Testy z Linii Komend

### Test 1: Health Check
```bash
curl http://localhost:8080/health
# Oczekiwany wynik: healthy
```

### Test 2: Logowanie Admin
**UWAGA:** Używaj **pojedynczych cudzysłowów** dla haseł ze znakami `!@#`

```bash
curl -i -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=admin@skanpolis.pl' \
  -d 'password=Admin123!@#'
```

Oczekiwany wynik:
```
HTTP/1.1 302 Found
Location: admin.php
Set-Cookie: PHPSESSID=...
```

### Test 3: Logowanie User
```bash
curl -i -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=user@example.pl' \
  -d 'password=User123!@#'
```

Oczekiwany wynik:
```
HTTP/1.1 302 Found
Location: ../html/main.html
Set-Cookie: PHPSESSID=...
```

### Test 4: Status Kontenerów
```bash
docker-compose ps
```

Wszystkie powinny mieć status `Up`:
- skanpolis_nginx
- skanpolis_php
- skanpolis_mysql
- skanpolis_redis
- skanpolis_phpmyadmin

---

## 🔧 Przydatne Komendy

### Status i Logi
```bash
# Status wszystkich kontenerów
docker-compose ps

# Logi na żywo (wszystkie serwisy)
docker-compose logs -f

# Logi tylko PHP (błędy aplikacji)
docker-compose logs -f php

# Logi tylko Nginx
docker-compose logs -f nginx
```

### Restart
```bash
# Restart całej aplikacji
docker-compose restart

# Restart tylko PHP
docker-compose restart php

# Restart tylko Nginx
docker-compose restart nginx
```

### Zatrzymanie
```bash
# Zatrzymaj aplikację (zachowaj dane)
docker-compose down

# Zatrzymaj i USUŃ WSZYSTKIE DANE (UWAGA!)
docker-compose down -v
```

### Baza Danych
```bash
# Podłącz się do MySQL
docker-compose exec mysql mysql -u root -p$(cat secrets/db_password.txt) insurance_db

# Pokaż tabele
docker-compose exec mysql mysql -u root -p$(cat secrets/db_password.txt) insurance_db -e "SHOW TABLES;"

# Pokaż użytkowników
docker-compose exec mysql mysql -u root -p$(cat secrets/db_password.txt) insurance_db -e "SELECT email, SUser FROM User;"

# Backup bazy
docker-compose exec mysql mysqldump -u root -p$(cat secrets/db_password.txt) insurance_db > backup_$(date +%Y%m%d).sql
```

---

## ❓ Częste Problemy

### Problem: "Port already in use"
```bash
# Sprawdź co używa portu 8080
sudo lsof -i :8080

# Zabij proces
sudo kill -9 <PID>

# Lub zmień port w docker-compose.yml
```

### Problem: "Cannot connect to database"
```bash
# Sprawdź czy MySQL działa
docker-compose logs mysql

# Restart MySQL
docker-compose restart mysql

# Sprawdź hasło
cat secrets/db_password.txt
```

### Problem: "Logowanie nie działa"
```bash
# Sprawdź logi PHP
docker-compose logs -f php

# Sprawdź czy hashe są prawidłowe
docker-compose exec mysql mysql -u root -p$(cat secrets/db_password.txt) insurance_db -e "SELECT email, LEFT(haslo, 30) FROM User;"

# Restart PHP
docker-compose restart php
```

### Problem: Curl zwraca "event not found" dla hasła
**Rozwiązanie:** Użyj **pojedynczych cudzysłowów** zamiast podwójnych!

❌ Źle:
```bash
curl -d "password=Admin123!@#"  # Bash interpretuje !@# jako event
```

✅ Dobrze:
```bash
curl -d 'password=Admin123!@#'  # Pojedyncze cudzysłowy = literal string
```

---

## 📊 Baza Danych - Co Jest W Środku?

### Tabele (6):
1. **User** - Użytkownicy (2 konta: admin + user)
2. **Vehicle** - Pojazdy (Toyota Corolla + Yamaha MT-07)
3. **CarInsurance** - Polisy samochodowe (3 oferty)
4. **MotorcycleInsurance** - Polisy motocyklowe (3 oferty) ← **NOWE!**
5. **SearchHistory** - Historia wyszukiwań
6. **Sessions** - Sesje użytkowników

### Przykładowe zapytania SQL:

```sql
-- Zobacz wszystkich użytkowników
SELECT Users_ID, email, SUser, Wiek FROM User;

-- Zobacz pojazdy
SELECT Vehicle_type, Brand, Model, Year FROM Vehicle;

-- Zobacz polisy samochodowe
SELECT Insurance_name, Insurance_type, Body_type, Price
FROM CarInsurance;

-- Zobacz polisy motocyklowe (NOWE!)
SELECT Insurance_name, Insurance_type, Engine_capacity, Power_HP, Price
FROM MotorcycleInsurance;
```

---

## 🎯 Następne Kroki

Po przetestowaniu logowania możesz:

**A. Dodać UI dla motocykli** 🏍️
```
- Formularz wyboru typu pojazdu (CAR/MOTORCYCLE)
- Wyszukiwarka polis motocyklowych
- Wyświetlanie ofert dla motocykli
```

**B. Security & MVC Refactoring** 🔒
```
- Session management w Redis
- CSRF protection
- Authorization middleware
- Slim Framework + Controllers/Models
```

**C. Eksploruj dalej** 🔍
```
- Zobacz kod w Insurance_project/
- Testuj różne funkcje
- Sprawdź PhpMyAdmin
```

---

## 📚 Dokumentacja

- **README.md** - Pełna dokumentacja
- **CLAUDE.md** - Przewodnik dla deweloperów/AI
- **INFRASTRUCTURE_STATUS.md** - Status infrastruktury

---

## ✅ Checklist Przed Rozpoczęciem Pracy

- [ ] Wszystkie kontenery działają (`docker-compose ps`)
- [ ] Health check zwraca "healthy" (`curl http://localhost:8080/health`)
- [ ] Logowanie admin działa w przeglądarce
- [ ] Logowanie user działa w przeglądarce
- [ ] PhpMyAdmin jest dostępny
- [ ] Znasz hasło do bazy (`cat secrets/db_password.txt`)

---

**🚀 Gotowe! Możesz zaczynać pracę!**
