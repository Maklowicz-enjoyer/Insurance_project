<?php
// Start session and output buffering
session_start();
ob_start();

require 'db_connect.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate inputs
    if (empty($email)) {
        $errors[] = 'E-mail is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid e-mail format.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    // If no validation errors, check the database
    if (empty($errors)) {
        try {
            $query = "SELECT haslo, SUser FROM User WHERE email = :email";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['haslo'])) {
                // Store user info in session
                $_SESSION['user_email'] = $email;
                $_SESSION['is_admin'] = ($user['SUser'] == 1);

                // Clear output buffer before redirect
                ob_end_clean();

                // Check if the user is an admin (SUser = 1)
                if ($user['SUser'] == 1) {
                    // Login successful for admin: Redirect to admin.php
                    header("Location: admin.php");
                    exit;
                } else {
                    // Login successful for regular user: Redirect to main.html
                    header("Location: ../html/main.html");
                    exit;
                }
            } else {
                $errors[] = 'Invalid email or password.';
            
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error. Please try again later.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// If we're still here, show the form
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkanPolis Logowanie</title>
  <link rel="stylesheet" href="/css/login.css">
</head>
<body>
  <header class="header">
    <div class="header-container">
        <a href="/index.html" class="btn back">POWRÓT</a>
        <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
    </div>
  </header>

  <main>
    <div class="form-container">
      <h2>Logowanie</h2>

      <?php if (!empty($errors)): ?>
        <div class="error-messages">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li style="color: red;"><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form action="/scripts/login.php" method="post" autocomplete="off">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="Wpisz e-mail" value="<?php echo htmlspecialchars($email); ?>" required autocomplete="email" spellcheck="false">

        <label for="password">Hasło</label>
        <input type="password" id="password" name="password" placeholder="Wpisz haslo" required autocomplete="current-password" spellcheck="false">

        <button type="submit" class="login-btn">ZALOGUJ SIĘ</button>
        <button type="button" class="reset-btn" onclick="alert('Funkcja odzyskiwania hasła jeszcze nie zaimplementowana')">Zapomniałem hasła</button>
      </form>
    </div>
  </main>

  <footer class="footer">
    <p>© <?php echo date("Y"); ?> Skanpolis. Wszelkie prawa zastrzeżone.</p>
  </footer>
</body>
</html>
