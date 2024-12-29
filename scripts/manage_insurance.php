<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require 'db_connect.php';
global $pdo;
$search_results = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $typ_nadwozia = $_POST['typ_nadwozia'] ?? ''; // Typ nadwozia from the form
    $use_type = $_POST['use_type'] ?? '';         // Use type from the form
    $typ_ubezpieczenia = $_POST['typ_ubezpieczenia'] ?? ''; // Typ ubezpieczenia from the form

    try {
        $query = "SELECT * FROM Insurance WHERE 1=1";
        $params = [];

        // Add filters if values are provided
        if (!empty($typ_nadwozia)) {
            $query .= " AND typ_nadwozia = :typ_nadwozia";
            $params[':typ_nadwozia'] = $typ_nadwozia;
        }
        if (!empty($use_type)) {
            $query .= " AND Use_type = :use_type";
            $params[':use_type'] = $use_type;
        }
        if (!empty($typ_ubezpieczenia)) {
            $query .= " AND typ_ubezpieczenia = :typ_ubezpieczenia";
            $params[':typ_ubezpieczenia'] = $typ_ubezpieczenia;
        }

        // Execute query
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        die();
    }
} else {
    // For direct access without form submission
    echo "<p>Invalid access. Please submit the form from the main page.</p>";
    $search_results = [];
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkanPolis - Wyniki Wyszukiwania</title>
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
        <h2>Wybrany pojazd</h2>
        <div class="vehicle-details">
            <p><strong>Marka:</strong> <span id="vehicle-brand"></span></p>
            <p><strong>Model:</strong> <span id="vehicle-model"></span></p>
            <p><strong>Data startu ubezpieczenia:</strong> <span id="insurance-start"></span></p>
        </div>
    </section>

    <!-- Wyniki wyszukiwania -->
    <section class="offers">
        <h2>Oferty</h2>

        <?php if (!empty($search_results)): ?>
            <?php foreach ($search_results as $insurance): ?>
                <div class="offer">
                    <div class="offer-details">
                        <h3 class="company-name"><?php echo htmlspecialchars($insurance['Insurance_name']); ?></h3>
                        <div class="offer-info">
                            <p><?php echo htmlspecialchars($insurance['Insurance_type']); ?></p>
                            <p><strong><?php echo rand(300, 1500); ?> zł</strong> lub <?php echo rand(50, 200); ?> zł x <?php echo rand(6, 12); ?> rat</p>
                        </div>
                        <a href="../html/detail.html" class="btn choose-btn">Wybierz ofertę</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Brak wyników pasujących do kryteriów wyszukiwania.</p>
        <?php endif; ?>
    </section>
</main>
<footer class="footer">
    <p>© 2024 Skanpolis. Wszelkie prawa zastrzeżone.</p>
</footer>
</body>
</html>
