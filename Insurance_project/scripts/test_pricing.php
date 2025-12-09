<?php
/**
 * Test Pricing System
 *
 * Ten skrypt testuje nowy system wyceniania polis ubezpieczeniowych
 * z uwzględnieniem VehicleRiskClassifier i opcji dodatkowych.
 */

require_once __DIR__ . '/InsuranceCalculator.php';

echo "=================================================\n";
echo "TEST SYSTEMU WYCENIANIA POLIS UBEZPIECZENIOWYCH\n";
echo "=================================================\n\n";

// Test 1: Młody kierowca z BMW (wysokie ryzyko)
echo "TEST 1: Młody kierowca (24 lata) z BMW 320d (2015r, 2000cc, Sedan)\n";
echo "Opcje: OC/AC + Standard Assistance + Accident Cover + Discount Protection\n";
echo "-------------------------------------------------\n";

$calculator = new InsuranceCalculator();

$testData1 = [
    'dob' => '2001-05-15',              // 24 lata
    'license_date' => '2022-06-01',     // 2.5 roku stażu (mniej niż 3)
    'year' => 2015,                      // 10 lat (OLD)
    'capacity' => 2000,                  // MEDIUM
    'damage' => 0,                       // Brak historii bezkolizyjnej
    'typ_ubezpieczenia' => 'OC/AC',
    'brand' => 'BMW',                    // PREMIUM
    'typ_nadwozia' => 'Sedan',           // FAMILY
    'assistance' => 'STANDARD',
    'accident_cover' => 1,
    'discount_protection' => 1
];

$result1 = $calculator->calculatePremiumWithBreakdown($testData1, 'CAR');

echo "Składka bazowa (OC/AC): " . number_format($result1['breakdown']['base_premium'], 2, ',', ' ') . " zł\n";
echo "  + Assistance (Standard): " . number_format($result1['breakdown']['assistance']['cost'], 2, ',', ' ') . " zł\n";
echo "  + Accident Cover: " . number_format($result1['breakdown']['accident_cover']['cost'], 2, ',', ' ') . " zł\n";
echo "  + Discount Protection: " . number_format($result1['breakdown']['discount_protection']['cost'], 2, ',', ' ') . " zł\n";
echo "SUMA CAŁKOWITA: " . number_format($result1['total_price'], 2, ',', ' ') . " zł/rok\n";
echo "(" . number_format($result1['total_price'] / 12, 2, ',', ' ') . " zł/miesiąc)\n\n";

// Test 2: Doświadczony kierowca z Toyotą (niskie ryzyko)
echo "\nTEST 2: Doświadczony kierowca (45 lat) z Toyota Corolla (2020r, 1500cc, Sedan)\n";
echo "Opcje: OC + Basic Assistance\n";
echo "-------------------------------------------------\n";

$testData2 = [
    'dob' => '1980-03-20',              // 45 lat
    'license_date' => '1998-07-15',     // 27 lat stażu
    'year' => 2020,                      // 5 lat (USED)
    'capacity' => 1500,                  // MEDIUM
    'damage' => 5,                       // 5 lat bez szkody = 50% zniżki
    'typ_ubezpieczenia' => 'OC',
    'brand' => 'Toyota',                 // MAINSTREAM
    'typ_nadwozia' => 'Sedan',           // FAMILY
    'assistance' => 'BASIC',
    'accident_cover' => 0,
    'discount_protection' => 0
];

$result2 = $calculator->calculatePremiumWithBreakdown($testData2, 'CAR');

echo "Składka bazowa (OC): " . number_format($result2['breakdown']['base_premium'], 2, ',', ' ') . " zł\n";
echo "  + Assistance (Basic): " . number_format($result2['breakdown']['assistance']['cost'], 2, ',', ' ') . " zł\n";
echo "SUMA CAŁKOWITA: " . number_format($result2['total_price'], 2, ',', ' ') . " zł/rok\n";
echo "(" . number_format($result2['total_price'] / 12, 2, ',', ' ') . " zł/miesiąc)\n\n";

// Test 3: Luksusowy samochód z pełną ochroną
echo "\nTEST 3: Kierowca (35 lat) z Porsche 911 (2023r, 3800cc, Coupe)\n";
echo "Opcje: OC/AC + Premium Assistance + Accident Cover + Discount Protection\n";
echo "-------------------------------------------------\n";

$testData3 = [
    'dob' => '1990-08-10',              // 35 lat
    'license_date' => '2008-06-01',     // 17 lat stażu
    'year' => 2023,                      // 2 lata (NEW)
    'capacity' => 3800,                  // VERY_LARGE
    'damage' => 3,                       // 3 lata bez szkody = 30% zniżki
    'typ_ubezpieczenia' => 'OC/AC',
    'brand' => 'Porsche',                // LUXURY
    'typ_nadwozia' => 'Coupe',           // SPORT
    'assistance' => 'PREMIUM',
    'accident_cover' => 1,
    'discount_protection' => 1
];

$result3 = $calculator->calculatePremiumWithBreakdown($testData3, 'CAR');

echo "Składka bazowa (OC/AC): " . number_format($result3['breakdown']['base_premium'], 2, ',', ' ') . " zł\n";
echo "  + Assistance (Premium): " . number_format($result3['breakdown']['assistance']['cost'], 2, ',', ' ') . " zł\n";
echo "  + Accident Cover: " . number_format($result3['breakdown']['accident_cover']['cost'], 2, ',', ' ') . " zł\n";
echo "  + Discount Protection: " . number_format($result3['breakdown']['discount_protection']['cost'], 2, ',', ' ') . " zł\n";
echo "SUMA CAŁKOWITA: " . number_format($result3['total_price'], 2, ',', ' ') . " zł/rok\n";
echo "(" . number_format($result3['total_price'] / 12, 2, ',', ' ') . " zł/miesiąc)\n\n";

// Test 4: Budget car z minimalną ochroną
echo "\nTEST 4: Kierowca (30 lat) z Dacia Sandero (2018r, 900cc, Hatchback)\n";
echo "Opcje: OC tylko (bez assistance)\n";
echo "-------------------------------------------------\n";

$testData4 = [
    'dob' => '1995-02-14',              // 30 lat
    'license_date' => '2013-05-20',     // 12 lat stażu
    'year' => 2018,                      // 7 lat (USED)
    'capacity' => 900,                   // SMALL
    'damage' => 2,                       // 2 lata bez szkody = 20% zniżki
    'typ_ubezpieczenia' => 'OC',
    'brand' => 'Dacia',                  // BUDGET
    'typ_nadwozia' => 'Hatchback',       // PRACTICAL
    'assistance' => 'NONE',
    'accident_cover' => 0,
    'discount_protection' => 0
];

$result4 = $calculator->calculatePremiumWithBreakdown($testData4, 'CAR');

echo "Składka bazowa (OC): " . number_format($result4['breakdown']['base_premium'], 2, ',', ' ') . " zł\n";
echo "SUMA CAŁKOWITA: " . number_format($result4['total_price'], 2, ',', ' ') . " zł/rok\n";
echo "(" . number_format($result4['total_price'] / 12, 2, ',', ' ') . " zł/miesiąc)\n\n";

echo "\n=================================================\n";
echo "WSZYSTKIE TESTY ZAKOŃCZONE\n";
echo "=================================================\n";
?>
