<?php
require 'db_connect.php';

session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pobieramy email i kod odzyskiwania z formularza
    $email = trim($_POST['email']);
    $kod = trim($_POST['kod']);
    
    // Sprawdzamy, czy kod jest poprawny (6 cyfr)
    if (!preg_match('/^\d{6}$/', $kod)) {
        $_SESSION['error'] = 'Kod odzyskiwania musi składać się z 6 cyfr.';
        header('Location: password_retrieval.php');
        exit();
    }
    
    // Wyszukaj użytkownika w bazie danych na podstawie emaila i kodu odzyskiwania
    $stmt = $pdo->prepare("SELECT Users_ID FROM User WHERE email = :email AND kod = :kod");
    $stmt->execute([
        ':email' => $email,
        ':kod' => $kod
    ]);

    // Sprawdzamy, czy znaleźliśmy użytkownika
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Jeśli użytkownik istnieje, zapisujemy ID w sesji i przenosimy na stronę zmiany hasła
        $_SESSION['userID'] = $user['Users_ID'];
        header('Location: new_password.php');
        exit();
    } else {
        // Jeśli użytkownik lub kod jest niepoprawny
        $_SESSION['error'] = 'Nieprawidłowy email lub kod odzyskiwania.';
        header('Location: password_retrieval.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Odzyskiwanie hasła - SkanPolis</title>
  <link rel="stylesheet" href="../css/password_retrieval.css">
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
        <form action="password_retrieval.php" method="post">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="Wpisz e-mail" value="<?php echo htmlspecialchars($email ?? ''); ?>">
            
            <label for="code">6-cyforwy kod odzyskiwania</label>
            <input type="number" id="kod" name="kod" placeholder="Podaj 6-cyforwy kod odzyskiwania">
            
            <button type="submit" class="pass-btn">Odzyskaj hasło</button>

        </form>
    </div>
  </main>
</body>
</html>