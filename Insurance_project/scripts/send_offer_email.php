<?php
/**
 * send_offer_email.php
 *
 * Endpoint do wysyłania polubionej oferty ubezpieczeniowej na email użytkownika
 * z załącznikiem PDF zawierającym szczegóły oferty.
 *
 * Security:
 * - Wymaga zalogowania (session_check)
 * - CSRF token validation
 * - Prepared statements
 * - Input validation
 */

// 1. Bezpieczeństwo sesji
require_once __DIR__ . '/session_check.php';

// 2. Połączenie z bazą
require_once __DIR__ . '/db_connect.php';

// 3. Serwisy
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/PdfService.php';

// 4. CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obsługa tylko POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../html/main.php");
    exit;
}

// Weryfikacja tokenu CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Błąd bezpieczeństwa CSRF. Odśwież stronę.");
}

// Walidacja parametrów
$insuranceId = filter_input(INPUT_POST, 'insurance_id', FILTER_VALIDATE_INT);
$insuranceType = $_POST['insurance_type'] ?? '';

if (!$insuranceId || !in_array($insuranceType, ['CAR', 'MOTORCYCLE'])) {
    $_SESSION['error_message'] = "Nieprawidłowe dane oferty.";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../html/main.php'));
    exit;
}

try {
    // 1. Pobierz dane użytkownika
    $userEmail = $_SESSION['user_email'];
    $userStmt = $pdo->prepare("SELECT Users_ID, email, Wiek FROM User WHERE email = :email");
    $userStmt->execute([':email' => $userEmail]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        throw new Exception("Nie znaleziono użytkownika.");
    }

    // 2. Pobierz szczegóły oferty z VIEW Insurance
    $offerStmt = $pdo->prepare("
        SELECT *
        FROM Insurance
        WHERE Insurance_ID = :id AND Vehicle_Category = :type
    ");
    $offerStmt->execute([':id' => $insuranceId, ':type' => $insuranceType]);
    $offer = $offerStmt->fetch(PDO::FETCH_ASSOC);

    if (!$offer) {
        throw new Exception("Oferta nie istnieje.");
    }

    // 3. Wygeneruj PDF
    $pdfService = new PdfService();
    $pdfContent = $pdfService->generateOfferPdf($offer, $userData);

    // Debug: Sprawdź czy PDF został wygenerowany
    if (empty($pdfContent)) {
        error_log("PDF generation failed - empty content");
        throw new Exception("Nie udało się wygenerować PDF.");
    }
    error_log("PDF generated successfully, size: " . strlen($pdfContent) . " bytes");

    // 4. Przygotuj nazwę pliku PDF (bezpieczna nazwa)
    $safeInsuranceName = preg_replace('/[^a-zA-Z0-9]/', '_', $offer['Insurance_name']);
    $pdfFilename = "SkanPolis_Oferta_{$safeInsuranceName}.pdf";

    // 5. Wyślij email z PDF
    $emailService = new EmailService();
    $emailSent = $emailService->sendOfferEmail(
        $userEmail,
        $offer['Insurance_name'],
        $pdfContent,
        $pdfFilename
    );

    error_log("Email send result: " . ($emailSent ? "SUCCESS" : "FAILED"));

    if ($emailSent) {
        $_SESSION['success_message'] = "Oferta została wysłana na Twój adres email!";
    } else {
        $_SESSION['error_message'] = "Nie udało się wysłać emaila. Spróbuj ponownie później.";
    }

} catch (PDOException $e) {
    error_log("Database error in send_offer_email.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Błąd bazy danych.";

} catch (Exception $e) {
    error_log("Error in send_offer_email.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Wystąpił błąd podczas wysyłania oferty.";
}

// Przekierowanie z powrotem do strony szczegółów oferty
$redirectUrl = "../html/detail.php?id={$insuranceId}&type={$insuranceType}";
header("Location: {$redirectUrl}");
exit;
