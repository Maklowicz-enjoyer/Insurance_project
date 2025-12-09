<?php

require_once __DIR__ . '/VehicleRiskClassifier.php';

class InsuranceCalculator {
    // ═══════════════════════════════════════════════════════════
    // BAZOWE STAWKI (Base Rates)
    // ═══════════════════════════════════════════════════════════
    private const BASE_RATE_CAR = 500.00;
    private const BASE_RATE_MOTO = 300.00;

    // ═══════════════════════════════════════════════════════════
    // ASSISTANCE (Pomoc Drogowa) - 3 poziomy
    // ═══════════════════════════════════════════════════════════
    private const ASSISTANCE_RATES = [
        'NONE' => [
            'price' => 0.00,
            'description' => 'Brak pomocy drogowej',
            'coverage' => []
        ],
        'BASIC' => [
            'price' => 120.00,
            'description' => 'Podstawowa pomoc (holowanie do 50km)',
            'coverage' => ['Holowanie do 50km', 'Uruchomienie auta', 'Wymiana koła']
        ],
        'STANDARD' => [
            'price' => 240.00,
            'description' => 'Standardowa pomoc (holowanie do 200km)',
            'coverage' => ['Holowanie do 200km', 'Auto zastępcze 24h', 'Pomoc techniczna 24/7', 'Tankowanie']
        ],
        'PREMIUM' => [
            'price' => 480.00,
            'description' => 'Premium pomoc (holowanie bez limitu)',
            'coverage' => ['Holowanie bez limitu km', 'Auto zastępcze 7 dni', 'Pomoc w UE', 'Hotel/nocleg', 'Taxi']
        ]
    ];

    // ═══════════════════════════════════════════════════════════
    // ACCIDENT COVER (Nieprzewidziane Wypadki / GAP Insurance)
    // ═══════════════════════════════════════════════════════════
    // Pokrywa różnicę między wartością rynkową a kwotą do spłaty leasingu
    private const ACCIDENT_COVER_MULTIPLIER = 0.15; // +15% do składki bazowej

    // ═══════════════════════════════════════════════════════════
    // DISCOUNT PROTECTION (Ochrona Zniżek)
    // ═══════════════════════════════════════════════════════════
    // Chroni bonus-malus przed utratą po kolizji
    private const DISCOUNT_PROTECTION_MULTIPLIER = 0.12; // +12% do składki bazowej

    private VehicleRiskClassifier $riskClassifier;

    public function __construct() {
        $this->riskClassifier = new VehicleRiskClassifier();
    }

    /**
     * Główna metoda wyliczająca składkę z pełnym breakdown
     *
     * @param array $data Dane z formularza (dob, license_date, year, capacity, damage, brand, body_type, etc.)
     * @param string $vehicleCategory 'CAR' lub 'MOTORCYCLE'
     * @return array ['total_price' => float, 'breakdown' => array]
     */
    public function calculatePremiumWithBreakdown(array $data, string $vehicleCategory): array {
        // ═══════════════════════════════════════════════════════════
        // CZĘŚĆ 1: BAZOWA SKŁADKA (OC/AC)
        // ═══════════════════════════════════════════════════════════
        $basePremium = $this->calculateBasePremium($data, $vehicleCategory);

        // ═══════════════════════════════════════════════════════════
        // CZĘŚĆ 2: OPCJE DODATKOWE
        // ═══════════════════════════════════════════════════════════
        $assistanceLevel = strtoupper($data['assistance'] ?? 'NONE');
        $assistanceCost = self::ASSISTANCE_RATES[$assistanceLevel]['price'] ?? 0.00;

        $accidentCover = (bool)($data['accident_cover'] ?? false);
        $accidentCoverCost = $accidentCover ? ($basePremium * self::ACCIDENT_COVER_MULTIPLIER) : 0.00;

        $discountProtection = (bool)($data['discount_protection'] ?? false);
        $discountProtectionCost = $discountProtection ? ($basePremium * self::DISCOUNT_PROTECTION_MULTIPLIER) : 0.00;

        // ═══════════════════════════════════════════════════════════
        // CZĘŚĆ 3: SUMA KOŃCOWA
        // ═══════════════════════════════════════════════════════════
        $totalPrice = $basePremium + $assistanceCost + $accidentCoverCost + $discountProtectionCost;

        return [
            'total_price' => round($totalPrice, 2),
            'breakdown' => [
                'base_premium' => round($basePremium, 2),
                'assistance' => [
                    'level' => $assistanceLevel,
                    'cost' => round($assistanceCost, 2),
                    'description' => self::ASSISTANCE_RATES[$assistanceLevel]['description'] ?? ''
                ],
                'accident_cover' => [
                    'enabled' => $accidentCover,
                    'cost' => round($accidentCoverCost, 2)
                ],
                'discount_protection' => [
                    'enabled' => $discountProtection,
                    'cost' => round($discountProtectionCost, 2)
                ]
            ]
        ];
    }

    /**
     * Stara metoda zachowana dla kompatybilności wstecznej
     */
    public function calculatePremium(array $data, string $vehicleCategory): float {
        $result = $this->calculatePremiumWithBreakdown($data, $vehicleCategory);
        return $result['total_price'];
    }

    /**
     * Oblicza bazową składkę OC/AC (bez opcji dodatkowych)
     */
    private function calculateBasePremium(array $data, string $vehicleCategory): float {
        // Jeśli wybrano samo Assistance (legacy compatibility)
        if (($data['typ_ubezpieczenia'] ?? '') === 'Assistance') {
            return 0.00; // Assistance jest dodatkową opcją, nie podstawową
        }

        $baseRate = ($vehicleCategory === 'CAR') ? self::BASE_RATE_CAR : self::BASE_RATE_MOTO;
        $multipliers = [];

        // ═══════════════════════════════════════════════════════════
        // 1. KLASYFIKACJA POJAZDU (VehicleRiskClassifier)
        // ═══════════════════════════════════════════════════════════
        $vehicleData = [
            'brand' => $data['brand'] ?? '',
            'body_type' => $data['typ_nadwozia'] ?? '',
            'capacity' => (int)($data['capacity'] ?? 1600),
            'year' => (int)($data['year'] ?? date('Y'))
        ];

        $riskResult = $this->riskClassifier->calculateRiskMultiplier($vehicleData);
        $multipliers[] = $riskResult['total_multiplier'];

        // ═══════════════════════════════════════════════════════════
        // 2. WIEK KIEROWCY (Driver Age Risk)
        // ═══════════════════════════════════════════════════════════
        // Młodzi kierowcy (<26 lat) płacą więcej (Ryzyko +50%)
        // Seniorzy (>65 lat) płacą nieznacznie więcej (Ryzyko +10%)
        $age = $this->calculateAge($data['dob'] ?? '');
        if ($age < 26) {
            $multipliers[] = 1.50;
        } elseif ($age > 65) {
            $multipliers[] = 1.10;
        } else {
            $multipliers[] = 1.00; // Neutralny
        }

        // ═══════════════════════════════════════════════════════════
        // 3. STAŻ PRAWA JAZDY (License Experience)
        // ═══════════════════════════════════════════════════════════
        // Posiadanie prawka krócej niż 3 lata = duże ryzyko (+30%)
        $licenseYears = $this->calculateAge($data['license_date'] ?? '');
        if ($licenseYears < 3) {
            $multipliers[] = 1.30;
        }

        // ═══════════════════════════════════════════════════════════
        // 4. HISTORIA SZKODOWA (Bonus-Malus)
        // ═══════════════════════════════════════════════════════════
        // Każdy rok bez szkody to -10%, max -60%.
        // Formularz przesyła "damage" jako "lata od ostatniej szkody"
        $damageFreeYears = (int)($data['damage'] ?? 0);
        $discount = min($damageFreeYears * 0.10, 0.60); // Max 60% zniżki
        $multipliers[] = (1.0 - $discount);

        // ═══════════════════════════════════════════════════════════
        // 5. TYP UBEZPIECZENIA (Product Type)
        // ═══════════════════════════════════════════════════════════
        $type = $data['typ_ubezpieczenia'] ?? 'OC';
        if ($type === 'OC/AC') {
            $multipliers[] = 2.50; // AC jest zazwyczaj 150% ceny OC
        }

        // ═══════════════════════════════════════════════════════════
        // KALKULACJA KOŃCOWA
        // ═══════════════════════════════════════════════════════════
        $finalPrice = $baseRate;
        foreach ($multipliers as $m) {
            $finalPrice *= $m;
        }

        return round($finalPrice, 2);
    }

    /**
     * Oblicza wiek na podstawie daty
     */
    private function calculateAge(string $dateString): int {
        if (empty($dateString)) return 30; // Fallback dla bezpieczeństwa
        try {
            $dob = new DateTime($dateString);
            $now = new DateTime();
            return $now->diff($dob)->y;
        } catch (Exception $e) {
            return 30; // Fallback
        }
    }

    /**
     * Zwraca informacje o wszystkich poziomach assistance
     */
    public static function getAssistanceLevels(): array {
        return self::ASSISTANCE_RATES;
    }

    /**
     * Zwraca opis opcji Accident Cover
     */
    public static function getAccidentCoverInfo(): array {
        return [
            'description' => 'Pokrycie GAP - różnica między wartością rynkową a kwotą do spłaty',
            'multiplier' => self::ACCIDENT_COVER_MULTIPLIER,
            'recommended_for' => ['Leasing', 'Nowe auto', 'Kredyt']
        ];
    }

    /**
     * Zwraca opis opcji Discount Protection
     */
    public static function getDiscountProtectionInfo(): array {
        return [
            'description' => 'Ochrona zniżek bonus-malus po kolizji',
            'multiplier' => self::DISCOUNT_PROTECTION_MULTIPLIER,
            'recommended_for' => ['Wysoki bonus (50%+)', 'Młodzi kierowcy', 'Duży ruch miejski']
        ];
    }
}
?>