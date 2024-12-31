<?php
require 'db_connect.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize error messages
    $errors = [];

    // Get form inputs
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $kod = trim($_POST['kod'] ?? '');

    // Validate email
    if (empty($email)) {
        $errors[] = 'E-mail is required.';
        var_dump($_POST);
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid e-mail format.';
        var_dump($_POST);
    }

    // Validate password
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must include at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must include at least one number.';
    }

    // Validate confirm password
    if (empty($confirmPassword)) {
        $errors[] = 'Confirm password is required.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // If no errors, process the form
    if (empty($errors)) {
        try {
            // Check if the email already exists
            $checkQuery = "SELECT User.email FROM User WHERE email = :email";
            $stmt = $pdo->prepare($checkQuery);
            $stmt->execute(['email' => $email]);
            $emailExists = $stmt->fetchColumn();

            if ($emailExists) {
                $errors[] = 'The email address is already registered.';
            } else {
                // Insert user into the database
                $insertQuery = "INSERT INTO User (email, haslo, SUser, Wiek, kod) VALUES (:email, :password, :isSuperUser, :age, :kod)";
                $stmt = $pdo->prepare($insertQuery);

                // Hash the password securely
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt->execute([
                    'email' => $email,
                    'password' => $hashedPassword,
                    'isSuperUser' => 0, // Default value for SUser
                    'age' => 18, // Replace with a form field or default value
                    'kod' => $kod
                ]);

                // Redirect to main.html
                header("Location: ../html/main.html");
                exit;
            }
        } catch (PDOException $e) {
            // Handle query-related errors
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkanPolis - Rejestracja</title>
    <link rel="stylesheet" href="../css/register.css">
</head>
<body>
<header class="header">
    <div class="header-container">
        <a href="../index.html" class="btn back">POWRÓT</a>
        <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
    </div>
</header>

<main>
    <div class="form-container">
        <h2>Rejestracja</h2>

        <!-- Display errors -->
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="register.php" method="post">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="Wpisz e-mail" value="<?php echo htmlspecialchars($email ?? ''); ?>">

            <label for="password">Hasło</label>
            <input type="password" id="password" name="password" placeholder="Wpisz hasło">

            <label for="confirm_password">Powtórz hasło</label>
            <input type="password" id="confirm_ password" name="confirm_password" placeholder="Ponownie wpisz hasło">

            <label for="kod">Podaj 6-cyfrowy kod odzyskiwania</label>
            <input type="number" id="kod" name="kod" placeholder="Podaj 6-cyfrowy kod">


            <button type="submit" class="register-btn">ZAREJESTRUJ SIĘ</button>
        </form>
    </div>
</main>

<footer class="footer">
    <p>© 2024 Skanpolis. Wszelkie prawa zastrzeżone.</p>
</footer>
</body>
</html>
