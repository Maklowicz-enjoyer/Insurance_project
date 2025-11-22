<?php
// ============================================
// STRONA SZCZEGÓŁÓW OFERTY
// ============================================
// Pokazuje pełne informacje o ofercie + opcja dodania do ulubionych

session_start();
require '../scripts/db_connect.php';
global $pdo;

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_email'])) {
    header('Location: /scripts/login.php');
    exit;
}

// Pobierz ID użytkownika
$stmt = $pdo->prepare("SELECT Users_ID FROM User WHERE email = :email");
$stmt->execute(['email' => $_SESSION['user_email']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_id = $user['Users_ID'];

// Pobierz parametry z URL
$insurance_id = $_GET['id'] ?? null;
$insurance_type = $_GET['type'] ?? 'car'; // 'car' lub 'motorcycle'

if (!$insurance_id) {
    die("Brak ID oferty");
}

// Obsługa dodawania do ulubionych
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_favorites'])) {
    $notes = $_POST['notes'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO FavoriteInsurance (Users_ID, Insurance_ID, Insurance_Type, Notes)
            VALUES (:user_id, :insurance_id, :insurance_type, :notes)
            ON DUPLICATE KEY UPDATE Notes = :notes
        ");
        
        $stmt->execute([
            ':user_id' => $user_id,
            ':insurance_id' => $insurance_id,
            ':insurance_type' => strtoupper($insurance_type),
            ':notes' => $notes
        ]);
        
        $message = '✓ Oferta dodana do ulubionych!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = '✗ Błąd: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Pobierz szczegóły oferty
try {
    if ($insurance_type === 'motorcycle') {
        $stmt = $pdo->prepare("
            SELECT * FROM MotorcycleInsurance 
            WHERE MotorcycleInsurance_ID = :id
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM CarInsurance 
            WHERE CarInsurance_ID = :id
        ");
    }
    
    $stmt->execute([':id' => $insurance_id]);
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$offer) {
        die("Nie znaleziono oferty");
    }
    
    // Sprawdź czy oferta jest już w ulubionych
    $stmt = $pdo->prepare("
        SELECT * FROM FavoriteInsurance 
        WHERE Users_ID = :user_id 
        AND Insurance_ID = :insurance_id 
        AND Insurance_Type = :insurance_type
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':insurance_id' => $insurance_id,
        ':insurance_type' => strtoupper($insurance_type)
    ]);
    $is_favorite = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły Oferty - SkanPolis</title>
    <link rel="stylesheet" href="/css/detail.css">
    <style>
        .favorite-section {
            background: #f0f8ff;
            border: 2px solid #4CAF50;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .favorite-section h3 {
            color: #2e7d32;
            margin-bottom: 15px;
        }
        
        .favorite-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            font-family: Arial, sans-serif;
            resize: vertical;
        }
        
        .btn-favorite {
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-favorite:hover {
            background: linear-gradient(135deg, #388E3C, #4CAF50);
        }
        
        .btn-favorite.already-favorite {
            background: linear-gradient(135deg, #FFC107, #FFD54F);
            color: #333;
        }
        
        .message {
            padding: 12px 20px;
            border-radius: 6px;
            margin: 15px 0;
            font-weight: bold;
        }
        
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .offer-specs {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .offer-specs h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .spec-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .spec-item:last-child {
            border-bottom: none;
        }
        
        .spec-label {
            font-weight: bold;
            color: #666;
        }
        
        .spec-value {
            color: #333;
        }
        
        .price-box {
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        
        .price-box .main-price {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .price-box .installment {
            font-size: 16px;
            opacity: 0.9;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-container">
        <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
        <div class="header-buttons">
            <a href="javascript:history.back()" class="btn back">POWRÓT</a>
            <a href="account.php" class="btn acc">MOJE KONTO</a>
            <a href="/index.html" class="btn logout">WYLOGUJ SIĘ</a>
        </div>
    </div>
</header>

<main>
    <?php if ($message): ?>
    <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>
    
    <section class="offer-detail">
        <div class="offer-summary">
            <h2 class="offer-title">
                <?php echo htmlspecialchars($offer['Insurance_name']); ?>
                <?php if ($insurance_type === 'motorcycle'): ?>
                🏍️
                <?php else: ?>
                🚗
                <?php endif; ?>
            </h2>
            <p class="offer-coverage">
                Typ: <?php echo htmlspecialchars($offer['Insurance_type']); ?>
            </p>
        </div>
        
        <!-- Cena -->
        <div class="price-box">
            <p style="font-size: 14px; margin: 0;">Cena ubezpieczenia</p>
            <div class="main-price">
                <?php echo number_format($offer['Price'], 2, ',', ' '); ?> zł
            </div>
            <p class="installment">
                lub <?php echo number_format($offer['Price'] / 12, 2, ',', ' '); ?> zł × 12 rat
            </p>
        </div>
        
        <!-- Specyfikacja -->
        <div class="offer-specs">
            <h3>📋 Szczegóły oferty</h3>
            
            <div class="spec-item">
                <span class="spec-label">Ubezpieczyciel:</span>
                <span class="spec-value"><?php echo htmlspecialchars($offer['Insurance_name']); ?></span>
            </div>
            
            <div class="spec-item">
                <span class="spec-label">Typ ubezpieczenia:</span>
                <span class="spec-value"><?php echo htmlspecialchars($offer['Insurance_type']); ?></span>
            </div>
            
            <div class="spec-item">
                <span class="spec-label">Typ użytkowania:</span>
                <span class="spec-value"><?php echo htmlspecialchars($offer['Use_type']); ?></span>
            </div>
            
            <?php if ($insurance_type === 'motorcycle'): ?>
                <div class="spec-item">
                    <span class="spec-label">Pojemność silnika:</span>
                    <span class="spec-value"><?php echo htmlspecialchars($offer['Engine_capacity']); ?> cm³</span>
                </div>
                
                <?php if (!empty($offer['Power_HP'])): ?>
                <div class="spec-item">
                    <span class="spec-label">Moc silnika:</span>
                    <span class="spec-value"><?php echo htmlspecialchars($offer['Power_HP']); ?> KM</span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($offer['Motorcycle_type'])): ?>
                <div class="spec-item">
                    <span class="spec-label">Typ motocykla:</span>
                    <span class="spec-value"><?php echo htmlspecialchars(ucfirst($offer['Motorcycle_type'])); ?></span>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="spec-item">
                    <span class="spec-label">Typ nadwozia:</span>
                    <span class="spec-value"><?php echo htmlspecialchars($offer['Body_type']); ?></span>
                </div>
                
                <?php if (!empty($offer['Planned_mileage'])): ?>
                <div class="spec-item">
                    <span class="spec-label">Planowany kilometraż:</span>
                    <span class="spec-value"><?php echo number_format($offer['Planned_mileage'], 0, ',', ' '); ?> km/rok</span>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="spec-item">
                <span class="spec-label">Data ważności:</span>
                <span class="spec-value"><?php echo htmlspecialchars($offer['License_release_date']); ?></span>
            </div>
        </div>
        
        <!-- Sekcja dodawania do ulubionych -->
        <div class="favorite-section">
            <h3>
                <?php if ($is_favorite): ?>
                ⭐ Ta oferta jest już w Twoich ulubionych
                <?php else: ?>
                ❤️ Dodaj do ulubionych
                <?php endif; ?>
            </h3>
            
            <form method="POST" class="favorite-form">
                <label for="notes">
                    <strong>Notatka (opcjonalnie):</strong>
                </label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    rows="3" 
                    placeholder="Np. 'Najlepsza cena dla mojego auta' lub 'Do porównania z innymi'"
                ><?php echo $is_favorite ? htmlspecialchars($is_favorite['Notes']) : ''; ?></textarea>
                
                <button 
                    type="submit" 
                    name="add_to_favorites" 
                    class="btn-favorite <?php echo $is_favorite ? 'already-favorite' : ''; ?>"
                >
                    <?php if ($is_favorite): ?>
                    ⭐ Zaktualizuj notatkę
                    <?php else: ?>
                    ❤️ Dodaj do ulubionych
                    <?php endif; ?>
                </button>
            </form>
        </div>
        
        <!-- Opcje wysyłki -->
        <div class="send-option">
            <button class="btn send-btn" onclick="alert('Funkcja wysyłki emaila zostanie wkrótce dodana!')">
                📧 WYŚLIJ NA MAIL
            </button>
        </div>
    </section>
</main>

<footer class="footer">
    <p>&copy; 2024 SkanPolis. Wszelkie prawa zastrzeżone.</p>
</footer>
</body>
</html>