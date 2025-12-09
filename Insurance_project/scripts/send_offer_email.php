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
require_once __DIR__ . '/InsuranceCalculator.php';

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

    // 2. Pobierz szczegóły oferty z FavoriteInsurance (z parametrami wyszukiwania)
    $offerStmt = $pdo->prepare("
        SELECT
            f.Brand,
            f.Body_type as Fav_Body_type,
            f.Production_year,
            f.Engine_capacity,
            f.DOB,
            f.License_date,
            f.Damage_free_years,
            f.Assistance_level,
            f.Accident_cover,
            f.Discount_protection,
            COALESCE(c.Insurance_name, m.Insurance_name) as Insurance_name,
            COALESCE(c.Insurance_type, m.Insurance_type) as Insurance_type,
            COALESCE(c.Use_type, m.Use_type) as Use_type,
            COALESCE(c.License_release_date, m.License_release_date) as License_release_date,
            COALESCE(c.Body_type, m.Motorcycle_type, 'Nieznany') as Body_type
        FROM FavoriteInsurance f
        LEFT JOIN CarInsurance c
            ON f.Insurance_ID = c.CarInsurance_ID AND f.Insurance_Type = 'CAR'
        LEFT JOIN MotorcycleInsurance m
            ON f.Insurance_ID = m.MotorcycleInsurance_ID AND f.Insurance_Type = 'MOTORCYCLE'
        WHERE f.Users_ID = :user_id AND f.Insurance_ID = :id AND f.Insurance_Type = :type
    ");
    $offerStmt->execute([
        ':user_id' => $userData['Users_ID'],
        ':id' => $insuranceId,
        ':type' => $insuranceType
    ]);
    $offer = $offerStmt->fetch(PDO::FETCH_ASSOC);

    if (!$offer) {
        // Fallback: spróbuj pobrać z VIEW Insurance (jeśli nie w ulubionych)
        $offerStmt = $pdo->prepare("
            SELECT * FROM Insurance
            WHERE Insurance_ID = :id AND Vehicle_Category = :type
        ");
        $offerStmt->execute([':id' => $insuranceId, ':type' => $insuranceType]);
        $offer = $offerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$offer) {
            throw new Exception("Oferta nie istnieje.");
        }

        // Użyj parametrów z sesji jeśli dostępne
        $searchParams = $_SESSION['last_search_params'] ?? [];
        $offer['Brand'] = $searchParams['brand'] ?? null;
        $offer['Fav_Body_type'] = $searchParams['typ_nadwozia'] ?? null;
        $offer['Production_year'] = $searchParams['year'] ?? null;
        $offer['Engine_capacity'] = $searchParams['capacity'] ?? 1600;
        $offer['DOB'] = $searchParams['dob'] ?? null;
        $offer['License_date'] = $searchParams['license_date'] ?? null;
        $offer['Damage_free_years'] = $searchParams['damage'] ?? 0;
        $offer['Assistance_level'] = $searchParams['assistance'] ?? 'NONE';
        $offer['Accident_cover'] = $searchParams['accident_cover'] ?? 0;
        $offer['Discount_protection'] = $searchParams['discount_protection'] ?? 0;
    }

    // 3. Przelicz cenę dynamicznie
    if ($offer['Brand'] && $offer['Production_year']) {
        $calculator = new InsuranceCalculator();
        $calcData = [
            'dob' => $offer['DOB'] ?? '',
            'license_date' => $offer['License_date'] ?? '',
            'year' => $offer['Production_year'],
            'capacity' => $offer['Engine_capacity'] ?? 1600,
            'damage' => $offer['Damage_free_years'] ?? 0,
            'typ_ubezpieczenia' => $offer['Insurance_type'] ?? 'OC',
            'brand' => $offer['Brand'],
            'typ_nadwozia' => $offer['Fav_Body_type'] ?? $offer['Body_type'],
            'assistance' => $offer['Assistance_level'] ?? 'NONE',
            'accident_cover' => $offer['Accident_cover'] ?? 0,
            'discount_protection' => $offer['Discount_protection'] ?? 0
        ];

        $priceResult = $calculator->calculatePremiumWithBreakdown($calcData, $insuranceType);

        // Różnicowanie cen per firma
        $companyFactor = (crc32($offer['Insurance_name']) % 20) / 100;
        $finalPrice = $priceResult['total_price'] * (1.0 + $companyFactor);

        $offer['Price'] = $finalPrice;
        $offer['price_breakdown'] = $priceResult['breakdown'];
    } else {
        $offer['Price'] = 0;
        $offer['price_breakdown'] = null;
    }

    // 4. Wygeneruj PDF
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
