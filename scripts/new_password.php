<?php
require 'db_connect.php';
session_start();
if (!isset($_SESSION['userID']) || empty($_SESSION['userID'])) {
    $_SESSION['error'] = 'Sesja wygasła lub użytkownik nie został rozpoznany.';
    header('Location: password_retrieval.php'); // Przekierowanie do odzyskiwania hasła
    exit();
}
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize error messages
    $errors = [];

    // Get form inputs
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($_SESSION['userID'])) {
        die('User ID in session is missing or invalid.');
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
    
    if (empty($errors)) {
        try{
            $new_password = $_POST['password'];

           
            // Haszujemy nowe hasło
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
            // Aktualizujemy hasło w bazie danych
            $query = "UPDATE User SET haslo = :haslo WHERE Users_ID = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':haslo' => $hashed_password,
                ':id' => $_SESSION['userID']
            ]);
        
            // Usuwamy ID użytkownika z sesji, aby zakończyć proces odzyskiwania hasła
            unset($_SESSION['userID']);
        
            // Przekierowanie na stronę logowania po udanej zmianie hasła
            $_SESSION['success'] = 'Hasło zostało pomyślnie zmienione!';
            header('Location: login.php');
            exit();

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
  <title>Odzyskiwanie hasła - SkanPolis</title>
  <link rel="stylesheet" href="../css/new_password.css">
</head>
<body>
  
  <header class="header">
    <div class="header-container">
      <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
      <a href="../index.html" class="btn back">POWRÓT</a>
    </div>
  </header>

  <main>
    <div class="form-container">
        <h2>Odzyskiwanie hasła</h2>
        <form action="new_password.php" method="post">

            <label for="password">Wpisz nowe hasło</label>
            <input type="password" id="password" name="password" placeholder="Wpisz nowe hasło">

            
            <label for="confirm_password">Powtórz nowe hasło</label>
            <input type="password" id="confirm_ password" name="confirm_password" placeholder="Ponownie wpisz nowe hasło">


            <button type="submit" class="pass-btn">Zmień hasło</button>

        </form>
    </div>
  </main>
</body>
</html>