<?php
session_start();
if (!isset($_SESSION['offers'])) {
    die("Nie znaleziono wyników wyszukiwania.");
}

$offers = $_SESSION['offers'];
$vehicleInfo = $_SESSION['vehicle_info'];

?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkanPolis - Wyniki Ofert</title>
  <link rel="stylesheet" href="../css/list.css">
</head>
<body>
  <header class="header">
    <div class="header-container">
      <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
      <div class="header-buttons">
        <a href="../html/main.html" class="btn back">ZMIEŃ DANE</a>
        <a href="../html/account.html" class="btn acc">MOJE KONTO</a>
        <a href="../index.html" class="btn logout">WYLOGUJ SIĘ</a>
      </div>
    </div>
  </header>
  <main>
    <section class="vehicle-info">
      <h2>Wybrany pojazd</h2>
      <div class="vehicle-details">
        <p><strong>Marka:</strong> <?= htmlspecialchars($vehicleInfo['brand']); ?></p>
        <p><strong>Model:</strong> <?= htmlspecialchars($vehicleInfo['bodyType']); ?></p>
        <p><strong>Data startu ubezpieczenia:</strong> <?= htmlspecialchars($vehicleInfo['insurance-date']); ?></p>
      </div>
    </section>
  
    <section class="offers">
      <h2>Dostępne oferty</h2>
      <?php foreach ($offers as $offer): ?>
      <div class="offer <?= $offer === reset($offers) ? 'recommended' : ''; ?>">
        <div class="offer-header">
          <?php if ($offer === reset($offers)): ?>
            <span class="badge">Najtańsza oferta</span>
          <?php endif; ?>
        </div>
        <div class="offer-details">
          <h3 class="company-name"><?= htmlspecialchars($offer['Insurance_name']); ?></h3>
          <div class="offer-info">
            <p><?= htmlspecialchars($offer['Insurance_type']); ?></p>
            <p><strong><?= number_format($offer['calculated_price'], 2); ?> zł</strong></p>
          </div>
          <a href="detail.php?id=<?= htmlspecialchars($offer['Insurance_ID']); ?>&price=<?= htmlspecialchars($offer['calculated_price']); ?>" class="btn choose-btn">Wybierz ofertę</a>
        </div>
      </div>
      <?php endforeach; ?>
    </section>
  </main>
  <footer class="footer">
    <p>© 2024 Skanpolis. Wszelkie prawa zastrzeżone.</p>
  </footer>
</body>
</html>
