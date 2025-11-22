<?php
// ============================================
// ULEPSZONE WYSZUKIWANIE OFERT SAMOCHODOWYCH
// ============================================
// Logika: ranking ofert na podstawie dopasowania
// Im więcej kryteriów pasuje, tym wyższy score

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connect.php';
global $pdo;
$search_results = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Pobierz dane z formularza
    $typ_nadwozia = $_POST['typ_nadwozia'] ?? '';
    $use_type = $_POST['use_type'] ?? '';
    $typ_ubezpieczenia = $_POST['typ_ubezpieczenia'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $year = $_POST['year'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $insurance_date = $_POST['insurance_date'] ?? '';
    
    try {
        // ============================================
        // NOWA LOGIKA: RANKING Z SCORE
        // ============================================
        // Każde dopasowanie daje punkty:
        // - Typ nadwozia: 3 pkt
        // - Typ użytkowania: 2 pkt
        // - Typ ubezpieczenia: 3 pkt
        
        $query = "
            SELECT 
                *,
                (
                    CASE WHEN Typ_nadwozia = :typ_nadwozia THEN 3 ELSE 0 END +
                    CASE WHEN Use_type = :use_type THEN 2 ELSE 0 END +
                    CASE WHEN Insurance_type = :typ_ubezpieczenia THEN 3 ELSE 0 END
                ) as match_score
            FROM Insurance
            WHERE Vehicle_Category = 'CAR'
        ";
        
        $params = [
            ':typ_nadwozia' => $typ_nadwozia ?: '',
            ':use_type' => $use_type ?: '',
            ':typ_ubezpieczenia' => $typ_ubezpieczenia ?: ''
        ];
        
        // Jeśli użytkownik wybrał konkretne wartości, pokaż podobne oferty
        // (nie wymagamy dokładnego dopasowania WSZYSTKICH pól)
        if (!empty($typ_nadwozia) || !empty($use_type) || !empty($typ_ubezpieczenia)) {
            $query .= " HAVING match_score > 0";
        }
        
        // Sortuj: najpierw najlepsze dopasowanie, potem po cenie
        $query .= " ORDER BY match_score DESC, Price ASC";
        
        // Ogranicz wyniki do 15 najlepszych ofert
        $query .= " LIMIT 15";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        die("Błąd wyszukiwania: " . $e->getMessage());
    }
} else {
    // Redirect jeśli ktoś próbuje wejść bez POST
    header('Location: ../html/main.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkanPolis - Wyniki Wyszukiwania</title>
    <link rel="stylesheet" href="../css/manage_insurance.css">
    <style>
        .match-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .match-perfect { background: #4CAF50; color: white; }
        .match-good { background: #8BC34A; color: white; }
        .match-partial { background: #FFC107; color: black; }
        
        .search-summary {
            background: #e6ffe6;
            border: 2px solid #99cc99;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .search-summary strong {
            color: #2e7d32;
        }
        
        .offer-match-details {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
            font-style: italic;
        }
        
        .no-results-help {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .no-results-help ul {
            margin-top: 10px;
            padding-left: 20px;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-container">
        <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
        <div class="header-buttons">
            <a href="../html/main.html" class="btn back">ZMIEŃ DANE</a>
            <a href="account.php" class="btn acc">MOJE KONTO</a>
            <a href="../index.html" class="btn logout">WYLOGUJ SIĘ</a>
        </div>
    </div>
</header>

<main>
    <!-- Podsumowanie wyszukiwania -->
    <section class="search-summary">
        <h2>🔍 Parametry wyszukiwania</h2>
        <p>
            <?php if (!empty($brand)): ?>
            <strong>Marka:</strong> <?php echo htmlspecialchars($brand); ?> &nbsp;
            <?php endif; ?>
            
            <?php if (!empty($typ_nadwozia)): ?>
            <strong>Typ nadwozia:</strong> <?php echo htmlspecialchars($typ_nadwozia); ?> &nbsp;
            <?php endif; ?>
            
            <?php if (!empty($typ_ubezpieczenia)): ?>
            <strong>Typ ubezpieczenia:</strong> <?php echo htmlspecialchars($typ_ubezpieczenia); ?> &nbsp;
            <?php endif; ?>
            
            <?php if (!empty($use_type)): ?>
            <strong>Użytkowanie:</strong> <?php echo htmlspecialchars($use_type); ?>
            <?php endif; ?>
        </p>
    </section>

    <!-- Wyniki wyszukiwania -->
    <section class="offers">
        <h2>📋 Znalezione oferty (<?php echo count($search_results); ?>)</h2>

        <?php if (!empty($search_results)): ?>
            
            <?php foreach ($search_results as $index => $insurance): ?>
                <?php
                    $score = $insurance['match_score'] ?? 0;
                    $is_recommended = ($index === 0); // Pierwsza oferta = najlepsza
                    
                    // Określ typ dopasowania
                    if ($score >= 7) {
                        $match_class = 'match-perfect';
                        $match_label = 'Idealne dopasowanie';
                    } elseif ($score >= 5) {
                        $match_class = 'match-good';
                        $match_label = 'Dobre dopasowanie';
                    } else {
                        $match_class = 'match-partial';
                        $match_label = 'Częściowe dopasowanie';
                    }
                ?>
                
                <div class="offer <?php echo $is_recommended ? 'recommended' : ''; ?>">
                    <?php if ($is_recommended): ?>
                    <div class="offer-header">
                        <span class="badge">⭐ NAJLEPSZA OFERTA</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="offer-details">
                        <h3 class="company-name">
                            <?php echo htmlspecialchars($insurance['Insurance_name']); ?>
                            <span class="match-badge <?php echo $match_class; ?>">
                                <?php echo $match_label; ?>
                            </span>
                        </h3>
                        
                        <div class="offer-info">
                            <p>
                                <strong>Typ:</strong> <?php echo htmlspecialchars($insurance['Insurance_type']); ?> |
                                <strong>Nadwozie:</strong> <?php echo htmlspecialchars($insurance['Typ_nadwozia']); ?> |
                                <strong>Użytkowanie:</strong> <?php echo htmlspecialchars($insurance['Use_type']); ?>
                            </p>
                            
                            <p class="offer-match-details">
                                <?php
                                    $matches = [];
                                    if ($insurance['Typ_nadwozia'] === $typ_nadwozia) {
                                        $matches[] = '✓ Typ nadwozia';
                                    }
                                    if ($insurance['Insurance_type'] === $typ_ubezpieczenia) {
                                        $matches[] = '✓ Typ ubezpieczenia';
                                    }
                                    if ($insurance['Use_type'] === $use_type) {
                                        $matches[] = '✓ Typ użytkowania';
                                    }
                                    
                                    if (count($matches) > 0) {
                                        echo 'Dopasowanie: ' . implode(', ', $matches);
                                    } else {
                                        echo 'Podobna oferta - może Cię zainteresować';
                                    }
                                ?>
                            </p>
                            
                            <p style="font-size: 20px; font-weight: bold; color: #2e7d32; margin-top: 10px;">
                                <?php echo number_format($insurance['Price'], 2, ',', ' '); ?> zł
                            </p>
                            
                            <p style="font-size: 14px; color: #666;">
                                lub <?php echo number_format($insurance['Price'] / 12, 2, ',', ' '); ?> zł × 12 rat
                            </p>
                        </div>
                        
                        <a href="detail.php?id=<?php echo $insurance['Insurance_ID']; ?>" 
                           class="btn choose-btn">
                            Wybierz ofertę
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <div class="no-results-help">
                <h3>😔 Nie znaleziono ofert spełniających Twoje kryteria</h3>
                <p>Spróbuj:</p>
                <ul>
                    <li>Zmienić typ nadwozia (niektóre oferty mogą być dostępne tylko dla konkretnych typów)</li>
                    <li>Wybrać inny typ ubezpieczenia (OC zamiast OC/AC)</li>
                    <li>Zmienić typ użytkowania</li>
                    <li>Zostawić niektóre pola puste, aby zobaczyć więcej opcji</li>
                </ul>
                <a href="../html/main.html" class="btn back-btn" style="margin-top: 15px; display: inline-block;">
                    ← Powrót do wyszukiwania
                </a>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="footer">
    <p>© <?php echo date("Y"); ?> Skanpolis. Wszelkie prawa zastrzeżone.</p>
</footer>
</body>
</html>