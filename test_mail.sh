cd ~/pab_project/Insurance_project

# ============================================
# PEŁNA NAPRAWA - BEZ PLIKU
# ============================================

echo "=== FULL RESET ==="

# 1. Napraw .env
echo "1. Naprawiam .env..."
cp .env .env.backup_final
sed -i 's/^MAIL_HOST=.*/MAIL_HOST=maildev/' .env
sed -i 's/^MAIL_PORT=.*/MAIL_PORT=1025/' .env
echo "✅ .env:"
grep "^MAIL_HOST=" .env

# 2. Napraw EmailService.php
echo ""
echo "2. Naprawiam EmailService.php..."
sed -i 's/SMTP::DEBUG_SERVICE/2/g' scripts/EmailService.php
echo "✅ DEBUG_SERVICE → 2"

# 3. FULL RESTART (wymuś nowe ENV)
echo ""
echo "3. Pełny restart (z nowym ENV)..."
docker-compose down
docker rm -f skanpolis_php 2>/dev/null || true
docker-compose up -d
echo "Czekam 10 sekund..."
sleep 10

# 4. Sprawdź ENV w PHP
echo ""
echo "4. Sprawdzam ENV w PHP:"
docker-compose exec php printenv MAIL_HOST
docker-compose exec php printenv MAIL_PORT

# 5. TEST
echo ""
echo "5. Test wysyłki..."
curl -s -c /tmp/c.txt http://localhost:8080/scripts/forgot_password.php > /tmp/p.html
T=$(grep -oP 'name="csrf_token" value="\K[^"]+' /tmp/p.html)
curl -s -b /tmp/c.txt -c /tmp/c.txt -X POST http://localhost:8080/scripts/forgot_password.php \
  -d "email=user@example.pl" -d "csrf_token=$T" > /tmp/r.html
grep -q "Email wysłany" /tmp/r.html && echo "✅ Odpowiedź OK" || echo "❌ Błąd"

# 6. Logi
echo ""
echo "6. Logi PHP:"
docker-compose logs php --tail=5 | grep -E "smtp|error|Error" || echo "✅ Brak błędów"

# 7. MailDev
echo ""
echo "7. MailDev (czekam 3s)..."
sleep 3
COUNT=$(curl -s http://localhost:1080/email 2>/dev/null | grep -o '"subject"' | wc -l)
echo "Emaili: $COUNT"

if [ "$COUNT" -gt 0 ]; then
    echo "✅✅✅ SUKCES!!!"
    echo "🌐 http://localhost:1080"
else
    echo "❌ Brak emaili - ostatnia próba..."
    docker-compose exec php php -r '
    require "/var/www/html/vendor/autoload.php";
    use PHPMailer\PHPMailer\PHPMailer;
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->Host = getenv("MAIL_HOST") ?: "maildev";
    $m->Port = 1025;
    $m->SMTPAuth = false;
    echo "Host: " . $m->Host . "\n";
    $m->setFrom("t@t.pl");
    $m->addAddress("u@u.pl");
    $m->Subject = "Manual Test";
    $m->Body = "Test";
    $m->send();
    echo "Sent!\n";
    '
    sleep 2
    COUNT=$(curl -s http://localhost:1080/email 2>/dev/null | grep -o '"subject"' | wc -l)
    echo "Emaili teraz: $COUNT"
fi