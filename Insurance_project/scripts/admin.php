<?php
// scripts/admin.php

// 1. Bezpieczeństwo sesji
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/db_connect.php';

// 2. Weryfikacja uprawnień
if (!$GLOBALS['current_user_is_admin']) {
    header("Location: ../html/main.php");
    exit;
}

// 3. CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success_message = '';
$error_message = '';

// --- OBSŁUGA POST (Dodawanie/Usuwanie) ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Weryfikacja tokenu CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Błąd bezpieczeństwa CSRF. Odśwież stronę.");
    }

    // A. Usuwanie rekordu
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $insurance_id = filter_input(INPUT_POST, 'insurance_id', FILTER_VALIDATE_INT);
        $category = $_POST['vehicle_category']; 

        if ($insurance_id && in_array($category, ['CAR', 'MOTORCYCLE'])) {
            try {
                $table = ($category === 'CAR') ? 'CarInsurance' : 'MotorcycleInsurance';
                $idColumn = ($category === 'CAR') ? 'CarInsurance_ID' : 'MotorcycleInsurance_ID';
                
                $stmt = $pdo->prepare("DELETE FROM $table WHERE $idColumn = :id");
                $stmt->execute([':id' => $insurance_id]);
                $success_message = "Oferta usunięta pomyślnie.";
            } catch (PDOException $e) {
                $error_message = "Błąd bazy danych: " . $e->getMessage();
            }
        }
    }

    // B. Dodawanie rekordu
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $vehicle_type = $_POST['vehicle_type'];
        
        $params = [
            ':insurance_name' => $_POST['insurance_name'],
            ':insurance_type' => $_POST['insurance_type'],
            ':use_type' => $_POST['use_type'],
            ':license_date' => $_POST['license_release_date'],
            ':price' => $_POST['price'],
            ':user_id' => 1,
            ':vehicle_id' => ($vehicle_type === 'CAR') ? 1 : 2
        ];

        try {
            if ($vehicle_type === 'CAR') {
                $query = "INSERT INTO CarInsurance 
                    (Users_ID, Vehicle_ID, Insurance_name, Insurance_type, Use_type, License_release_date, Body_type, Planned_mileage, Price) 
                    VALUES (:user_id, :vehicle_id, :insurance_name, :insurance_type, :use_type, :license_date, :body_type, :mileage, :price)";
                
                $params[':body_type'] = $_POST['body_type'];
                $params[':mileage'] = $_POST['planned_mileage'];

            } else { 
                $query = "INSERT INTO MotorcycleInsurance 
                    (Users_ID, Vehicle_ID, Insurance_name, Insurance_type, Use_type, License_release_date, Engine_capacity, Power_HP, Motorcycle_type, Price) 
                    VALUES (:user_id, :vehicle_id, :insurance_name, :insurance_type, :use_type, :license_date, :engine_capacity, :power_hp, :moto_type, :price)";
                
                $params[':engine_capacity'] = $_POST['engine_capacity'];
                $params[':power_hp'] = $_POST['power_hp'];
                $params[':moto_type'] = $_POST['motorcycle_type'];
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $success_message = "Nowa oferta ($vehicle_type) dodana pomyślnie!";

        } catch (PDOException $e) {
            $error_message = "Błąd dodawania: " . $e->getMessage();
        }
    }
}

// --- OBSŁUGA GET (Wyszukiwanie i Stronicowanie tabeli) ---
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM Insurance WHERE 1=1";
$countQuery = "SELECT COUNT(*) FROM Insurance WHERE 1=1";
$queryParams = [];

if (!empty($search)) {
    $searchCondition = " AND (Insurance_name LIKE :search OR Insurance_type LIKE :search)";
    $query .= $searchCondition;
    $countQuery .= $searchCondition;
    $queryParams[':search'] = "%$search%";
}

$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($queryParams);
    $insurances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtCount = $pdo->prepare($countQuery);
    $stmtCount->execute($queryParams);
    $totalRecords = $stmtCount->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
} catch (PDOException $e) {
    $error_message = "Nie udało się pobrać danych.";
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administratora - SkanPolis</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/material_green.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/pl.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    
    <header class="header">
        <div class="header-container">
            <div class="logo">SKAN<span class="part1">POLIS</span> <small style="font-size: 14px; opacity: 0.8;">| Panel Admina</small></div>
            <div class="user-info">
                <span style="color: white; margin-right: 15px;">Witaj, <?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
                <a href="../scripts/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Wyloguj</a>
            </div>
        </div>
    </header>

    <main class="main-content">

        <?php if ($success_message): ?>
            <div class="message success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
        <?php endif; ?>

        <section class="dashboard-header" style="margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0; color: #333;">Analityka Biznesowa</h2>
                <p style="margin: 5px 0 0; color: #777; font-size: 14px;">Przegląd wyników w czasie rzeczywistym</p>
            </div>
            
            <div class="date-filter" style="display: flex; gap: 10px; align-items: center;">
                <label for="date-range" style="font-weight: bold; color: #555;"><i class="fas fa-calendar-alt"></i> Okres:</label>
                <input type="text" id="date-range" class="flatpickr-input" placeholder="Wybierz zakres dat" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 250px;">
            </div>
        </section>

        <div class="kpi-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="card kpi-card" style="border-left: 5px solid #00897b; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h4 style="margin: 0; color: #777; font-size: 12px; text-transform: uppercase;">Wyszukiwania</h4>
                <div class="value" id="kpi-searches" style="font-size: 28px; font-weight: bold; color: #333;">...</div>
                <div class="trend" style="font-size: 12px; color: #00897b;">w wybranym okresie</div>
            </div>
            <div class="card kpi-card" style="border-left: 5px solid #ff6f00; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h4 style="margin: 0; color: #777; font-size: 12px; text-transform: uppercase;">Polubione Oferty</h4>
                <div class="value" id="kpi-favorites" style="font-size: 28px; font-weight: bold; color: #333;">...</div>
                <div class="trend" style="font-size: 12px; color: #ff6f00;">potencjalni klienci</div>
            </div>
            <div class="card kpi-card" style="border-left: 5px solid #1976d2; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h4 style="margin: 0; color: #777; font-size: 12px; text-transform: uppercase;">Nowi Użytkownicy</h4>
                <div class="value" id="kpi-users" style="font-size: 28px; font-weight: bold; color: #333;">...</div>
                <div class="trend" style="font-size: 12px; color: #1976d2;">rejestracje</div>
            </div>
            <div class="card kpi-card" style="border-left: 5px solid #8e24aa; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h4 style="margin: 0; color: #777; font-size: 12px; text-transform: uppercase;">Współczynnik Konwersji</h4>
                <div class="value" id="kpi-conversion" style="font-size: 28px; font-weight: bold; color: #333;">...%</div>
                <div class="trend" style="font-size: 12px; color: #8e24aa;">(Polubienia / Wyszukiwania)</div>
            </div>
        </div>

        <div class="charts-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div class="card" style="padding: 20px; height: 400px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3><i class="fas fa-chart-line"></i> Aktywność Użytkowników</h3>
                <div style="position: relative; height: 320px; width: 100%;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <div class="card" style="padding: 20px; height: 400px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3><i class="fas fa-car"></i> Top 5 Marek</h3>
                <div style="position: relative; height: 320px; width: 100%;">
                    <canvas id="brandsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="charts-grid-lower" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div class="card" style="padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3><i class="fas fa-shield-alt"></i> Rodzaje Polis</h3>
                <div style="position: relative; height: 250px;">
                    <canvas id="typesChart"></canvas>
                </div>
            </div>

            <div class="card" style="padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3><i class="fas fa-motorcycle"></i> Auto vs Moto</h3>
                <div style="position: relative; height: 250px; display: flex; justify-content: center;">
                    <canvas id="vehicleSplitChart"></canvas>
                </div>
            </div>
        </div>

        <section class="table-section">
            <div class="card">
                <div class="actions-toolbar">
                    <h3>Zarządzanie Ofertami</h3>
                    
                    <form method="GET" class="search-box">
                        <input type="text" name="search" placeholder="Szukaj ubezpieczyciela..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="limit" onchange="this.form.submit()">
                            <option value="15" <?php if($limit == 15) echo 'selected'; ?>>15 na stronę</option>
                            <option value="30" <?php if($limit == 30) echo 'selected'; ?>>30 na stronę</option>
                            <option value="50" <?php if($limit == 50) echo 'selected'; ?>>50 na stronę</option>
                        </select>
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>

                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Typ Pojazdu</th>
                                <th>Ubezpieczyciel</th>
                                <th>Rodzaj</th>
                                <th>Nadwozie / Typ</th>
                                <th>Cena</th>
                                <th>Użycie</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($insurances)): ?>
                                <?php foreach ($insurances as $row): ?>
                                    <tr>
                                        <td>#<?php echo $row['Insurance_ID']; ?></td>
                                        <td>
                                            <span class="badge <?php echo ($row['Vehicle_Category'] == 'CAR' ? 'badge-car' : 'badge-moto'); ?>">
                                                <?php echo $row['Vehicle_Category'] == 'CAR' ? '<i class="fas fa-car"></i> Auto' : '<i class="fas fa-motorcycle"></i> Moto'; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($row['Insurance_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['Insurance_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Typ_nadwozia']); ?></td>
                                        <td><?php echo number_format($row['Price'], 2); ?> zł</td>
                                        <td><?php echo htmlspecialchars($row['Use_type']); ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Czy na pewno usunąć tę ofertę?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="insurance_id" value="<?php echo $row['Insurance_ID']; ?>">
                                                <input type="hidden" name="vehicle_category" value="<?php echo $row['Vehicle_Category']; ?>">
                                                <button type="submit" class="btn-delete"><i class="fas fa-trash"></i> Usuń</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align:center; padding: 30px;">Brak ofert spełniających kryteria.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>" 
                           class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                           <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="form-section">
            <h3><i class="fas fa-plus-circle"></i> Dodaj Nową Ofertę</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>Rodzaj Pojazdu:</label>
                    <select name="vehicle_type" id="vehicleTypeSelector" onchange="toggleFormFields()">
                        <option value="CAR">Samochód Osobowy</option>
                        <option value="MOTORCYCLE">Motocykl</option>
                    </select>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Ubezpieczyciel:</label>
                        <input type="text" name="insurance_name" required placeholder="np. PZU, Allianz">
                    </div>
                    
                    <div class="form-group">
                        <label>Typ Ubezpieczenia:</label>
                        <select name="insurance_type" required>
                            <option value="OC">OC</option>
                            <option value="OC/AC">OC/AC</option>
                            <option value="Assistance">Assistance</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Typ Użytkowania:</label>
                        <select name="use_type" required>
                            <option value="PRYWATNIE">PRYWATNIE</option>
                            <option value="LEASING">LEASING</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Data Ważności Oferty:</label>
                        <input type="date" name="license_release_date" required>
                    </div>

                    <div class="form-group">
                        <label>Cena (PLN):</label>
                        <input type="number" step="0.01" name="price" required placeholder="0.00">
                    </div>

                    <div class="form-group car-field">
                        <label>Typ nadwozia:</label>
                        <select name="body_type">
                            <option value="Sedan">Sedan</option>
                            <option value="SUV">SUV</option>
                            <option value="Kombi">Kombi</option>
                            <option value="Hatchback">Hatchback</option>
                            <option value="Kompakt">Kompakt</option>
                            <option value="Coupe">Coupe</option>
                            <option value="Kabriolet">Kabriolet</option>
                        </select>
                    </div>

                    <div class="form-group car-field">
                        <label>Planowany Przebieg (km):</label>
                        <input type="number" name="planned_mileage" placeholder="np. 15000">
                    </div>

                    <div class="form-group moto-field hidden">
                        <label>Typ Motocykla:</label>
                        <select name="motorcycle_type">
                            <option value="naked">Naked</option>
                            <option value="cruiser">Cruiser</option>
                            <option value="bobber">Bobber</option>
                            <option value="cross">Cross/Enduro</option>
                        </select>
                    </div>

                    <div class="form-group moto-field hidden">
                        <label>Pojemność (cm³):</label>
                        <input type="number" name="engine_capacity" placeholder="np. 600">
                    </div>

                    <div class="form-group moto-field hidden">
                        <label>Moc (KM):</label>
                        <input type="number" name="power_hp" placeholder="np. 75">
                    </div>
                </div>

                <button type="submit" class="btn-add"><i class="fas fa-save"></i> Zapisz Ofertę</button>
            </form>
        </section>

    </main>

    <script>
        // Logika przełączania pól formularza (Car vs Moto)
        function toggleFormFields() {
            const type = document.getElementById('vehicleTypeSelector').value;
            const carFields = document.querySelectorAll('.car-field');
            const motoFields = document.querySelectorAll('.moto-field');

            if (type === 'CAR') {
                carFields.forEach(el => el.classList.remove('hidden'));
                motoFields.forEach(el => el.classList.add('hidden'));
                
                // Ustaw wymagania dla walidacji HTML5
                document.querySelector('[name="body_type"]').setAttribute('required', 'required');
                document.querySelector('[name="engine_capacity"]').removeAttribute('required');
            } else {
                carFields.forEach(el => el.classList.add('hidden'));
                motoFields.forEach(el => el.classList.remove('hidden'));

                // Zmień wymagania
                document.querySelector('[name="body_type"]').removeAttribute('required');
                document.querySelector('[name="engine_capacity"]').setAttribute('required', 'required');
            }
        }

        // Uruchom na starcie
        window.addEventListener('DOMContentLoaded', toggleFormFields);
    </script>

    <script src="../js/admin_dashboard.js"></script>

</body>
</html>