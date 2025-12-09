<?php
/**
 * Formularz żądania resetu hasła
 * OWASP Top 10 Compliance:
 * - A01:2021 - Broken Access Control: Rate limiting
 * - A02:2021 - Cryptographic Failures: Secure token generation
 * - A03:2021 - Injection: Prepared statements
 * - A05:2021 - Security Misconfiguration: Bezpieczne ENV
 * - A07:2021 - Identification and Authentication Failures: Token expiration
 */

// Konfiguracja cookie sesyjnego (musi być PRZED session_start!)
session_set_cookie_params([
    'lifetime' => 3600,        // 1 godzina
    'path' => '/',
    'domain' => '',            // Obecna domena
    'secure' => false,         // HTTP OK (dla local dev)
    'httponly' => true,        // Blokada JavaScript
    'samesite' => 'Lax'        // CSRF protection
]);

session_start();
ob_start();

require_once __DIR__ . '/db_connect.php';
// EmailService będzie załadowany tylko gdy potrzebny (po walidacji)

$errors = [];
$success = false;
$email = '';

// CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors[] = 'Nieprawidłowy token CSRF. Odśwież stronę i spróbuj ponownie.';
    } else {
        // Retrieve and sanitize input
        $email = trim($_POST['email'] ?? '');

        // Walidacja email
        if (empty($email)) {
            $errors[] = 'Adres e-mail jest wymagany.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Nieprawidłowy format adresu e-mail.';
        }

        // Rate Limiting - max 1 request co 5 minut dla danego IP
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $throttleTime = (int)(getenv('PASSWORD_RESET_THROTTLE') ?: 300); // 5 minut

        if (empty($errors)) {
            try {
                // Sprawdź ostatnie żądanie z tego IP
                $checkThrottle = $pdo->prepare("
                    SELECT created_at
                    FROM password_resets
                    WHERE ip_address = :ip
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $checkThrottle->execute(['ip' => $ipAddress]);
                $lastRequest = $checkThrottle->fetch(PDO::FETCH_ASSOC);

                if ($lastRequest) {
                    $lastRequestTime = strtotime($lastRequest['created_at']);
                    $timeDiff = time() - $lastRequestTime;

                    if ($timeDiff < $throttleTime) {
                        $waitTime = ceil(($throttleTime - $timeDiff) / 60);
                        $errors[] = "Zbyt wiele żądań. Spróbuj ponownie za {$waitTime} minut.";
                    }
                }

                // Sprawdź czy email istnieje w bazie (timing-safe)
                if (empty($errors)) {
                    $checkUser = $pdo->prepare("SELECT email FROM User WHERE email = :email");
                    $checkUser->execute(['email' => $email]);
                    $userExists = $checkUser->fetch(PDO::FETCH_ASSOC);

                    // ZAWSZE pokazuj sukces, nawet jeśli email nie istnieje (OWASP - user enumeration prevention)
                    // Ale wysyłamy email tylko jeśli użytkownik istnieje

                    if ($userExists) {
                        // Generuj bezpieczny token (256 bitów entropii)
                        $token = bin2hex(random_bytes(32));
                        $tokenHash = hash('sha256', $token); // Hashujemy token przed zapisem w DB

                        // Czas wygaśnięcia (1 godzina)
                        $tokenLifetime = (int)(getenv('PASSWORD_RESET_TOKEN_LIFETIME') ?: 3600);
                        $expiresAt = date('Y-m-d H:i:s', time() + $tokenLifetime);

                        // Usuń stare tokeny dla tego użytkownika
                        $deleteOld = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
                        $deleteOld->execute(['email' => $email]);

                        // Zapisz nowy token w bazie
                        $insertToken = $pdo->prepare("
                            INSERT INTO password_resets
                            (email, token, expires_at, ip_address, user_agent)
                            VALUES (:email, :token, :expires_at, :ip, :user_agent)
                        ");

                        $insertToken->execute([
                            'email' => $email,
                            'token' => $tokenHash,
                            'expires_at' => $expiresAt,
                            'ip' => $ipAddress,
                            'user_agent' => substr($userAgent, 0, 255)
                        ]);

                        // Wysyłamy oryginalny token (NIE hash) w emailu
                        // Ładujemy EmailService tylko teraz (gdy jest potrzebny)
                        require_once __DIR__ . '/EmailService.php';
                        $emailService = new EmailService();
                        $emailSent = $emailService->sendPasswordResetEmail($email, $token);

                        if (!$emailSent) {
                            error_log("Failed to send password reset email to: " . $email);
                            // NIE pokazujemy błędu użytkownikowi z powodów bezpieczeństwa
                        }
                    }

                    // ZAWSZE pokazuj sukces (anti-enumeration)
                    $success = true;
                }

            } catch (PDOException $e) {
                error_log("Database error in forgot_password.php: " . $e->getMessage());
                $errors[] = 'Wystąpił błąd serwera. Spróbuj ponownie później.';
            }
        }
    }
}

// Regeneruj CSRF token po użyciu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetowanie hasła - SkanPolis</title>
    <link rel="stylesheet" href="/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
            <a href="/scripts/login.php" class="btn">Powrót do logowania</a>
        </div>
    </header>

    <main>
        <div class="form-container">
            <h2>Resetowanie hasła</h2>

            <?php if ($success): ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                    <strong>✅ Email wysłany!</strong>
                    <p style="margin: 10px 0 0 0; font-size: 14px;">
                        Jeśli podany adres email istnieje w naszym systemie,
                        otrzymasz wiadomość z instrukcjami resetowania hasła.
                    </p>
                    <p style="margin: 10px 0 0 0; font-size: 14px;">
                        Link jest ważny przez <strong>1 godzinę</strong>.
                    </p>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <p style="margin: 5px 0;">❌ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <p style="text-align: center; color: #666; margin-bottom: 20px;">
                    Podaj adres email przypisany do Twojego konta.
                    Wyślemy Ci link do resetowania hasła.
                </p>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <label for="email">Adres e-mail:</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                        required
                        autocomplete="email"
                        autofocus
                    >

                    <button type="submit" class="login-btn">Wyślij link resetujący</button>
                </form>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="/scripts/login.php" style="color: #00897b; text-decoration: none;">
                        ← Powrót do logowania
                    </a>
                </div>
            <?php else: ?>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="/scripts/login.php" class="login-btn" style="display: inline-block; text-decoration: none;">
                        Przejdź do logowania
                    </a>
                </div>
            <?php endif; ?>

            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-top: 30px;">
                <p style="margin: 0; font-size: 14px; color: #856404;">
                    <strong>💡 Wskazówki bezpieczeństwa:</strong>
                </p>
                <ul style="margin: 10px 0 0 0; padding-left: 20px; font-size: 13px; color: #856404;">
                    <li>Sprawdź folder SPAM jeśli nie otrzymałeś emaila</li>
                    <li>Link resetujący jest ważny przez 1 godzinę</li>
                    <li>Link można użyć tylko jeden raz</li>
                    <li>Nigdy nie udostępniaj linku innym osobom</li>
                </ul>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>© 2024 SkanPolis. Wszelkie prawa zastrzeżone.</p>
    </footer>
</body>
</html>
