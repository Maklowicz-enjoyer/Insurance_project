<?php
/**
 * Formularz resetowania hasła - walidacja tokenu i ustawienie nowego hasła
 * OWASP Top 10 Compliance:
 * - A01:2021 - Broken Access Control: Token validation
 * - A02:2021 - Cryptographic Failures: Secure password hashing
 * - A03:2021 - Injection: Prepared statements
 * - A07:2021 - Identification and Authentication Failures: Strong password policy
 */

session_start();
ob_start();

require_once __DIR__ . '/db_connect.php';

$errors = [];
$success = false;
$tokenValid = false;
$email = null;
$token = '';

// CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Sprawdź token z URL
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    // Walidacja formatu tokenu (64 hex chars)
    if (preg_match('/^[a-f0-9]{64}$/i', $token)) {
        try {
            // Hash tokenu (w DB przechowujemy hash)
            $tokenHash = hash('sha256', $token);

            // Sprawdź token w bazie
            $stmt = $pdo->prepare("
                SELECT email, expires_at, used
                FROM password_resets
                WHERE token = :token
                AND used = 0
                AND expires_at > NOW()
                LIMIT 1
            ");

            $stmt->execute(['token' => $tokenHash]);
            $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resetRequest) {
                $tokenValid = true;
                $email = $resetRequest['email'];
            } else {
                $errors[] = 'Link resetowania hasła jest nieprawidłowy, wygasł lub został już użyty.';
            }

        } catch (PDOException $e) {
            error_log("Database error in reset_password.php (token check): " . $e->getMessage());
            $errors[] = 'Wystąpił błąd serwera. Spróbuj ponownie później.';
        }
    } else {
        $errors[] = 'Nieprawidłowy format tokenu.';
    }
} else {
    $errors[] = 'Brak tokenu resetowania hasła.';
}

// Przetwarzanie formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    // CSRF Protection
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors[] = 'Nieprawidłowy token CSRF. Odśwież stronę i spróbuj ponownie.';
    } else {
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $tokenFromForm = $_POST['token'] ?? '';

        // Walidacja tokenu z formularza
        if ($tokenFromForm !== $token) {
            $errors[] = 'Nieprawidłowy token.';
        }

        // Walidacja hasła
        if (empty($password)) {
            $errors[] = 'Hasło jest wymagane.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Hasła nie są identyczne.';
        }

        // Sprawdzenie siły hasła (zgodnie z polityką bezpieczeństwa)
        $passwordErrors = validatePasswordStrength($password);
        if (!empty($passwordErrors)) {
            $errors = array_merge($errors, $passwordErrors);
        }

        // Jeśli brak błędów, zaktualizuj hasło
        if (empty($errors)) {
            try {
                // Hash tokenu
                $tokenHash = hash('sha256', $token);

                // Sprawdź ponownie token (double-check)
                $checkToken = $pdo->prepare("
                    SELECT email
                    FROM password_resets
                    WHERE token = :token
                    AND used = 0
                    AND expires_at > NOW()
                    LIMIT 1
                ");
                $checkToken->execute(['token' => $tokenHash]);
                $resetData = $checkToken->fetch(PDO::FETCH_ASSOC);

                if (!$resetData) {
                    $errors[] = 'Token wygasł lub został już użyty.';
                } else {
                    // Rozpocznij transakcję
                    $pdo->beginTransaction();

                    try {
                        // Zaktualizuj hasło użytkownika
                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                        $updatePassword = $pdo->prepare("
                            UPDATE User
                            SET haslo = :password, updated_at = NOW()
                            WHERE email = :email
                        ");

                        $updatePassword->execute([
                            'password' => $hashedPassword,
                            'email' => $resetData['email']
                        ]);

                        // Oznacz token jako użyty
                        $markUsed = $pdo->prepare("
                            UPDATE password_resets
                            SET used = 1
                            WHERE token = :token
                        ");
                        $markUsed->execute(['token' => $tokenHash]);

                        // Usuń wszystkie inne tokeny dla tego użytkownika
                        $deleteOthers = $pdo->prepare("
                            DELETE FROM password_resets
                            WHERE email = :email
                            AND token != :token
                        ");
                        $deleteOthers->execute([
                            'email' => $resetData['email'],
                            'token' => $tokenHash
                        ]);

                        // Zatwierdź transakcję
                        $pdo->commit();

                        $success = true;
                        $tokenValid = false;

                        // Wyczyść sesję z bezpieczeństwa
                        session_destroy();

                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        error_log("Database error in reset_password.php (update): " . $e->getMessage());
                        $errors[] = 'Wystąpił błąd podczas aktualizacji hasła.';
                    }
                }

            } catch (PDOException $e) {
                error_log("Database error in reset_password.php (process): " . $e->getMessage());
                $errors[] = 'Wystąpił błąd serwera. Spróbuj ponownie później.';
            }
        }
    }
}

/**
 * Walidacja siły hasła
 * Zgodnie z polityką bezpieczeństwa: min 12 znaków, wielka litera, cyfra, znak specjalny
 */
function validatePasswordStrength(string $password): array {
    $errors = [];

    // Min długość (konfiguracja z ENV lub default 12)
    $minLength = (int)(getenv('PASSWORD_MIN_LENGTH') ?: 12);
    if (strlen($password) < $minLength) {
        $errors[] = "Hasło musi mieć minimum {$minLength} znaków.";
    }

    // Wielka litera
    if (getenv('PASSWORD_REQUIRE_UPPERCASE') !== 'false') {
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Hasło musi zawierać co najmniej jedną wielką literę.';
        }
    }

    // Mała litera
    if (getenv('PASSWORD_REQUIRE_LOWERCASE') !== 'false') {
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Hasło musi zawierać co najmniej jedną małą literę.';
        }
    }

    // Cyfra
    if (getenv('PASSWORD_REQUIRE_NUMBER') !== 'false') {
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Hasło musi zawierać co najmniej jedną cyfrę.';
        }
    }

    // Znak specjalny
    if (getenv('PASSWORD_REQUIRE_SPECIAL') !== 'false') {
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Hasło musi zawierać co najmniej jeden znak specjalny (!@#$%^&*).';
        }
    }

    return $errors;
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
    <title>Ustaw nowe hasło - SkanPolis</title>
    <link rel="stylesheet" href="/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .password-requirements {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
            font-size: 13px;
        }
        .password-requirements ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .password-requirements li {
            margin: 5px 0;
            color: #2e7d32;
        }
        .password-strength {
            margin-top: 10px;
            height: 5px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            transition: width 0.3s, background 0.3s;
            width: 0%;
            background: #f44336;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
            <a href="/scripts/login.php" class="btn">Logowanie</a>
        </div>
    </header>

    <main>
        <div class="form-container">
            <h2>Ustaw nowe hasło</h2>

            <?php if ($success): ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 10px;">✅</div>
                    <strong style="font-size: 18px;">Hasło zostało zmienione!</strong>
                    <p style="margin: 15px 0 0 0; font-size: 14px;">
                        Twoje hasło zostało pomyślnie zaktualizowane.
                        Możesz teraz zalogować się używając nowego hasła.
                    </p>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="/scripts/login.php" class="login-btn" style="display: inline-block; text-decoration: none;">
                        Przejdź do logowania
                    </a>
                </div>

            <?php elseif (!$tokenValid): ?>
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 10px;">❌</div>
                    <strong style="font-size: 18px;">Link resetowania nieważny</strong>
                    <?php if (!empty($errors)): ?>
                        <div style="margin-top: 15px;">
                            <?php foreach ($errors as $error): ?>
                                <p style="margin: 5px 0; font-size: 14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="/scripts/forgot_password.php" class="login-btn" style="display: inline-block; text-decoration: none;">
                        Wyślij nowy link resetujący
                    </a>
                </div>

                <div style="text-align: center; margin-top: 15px;">
                    <a href="/scripts/login.php" style="color: #00897b; text-decoration: none;">
                        ← Powrót do logowania
                    </a>
                </div>

            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <p style="margin: 5px 0;">❌ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="password-requirements">
                    <strong>📋 Wymagania dotyczące hasła:</strong>
                    <ul>
                        <li>Minimum <?= (int)(getenv('PASSWORD_MIN_LENGTH') ?: 12) ?> znaków</li>
                        <li>Co najmniej jedna wielka litera (A-Z)</li>
                        <li>Co najmniej jedna mała litera (a-z)</li>
                        <li>Co najmniej jedna cyfra (0-9)</li>
                        <li>Co najmniej jeden znak specjalny (!@#$%^&*)</li>
                    </ul>
                </div>

                <form method="POST" action="" id="resetForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                    <label for="password">Nowe hasło:</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        autofocus
                    >
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>

                    <label for="password_confirm">Potwierdź hasło:</label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        required
                        autocomplete="new-password"
                    >

                    <button type="submit" class="login-btn">Zmień hasło</button>
                </form>

                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-top: 20px;">
                    <p style="margin: 0; font-size: 14px; color: #856404;">
                        <strong>🔒 Bezpieczeństwo:</strong>
                    </p>
                    <ul style="margin: 10px 0 0 0; padding-left: 20px; font-size: 13px; color: #856404;">
                        <li>Użyj silnego, unikalnego hasła</li>
                        <li>Nie używaj ponownie hasła z innych serwisów</li>
                        <li>Link resetujący może być użyty tylko jeden raz</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <p>© 2024 SkanPolis. Wszelkie prawa zastrzeżone.</p>
    </footer>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');

        if (passwordInput && strengthBar) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;

                // Długość
                if (password.length >= 12) strength += 25;
                if (password.length >= 16) strength += 10;

                // Wielka litera
                if (/[A-Z]/.test(password)) strength += 20;

                // Mała litera
                if (/[a-z]/.test(password)) strength += 20;

                // Cyfra
                if (/[0-9]/.test(password)) strength += 15;

                // Znak specjalny
                if (/[^A-Za-z0-9]/.test(password)) strength += 20;

                // Kolor i szerokość paska
                strengthBar.style.width = strength + '%';

                if (strength < 40) {
                    strengthBar.style.background = '#f44336'; // czerwony
                } else if (strength < 70) {
                    strengthBar.style.background = '#ff9800'; // pomarańczowy
                } else if (strength < 90) {
                    strengthBar.style.background = '#ffeb3b'; // żółty
                } else {
                    strengthBar.style.background = '#4caf50'; // zielony
                }
            });
        }

        // Walidacja przed wysłaniem formularza
        const form = document.getElementById('resetForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const passwordConfirm = document.getElementById('password_confirm').value;

                if (password !== passwordConfirm) {
                    e.preventDefault();
                    alert('Hasła nie są identyczne!');
                    return false;
                }
            });
        }
    </script>
</body>
</html>
