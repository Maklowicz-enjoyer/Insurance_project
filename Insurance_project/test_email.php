<?php
/**
 * Test script dla EmailService
 */

require_once __DIR__ . '/scripts/EmailService.php';

echo "=== Test EmailService ===\n\n";

// Wyświetl konfigurację
echo "Konfiguracja:\n";
echo "MAIL_HOST: " . (getenv('MAIL_HOST') ?: 'NOT SET') . "\n";
echo "MAIL_PORT: " . (getenv('MAIL_PORT') ?: 'NOT SET') . "\n";
echo "MAIL_FROM_ADDRESS: " . (getenv('MAIL_FROM_ADDRESS') ?: 'NOT SET') . "\n";
echo "APP_ENV: " . (getenv('APP_ENV') ?: 'NOT SET') . "\n";
echo "\n";

try {
    $emailService = new EmailService();
    echo "✅ EmailService utworzony\n\n";

    // Test tokenu
    $testToken = bin2hex(random_bytes(32));
    echo "Token testowy: " . substr($testToken, 0, 20) . "...\n\n";

    echo "Wysyłanie emaila do: user@example.pl\n";
    echo "==========================================\n";

    $result = $emailService->sendPasswordResetEmail('user@example.pl', $testToken);

    echo "\n==========================================\n";

    if ($result) {
        echo "✅ Email wysłany pomyślnie!\n";
    } else {
        echo "❌ Błąd wysyłki emaila\n";
    }

} catch (Exception $e) {
    echo "❌ BŁĄD: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Koniec testu ===\n";
