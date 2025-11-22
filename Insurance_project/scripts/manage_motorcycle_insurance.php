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
    $motorcycle_type = $_POST['motorcycle_type'] ?? '';
    
    // Zapisz parametry do wyświetlenia
    $search_params = [
        'brand' => $_POST['brand'] ?? 'Nie podano',
        'insurance_date' => $_POST['insurance_date'] ?? 'Nie podano',
        'engine_capacity' => $engine_capacity,
        'power_hp' => $power_hp,
        'typ_ubezpieczenia' => $typ_ubezpieczenia,
        'use_type' => $use_type,
        'motorcycle_type' => $motorcycle_type
    ];
    
    try {
        // ============================================
        // ELASTYCZNE ZAKRESY POJEMNOŚCI
        // ============================================
        // Zamiast dokładnej pojemności, szukaj w zakresie ±200cc
        
        $capacity_min = $engine_capacity ? max(50, $engine_capacity - 200) : 50;
        $capacity_max = $engine_capacity ? $engine_capacity + 200 : 2500;
        
        $query = "
            SELECT 
                *,
                ABS(Engine_capacity - :engine_capacity_target) as capacity_diff,
                (
                    CASE WHEN Use_type = :use_type THEN 2 ELSE 0 END +
                    CASE WHEN Insurance_type = :typ_ubezpieczenia THEN 3 ELSE 0 END +
                    CASE WHEN Motorcycle_type = :motorcycle_type THEN 1 ELSE 0 END +
                    CASE WHEN ABS(Engine_capacity - :engine_capacity_target) <= 100 THEN 3 
                         WHEN ABS(Engine_capacity - :engine_capacity_target) <= 200 THEN 2 
                         ELSE 1 END
                ) as match_score
            FROM MotorcycleInsurance
            WHERE Engine_capacity BETWEEN :capacity_min AND :capacity_max
        ";
        
        $params = [
            ':engine_capacity_target' => $engine_capacity ?: 500,
            ':capacity_min' => $capacity_min,
            ':capacity_max' => $capacity_max,
            ':use_type' => $use_type ?: '',
            ':typ_ubezpieczenia' => $typ_ubezpieczenia ?: '',
            ':motorcycle_type' => $motorcycle_type ?: ''
        ];
        
        // Filtruj po mocy (opcjonalne)
        if (!empty($power_hp)) {
            $query .= " AND Power_HP <= :power_hp_max";
            $params[':power_hp_max'] = $power_hp + 30; // ±30 KM
        }
        
        // Sortuj: najlepsze dopasowanie, potem po cenie
        $query .= " ORDER BY match_score DESC, capacity_diff ASC, Price ASC";
        
        // Ogranicz do 15 ofert
        $query .= " LIMIT 15";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Motorcycle search error: " . $e->getMessage());
        die("Błąd wyszukiwania: " . $e->getMessage());
    }
} else {
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
            <a href="account.php" class="btn acc">MOJE KONTO</a>
            <a href="../index.html" class="btn logout">WYLOGUJ SIĘ</a>
        </div>
    </div>
</header>

<main>
    <!-- Podsumowanie wyszukiwania -->
    <section class="search-summary">
        <h2>🏍️ Parametry wyszukiwania motocykla</h2>
        <p>
            <?php if (!empty($search_params['brand'])): ?>
            <strong>Marka:</strong> <?php echo htmlspecialchars($search_params['brand']); ?> &nbsp;
            <?php endif; ?>
            
            <?php if (!empty($search_params['engine_capacity'])): ?>
            <strong>Pojemność:</strong> <?php echo htmlspecialchars($search_params['engine_capacity']); ?> cm³ &nbsp;
            <?php endif; ?>
            
            <?php if (!empty($search_params['power_hp'])): ?>
            <strong>Moc:</strong> <?php echo htmlspecialchars($search_params['power_hp']); ?> KM &nbsp;
            <?php endif; ?>
            
            <?php if (!empty($search_params['motorcycle_type'])): ?>
            <strong>Typ:</strong> <?php echo htmlspecialchars(ucfirst($search_params['motorcycle_type'])); ?> &nbsp;
            <?php endif; ?>
            
            <?php if (!empty($search_params['typ_ubezpieczenia'])): ?>
            <strong>Ubezpieczenie:</strong> <?php echo htmlspecialchars($search_params['typ_ubezpieczenia']); ?> &nbsp;
            <?php endif; ?>
            
            <?php if (!empty($search_params['use_type'])): ?>
            <strong>Użytkowanie:</strong> <?php echo htmlspecialchars($search_params['use_type']); ?>
            <?php endif; ?>
        </p>
        
        <?php if (!empty($search_params['engine_capacity'])): ?>
        <div class="capacity-range-info">
            ℹ️ Wyszukujemy oferty dla motocykli o pojemności 
            <strong><?php echo max(50, $search_params['engine_capacity'] - 200); ?> - 
            <?php echo $search_params['engine_capacity'] + 200; ?> cm³</strong>
            (elastyczny zakres ±200cc)
        </div>
        <?php endif; ?>
    </section>

    <!-- Wyniki wyszukiwania -->
    <section class="offers">
        <h2>📋 Znalezione oferty (<?php echo count($search_results); ?>)</h2>

        <?php if (!empty($search_results)): ?>
            
            <?php foreach ($search_results as $index => $insurance): ?>
                <?php
                    $score = $insurance['match_score'] ?? 0;
                    $is_recommended = ($index === 0);
                    
                    // Określ typ dopasowania
                    if ($score >= 8) {
                        $match_class = 'match-perfect';
                        $match_label = 'Idealne dopasowanie';
                    } elseif ($score >= 5) {
                        $match_class = 'match-good';
                        $match_label = 'Dobre dopasowanie';
                    } else {
                        $match_class = 'match-partial';
                        $match_label = 'Podobna oferta';
                    }
                    
                    // Różnica pojemności
                    $capacity_diff = abs($insurance['Engine_capacity'] - ($search_params['engine_capacity'] ?: $insurance['Engine_capacity']));
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
                                <strong>Pojemność:</strong> <?php echo htmlspecialchars($insurance['Engine_capacity']); ?> cm³
                                
                                <?php if ($capacity_diff > 0): ?>
                                <span class="capacity-badge">
                                    <?php echo $capacity_diff <= 100 ? '✓ Bardzo podobna' : '≈ Podobna'; ?>
                                </span>
                                <?php endif; ?>
                            </p>
                            
                            <?php if (!empty($insurance['Motorcycle_type'])): ?>
                            <p>
                                <strong>Kategoria:</strong> <?php echo htmlspecialchars(ucfirst($insurance['Motorcycle_type'])); ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($insurance['Power_HP'])): ?>
                            <p>
                                <strong>Moc:</strong> <?php echo htmlspecialchars($insurance['Power_HP']); ?> KM
                            </p>
                            <?php endif; ?>
                            
                            <p class="offer-match-details">
                                <?php
                                    $matches = [];
                                    
                                    if ($insurance['Insurance_type'] === $search_params['typ_ubezpieczenia']) {
                                        $matches[] = '✓ Typ ubezpieczenia';
                                    }
                                    if ($insurance['Use_type'] === $search_params['use_type']) {
                                        $matches[] = '✓ Typ użytkowania';
                                    }
                                    if (!empty($search_params['motorcycle_type']) && 
                                        $insurance['Motorcycle_type'] === $search_params['motorcycle_type']) {
                                        $matches[] = '✓ Typ motocykla';
                                    }
                                    if ($capacity_diff <= 100) {
                                        $matches[] = '✓ Pojemność bardzo zbliżona';
                                    } elseif ($capacity_diff <= 200) {
                                        $matches[] = '≈ Pojemność podobna';
                                    }
                                    
                                    if (count($matches) > 0) {
                                        echo 'Dopasowanie: ' . implode(', ', $matches);
                                    } else {
                                        echo 'Alternatywna oferta - może Cię zainteresować';
                                    }
                                ?>
                            </p>
                            
                            <p style="font-size: 20px; font-weight: bold; color: #0050b3; margin-top: 10px;">
                                <?php echo number_format($insurance['Price'], 2, ',', ' '); ?> zł
                            </p>
                            
                            <p style="font-size: 14px; color: #666;">
                                lub <?php echo number_format($insurance['Price'] / 12, 2, ',', ' '); ?> zł × 12 rat
                            </p>
                        </div>
                        
                        <a href="detail.php?id=<?php echo $insurance['MotorcycleInsurance_ID']; ?>&type=motorcycle" 
                           class="btn choose-btn">
                            Wybierz ofertę
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <div class="no-results-help">
                <h3>😔 Nie znaleziono ofert dla podanych parametrów</h3>
                <p>Spróbuj:</p>
                <ul>
                    <li>Zmienić zakres pojemności silnika (automatycznie szukamy ±200cc)</li>
                    <li>Wybrać inny typ ubezpieczenia (OC zamiast OC/AC)</li>
                    <li>Zmienić typ użytkowania</li>
                    <li>Zostawić pole "Typ motocykla" puste aby zobaczyć wszystkie kategorie</li>
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