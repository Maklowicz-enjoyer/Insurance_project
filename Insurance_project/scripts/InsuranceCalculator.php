<?php

class InsuranceCalculator {
    // Bazowe stawki (Base Rate)
    private const BASE_RATE_CAR = 500.00;
    private const BASE_RATE_MOTO = 300.00;
    private const ASSISTANCE_FLAT_RATE = 240.00;

    /**
     * Główna metoda wyliczająca składkę
     */
    public function calculatePremium(array $data, string $vehicleCategory): float {
        // Jeśli wybrano samo Assistance, zwracamy stałą stawkę
        if (($data['typ_ubezpieczenia'] ?? '') === 'Assistance') {
            return self::ASSISTANCE_FLAT_RATE;
        }

        $baseRate = ($vehicleCategory === 'CAR') ? self::BASE_RATE_CAR : self::BASE_RATE_MOTO;
        $multipliers = [];

        // 1. Wiek kierowcy (Driver Age Risk)
        // Młodzi kierowcy (<26 lat) płacą więcej (Ryzyko +50%)
        // Seniorzy (>65 lat) płacą nieznacznie więcej (Ryzyko +10%)
        $age = $this->calculateAge($data['dob']);
        if ($age < 26) {
            $multipliers[] = 1.50;
        } elseif ($age > 65) {
            $multipliers[] = 1.10;
        } else {
            $multipliers[] = 1.00; // Neutralny
        }

        // 2. Staż prawa jazdy (License Experience)
        // Posiadanie prawka krócej niż 3 lata = duże ryzyko (+30%)
        $licenseYears = $this->calculateAge($data['license_date']);
        if ($licenseYears < 3) {
            $multipliers[] = 1.30;
        }

        // 3. Wiek pojazdu (Vehicle Age Depreciation)
        // Starsze auta w OC są droższe (stan techniczny), w AC składka rośnie procentowo do wartości szkody całkowitej
        $vehicleAge = date('Y') - (int)$data['year'];
        if ($vehicleAge > 15) {
            $multipliers[] = 1.20;
        } elseif ($vehicleAge > 10) {
            $multipliers[] = 1.10;
        }

        // 4. Pojemność silnika (Engine Displacement)
        // Ignorujemy model, ale pojemność jest kluczowa dla OC
        $capacity = (int)$data['capacity'];
        if ($vehicleCategory === 'CAR') {
            if ($capacity > 2500) $multipliers[] = 1.40;
            elseif ($capacity > 1900) $multipliers[] = 1.25;
            elseif ($capacity < 1200) $multipliers[] = 0.90;
        } else {
            // Dla motocykli pojemność jest krytyczna
            if ($capacity > 1000) $multipliers[] = 1.80; // "Ścigacze"
            elseif ($capacity > 600) $multipliers[] = 1.40;
            elseif ($capacity < 125) $multipliers[] = 0.70; // Motorowery/125cc
        }

        // 5. Historia szkodowa (Bonus-Malus)
        // Każdy rok bez szkody to -10%, max -60%.
        // Formularz przesyła "damage" jako "lata od ostatniej szkody"
        $damageFreeYears = (int)($data['damage'] ?? 0);
        $discount = min($damageFreeYears * 0.10, 0.60); // Max 60% zniżki
        $multipliers[] = (1.0 - $discount);

        // 6. Typ ubezpieczenia (Product Type)
        $type = $data['typ_ubezpieczenia'] ?? 'OC';
        if ($type === 'OC/AC') {
            $multipliers[] = 2.50; // AC jest zazwyczaj 150% ceny OC
        }

        // Kalkulacja końcowa: Base Rate * iloczyn wszystkich mnożników
        $finalPrice = $baseRate;
        foreach ($multipliers as $m) {
            $finalPrice *= $m;
        }

        return round($finalPrice, 2);
    }

    private function calculateAge(string $dateString): int {
        if (empty($dateString)) return 30; // Fallback dla bezpieczeństwa
        $dob = new DateTime($dateString);
        $now = new DateTime();
        return $now->diff($dob)->y;
    }
}
?>