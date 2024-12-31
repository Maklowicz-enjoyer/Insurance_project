<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'db_connect.php'; // Połączenie z bazą danych w oddzielnym pliku

try {
    session_start();

    if (!isset($_SESSION['Users_ID'])) {
        die("Nie jesteś zalogowany. Proszę się zalogować.");
    }

    $userID = $_SESSION['Users_ID'];

    // Pobieranie danych użytkownika
    $stmt = $pdo->prepare("SELECT email FROM User WHERE Users_ID = :id");
    $stmt->execute(['id' => $userID]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Użytkownik o podanym ID nie istnieje.");
    }

    // Pobieranie zapisanych polis użytkownika
    $sql = "SELECT Insurance_name, Insurance_type, Price FROM Insurance WHERE Users_ID = :Users_ID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['Users_ID' => $userID]);
    $savedIns = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Użytkownika - SkanPolis</title>
  <link rel="stylesheet" href="../css/account.css">
</head>
<body>
  <header class="header">
    <div class="header-container">
      <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
      <a href="../html/main.html" class="btn back">POWRÓT</a>
    </div>
  </header>

  <main>
    <section class="profile">
      <h2 class="profile-title">Twój profil</h2>
      <p class="login-info">Zalogowano jako: <span class="user-email"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span></p>
    </section>

    <section class="saved-policies">
      <h3 class="section-title">Zapisane polisy</h3>
      <div class="calc-container">
        <?php if (!empty($savedIns)): ?>
          <?php foreach ($savedIns as $insurance): ?>
            <div class="calc-card">
              <h4 class="car-title"><?php echo htmlspecialchars($insurance['Insurance_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
              <p class="car-details">Typ polisy: <span><?php echo htmlspecialchars($insurance['Insurance_type'], ENT_QUOTES, 'UTF-8'); ?></span></p>
              <p class="price">Cena: <strong><?php echo number_format($insurance['Price'], 2); ?> zł</strong></p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>Brak zapisanych polis.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer class="footer">
    <p>© 2024 Skanpolis. Wszelkie prawa zastrzeżone.</p>
  </footer>
</body>
</html>
