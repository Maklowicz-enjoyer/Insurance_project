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

    //Obsługa zapisywania oferty
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if(!isset($_SESSION['Users_ID'])) {
            die("Musisz być zalogowany, aby zapisać ofertę");
        }
        $userID = $_SESSION['Users_ID'];

        $savestmt = $pdo->prepare("UPDATE Insurance SET Users_ID = :userID WHERE Insurance_ID = :insuranceId");
        $savestmt->execute([
            'userID' => $userID,
            'insuranceId' => $insuranceId
        ]);

        $message = "Oferta została zapisana pomyślnie";
    }

    //Dane sesji
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
        <a href="account.php" class="btn acc">MOJE KONTO</a>
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
     <!-- Formularz zapisywania oferty -->
      <div class="save-offer">
        <form method="POST">
            <button type="submit" class="btn save-btn">ZAPISZ OFERTĘ</button>
        </form>
        <?php if (isset($message)): ?>
          <p class="success-message"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>
      </div>
    </section>
  </main>
  <footer class="footer">
    <p>&copy; 2024 SkanPolis. Wszelkie prawa zastrzeżone.</p>
  </footer>
</body>
</html>
