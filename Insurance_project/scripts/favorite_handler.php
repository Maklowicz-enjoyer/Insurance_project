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

    // 3. Obsługa akcji na tabeli FavoriteInsurance
    if ($action === 'add') {
        // Używamy INSERT IGNORE, żeby nie było błędów przy duplikatach
        $sql = "INSERT IGNORE INTO FavoriteInsurance (Users_ID, Insurance_ID, Insurance_Type) VALUES (:uid, :iid, :itype)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $userId,
            ':iid' => $insuranceId,
            ':itype' => $insuranceType
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