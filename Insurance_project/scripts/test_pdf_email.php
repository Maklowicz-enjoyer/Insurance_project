<?php
/**
 * Test script for PDF email functionality
 * Run this to test PDF generation and email sending
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/PdfService.php';

// Test data - symulacja oferty z bazy
$testOffer = [
    'Insurance_ID' => 999,
    'Insurance_name' => 'Test PZU',
    'Insurance_type' => 'OC/AC',
    'Use_type' => 'PRYWATNIE',
    'License_release_date' => '2025-12-31',
    'Vehicle_Category' => 'CAR',
    'Typ_nadwozia' => 'Sedan',
    'Planned_mileage' => 15000,
    'Price' => 1234.56
];

$testUser = [
    'email' => 'test@example.com',
    'Wiek' => 35
];

echo "=== PDF Email Test ===\n\n";

// 1. Test PDF generation
echo "1. Generating PDF...\n";
try {
    $pdfService = new PdfService();
    $pdfContent = $pdfService->generateOfferPdf($testOffer, $testUser);

    if (empty($pdfContent)) {
        echo "ERROR: PDF content is empty!\n";
        exit(1);
    }

    echo "SUCCESS: PDF generated, size: " . strlen($pdfContent) . " bytes\n\n";

    // Save PDF to file for manual inspection
    $testFile = '/tmp/test_offer.pdf';
    file_put_contents($testFile, $pdfContent);
    echo "PDF saved to: $testFile\n\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Test email sending
echo "2. Sending email...\n";
try {
    $emailService = new EmailService();
    $result = $emailService->sendOfferEmail(
        $testUser['email'],
        $testOffer['Insurance_name'],
        $pdfContent,
        'Test_Oferta.pdf'
    );

    if ($result) {
        echo "SUCCESS: Email sent!\n";
        echo "Check MailDev at http://localhost:1080\n";
    } else {
        echo "ERROR: Email send failed!\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
