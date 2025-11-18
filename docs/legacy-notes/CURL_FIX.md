# 🔧 Fix: Curl z Hasłami ze Znakami Specjalnymi

## Problem
Curl nie działał z hasłem `Admin123!@#` - bash interpretował `!@#` jako "event"

```bash
# ❌ BŁĄD
curl -d "password=Admin123!@#"
# → bash: !@#: event not found
```

## Rozwiązanie
**Używaj POJEDYNCZYCH cudzysłowów** zamiast podwójnych:

```bash
# ✅ POPRAWNIE
curl -d 'password=Admin123!@#'
```

---

## Poprawne Przykłady

### Test 1: Logowanie Admin (z redirectem)
```bash
curl -L -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=admin@skanpolis.pl' \
  -d 'password=Admin123!@#'
```
→ Załaduje stronę: "Panel Administratora - SkanPolis"

### Test 2: Logowanie User (z redirectem)
```bash
curl -L -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=user@example.pl' \
  -d 'password=User123!@#'
```
→ Załaduje formularz główny

### Test 3: Zobacz tylko headers
```bash
curl -i -X POST 'http://localhost:8080/scripts/login.php' \
  -d 'email=admin@skanpolis.pl' \
  -d 'password=Admin123!@#'
```
→ Pokaże: `HTTP/1.1 302 Found` + `Location: admin.php`

---

## Dlaczego?

**Pojedyncze vs Podwójne Cudzysłowy:**
- `' '` = literal string (bash NIE interpretuje)
- `" "` = interpreted string (bash INTERPRETUJE zmienne i znaki specjalne)

**Znaki specjalne w bash:**
- `!` = history expansion (event)
- `@` = array expansion  
- `#` = komentarz
- `$` = zmienne
- `&` = background process
- etc.

---

## Dla Przeglądarki

**NIE MA PROBLEMU!** Przeglądarka poprawnie obsługuje znaki specjalne w formularzach.

**Najłatwiejszy sposób testowania:**
```
http://localhost:8080/test_login.html
```
Kliknij przycisk i użyj hasła normalnie.

---

## Weryfikacja

✅ Admin login działa (przekierowanie do admin.php)  
✅ User login działa (przekierowanie do main.html)  
✅ Session cookies są ustawiane (PHPSESSID)  
✅ Wszystkie kontenery działają  

---

## Zobacz Też

- **README.md** - sekcja "Test Logowania (curl)"
- **QUICK_START.md** - wszystkie przykłady z pojedynczymi cudzysłowami
- **test_login.html** - strona testowa dla przeglądarki
