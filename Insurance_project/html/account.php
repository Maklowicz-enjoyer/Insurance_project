<?php
// html/account.php
require_once __DIR__ . '/../scripts/session_check.php';
require_once __DIR__ . '/../scripts/db_connect.php';
require_once __DIR__ . '/../scripts/InsuranceCalculator.php';

$email = $_SESSION['user_email'];
$favorites = [];
$error_msg = "";

// Pobieramy filtr z URL, domyślnie 'ALL'
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'ALL';

// Zabezpieczenie: upewniamy się, że filtr ma jedną z dozwolonych wartości
if (!in_array($filter, ['ALL', 'CAR', 'MOTORCYCLE'])) {
    $filter = 'ALL';
}

try {
    $stmt = $pdo->prepare("SELECT Users_ID FROM User WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $userId = $stmt->fetchColumn();

    if ($userId) {
        // Budujemy zapytanie dynamicznie w zależności od filtra
        $query = "
            SELECT
                f.Favorite_ID,
                f.Insurance_ID,
                f.Insurance_Type as Fav_Type,
                f.Added_Date,
                f.Brand,
                f.Body_type as Fav_Body_type,
                f.Production_year,
                f.Engine_capacity,
                f.DOB,
                f.License_date,
                f.Damage_free_years,
                f.Assistance_level,
                f.Accident_cover,
                f.Discount_protection,
                COALESCE(c.Insurance_name, m.Insurance_name) as Insurance_name,
                COALESCE(c.Insurance_type, m.Insurance_type) as Insurance_subtype,
                COALESCE(c.Body_type, m.Motorcycle_type, 'Nieznany') as Body_type
            FROM FavoriteInsurance f
            LEFT JOIN CarInsurance c
                ON f.Insurance_ID = c.CarInsurance_ID AND f.Insurance_Type = 'CAR'
            LEFT JOIN MotorcycleInsurance m
                ON f.Insurance_ID = m.MotorcycleInsurance_ID AND f.Insurance_Type = 'MOTORCYCLE'
            WHERE f.Users_ID = :uid
        ";

        // Dodajemy warunek filtra, jeśli nie wybrano 'ALL'
        if ($filter !== 'ALL') {
            $query .= " AND f.Insurance_Type = :filterType";
        }

        $query .= " ORDER BY f.Added_Date DESC";
        
        $favStmt = $pdo->prepare($query);
        
        // Bindujemy parametry
        $favStmt->bindValue(':uid', $userId);
        if ($filter !== 'ALL') {
            $favStmt->bindValue(':filterType', $filter);
        }

        $favStmt->execute();
        $favorites = $favStmt->fetchAll(PDO::FETCH_ASSOC);

        // Przelicz ceny dynamicznie dla każdej ulubionej oferty
        $calculator = new InsuranceCalculator();
        foreach ($favorites as &$fav) {
            // Sprawdź czy mamy zapisane parametry wyszukiwania
            if ($fav['Brand'] && $fav['Production_year']) {
                $calcData = [
                    'dob' => $fav['DOB'] ?? '',
                    'license_date' => $fav['License_date'] ?? '',
                    'year' => $fav['Production_year'],
                    'capacity' => $fav['Engine_capacity'] ?? 1600,
                    'damage' => $fav['Damage_free_years'] ?? 0,
                    'typ_ubezpieczenia' => $fav['Insurance_subtype'] ?? 'OC',
                    'brand' => $fav['Brand'],
                    'typ_nadwozia' => $fav['Fav_Body_type'] ?? $fav['Body_type'],
                    'assistance' => $fav['Assistance_level'] ?? 'NONE',
                    'accident_cover' => $fav['Accident_cover'] ?? 0,
                    'discount_protection' => $fav['Discount_protection'] ?? 0
                ];

                $priceResult = $calculator->calculatePremiumWithBreakdown($calcData, $fav['Fav_Type']);

                // Różnicowanie cen per firma (ta sama logika co w manage_insurance.php)
                $companyFactor = (crc32($fav['Insurance_name']) % 20) / 100;
                $finalPrice = $priceResult['total_price'] * (1.0 + $companyFactor);

                $fav['Price'] = $finalPrice;
                $fav['price_breakdown'] = $priceResult['breakdown'];
            } else {
                // Fallback: jeśli brak parametrów, ustaw cenę na 0 lub komunikat
                $fav['Price'] = 0;
                $fav['price_breakdown'] = null;
            }
        }
        unset($fav); // Zawsze unset referencji po foreach
    }

} catch (PDOException $e) {
    $error_msg = "Błąd SQL: " . $e->getMessage();
} catch (Exception $e) {
    $error_msg = "Błąd obliczania ceny: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Użytkownika - SkanPolis</title>
  <link rel="stylesheet" href="../css/account.css">
  <style>
      .remove-fav-btn {
          background-color: #e53935;
          color: white;
          border: none;
          padding: 5px 10px;
          border-radius: 4px;
          cursor: pointer;
          font-size: 12px;
          margin-top: 5px;
      }
      .fav-date { font-size: 11px; color: #777; margin-bottom: 5px; }
      .calc-card { min-width: 280px; background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 8px; display: flex; flex-direction: column; justify-content: space-between; }
      .error-box { background: #fdd; color: #900; padding: 10px; margin: 10px 0; border: 1px solid #900; }

      /* Style dla filtrów */
      .filters {
          display: flex;
          gap: 10px;
          margin-bottom: 20px;
          justify-content: flex-start;
      }
      .filter-btn {
          padding: 8px 16px;
          border: 1px solid #00897b;
          border-radius: 20px;
          text-decoration: none;
          color: #00897b;
          font-weight: bold;
          transition: all 0.3s ease;
          background: white;
      }
      .filter-btn:hover {
          background-color: #e0f2f1;
      }
      .filter-btn.active {
          background-color: #00897b;
          color: white;
      }
  </style>
</head>
<body>
  
  <header class="header">
    <div class="header-container">
      <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
      <a href="main.php" class="btn back">POWRÓT</a>
    </div>
  </header>

  <main>
    <section class="profile">
        <h2 class="profile-title">Twój profil: <?php echo htmlspecialchars($email); ?></h2>
    </section>

    <?php if ($error_msg): ?>
        <div class="error-box"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <section class="saved-calc">
        <h3 class="section-title">Twoje Ulubione Oferty ❤️</h3>
        
        <div class="filters">
            <a href="?filter=ALL" class="filter-btn <?php echo $filter === 'ALL' ? 'active' : ''; ?>">Wszystkie</a>
            <a href="?filter=CAR" class="filter-btn <?php echo $filter === 'CAR' ? 'active' : ''; ?>">🚗 Samochody</a>
            <a href="?filter=MOTORCYCLE" class="filter-btn <?php echo $filter === 'MOTORCYCLE' ? 'active' : ''; ?>">🏍️ Motocykle</a>
        </div>

        <?php if (empty($favorites)): ?>
            <div class="calc-container">
                <p class="no-policies">
                    <?php 
                        if ($filter === 'CAR') echo "Brak zapisanych ofert samochodowych.";
                        elseif ($filter === 'MOTORCYCLE') echo "Brak zapisanych ofert motocyklowych.";
                        else echo "Nie masz jeszcze żadnych ofert w ulubionych.";
                    ?>
                </p>
            </div>
        <?php else: ?>
            <div class="calc-container" style="flex-wrap: wrap; gap: 20px;">
                <?php foreach ($favorites as $fav): ?>
                    <?php if ($fav['Insurance_name']): ?>
                        <div class="calc-card">
                            <div>
                                <h4 class="car-title">
                                    <?php echo $fav['Fav_Type'] === 'MOTORCYCLE' ? '🏍️' : '🚗'; ?> 
                                    <?php echo htmlspecialchars($fav['Insurance_name']); ?>
                                </h4>
                                <p class="car-details">
                                    <strong><?php echo htmlspecialchars($fav['Insurance_subtype']); ?></strong><br>
                                    Typ: <?php echo htmlspecialchars($fav['Body_type']); ?>
                                </p>
                                <p class="price">Cena: <strong><?php echo number_format($fav['Price'], 2); ?> zł</strong></p>
                                <p class="fav-date">Dodano: <?php echo date('d.m.Y H:i', strtotime($fav['Added_Date'])); ?></p>
                            </div>
                            
                            <div>
                                <a href="detail.php?id=<?php echo $fav['Insurance_ID']; ?>&type=<?php echo $fav['Fav_Type']; ?>" class="btn green" style="display:block; margin-bottom: 5px; text-align:center;">Szczegóły</a>

                                <form action="../scripts/favorite_handler.php" method="POST">
                                    <input type="hidden" name="insurance_id" value="<?php echo $fav['Insurance_ID']; ?>">
                                    <input type="hidden" name="insurance_type" value="<?php echo $fav['Fav_Type']; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="redirect" value="account">
                                    <input type="hidden" name="current_filter" value="<?php echo $filter; ?>">
                                    <button type="submit" class="remove-fav-btn" style="width:100%">Usuń</button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="calc-card" style="opacity: 0.7;">
                            <h4 class="car-title">Oferta niedostępna</h4>
                            <p class="fav-date">Oferta wygasła.</p>
                            <form action="../scripts/favorite_handler.php" method="POST">
                                <input type="hidden" name="insurance_id" value="<?php echo $fav['Insurance_ID']; ?>">
                                <input type="hidden" name="insurance_type" value="<?php echo $fav['Fav_Type']; ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="redirect" value="account">
                                <button type="submit" class="remove-fav-btn" style="width:100%">Usuń z listy</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="notifications">
      <h2>Powiadomienia</h2>
       <div class="notification-options">
        <label><input type="checkbox" id="reminder-end"> Przypomnienie o końcu ubezpieczenia</label>
        <label><input type="checkbox" id="new-offers"> Powiadomienia o nowych ofertach</label>
      </div>
    </section>
  </main>
  <footer class="footer">
    <p>© 2024 Skanpolis. Wszelkie prawa zastrzeżone.</p>
  </footer>
</body>
</html>