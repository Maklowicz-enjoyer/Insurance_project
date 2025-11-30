<?php
// scripts/admin.php
// 1. Bezpieczeństwo sesji
require_once __DIR__ . '/session_check.php';

// 2. Weryfikacja uprawnień (Security Barrier)
if (!$GLOBALS['current_user_is_admin']) {
    header("Location: ../html/main.php");
    exit;
}

// 3. Połączenie z bazą
include('db_connect.php');

// 4. CSRF Protection (Security)
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
        $category = $_POST['vehicle_category']; // CAR lub MOTORCYCLE

        if ($insurance_id && in_array($category, ['CAR', 'MOTORCYCLE'])) {
            try {
                // Musimy usunąć z konkretnej tabeli, nie z VIEW
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
        $vehicle_type = $_POST['vehicle_type']; // CAR lub MOTORCYCLE
        
        // Wspólne pola
        $params = [
            ':insurance_name' => $_POST['insurance_name'],
            ':insurance_type' => $_POST['insurance_type'], // OC, AC, OC/AC
            ':use_type' => $_POST['use_type'], // PRYWATNIE / LEASING
            ':license_date' => $_POST['license_release_date'],
            ':price' => $_POST['price'],
            ':user_id' => 1, // Domyślnie przypisujemy do admina lub systemowego usera
            ':vehicle_id' => ($vehicle_type === 'CAR') ? 1 : 2 // Placeholder: w realnym app tu byłby wybór pojazdu
        ];

        try {
            if ($vehicle_type === 'CAR') {
                $query = "INSERT INTO CarInsurance 
                    (Users_ID, Vehicle_ID, Insurance_name, Insurance_type, Use_type, License_release_date, Body_type, Planned_mileage, Price) 
                    VALUES (:user_id, :vehicle_id, :insurance_name, :insurance_type, :use_type, :license_date, :body_type, :mileage, :price)";
                
                $params[':body_type'] = $_POST['body_type'];
                $params[':mileage'] = $_POST['planned_mileage'];

            } else { // MOTORCYCLE
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

// --- OBSŁUGA GET (Wyszukiwanie i Stronicowanie) ---

// Parametry stronicowania
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Parametry wyszukiwania
$search = $_GET['search'] ?? '';

// Budowanie zapytania
$query = "SELECT * FROM Insurance WHERE 1=1";
$countQuery = "SELECT COUNT(*) FROM Insurance WHERE 1=1";
$queryParams = [];

if (!empty($search)) {
    $searchCondition = " AND (Insurance_name LIKE :search OR Insurance_type LIKE :search)";
    $query .= $searchCondition;
    $countQuery .= $searchCondition;
    $queryParams[':search'] = "%$search%";
}

// Sortowanie i limit
$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

try {
    // Pobranie rekordów
    $stmt = $pdo->prepare($query);
    $stmt->execute($queryParams);
    $insurances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pobranie liczby wszystkich rekordów (do paginacji)
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
    <link rel="stylesheet" href="/../css/admin_panel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        <section class="dashboard-grid">
            <div class="card">
                <h3><i class="fas fa-chart-pie"></i> Statystyki Ofert</h3>
                <div class="chart-placeholder">
                    [Wykres: Udział Ubezpieczycieli]
                    </div>
            </div>
            <div class="card">
                <h3><i class="fas fa-users"></i> Wybory Użytkowników</h3>
                <div class="chart-placeholder">
                    [Wykres: Car vs Moto]
                </div>
            </div>
            <div class="card">
                <h3><i class="fas fa-database"></i> Szybki Status</h3>
                <p>Liczba ofert: <strong><?php echo $totalRecords; ?></strong></p>
                <p>Strona: <strong><?php echo $page; ?> / <?php echo $totalPages; ?></strong></p>
            </div>
        </section>

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
                                        <td>
                                            <?php 
                                                // Typ_nadwozia w widoku zawiera body_type dla aut lub 'MOTORCYCLE' dla moto
                                                // Ale w bazie mamy kolumnę Typ_nadwozia dla View.
                                                // Dla motocykli chcemy pokazać konkretny typ jeśli jest dostępny, ale VIEW w obecnej wersji zwraca 'MOTORCYCLE' w tej kolumnie.
                                                // W lepszej wersji VIEW można by zmapować 'Motorcycle_type' do tej kolumny.
                                                echo htmlspecialchars($row['Typ_nadwozia']); 
                                            ?>
                                        </td>
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
</body>
</html>