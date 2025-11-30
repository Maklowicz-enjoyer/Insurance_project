<?php
/**
 * EmailService - Bezpieczny serwis do wysyłania emaili
 * Używa PHPMailer do wysyłania wiadomości email
 * Wszystkie dane konfiguracyjne są pobierane z zmiennych środowiskowych
 */

// Autoload Composer (dla PHPMailer)
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $fromAddress;
    private $fromName;

    public function __construct() {
        $this->mailer = new PHPMailer(true);

        // Konfiguracja z ENV - NIE HARDCODED
        $this->fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@skanpolis.pl';
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'SkanPolis';

        $this->configureSMTP();
    }

    /**
     * Konfiguracja SMTP z bezpiecznym pobieraniem danych z ENV
     */
    private function configureSMTP(): void {
        try {
            // Tryb debug tylko w developmencie
            if (getenv('APP_ENV') === 'development') {
                $this->mailer->SMTPDebug = SMTP::DEBUG_OFF; // Wyłącz debug
            }

            $this->mailer->isSMTP();
            $this->mailer->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';

            // SMTP Auth - tylko jeśli username jest ustawiony
            $username = getenv('MAIL_USERNAME');
            if (!empty($username)) {
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $username;
                $this->mailer->Password = getenv('MAIL_PASSWORD');
            } else {
                $this->mailer->SMTPAuth = false;
            }

            // Encryption - tylko jeśli ustawione
            $encryption = getenv('MAIL_ENCRYPTION');
            if (!empty($encryption)) {
                $this->mailer->SMTPSecure = $encryption === 'tls'
                    ? PHPMailer::ENCRYPTION_STARTTLS
                    : PHPMailer::ENCRYPTION_SMTPS;
            }

            $this->mailer->Port = getenv('MAIL_PORT') ?: 587;
            $this->mailer->CharSet = 'UTF-8';

        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
            throw new Exception("Błąd konfiguracji email");
        }
    }

    /**
     * Wysyła email resetowania hasła
     *
     * @param string $toEmail Email odbiorcy
     * @param string $resetToken Token resetowania (nie URL!)
     * @return bool
     */
    public function sendPasswordResetEmail(string $toEmail, string $resetToken): bool {
        try {
            // Walidacja email
            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid email address: " . $toEmail);
                return false;
            }

            // Budowanie bezpiecznego URL (używamy APP_URL z ENV)
            $appUrl = getenv('APP_URL') ?: 'http://localhost:8080';
            $resetUrl = $appUrl . '/scripts/reset_password.php?token=' . urlencode($resetToken);

            // Nadawca
            $this->mailer->setFrom($this->fromAddress, $this->fromName);

            // Odbiorca
            $this->mailer->addAddress($toEmail);

            // Treść emaila
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'SkanPolis - Reset hasła';

            // HTML body z bezpiecznym escapowaniem
            $this->mailer->Body = $this->getPasswordResetHTMLBody($resetUrl);

            // Alt body (text-only)
            $this->mailer->AltBody = $this->getPasswordResetTextBody($resetUrl);

            // Wysyłka
            $result = $this->mailer->send();

            // Czyszczenie dla następnego użycia
            $this->mailer->clearAddresses();

            return $result;

        } catch (Exception $e) {
            error_log("Email send error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * HTML template dla emaila resetowania hasła
     */
    private function getPasswordResetHTMLBody(string $resetUrl): string {
        $escapedUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset hasła - SkanPolis</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #00897b, #26a69a); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 28px;">
            <span style="color: #e0f7f4;">SKAN</span>POLIS
        </h1>
    </div>

    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px;">
        <h2 style="color: #00897b; margin-top: 0;">Resetowanie hasła</h2>

        <p>Witaj,</p>

        <p>Otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta w SkanPolis.</p>

        <p>Aby ustawić nowe hasło, kliknij poniższy przycisk:</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{$escapedUrl}"
               style="background: linear-gradient(135deg, #00897b, #26a69a);
                      color: white;
                      padding: 12px 30px;
                      text-decoration: none;
                      border-radius: 5px;
                      display: inline-block;
                      font-weight: bold;">
                Zresetuj hasło
            </a>
        </div>

        <p style="font-size: 14px; color: #666;">
            Jeśli przycisk nie działa, skopiuj i wklej poniższy link do przeglądarki:
        </p>
        <p style="font-size: 12px; color: #00897b; word-break: break-all; background: white; padding: 10px; border-radius: 3px;">
            {$escapedUrl}
        </p>

        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #856404;">
                <strong>⚠️ Ważne informacje bezpieczeństwa:</strong>
            </p>
            <ul style="margin: 10px 0 0 0; padding-left: 20px; font-size: 14px; color: #856404;">
                <li>Link jest ważny przez <strong>1 godzinę</strong></li>
                <li>Może być użyty tylko <strong>jeden raz</strong></li>
                <li>Jeśli nie prosiłeś o reset hasła, zignoruj tę wiadomość</li>
            </ul>
        </div>

        <p style="font-size: 13px; color: #666; margin-top: 30px;">
            Pozdrawiamy,<br>
            <strong>Zespół SkanPolis</strong>
        </p>
    </div>

    <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
        <p>© 2024 SkanPolis. Wszelkie prawa zastrzeżone.</p>
        <p>To wiadomość automatyczna, nie odpowiadaj na nią.</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Text-only template dla emaila resetowania hasła
     */
    private function getPasswordResetTextBody(string $resetUrl): string {
        return <<<TEXT
SKANPOLIS - Resetowanie hasła

Witaj,

Otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta w SkanPolis.

Aby ustawić nowe hasło, przejdź do poniższego linku:

{$resetUrl}

WAŻNE INFORMACJE BEZPIECZEŃSTWA:
- Link jest ważny przez 1 godzinę
- Może być użyty tylko jeden raz
- Jeśli nie prosiłeś o reset hasła, zignoruj tę wiadomość

Pozdrawiamy,
Zespół SkanPolis

---
© 2024 SkanPolis. Wszelkie prawa zastrzeżone.
To wiadomość automatyczna, nie odpowiadaj na nią.
TEXT;
    }

    /**
     * Wysyła email z ofertą ubezpieczeniową i załącznikiem PDF
     *
     * @param string $toEmail Email odbiorcy
     * @param string $insuranceName Nazwa ubezpieczyciela (do tematu emaila)
     * @param string $pdfContent Zawartość PDF jako string (z PdfService)
     * @param string $pdfFilename Nazwa pliku PDF
     * @return bool
     */
    public function sendOfferEmail(string $toEmail, string $insuranceName, string $pdfContent, string $pdfFilename = 'oferta.pdf'): bool {
        try {
            // Walidacja email
            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid email address: " . $toEmail);
                return false;
            }

            // Nadawca
            $this->mailer->setFrom($this->fromAddress, $this->fromName);

            // Odbiorca
            $this->mailer->addAddress($toEmail);

            // Temat emaila z nazwą ubezpieczyciela
            $safeInsuranceName = htmlspecialchars($insuranceName, ENT_QUOTES, 'UTF-8');
            $this->mailer->Subject = "SkanPolis - Oferta {$safeInsuranceName}";

            // Debug: Sprawdź rozmiar PDF
            error_log("EmailService: Adding PDF attachment, size: " . strlen($pdfContent) . " bytes, filename: " . $pdfFilename);

            // Załącznik PDF - PHPMailer automatycznie wybierze encoding
            $this->mailer->addStringAttachment($pdfContent, $pdfFilename);

            // Treść emaila
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getOfferEmailHTMLBody($insuranceName);
            $this->mailer->AltBody = $this->getOfferEmailTextBody($insuranceName);

            // Wysyłka
            $result = $this->mailer->send();

            // Czyszczenie dla następnego użycia
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            return $result;

        } catch (Exception $e) {
            error_log("Email send error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * HTML template dla emaila z ofertą
     */
    private function getOfferEmailHTMLBody(string $insuranceName): string {
        $safeInsuranceName = htmlspecialchars($insuranceName, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oferta ubezpieczenia - SkanPolis</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #00897b, #26a69a); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 28px;">
            <span style="color: #e0f7f4;">SKAN</span>POLIS
        </h1>
    </div>

    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px;">
        <h2 style="color: #00897b; margin-top: 0;">Twoja polubiona oferta</h2>

        <p>Witaj,</p>

        <p>Przesyłamy szczegóły wybranej przez Ciebie oferty ubezpieczeniowej od <strong>{$safeInsuranceName}</strong>.</p>

        <div style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #2e7d32;">
                <strong>📎 Dokument PDF</strong>
            </p>
            <p style="margin: 10px 0 0 0; font-size: 14px; color: #2e7d32;">
                W załączniku znajdziesz pełne informacje o ofercie w formacie PDF, który możesz pobrać i zachować.
            </p>
        </div>

        <p style="font-size: 14px; color: #666;">
            Jeśli masz pytania lub chcesz skorzystać z oferty, skontaktuj się bezpośrednio z ubezpieczycielem lub odwiedź naszą stronę.
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{$this->getAppUrl()}/html/main.php"
               style="background: linear-gradient(135deg, #00897b, #26a69a);
                      color: white;
                      padding: 12px 30px;
                      text-decoration: none;
                      border-radius: 5px;
                      display: inline-block;
                      font-weight: bold;">
                Przeglądaj więcej ofert
            </a>
        </div>

        <p style="font-size: 13px; color: #666; margin-top: 30px;">
            Pozdrawiamy,<br>
            <strong>Zespół SkanPolis</strong>
        </p>
    </div>

    <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
        <p>© 2024 SkanPolis. Wszelkie prawa zastrzeżone.</p>
        <p>To wiadomość automatyczna, nie odpowiadaj na nią.</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Text-only template dla emaila z ofertą
     */
    private function getOfferEmailTextBody(string $insuranceName): string {
        $safeInsuranceName = htmlspecialchars($insuranceName, ENT_QUOTES, 'UTF-8');
        $appUrl = $this->getAppUrl();

        return <<<TEXT
SKANPOLIS - Twoja polubiona oferta

Witaj,

Przesyłamy szczegóły wybranej przez Ciebie oferty ubezpieczeniowej od {$safeInsuranceName}.

W załączniku znajdziesz pełne informacje o ofercie w formacie PDF, który możesz pobrać i zachować.

Jeśli masz pytania lub chcesz skorzystać z oferty, skontaktuj się bezpośrednio z ubezpieczycielem lub odwiedź naszą stronę:
{$appUrl}/html/main.php

Pozdrawiamy,
Zespół SkanPolis

---
© 2024 SkanPolis. Wszelkie prawa zastrzeżone.
To wiadomość automatyczna, nie odpowiadaj na nią.
TEXT;
    }

    /**
     * Pomocnicza metoda do pobierania URL aplikacji
     */
    private function getAppUrl(): string {
        return getenv('APP_URL') ?: 'http://localhost:8080';
    }

    /**
     * Testowa metoda do weryfikacji konfiguracji SMTP
     * NIE używać w produkcji!
     */
    public function testConnection(): bool {
        try {
            return $this->mailer->smtpConnect();
        } catch (Exception $e) {
            error_log("SMTP connection test failed: " . $e->getMessage());
            return false;
        }
    }
}
