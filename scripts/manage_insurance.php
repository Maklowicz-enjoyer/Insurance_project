<?php
require 'connect_db.php'; // Include the database connection script

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $insurance_name = $_POST['insurance_name'];
    $insurance_type = $_POST['insurance_type'];
    $use_type = $_POST['use_type'];
    $license_release_date = $_POST['license_release_date'];
    $last_accident = isset($_POST['last_accident']) ? 1 : 0;
    $date_of_last_collision = $_POST['date_of_last_collision'];
    $planned_mileage = $_POST['planned_mileage'];
    $typ_nadwozia = $_POST['typ_nadwozia'];

    // Validate form data
    if (empty($insurance_name) || empty($insurance_type) || empty($use_type) || empty($license_release_date) || empty($planned_mileage) || empty($typ_nadwozia)) {
        echo "All required fields must be filled out.";
    } else {
        // Prepare and execute
        $stmt = $pdo->prepare("INSERT INTO insurances (Insurance_name, Insurance_type, Use_type, License_release_date, Last_accident, Date_of_last_collision, Planned_mileage, Typ_nadwozia) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$insurance_name, $insurance_type, $use_type, $license_release_date, $last_accident, $date_of_last_collision, $planned_mileage, $typ_nadwozia]);

        echo "New insurance added successfully.";
    }
}

// List all insurances
$stmt = $pdo->query("SELECT * FROM insurances");
$insurances = $stmt->fetchAll();

if ($insurances) {
    echo "<table><tr><th>ID</th><th>Insurance Name</th><th>Insurance Type</th><th>Use Type</th><th>License Release Date</th><th>Last Accident</th><th>Date of Last Collision</th><th>Planned Mileage</th><th>Typ Nadwozia</th></tr>";
    foreach ($insurances as $insurance) {
        echo "<tr><td>{$insurance['Insurance_ID']}</td><td>{$insurance['Insurance_name']}</td><td>{$insurance['Insurance_type']}</td><td>{$insurance['Use_type']}</td><td>{$insurance['License_release_date']}</td><td>{$insurance['Last_accident']}</td><td>{$insurance['Date_of_last_collision']}</td><td>{$insurance['Planned_mileage']}</td><td>{$insurance['Typ_nadwozia']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No insurance entries found.";
}
?>
