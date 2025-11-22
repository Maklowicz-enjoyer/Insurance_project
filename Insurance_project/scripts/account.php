<?php
// ============================================
// PROFIL UŻYTKOWNIKA Z ULUBIONYMI OFERTAMI
// ============================================
session_start();
require '../scripts/db_connect.php';
global $pdo;

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_email'])) {
    header('Location: /scripts/login.php');
    exit;
}

// Pobierz ID użytkownika
$stmt = $pdo->prepare("SELECT Users_ID, email FROM User WHERE email = :email");
$stmt->execute(['email' => $_SESSION['user_email']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_id = $user['Users_ID'];

// Obsługa usuwania z ulubionych
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favorite'])) {
    $favorite_id = $_POST['favorite_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM FavoriteInsurance WHERE Favorite_ID = :id AND Users_ID = :user_id");
        $stmt->execute([
            ':id' => $favorite_id,
            ':user_id' => $user_id
        ]);
        
        $message = '✓ Oferta usunięta z ulubionych';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = '✗ Błąd: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Pobierz ulubione oferty użytkownika
try {
    // Ulubione samochody
    $stmt = $pdo->prepare("
        SELECT 
            f.Favorite_ID,
            f.Insurance_Type,
            f.Added_Date,
            f.Notes,
            c.CarInsurance_ID as Insurance_ID,
            c.Insurance_name,
            c.Insurance_type,
            c.Body_type as Type_Detail,
            c.Price,
            c.Use_type
        FROM FavoriteInsurance f
        JOIN CarInsurance c ON f.Insurance_ID = c.CarInsurance_ID
        WHERE f.Users_ID = :user_id AND f.Insurance_Type = 'CAR'
        ORDER BY f.Added_Date DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $favorite_cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ulubione motocykle
    $stmt = $pdo->prepare("
        SELECT 
            f.Favorite_ID,
            f.Insurance_Type,
            f.Added_Date,
            f.Notes,
            m.MotorcycleInsurance_ID as Insurance_ID,
            m.Insurance_name,
            m.Insurance_type,
            CONCAT(m.Engine_capacity, ' cm³') as Type_Detail,
            m.Price,
            m.Use_type,
            m.Motorcycle_type
        FROM FavoriteInsurance f
        JOIN MotorcycleInsurance m ON f.Insurance_ID = m.MotorcycleInsurance_ID
        WHERE f.Users_ID = :user_id AND f.Insurance_Type = 'MOTORCYCLE'
        ORDER BY f.Added_Date DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $favorite_motos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje Konto - SkanPolis</title>
    <link rel="stylesheet" href="/css/account.css">
    <style>
        .favorites-section {
            margin: 30px 0;
        }
        
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .favorite-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        
        .favorite-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .favorite-card.car {
            border-left: 4px solid #4CAF50;
        }
        
        .favorite-card.motorcycle {
            border-left: 4px solid #2196F3;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .card-badge.car {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .card-badge.motorcycle {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .card-info {
            margin: 12px 0;
        }
        
        .card-info-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
            color: #666;
        }
        
        .card-info-label {
            font-weight: 600;
        }
        
        .card-price {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
            text-align: center;
            margin: 15px 0;
        }
        
        .card-notes {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 6px;
            margin: 12px 0;
            font-size: 13px;
            color: #555;
            font-style: italic;
        }
        
        .card-notes:empty {
            display: none;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-small {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
            color: white;
        }
        
        .btn-view:hover {
            background: linear-gradient(135deg, #388E3C, #4CAF50);
        }
        
        .btn-remove {
            background: #f44336;
            color: white;
        }
        
        .btn-remove:hover {
            background: #d32f2f;
        }
        
        .card-date {
            font-size: 11px;
            color: #999;
            text-align: right;
            margin-top: 10px;
        }
        
        .empty-favorites {
            text-align: center;
            padding: 60px 20px;
            background: #f9f9f9;
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .empty-favorites-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .empty-favorites h3 {
            color: #666;
            margin-bottom: 15px;
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
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #4CAF50;
            border-bottom-color: #4CAF50;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-container">
        <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
        <a href="/html/main.html" class="btn back">POWRÓT</a>
    </div>
</header>

<main>
    <?php if (isset($message)): ?>
    <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>
    
    <section class="profile">
        <h2 class="profile-title">👤 Twój profil</h2>
        <div class="login-info">
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        </div>
    </section>
    
    <!-- NOWA SEKCJA: ULUBIONE OFERTY -->
    <section class="favorites-section">
        <h2 class="section-title">⭐ Ulubione oferty</h2>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('all')">
                Wszystkie (<?php echo count($favorite_cars) + count($favorite_motos); ?>)
            </button>
            <button class="tab" onclick="switchTab('cars')">
                🚗 Samochody (<?php echo count($favorite_cars); ?>)
            </button>
            <button class="tab" onclick="switchTab('motorcycles')">
                🏍️ Motocykle (<?php echo count($favorite_motos); ?>)
            </button>
        </div>
        
        <!-- Tab: Wszystkie -->
        <div id="tab-all" class="tab-content active">
            <?php if (empty($favorite_cars) && empty($favorite_motos)): ?>
            <div class="empty-favorites">
                <div class="empty-favorites-icon">💔</div>
                <h3>Nie masz jeszcze żadnych ulubionych ofert</h3>
                <p>Przeglądaj oferty i dodawaj te, które Cię interesują!</p>
                <a href="/html/main.html" class="btn" style="margin-top: 20px; display: inline-block;">
                    Szukaj ofert
                </a>
            </div>
            <?php else: ?>
            <div class="favorites-grid">
                <?php foreach (array_merge($favorite_cars, $favorite_motos) as $fav): ?>
                <div class="favorite-card <?php echo strtolower($fav['Insurance_Type']); ?>">
                    <div class="card-header">
                        <div class="card-title">
                            <?php echo $fav['Insurance_Type'] === 'CAR' ? '🚗' : '🏍️'; ?>
                            <?php echo htmlspecialchars($fav['Insurance_name']); ?>
                        </div>
                        <span class="card-badge <?php echo strtolower($fav['Insurance_Type']); ?>">
                            <?php echo $fav['Insurance_TYPE'] === 'CAR' ? 'Samochód' : 'Motocykl'; ?>
                        </span>
                    </div>
                    
                    <div class="card-info">
                        <div class="card-info-item">
                            <span class="card-info-label">Typ:</span>
                            <span><?php echo htmlspecialchars($fav['Insurance_type']); ?></span>
                        </div>
                        <div class="card-info-item">
                            <span class="card-info-label">
                                <?php echo $fav['Insurance_TYPE'] === 'CAR' ? 'Nadwozie:' : 'Pojemność:'; ?>
                            </span>
                            <span><?php echo htmlspecialchars($fav['Type_Detail']); ?></span>
                        </div>
                        <div class="card-info-item">
                            <span class="card-info-label">Użytkowanie:</span>
                            <span><?php echo htmlspecialchars($fav['Use_type']); ?></span>
                        </div>
                    </div>
                    
                    <div class="card-price">
                        <?php echo number_format($fav['Price'], 2, ',', ' '); ?> zł
                    </div>
                    
                    <?php if (!empty($fav['Notes'])): ?>
                    <div class="card-notes">
                        📝 <?php echo htmlspecialchars($fav['Notes']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-actions">
                        <a href="/scripts/detail.php?id=<?php echo $fav['Insurance_ID']; ?>&type=<?php echo strtolower($fav['Insurance_TYPE']); ?>" 
                           class="btn-small btn-view">
                            👁️ Zobacz szczegóły
                        </a>
                        
                        <form method="POST" style="flex: 1; margin: 0;" onsubmit="return confirm('Czy na pewno chcesz usunąć tę ofertę z ulubionych?')">
                            <input type="hidden" name="favorite_id" value="<?php echo $fav['Favorite_ID']; ?>">
                            <button type="submit" name="remove_favorite" class="btn-small btn-remove">
                                🗑️ Usuń
                            </button>
                        </form>
                    </div>
                    
                    <div class="card-date">
                        Dodano: <?php echo date('d.m.Y', strtotime($fav['Added_Date'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Tab: Samochody -->
        <div id="tab-cars" class="tab-content">
            <?php if (empty($favorite_cars)): ?>
            <div class="empty-favorites">
                <div class="empty-favorites-icon">🚗</div>
                <h3>Nie masz ulubionych ofert dla samochodów</h3>
            </div>
            <?php else: ?>
            <div class="favorites-grid">
                <?php foreach ($favorite_cars as $fav): ?>
                <!-- Ten sam kod karty co wyżej -->
                <div class="favorite-card car">
                    <!-- ... (identyczna struktura jak w tab-all) ... -->
                    <div class="card-header">
                        <div class="card-title">
                            🚗 <?php echo htmlspecialchars($fav['Insurance_name']); ?>
                        </div>
                        <span class="card-badge car">Samochód</span>
                    </div>
                    
                    <div class="card-info">
                        <div class="card-info-item">
                            <span class="card-info-label">Typ:</span>
                            <span><?php echo htmlspecialchars($fav['Insurance_type']); ?></span>
                        </div>
                        <div class="card-info-item">
                            <span class="card-info-label">Nadwozie:</span>
                            <span><?php echo htmlspecialchars($fav['Type_Detail']); ?></span>
                        </div>
                    </div>
                    
                    <div class="card-price">
                        <?php echo number_format($fav['Price'], 2, ',', ' '); ?> zł
                    </div>
                    
                    <?php if (!empty($fav['Notes'])): ?>
                    <div class="card-notes">
                        📝 <?php echo htmlspecialchars($fav['Notes']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-actions">
                        <a href="/scripts/detail.php?id=<?php echo $fav['Insurance_ID']; ?>&type=car" 
                           class="btn-small btn-view">
                            👁️ Zobacz
                        </a>
                        
                        <form method="POST" style="flex: 1; margin: 0;" onsubmit="return confirm('Czy na pewno?')">
                            <input type="hidden" name="favorite_id" value="<?php echo $fav['Favorite_ID']; ?>">
                            <button type="submit" name="remove_favorite" class="btn-small btn-remove">
                                🗑️ Usuń
                            </button>
                        </form>
                    </div>
                    
                    <div class="card-date">
                        Dodano: <?php echo date('d.m.Y', strtotime($fav['Added_Date'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Tab: Motocykle -->
        <div id="tab-motorcycles" class="tab-content">
            <?php if (empty($favorite_motos)): ?>
            <div class="empty-favorites">
                <div class="empty-favorites-icon">🏍️</div>
                <h3>Nie masz ulubionych ofert dla motocykli</h3>
            </div>
            <?php else: ?>
            <div class="favorites-grid">
                <?php foreach ($favorite_motos as $fav): ?>
                <div class="favorite-card motorcycle">
                    <div class="card-header">
                        <div class="card-title">
                            🏍️ <?php echo htmlspecialchars($fav['Insurance_name']); ?>
                        </div>
                        <span class="card-badge motorcycle">Motocykl</span>
                    </div>
                    
                    <div class="card-info">
                        <div class="card-info-item">
                            <span class="card-info-label">Typ:</span>
                            <span><?php echo htmlspecialchars($fav['Insurance_type']); ?></span>
                        </div>
                        <div class="card-info-item">
                            <span class="card-info-label">Pojemność:</span>
                            <span><?php echo htmlspecialchars($fav['Type_Detail']); ?></span>
                        </div>
                    </div>
                    
                    <div class="card-price">
                        <?php echo number_format($fav['Price'], 2, ',', ' '); ?> zł
                    </div>
                    
                    <?php if (!empty($fav['Notes'])): ?>
                    <div class="card-notes">
                        📝 <?php echo htmlspecialchars($fav['Notes']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-actions">
                        <a href="/scripts/detail.php?id=<?php echo $fav['Insurance_ID']; ?>&type=motorcycle" 
                           class="btn-small btn-view">
                            👁️ Zobacz
                        </a>
                        
                        <form method="POST" style="flex: 1; margin: 0;" onsubmit="return confirm('Czy na pewno?')">
                            <input type="hidden" name="favorite_id" value="<?php echo $fav['Favorite_ID']; ?>">
                            <button type="submit" name="remove_favorite" class="btn-small btn-remove">
                                🗑️ Usuń
                            </button>
                        </form>
                    </div>
                    
                    <div class="card-date">
                        Dodano: <?php echo date('d.m.Y', strtotime($fav['Added_Date'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Stare sekcje (niezmienione) -->
    <section class="notifications">
        <h2>🔔 Powiadomienia</h2>
        <div class="notification-options">
            <label>
                <input type="checkbox" id="reminder-end"> 
                Przypomnienie o końcu ubezpieczenia
            </label>
            <label>
                <input type="checkbox" id="new-offers"> 
                Powiadomienia o nowych ofertach
            </label>
        </div>
    </section>
</main>

<footer class="footer">
    <p>© <?php echo date("Y"); ?> Skanpolis. Wszelkie prawa zastrzeżone.</p>
</footer>

<script>
function switchTab(tab) {
    // Ukryj wszystkie taby
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.querySelectorAll('.tab').forEach(button => {
        button.classList.remove('active');
    });
    
    // Pokaż wybrany tab
    if (tab === 'all') {
        document.getElementById('tab-all').classList.add('active');
        document.querySelectorAll('.tab')[0].classList.add('active');
    } else if (tab === 'cars') {
        document.getElementById('tab-cars').classList.add('active');
        document.querySelectorAll('.tab')[1].classList.add('active');
    } else if (tab === 'motorcycles') {
        document.getElementById('tab-motorcycles').classList.add('active');
        document.querySelectorAll('.tab')[2].classList.add('active');
    }
}
</script>

</body>
</html>