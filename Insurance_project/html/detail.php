<?php
// html/detail.php
require_once __DIR__ . '/../scripts/session_check.php';
require_once __DIR__ . '/../scripts/db_connect.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? '';

if (!$id || !in_array($type, ['CAR', 'MOTORCYCLE'])) {
    die("Nieprawidłowa oferta.");
}

try {
    // 1. Pobierz szczegóły oferty (nadal z VIEW Insurance - to jest OK do wyświetlania)
    // UWAGA: Upewnij się, że kolumna Vehicle_Category w VIEW Insurance odpowiada 'CAR'/'MOTORCYCLE'
    $stmt = $pdo->prepare("SELECT * FROM Insurance WHERE Insurance_ID = :id AND Vehicle_Category = :type");
    $stmt->execute([':id' => $id, ':type' => $type]);
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offer) {
        die("Oferta nie istnieje.");
    }

    // 2. Sprawdź czy jest w FavoriteInsurance
    $email = $_SESSION['user_email'];
    $userStmt = $pdo->prepare("SELECT Users_ID FROM User WHERE email = :email");
    $userStmt->execute([':email' => $email]);
    $userId = $userStmt->fetchColumn();

    $favStmt = $pdo->prepare("SELECT 1 FROM FavoriteInsurance WHERE Users_ID = :uid AND Insurance_ID = :iid AND Insurance_Type = :itype");
    $favStmt->execute([':uid' => $userId, ':iid' => $id, ':itype' => $type]);
    $isFavorite = $favStmt->fetchColumn();

} catch (PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkanPolis - Szczegóły Oferty</title>
  <link rel="stylesheet" href="../css/detail.css">
  <style>
      .btn.fav { background: #ff9800; margin-top: 10px; }
      .btn.fav.remove { background: #e53935; }
      .alert { padding: 10px; background: #dff0d8; color: #3c763d; margin-bottom: 10px; border-radius: 4px; text-align: center; }
  </style>
</head>
<body>
 
  <header class="header">
    <div class="header-container">
      <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
      <div class="header-buttons">
        <a href="../scripts/manage_insurance.php" class="btn back">POWRÓT</a>
        <a href="account.php" class="btn acc">MOJE KONTO</a>
        <a href="../scripts/logout.php" class="btn logout">WYLOGUJ SIĘ</a>
      </div>
    </div>
  </header>
  
  <main>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
        <div class="alert">Oferta została dodana do ulubionych!</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'removed'): ?>
        <div class="alert" style="background: #f2dede; color: #a94442;">Oferta usunięta z ulubionych.</div>
    <?php endif; ?>

    <section class="offer-detail">
      <div class="offer-summary">
        <h2 class="offer-title"><?php echo htmlspecialchars($offer['Insurance_name']); ?></h2>
        <p class="offer-price">Cena: <span><?php echo number_format($offer['Price'], 2); ?> ZŁ</span></p>
      </div>
      
      <div class="offer-details">
        <h3>SZCZEGÓŁY OFERTY:</h3>
        <div class="offer-description">
          <p>Typ: <strong><?php echo htmlspecialchars($offer['Insurance_type']); ?></strong></p>
          <p>Użytkowanie: <?php echo htmlspecialchars($offer['Use_type']); ?></p>
          <p>Ważne do: <?php echo htmlspecialchars($offer['License_release_date']); ?></p>
          
          <?php if($type === 'CAR'): ?>
             <p>Nadwozie: <?php echo htmlspecialchars($offer['Typ_nadwozia']); ?></p>
             <p>Przebieg: <?php echo htmlspecialchars($offer['Planned_mileage']); ?> km</p>
          <?php else: ?>
             <p>Kategoria: Motocykl</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="send-option">
        <button class="btn send-btn" onclick="alert('Funkcja mailowa wkrótce!')">WYŚLIJ NA MAIL-a</button>
        
        <form action="../scripts/favorite_handler.php" method="POST" style="display: inline;">
            <input type="hidden" name="insurance_id" value="<?php echo $id; ?>">
            <input type="hidden" name="insurance_type" value="<?php echo $type; ?>">
            
            <?php if ($isFavorite): ?>
                <input type="hidden" name="action" value="remove">
                <button type="submit" class="btn fav remove">USUŃ Z ULUBIONYCH</button>
            <?php else: ?>
                <input type="hidden" name="action" value="add">
                <button type="submit" class="btn fav">DODAJ DO ULUBIONYCH ❤️</button>
            <?php endif; ?>
        </form>
      </div>
    </section>
  </main>
  
  <footer class="footer">
    <p>&copy; 2024 SkanPolis. Wszelkie prawa zastrzeżone.</p>
  </footer>
</body>
</html>