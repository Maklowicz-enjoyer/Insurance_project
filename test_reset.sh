#!/bin/bash

echo "=== Test resetowania hasła ==="
echo ""

# 1. Pobierz stronę i wyciągnij CSRF token
echo "1. Pobieranie CSRF tokenu..."
RESPONSE=$(curl -s -c /tmp/test_cookies.txt http://localhost:8080/scripts/forgot_password.php)
CSRF_TOKEN=$(echo "$RESPONSE" | grep -oP 'name="csrf_token" value="\K[^"]+')

echo "   CSRF Token: ${CSRF_TOKEN:0:20}..."

# 2. Wyślij POST z emailem
echo ""
echo "2. Wysyłanie żądania resetu dla user@example.pl..."
RESULT=$(curl -s -X POST \
  -b /tmp/test_cookies.txt \
  -c /tmp/test_cookies.txt \
  --data-urlencode "email=user@example.pl" \
  --data-urlencode "csrf_token=$CSRF_TOKEN" \
  http://localhost:8080/scripts/forgot_password.php)

# 3. Sprawdź czy sukces
if echo "$RESULT" | grep -q "Email wysłany"; then
    echo "   ✅ Otrzymano komunikat sukcesu"
else
    echo "   ❌ Brak komunikatu sukcesu"
    echo "   Response preview:"
    echo "$RESULT" | grep -A 5 -B 5 "error\|Error" | head -20
fi

# 4. Sprawdź bazę danych
echo ""
echo "3. Sprawdzanie bazy danych..."
TOKEN_DATA=$(docker exec skanpolis_mysql mysql -u root -p'qaBDlRM+qLLDSgqTlICBzvk7D+zhY8H3kO01kSCNGiA=' insurance_db -se "SELECT email, LEFT(token, 30), expires_at FROM password_resets ORDER BY created_at DESC LIMIT 1;" 2>/dev/null)

if [ -n "$TOKEN_DATA" ]; then
    echo "   ✅ Token utworzony w bazie:"
    echo "   $TOKEN_DATA"
else
    echo "   ❌ Brak tokenów w bazie"
fi

# 5. Sprawdź MailDev
echo ""
echo "4. Sprawdzanie MailDev..."
EMAIL_COUNT=$(curl -s http://localhost:1080/email | jq '. | length' 2>/dev/null)

if [ "$EMAIL_COUNT" -gt 0 ]; then
    echo "   ✅ Emaile w MailDev: $EMAIL_COUNT"
    echo "   🌐 Sprawdź: http://localhost:1080"
else
    echo "   ❌ Brak emaili w MailDev"
    echo "   Sprawdź logi PHP:"
    docker logs skanpolis_php 2>&1 | grep -i "email\|smtp" | tail -5
fi

echo ""
echo "=== Koniec testu ==="
