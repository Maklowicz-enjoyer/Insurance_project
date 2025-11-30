<?php
// scripts/manage_motorcycle_insurance.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db_connect.php';
require_once 'InsuranceCalculator.php';

global $pdo;
$search_results = [];

// Odbieramy dane do wyświetlenia w nagłówku
$display_brand = $_POST['motorcycle-brand'] ?? 'Nie podano';
$display_year  = $_POST['year'] ?? '-';
$display_date  = $_POST['insurance_date'] ?? date('Y-m-d');

// Dodatkowe parametry do wyświetlenia
$display_type  = $_POST['typ_ubezpieczenia'] ?? '-';
$display_capacity = $_POST['capacity'] ?? '-';
$display_usage = $_POST['use_type'] ?? '-';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Dane do filtrów
    $use_type = $_POST['use_type'] ?? '';
    $typ_ubezpieczenia = $_POST['typ_ubezpieczenia'] ?? '';

    // Dane do kalkulatora ceny
    $calcData = [
        'dob' => $_POST['dob'] ?? '',
        'license_date' => $_POST['license_date'] ?? '',
        'year' => $_POST['year'] ?? date('Y'),
        'capacity' => $_POST['capacity'] ?? 125,
        'damage' => $_POST['damage'] ?? 0,
        'typ_ubezpieczenia' => $typ_ubezpieczenia
    ];

    try {
        $query = "SELECT * FROM Insurance WHERE Vehicle_Category = 'MOTORCYCLE'";
        $params = [];

        // Filtrowanie ofert w bazie
        if (!empty($use_type)) {
            $query .= " AND Use_type = :use_type";
            $params[':use_type'] = $use_type;
        }
        if (!empty($typ_ubezpieczenia)) {
            $query .= " AND Insurance_type = :typ_ubezpieczenia";
            $params[':typ_ubezpieczenia'] = $typ_ubezpieczenia;
        }

        $query .= " LIMIT 15";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $db_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Przeliczanie cen przez InsuranceCalculator
        $calculator = new InsuranceCalculator();

        foreach ($db_results as $row) {
            $technicalPrice = $calculator->calculatePremium($calcData, 'MOTORCYCLE');
            
            // Różnicowanie cen per firma
            $companyFactor = (crc32($row['Insurance_name']) % 20) / 100; 
            $finalPrice = $technicalPrice * (1.0 + $companyFactor);

            $row['Price'] = $finalPrice;

            $search_results[] = $row;
        }

        // Sortowanie po cenie
        usort($search_results, function($a, $b) {
            return $a['Price'] <=> $b['Price'];
        });

    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkanPolis - Wyniki Motocykle</title>
    <link rel="stylesheet" href="../css/manage_insurance.css">
</head>
<body>
<header class="header">
    <div class="header-container">
        <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
        <div class="header-buttons">
            <a href="../html/main.php" class="btn back">ZMIEŃ DANE</a>
            <a href="../html/account.php" class="btn acc">MOJE KONTO</a>
            <a href="../index.html" class="btn logout">WYLOGUJ SIĘ</a>
        </div>
    </div>
</header>

<main>
    <section class="vehicle-info">
        <h2>Wybrany motocykl</h2>
        <div class="vehicle-details">
            <p><strong>Marka:</strong> <span><?php echo htmlspecialchars($display_brand); ?></span></p>
            <p><strong>Rok produkcji:</strong> <span><?php echo htmlspecialchars($display_year); ?></span></p>
            <p><strong>Data startu:</strong> <span><?php echo htmlspecialchars($display_date); ?></span></p>
            <hr style="margin: 10px 0; border: 0; border-top: 1px solid #ddd;">
            <p><strong>Typ ubezpieczenia:</strong> <span><?php echo htmlspecialchars($display_type); ?></span></p>
            <p><strong>Pojemność:</strong> <span><?php echo htmlspecialchars($display_capacity); ?> cm³</span></p>
            <p><strong>Użytkowanie:</strong> <span><?php echo htmlspecialchars($display_usage); ?></span></p>
        </div>
    </section>

    <section class="offers">
        <h2>Dostępne oferty</h2>
        <?php if (!empty($search_results)): ?>
            <?php foreach ($search_results as $insurance): ?>
                <div class="offer">
                    <div class="offer-details">
                        <h3 class="company-name"><?php echo htmlspecialchars($insurance['Insurance_name']); ?></h3>
                        <div class="offer-info">
                            <p><strong><?php echo htmlspecialchars($insurance['Insurance_type']); ?></strong></p>
                            <p>
                                <strong><?php echo number_format((float)$insurance['Price'], 2, ',', ' '); ?> zł</strong>
                                lub <?php echo number_format((float)$insurance['Price'] / 12, 2, ',', ' '); ?> zł / mies.
                            </p>
                        </div>
                        <a href="../html/detail.php?id=<?php echo $insurance['Insurance_ID']; ?>&type=MOTORCYCLE" class="btn choose-btn">
                            Wybierz ofertę
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Brak ofert pasujących do kryteriów. Spróbuj zmienić parametry.</p>
        <?php endif; ?>
    </section>
</main>
<footer class="footer">
    <p>© 2025 Skanpolis. Wszelkie prawa zastrzeżone.</p>
</footer>
</body>
</html>