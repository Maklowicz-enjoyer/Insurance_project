# 🚗 SkanPolis - Platforma Porównywania Ubezpieczeń

Nowoczesna platforma do porównywania ubezpieczeń komunikacyjnych dla samochodów i motocykli.

## 📋 Wymagania

- Docker >= 20.10
- Docker Compose >= 2.0
- 4GB wolnej pamięci RAM
- Porty: 8080, 8081, 3306, 6379 (wolne)

## 🚀 Szybki Start

### Dla Nowego Developera (WAŻNE!)

**Pierwszy raz klonujesz repo?** Wykonaj te kroki:

```bash
# 1. Sklonuj repozytorium
git clone <your-repo-url>
cd project

# 2. Uruchom automatyczny setup
chmod +x setup-dev.sh
./setup-dev.sh
```

**To wszystko!** Skrypt automatycznie:
- ✅ Sprawdzi wymagania systemowe (Docker, Docker Compose, wolne porty)
- ✅ Wygeneruje bezpieczne hasła i tokeny (sekrety NIE są w git!)
- ✅ Utworzy pliki konfiguracyjne (.env, secrets/)
- ✅ Zbuduje obrazy Docker
- ✅ Uruchomi wszystkie serwisy (nginx, php, mysql, redis)
- ✅ Zainicjalizuje bazę danych z przykładowymi danymi

Po zakończeniu zobaczysz:
```
✅ SUCCESS! SkanPolis is running
🌐 Application: http://localhost:8080
🗄️  PhpMyAdmin: http://localhost:8081
```

### Automatyczne uruchomienie (zalecane)

Jeśli masz już skonfigurowane środowisko:

```bash
./setup-dev.sh
```

### Ręczne uruchomienie

```bash
# 1. Skopiuj szablon środowiska
cp .env.example .env

# 2. Edytuj .env i ustaw hasła
nano .env

# 3. Utwórz katalog secrets
mkdir -p secrets

# 4. Wygeneruj hasła
openssl rand -base64 32 > secrets/db_password.txt
openssl rand -base64 64 > secrets/session_secret.txt
openssl rand -base64 32 > secrets/redis_password.txt

# 5. Zbuduj i uruchom kontenery
docker-compose build
docker-compose up -d
```

## 🌐 Dostęp do Aplikacji

Po uruchomieniu aplikacja dostępna jest pod adresami:

| Serwis | URL | Opis |
|--------|-----|------|
| **Aplikacja** | http://localhost:8080 | Główna strona SkanPolis |
| **PhpMyAdmin** | http://localhost:8081 | Panel administracyjny bazy danych |
| **MySQL** | localhost:3306 | Bezpośrednie połączenie z bazą |
| **Redis** | localhost:6379 | Cache i sesje |

## 👤 Domyślne Konta

### Administrator
- **Email:** admin@skanpolis.pl
- **Hasło:** Admin123!@#

### Użytkownik testowy
- **Email:** user@example.pl
- **Hasło:** User123!@#

**⚠️ WAŻNE:** Zmień te hasła w środowisku produkcyjnym!

## 🏗️ Architektura

```
┌─────────────┐
│   Nginx     │  ← Reverse proxy + static files
│   :80       │
└──────┬──────┘
       │
┌──────▼──────┐
│  PHP-FPM    │  ← Aplikacja PHP 8.2
│   :9000     │
└──────┬──────┘
       │
   ┌───┴───┬────────┐
   │       │        │
┌──▼──┐ ┌──▼──┐ ┌──▼──┐
│MySQL│ │Redis│ │Files│
└─────┘ └─────┘ └─────┘
```

### Komponenty

1. **Nginx** - Serwer WWW
   - Obsługa plików statycznych (CSS, JS, obrazy)
   - Proxy dla PHP-FPM
   - Rate limiting dla login/register
   - Security headers

2. **PHP-FPM** - Backend aplikacji
   - PHP 8.2 z extensions (PDO, Redis, GD, etc.)
   - Composer dla zarządzania zależnościami
   - Sesje w Redis

3. **MySQL 8.0** - Baza danych
   - Schemat z obsługą samochodów i motocykli
   - Automatyczna inicjalizacja z `init.sql`
   - Dane testowe

4. **Redis** - Cache i sesje
   - Session storage (zamiast plików)
   - Przyszłość: cache dla wyników wyszukiwania

## 📁 Struktura Projektu

```
project/
├── docker/                    # Konfiguracja Docker
│   ├── nginx/
│   │   ├── Dockerfile
│   │   ├── nginx.conf
│   │   └── conf.d/
│   │       └── skanpolis.conf
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   └── mysql/
│       ├── init.sql          # Schemat bazy danych
│       └── my.cnf
├── Insurance_project/         # Kod aplikacji
│   ├── index.html            # Landing page
│   ├── html/                 # Widoki (main, admin, account, etc.)
│   ├── scripts/              # PHP backend (login, register, admin)
│   └── css/                  # Stylizacja
├── docs/                      # Dokumentacja projektu (opcjonalnie w git)
│   └── legacy-notes/         # Notatki z migracji
├── secrets/                   # ⚠️ NIE W GIT! Generowane przez setup-dev.sh
│   ├── db_password.txt
│   ├── session_secret.txt
│   └── redis_password.txt
├── docker-compose.yml         # Orkiestracja kontenerów
├── .env                       # ⚠️ NIE W GIT! Generowany przez setup-dev.sh
├── .env.example              # ✅ Szablon środowiska (W GIT)
├── .gitignore                # ✅ Chroni sekrety
├── setup-dev.sh              # 🚀 Skrypt automatycznego setupu
└── README.md                 # Ta dokumentacja
```

### ⚠️ WAŻNE dla nowych developerów

Pliki **NIE w repozytorium** (generowane lokalnie):
- `.env` - Twoje lokalne ustawienia środowiska
- `secrets/` - Twoje lokalne hasła i tokeny
- `vendor/` - Zależności Composer (przyszłość)

Te pliki są **chronione przez .gitignore** i każdy developer ma własne kopie.

## 🗄️ Schemat Bazy Danych

### Tabele

1. **User** - Użytkownicy systemu
   - Hasła hashowane bcrypt (cost 12)
   - Flaga `SUser` dla adminów

2. **Vehicle** - Pojazdy użytkowników
   - Obsługa CAR i MOTORCYCLE
   - Powiązanie z użytkownikiem

3. **CarInsurance** - Polisy samochodowe
   - Typ nadwozia (Sedan, SUV, Kombi, etc.)
   - Pojemność silnika, przebieg

4. **MotorcycleInsurance** - Polisy motocyklowe (NOWE!)
   - Pojemność silnika (obowiązkowe)
   - Moc w KM

5. **SearchHistory** - Historia wyszukiwań
   - Audyt zachowań użytkowników
   - Rekomendacje

## 🛠️ Użyteczne Komendy

### Docker

```bash
# Wyświetl logi wszystkich serwisów
docker-compose logs -f

# Logi konkretnego serwisu
docker-compose logs -f php
docker-compose logs -f mysql

# Zatrzymaj wszystkie kontenery
docker-compose down

# Zatrzymaj i usuń woluminy (UWAGA: kasuje dane!)
docker-compose down -v

# Restart serwisu
docker-compose restart php

# Rebuild po zmianach w Dockerfile
docker-compose build --no-cache php
docker-compose up -d
```

### Shell w kontenerze

```bash
# PHP container
docker-compose exec php bash

# MySQL console
docker-compose exec mysql mysql -u root -p

# Redis console
docker-compose exec redis redis-cli
```

### Baza danych

```bash
# Import SQL
docker-compose exec -T mysql mysql -u root -p insurance_db < backup.sql

# Export SQL
docker-compose exec mysql mysqldump -u root -p insurance_db > backup.sql

# Podłącz się do bazy
docker-compose exec mysql mysql -u insurance_user -p insurance_db
```

## 🔒 Bezpieczeństwo

### Zaimplementowane

✅ **Hasła** - bcrypt z cost=12
✅ **SQL Injection** - Prepared statements
✅ **XSS** - htmlspecialchars() na output
✅ **Rate Limiting** - Nginx (5 req/min dla login)
✅ **Security Headers** - X-Frame-Options, CSP, etc.
✅ **Secrets Management** - Poza repozytorium git
✅ **Session Security** - Redis, HttpOnly, Secure, SameSite

### Do zaimplementowania (Faza 2)

⏳ CSRF Protection
⏳ Input Validation (Services layer)
⏳ Authorization Middleware
⏳ HTTPS (produkcja)
⏳ Password Policy Enforcement

## 🧪 Testowanie

### Testy Podstawowe

```bash
# Sprawdź czy wszystkie serwisy działają
docker-compose ps

# Health check nginx
curl http://localhost:8080/health

# Sprawdź połączenie z MySQL
docker-compose exec mysql mysqladmin ping -h localhost

# Sprawdź Redis
docker-compose exec redis redis-cli ping
```

### Test Logowania (curl)

**WAŻNE:** Używaj pojedynczych cudzysłowów dla haseł ze znakami specjalnymi!

```bash
# Test logowania ADMIN
curl -i -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=admin@skanpolis.pl' \
  -d 'password=Admin123!@#'
# Oczekiwany wynik: HTTP/1.1 302 Found + Location: admin.php

# Test logowania USER
curl -i -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=user@example.pl' \
  -d 'password=User123!@#'
# Oczekiwany wynik: HTTP/1.1 302 Found + Location: ../html/main.html
```

### Test w Przeglądarce

**Najłatwiejszy sposób:** Otwórz stronę testową z przyciskami:
```
http://localhost:8080/test_login.html
```

**Lub ręcznie:**
1. Otwórz: `http://localhost:8080/scripts/login.php`
2. Użyj credentials z sekcji "Domyślne Konta" powyżej
3. Po zalogowaniu zostaniesz przekierowany do odpowiedniej strony

## 📊 Monitorowanie

### Logi

```bash
# Wszystkie logi
docker-compose logs -f

# Tylko błędy
docker-compose logs -f | grep -i error

# Logi Nginx (access)
docker-compose exec nginx tail -f /var/log/nginx/access.log

# Logi PHP
docker-compose exec php tail -f /var/log/php_errors.log
```

### Metryki

```bash
# Użycie zasobów
docker stats

# Stan kontenerów
docker-compose ps
```


## 🐛 Troubleshooting

### Port już zajęty

```bash
# Sprawdź co używa portu
sudo lsof -i :8080

# Zmień port w docker-compose.yml
ports:
  - "9080:80"  # Zamiast 8080:80
```

### MySQL nie startuje

```bash
# Sprawdź logi
docker-compose logs mysql

# Reset woluminu (UWAGA: kasuje dane!)
docker-compose down -v
docker-compose up -d
```

### Redis connection refused

```bash
# Sprawdź czy Redis działa
docker-compose exec redis redis-cli ping

# Sprawdź hasło w .env
grep REDIS_PASSWORD .env
```

### Błąd uprawnień (Permission denied)

```bash
# Zmień właściciela plików
sudo chown -R 1000:1000 Insurance_project/

# Lub w kontenerze
docker-compose exec php chown -R www:www /var/www/html
```

## 📞 Wsparcie

Masz pytania? Problemy?
- Sprawdź logi: `docker-compose logs -f`
- Dokumentacja Docker: https://docs.docker.com

