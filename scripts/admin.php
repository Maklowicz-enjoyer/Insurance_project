
<?php
// Include database configuration
include('db_connect.php');

// Check if the delete request is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $insurance_id = $_POST['insurance_id'];

    try {
        // Delete the insurance record
        $stmt = $pdo->prepare("DELETE FROM Insurance WHERE Insurance_ID = :insurance_id");
        $stmt->execute([':insurance_id' => $insurance_id]);

        $success_message = "Ubezpieczenie zostało pomyślnie usunięte!";
    } catch (PDOException $e) {
        $error_message = "Błąd podczas usuwania ubezpieczenia: " . $e->getMessage();
    }
}

// Check if the form is submitted to add a new insurance
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    // Retrieve data from the form
    $insurance_name = $_POST['insurance_name'];
    $insurance_type = $_POST['insurance_type'];
    $use_type = $_POST['use_type'];
    $license_release_date = $_POST['license_release_date'];
    $planned_mileage = $_POST['planned_mileage'];
    $typ_nadwozia = $_POST['typ_nadwozia'];

    try {
        // Insert insurance record into the database
        $stmt = $pdo->prepare("
            INSERT INTO Insurance (
                Insurance_name, Insurance_type, Use_type, 
                License_release_date, Planned_mileage, Typ_nadwozia
            ) VALUES (
                :insurance_name, :insurance_type, :use_type, 
                :license_release_date, :planned_mileage, :typ_nadwozia
            )
        ");

        $stmt->execute([
            ':insurance_name' => $insurance_name,
            ':insurance_type' => $insurance_type,
            ':use_type' => $use_type,
            ':license_release_date' => $license_release_date,
            ':planned_mileage' => $planned_mileage,
            ':typ_nadwozia' => $typ_nadwozia
        ]);

        $success_message = "Ubezpieczenie zostało pomyślnie dodane!";
    } catch (PDOException $e) {
        $error_message = "Błąd: " . $e->getMessage();
    }
}

// Fetch all insurance records to display in the table
try {
    $stmt = $pdo->query("SELECT * FROM Insurance");
    $insurances = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Błąd podczas pobierania danych: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora - SkanPolis</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="../scripts/login.php" class="btn back">WYLOGUJ SIĘ</a>
            <h1 class="admin-title">Panel Administratora</h1>
            <div class="logo">SKAN<span class="part1">POLIS</span></div>
        </div>
    </header>

    <main>
        <!-- Display success or error messages -->
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <section class="admin-section">
            <h2 class="section-title">Zarządzanie Ubezpieczeniami</h2>
            <!-- Display insurance table -->
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ubezpieczyciel</th>
                        <th>Typ Ubezpieczenia</th>
                        <th>Typ Nadwozia</th>
                        <th>Data Ważności</th>
                        <th>Planowany Kilometraż</th>
                        <th>Typ Użytkowania</th>
                        <th>Opcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($insurances)): ?>
                        <?php foreach ($insurances as $insurance): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($insurance['Insurance_ID']); ?></td>
                                <td><?php echo htmlspecialchars($insurance['Insurance_name']); ?></td>
                                <td><?php echo htmlspecialchars($insurance['Insurance_type']); ?></td>
                                <td><?php echo htmlspecialchars($insurance['Typ_nadwozia']); ?></td>
                                <td><?php echo htmlspecialchars($insurance['License_release_date']); ?></td>
                                <td><?php echo htmlspecialchars($insurance['Planned_mileage']); ?></td>
                                <td><?php echo htmlspecialchars($insurance['Use_type']); ?></td>
                                <td>
                                    <!-- Delete button -->
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="insurance_id" value="<?php echo $insurance['Insurance_ID']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn delete">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">Brak ubezpieczeń do wyświetlenia.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="add-insurance">
            <h2 class="section-title">Dodaj Ubezpieczenie</h2>
            <form method="POST" class="add-insurance-form">
                <input type="hidden" name="action" value="add">
                <label for="insurance_name">Ubezpieczyciel:</label>
                <input type="text" id="insurance_name" name="insurance_name" required>

                <label for="insurance_type">Typ Ubezpieczenia:</label>
                <select id="insurance_type" name="insurance_type" required>
                    <option value="" disabled selected>Wybierz typ</option>
                    <option value="OC">OC</option>
                    <option value="AC">AC</option>
                    <option value="OC/AC">OC/AC</option>
                </select>

                <label for="typ_nadwozia">Typ Nadwozia:</label>
                <select id="typ_nadwozia" name="typ_nadwozia" required>
                    <option value="" disabled selected>Wybierz typ nadwozia</option>
                    <option value="Sedan">Sedan</option>
                    <option value="SUV">SUV</option>
                    <option value="Kombi">Kombi</option>
                    <option value="Hatchback">Hatchback</option>
                    <option value="Kompakt">Kompakt</option>
                    <option value="Coupe">Coupe</option>
                    <option value="Kabriolet">Kabriolet</option>
                </select>

                <label for="license_release_date">Data Ważności Ubezpieczenia:</label>
                <input type="date" id="license_release_date" name="license_release_date" required>

                <label for="planned_mileage">Planowany Kilometraż:</label>
                <input type="number" id="planned_mileage" name="planned_mileage" min="0" required>

                <label for="use_type">Typ Użytkowania:</label>
                <select id="use_type" name="use_type" required>
                    <option value="" disabled selected>Wybierz typ</option>
                    <option value="LEASING">LEASING</option>
                    <option value="PRYWATNIE">PRYWATNIE</option>
                </select>

                <button type="submit" class="btn add">Dodaj Ubezpieczenie</button>
            </form>
        </section>
    </main>

    <footer class="footer">
        <p>© 2024 SkanPolis. Wszelkie prawa zastrzeżone.</p>
    </footer>
</body>
</html>