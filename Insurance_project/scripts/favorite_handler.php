<?php
// scripts/favorite_handler.php
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/db_connect.php';

// Sprawdź czy to żądanie POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../html/main.php");
    exit;
}

$email = $_SESSION['user_email'] ?? null;

if (!$email) {
    header("Location: ../scripts/login.php");
    exit;
}

try {
    // 1. Pobierz ID użytkownika
    $stmt = $pdo->prepare("SELECT Users_ID FROM User WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        die("Błąd użytkownika.");
    }

    // 2. Walidacja danych
    $insuranceId = filter_input(INPUT_POST, 'insurance_id', FILTER_VALIDATE_INT);
    $insuranceType = $_POST['insurance_type'] ?? '';
    $action = $_POST['action'] ?? 'add';

    if (!$insuranceId || !in_array($insuranceType, ['CAR', 'MOTORCYCLE'])) {
        die("Nieprawidłowe dane oferty.");
    }

    // 3. Pobierz parametry wyszukiwania z sesji (jeśli dostępne)
    $searchParams = $_SESSION['last_search_params'] ?? [];

    // 3. Obsługa akcji na tabeli FavoriteInsurance
    if ($action === 'add') {
        // Przygotuj dane do zapisania (z parametrami wyszukiwania)
        $sql = "INSERT IGNORE INTO FavoriteInsurance
                (Users_ID, Insurance_ID, Insurance_Type, Brand, Body_type, Production_year,
                 Engine_capacity, DOB, License_date, Damage_free_years,
                 Assistance_level, Accident_cover, Discount_protection)
                VALUES (:uid, :iid, :itype, :brand, :body_type, :year,
                        :capacity, :dob, :license_date, :damage,
                        :assistance, :accident_cover, :discount_protection)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $userId,
            ':iid' => $insuranceId,
            ':itype' => $insuranceType,
            ':brand' => $searchParams['brand'] ?? null,
            ':body_type' => $searchParams['typ_nadwozia'] ?? null,
            ':year' => $searchParams['year'] ?? null,
            ':capacity' => $searchParams['capacity'] ?? null,
            ':dob' => $searchParams['dob'] ?? null,
            ':license_date' => $searchParams['license_date'] ?? null,
            ':damage' => $searchParams['damage'] ?? 0,
            ':assistance' => $searchParams['assistance'] ?? 'NONE',
            ':accident_cover' => $searchParams['accident_cover'] ?? 0,
            ':discount_protection' => $searchParams['discount_protection'] ?? 0
        ]);

        header("Location: ../html/detail.php?id=$insuranceId&type=$insuranceType&msg=added");

    } elseif ($action === 'remove') {
        $sql = "DELETE FROM FavoriteInsurance WHERE Users_ID = :uid AND Insurance_ID = :iid AND Insurance_Type = :itype";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $userId,
            ':iid' => $insuranceId,
            ':itype' => $insuranceType
        ]);

        $redirect = $_POST['redirect'] ?? 'detail';
        if ($redirect === 'account') {
            header("Location: ../html/account.php?msg=removed");
        } else {
            header("Location: ../html/detail.php?id=$insuranceId&type=$insuranceType&msg=removed");
        }
    }

} catch (PDOException $e) {
    error_log("Database Error in favorites: " . $e->getMessage());
    die("Wystąpił błąd bazy danych.");
}
?>