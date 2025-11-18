<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connect.php';
global $pdo;
$search_results = [];
$search_params = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Pobierz dane z formularza
    $engine_capacity = $_POST['engine_capacity'] ?? '';
    $use_type = $_POST['use_type'] ?? '';
    $typ_ubezpieczenia = $_POST['typ_ubezpieczenia'] ?? '';
    $power_hp = $_POST['power_hp'] ?? '';

    // Zapisz parametry wyszukiwania do wyświetlenia
    $search_params = [
        'brand' => $_POST['brand'] ?? 'Nie podano',
        'insurance_date' => $_POST['insurance_date'] ?? 'Nie podano',
        'engine_capacity' => $engine_capacity,
        'power_hp' => $power_hp,
        'typ_ubezpieczenia' => $typ_ubezpieczenia,
        'use_type' => $use_type
    ];

    try {
        $query = "SELECT * FROM MotorcycleInsurance WHERE 1=1";
        $params = [];

        // Filtruj po pojemności silnika (obowiązkowe)
        if (!empty($engine_capacity)) {
            $query .= " AND Engine_capacity <= :engine_capacity";
            $params[':engine_capacity'] = $engine_capacity;
        }

        // Filtruj po typie użytkowania
        if (!empty($use_type)) {
            $query .= " AND Use_type = :use_type";
            $params[':use_type'] = $use_type;
        }

        // Filtruj po typie ubezpieczenia
        if (!empty($typ_ubezpieczenia)) {
            $query .= " AND Insurance_type = :typ_ubezpieczenia";
            $params[':typ_ubezpieczenia'] = $typ_ubezpieczenia;
        }

        // Filtruj po mocy (opcjonalne)
        if (!empty($power_hp)) {
            $query .= " AND Power_HP <= :power_hp";
            $params[':power_hp'] = $power_hp;
        }

        // Sortuj po cenie
        $query .= " ORDER BY Price ASC";

        // Wykonaj zapytanie
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        die();
    }
} else {
    // Dla bezpośredniego dostępu bez wysłania formularza
    header('Location: ../html/main.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkanPolis - Wyniki Wyszukiwania Motocykli</title>
    <link rel="stylesheet" href="../css/manage_insurance.css">
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
    <!-- Informacje o pojeździe -->
    <section class="vehicle-info">
        <h2>🏍️ Wyszukiwanie dla motocykla</h2>
        <div class="vehicle-details">
            <p><strong>Marka:</strong> <?php echo htmlspecialchars($search_params['brand'] ?? 'Nie podano'); ?></p>
            <p><strong>Pojemność silnika:</strong> <?php echo htmlspecialchars($search_params['engine_capacity'] ?? 'Nie podano'); ?> cm³</p>
            <?php if (!empty($search_params['power_hp'])): ?>
            <p><strong>Moc:</strong> <?php echo htmlspecialchars($search_params['power_hp']); ?> KM</p>
            <?php endif; ?>
            <p><strong>Typ ubezpieczenia:</strong> <?php echo htmlspecialchars($search_params['typ_ubezpieczenia'] ?? 'Dowolny'); ?></p>
            <p><strong>Typ użytkowania:</strong> <?php echo htmlspecialchars($search_params['use_type'] ?? 'Dowolny'); ?></p>
        </div>
    </section>

    <!-- Wyniki wyszukiwania -->
    <section class="offers">
        <h2>Oferty ubezpieczenia motocyklowego</h2>

        <?php if (!empty($search_results)): ?>
            <p class="results-count">Znaleziono <?php echo count($search_results); ?> ofert(y)</p>

            <?php foreach ($search_results as $insurance): ?>
                <div class="offer">
                    <div class="offer-details">
                        <h3 class="company-name"><?php echo htmlspecialchars($insurance['Insurance_name']); ?></h3>
                        <div class="offer-info">
                            <p><strong>Typ:</strong> <?php echo htmlspecialchars($insurance['Insurance_type']); ?></p>
                            <p><strong>Pojemność:</strong> <?php echo htmlspecialchars($insurance['Engine_capacity']); ?> cm³</p>
                            <?php if (!empty($insurance['Power_HP'])): ?>
                            <p><strong>Moc:</strong> <?php echo htmlspecialchars($insurance['Power_HP']); ?> KM</p>
                            <?php endif; ?>
                            <p class="price"><strong><?php echo number_format($insurance['Price'], 2, ',', ' '); ?> zł</strong></p>
                            <p class="installment">lub <?php echo number_format($insurance['Price'] / 12, 2, ',', ' '); ?> zł x 12 rat</p>
                        </div>
                        <a href="../html/detail.html" class="btn choose-btn">Wybierz ofertę</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <p>😔 Brak ofert pasujących do Twoich kryteriów wyszukiwania.</p>
                <p>Spróbuj zmienić parametry wyszukiwania:</p>
                <ul>
                    <li>Zwiększ zakres pojemności silnika</li>
                    <li>Zmień typ ubezpieczenia (spróbuj tylko OC)</li>
                    <li>Zmień typ użytkowania</li>
                </ul>
                <a href="../html/main.html" class="btn back-btn">Powrót do wyszukiwania</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="footer">
    <p>© 2024 Skanpolis. Wszelkie prawa zastrzeżone.</p>
</footer>
</body>
</html>
