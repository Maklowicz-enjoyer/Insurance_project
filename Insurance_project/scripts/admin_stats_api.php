<?php
// scripts/admin_stats_api.php
header('Content-Type: application/json');

require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/db_connect.php';

// 1. Security & Authorization
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// 2. Odbiór i walidacja dat (domyślnie ostatnie 30 dni)
$input = json_decode(file_get_contents('php://input'), true);
$startDate = $input['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $input['end_date'] ?? date('Y-m-d');

// Zabezpieczenie formatu daty (proste sprawdzenie)
if (!strtotime($startDate) || !strtotime($endDate)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

// Dodajemy czas do dat, aby objąć cały dzień końcowy
$startTs = $startDate . " 00:00:00";
$endTs = $endDate . " 23:59:59";

$response = [];

try {
    // --- KPI 1: Liczba wyszukiwań w okresie ---
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM SearchHistory WHERE Date_of_search BETWEEN :start AND :end");
    $stmt->execute(['start' => $startTs, 'end' => $endTs]);
    $response['total_searches'] = $stmt->fetchColumn();

    // --- KPI 2: Liczba polubień (Konwersja) ---
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM FavoriteInsurance WHERE Added_Date BETWEEN :start AND :end");
    $stmt->execute(['start' => $startTs, 'end' => $endTs]);
    $response['total_favorites'] = $stmt->fetchColumn();

    // --- KPI 3: Nowi użytkownicy ---
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM User WHERE created_at BETWEEN :start AND :end AND SUser = 0");
    $stmt->execute(['start' => $startTs, 'end' => $endTs]);
    $response['new_users'] = $stmt->fetchColumn();

    // --- WYKRES 1: Trendy wyszukiwań (Liniowy) ---
    // Grupowanie po dniach
    $stmt = $pdo->prepare("
        SELECT DATE(Date_of_search) as date, COUNT(*) as count 
        FROM SearchHistory 
        WHERE Date_of_search BETWEEN :start AND :end 
        GROUP BY DATE(Date_of_search) 
        ORDER BY date ASC
    ");
    $stmt->execute(['start' => $startTs, 'end' => $endTs]);
    $response['search_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- WYKRES 2: Popularność Marek (Kołowy) ---
    // Sprawdzamy, czego szukają użytkownicy (SearchHistory)
    $stmt = $pdo->prepare("
        SELECT Brand_Name, COUNT(*) as count 
        FROM SearchHistory 
        WHERE Date_of_search BETWEEN :start AND :end AND Brand_Name IS NOT NULL
        GROUP BY Brand_Name 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $stmt->execute(['start' => $startTs, 'end' => $endTs]);
    $response['top_brands'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- WYKRES 3: Preferencje Typu Ubezpieczenia (Słupkowy) ---
    $stmt = $pdo->prepare("
        SELECT Insurance_type, COUNT(*) as count
        FROM SearchHistory
        WHERE Date_of_search BETWEEN :start AND :end
        GROUP BY Insurance_type
    ");
    $stmt->execute(['start' => $startTs, 'end' => $endTs]);
    $response['insurance_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- WYKRES 4: Car vs Moto (Doughnut) ---
    $stmt = $pdo->prepare("
        SELECT Vehicle_Type, COUNT(*) as count
        FROM SearchHistory
        WHERE Date_of_search BETWEEN :start AND :end
        GROUP BY Vehicle_Type
    ");
    $stmt->execute(['start' => $startTs, 'end' => $endTs]);
    $response['vehicle_split'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>