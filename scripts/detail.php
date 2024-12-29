<?php
require 'db_connect.php';

try {
    session_start();

    if (!isset($_GET['id'])) {
        die('ID oferty nie zostało przekazane w URL.');
    }
    
    $insuranceId = intval($_GET['id']);

    if (isset($_GET['price'])) {
        $_SESSION['calculated_price'] = floatval($_GET['price']);
    }

    // Pobranie szczegółowych danych oferty z bazy
    $stmt = $pdo->prepare("SELECT * FROM Insurance WHERE Insurance_ID = :id");
    $stmt->bindParam(':id', $insuranceId, PDO::PARAM_INT);
    $stmt->execute();
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offer) {
        die("Nie znaleziono oferty o podanym identyfikatorze.");
    }

    // Przetwarzanie danych oferty
    
    $vehicleInfo = $_SESSION['vehicle_info'] ?? null;
    $offers = $_SESSION['offers'] ?? null;
    $calculatedPrice = $_SESSION['calculated_price'] ?? null;

} catch (PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkanPolis - Szczegóły Oferty</title>
  <link rel="stylesheet" href="../css/detail.css">
</head>
<body>
  <header class="header">
    <div class="header-container">
      <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
      <div class="header-buttons">
        <a href="list.php" class="btn back">POWRÓT</a>
        <a href="../html/main.html" class="btn back">ZMIEŃ DANE</a>
        <a href="../html/account.html" class="btn acc">MOJE KONTO</a>
        <a href="../index.html" class="btn logout">WYLOGUJ SIĘ</a>
      </div>
    </div>
  </header>
  <main>
    <section class="offer-detail">
      <div class="offer-summary">
        <h2 class="offer-title"><?= htmlspecialchars($offer['Insurance_name']); ?></h2>
        <p class="offer-price">Cena: <span><?= number_format($calculatedPrice, 2); ?> zł</span></p>
      </div>
      <div class="offer-details">
        <h3>SZCZEGÓŁY WYSZUKANEJ OFERTY:</h3>
        <div class="offer-description">
          <p>Rodzaj ubezpieczenia: <?= htmlspecialchars($offer['Insurance_type']); ?></p>
          <p>Opis oferty: </p>
          <?php if ($vehicleInfo): ?>
            <p>Marka pojazdu: <?= htmlspecialchars($vehicleInfo['brand']); ?></p>
            <p>Typ nadwozia: <?= htmlspecialchars($vehicleInfo['bodyType']); ?></p>
            <p>Data startu ubezpieczenia: <?= htmlspecialchars($vehicleInfo['insurance-date']); ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="send-option">
        <button class="btn send-btn">WYŚLIJ NA MAIL-a</button>
      </div>
    </section>
  </main>
  <footer class="footer">
    <p>&copy; 2024 SkanPolis. Wszelkie prawa zastrzeżone.</p>
  </footer>
</body>
</html>
