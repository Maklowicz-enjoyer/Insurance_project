<?php
/**
 * VehicleRiskClassifier
 *
 * System klasyfikacji pojazdów do grup ryzyka.
 * Każda grupa ma przypisany współczynnik (multiplier) wpływający na cenę polisy.
 *
 * JAK TO DZIAŁA (dla 12-latka):
 *
 * 1. Bierzemy auto (np. BMW 320d z 2015 roku, 2000cc, Sedan)
 * 2. Sprawdzamy w której grupie jest każda cecha:
 *    - Marka BMW → Segment PREMIUM (mnożnik 1.4)
 *    - Nadwozie Sedan → Typ FAMILY (mnożnik 1.0)
 *    - Silnik 2000cc → Pojemność MEDIUM (mnożnik 1.15)
 *    - Rok 2015 → Wiek USED (mnożnik 1.10)
 * 3. Mnożymy wszystkie współczynniki: 1.4 × 1.0 × 1.15 × 1.10 = 1.771
 * 4. Bazowa cena 500 zł × 1.771 = 885.50 zł
 *
 * Proste!
 */

class VehicleRiskClassifier {

    // ═══════════════════════════════════════════════════════════
    // SEGMENT RYZYKA (według marki)
    // ═══════════════════════════════════════════════════════════

    private const SEGMENTS = [
        'BUDGET' => [
            'brands' => ['Dacia', 'Skoda', 'Seat', 'Kia', 'Hyundai', 'Renault', 'Citroen', 'Peugeot', 'Fiat'],
            'multiplier' => 0.85,
            'description' => 'Auta ekonomiczne, tanie w naprawie'
        ],
        'MAINSTREAM' => [
            'brands' => ['Toyota', 'Volkswagen', 'Ford', 'Opel', 'Mazda', 'Honda', 'Nissan', 'Subaru'],
            'multiplier' => 1.0,
            'description' => 'Popularne marki, średnie koszty'
        ],
        'PREMIUM' => [
            'brands' => ['BMW', 'Audi', 'Mercedes-Benz', 'Volvo', 'Lexus', 'Infiniti', 'Land Rover', 'Jaguar'],
            'multiplier' => 1.4,
            'description' => 'Drogie auta, wysokie koszty napraw'
        ],
        'LUXURY' => [
            'brands' => ['Porsche', 'Ferrari', 'Lamborghini', 'Bentley', 'Rolls-Royce', 'Maserati', 'Aston Martin'],
            'multiplier' => 2.2,
            'description' => 'Samochody luksusowe, bardzo drogie'
        ]
    ];

    // ═══════════════════════════════════════════════════════════
    // TYP NADWOZIA (jak jest używany?)
    // ═══════════════════════════════════════════════════════════

    private const BODY_TYPES = [
        'FAMILY' => [
            'types' => ['Sedan', 'Kombi'],
            'multiplier' => 1.0,
            'description' => 'Rodzinne auta, ostrożna jazda'
        ],
        'PRACTICAL' => [
            'types' => ['SUV', 'Hatchback', 'Kompakt'],
            'multiplier' => 1.1,
            'description' => 'Praktyczne, miejska jazda'
        ],
        'SPORT' => [
            'types' => ['Coupe', 'Kabriolet'],
            'multiplier' => 1.35,
            'description' => 'Sportowe, dynamiczna jazda'
        ]
    ];

    // ═══════════════════════════════════════════════════════════
    // POJEMNOŚĆ SILNIKA (moc = ryzyko)
    // ═══════════════════════════════════════════════════════════

    private const ENGINE_CAPACITY = [
        'SMALL' => [
            'range' => [0, 1400],
            'multiplier' => 0.90,
            'description' => 'Małe, ekonomiczne silniki'
        ],
        'MEDIUM' => [
            'range' => [1401, 2000],
            'multiplier' => 1.15,
            'description' => 'Średnie silniki, popularne'
        ],
        'LARGE' => [
            'range' => [2001, 3000],
            'multiplier' => 1.35,
            'description' => 'Duże silniki, mocne'
        ],
        'VERY_LARGE' => [
            'range' => [3001, 10000],
            'multiplier' => 1.8,
            'description' => 'Bardzo duże silniki, sportowe'
        ]
    ];

    // ═══════════════════════════════════════════════════════════
    // WIEK POJAZDU (starsze = większe ryzyko awarii)
    // ═══════════════════════════════════════════════════════════

    private const VEHICLE_AGE = [
        'NEW' => [
            'range' => [0, 3],
            'multiplier' => 0.95,
            'description' => 'Nowe auto, gwarancja'
        ],
        'USED' => [
            'range' => [4, 7],
            'multiplier' => 1.10,
            'description' => 'Używane, sprawne'
        ],
        'OLD' => [
            'range' => [8, 15],
            'multiplier' => 1.25,
            'description' => 'Stare, częste naprawy'
        ],
        'VERY_OLD' => [
            'range' => [16, 100],
            'multiplier' => 1.45,
            'description' => 'Bardzo stare, wysokie ryzyko'
        ]
    ];

    // ═══════════════════════════════════════════════════════════
    // PUBLICZNE METODY - API klasy
    // ═══════════════════════════════════════════════════════════

    /**
     * Główna metoda - oblicza całkowity współczynnik ryzyka
     *
     * @param array $vehicleData [brand, body_type, capacity, year]
     * @return array ['total_multiplier' => float, 'breakdown' => array]
     */
    public function calculateRiskMultiplier(array $vehicleData): array {
        $breakdown = [];
        $totalMultiplier = 1.0;

        // 1. Segment (marka)
        $segmentMultiplier = $this->getSegmentMultiplier($vehicleData['brand'] ?? '');
        $breakdown['segment'] = [
            'category' => $this->getSegmentName($vehicleData['brand'] ?? ''),
            'multiplier' => $segmentMultiplier
        ];
        $totalMultiplier *= $segmentMultiplier;

        // 2. Typ nadwozia
        $bodyMultiplier = $this->getBodyTypeMultiplier($vehicleData['body_type'] ?? '');
        $breakdown['body_type'] = [
            'category' => $this->getBodyTypeName($vehicleData['body_type'] ?? ''),
            'multiplier' => $bodyMultiplier
        ];
        $totalMultiplier *= $bodyMultiplier;

        // 3. Pojemność silnika
        $capacityMultiplier = $this->getEngineCapacityMultiplier($vehicleData['capacity'] ?? 1600);
        $breakdown['engine_capacity'] = [
            'category' => $this->getEngineCapacityName($vehicleData['capacity'] ?? 1600),
            'multiplier' => $capacityMultiplier
        ];
        $totalMultiplier *= $capacityMultiplier;

        // 4. Wiek pojazdu
        $vehicleAge = (int)date('Y') - ($vehicleData['year'] ?? (int)date('Y'));
        $ageMultiplier = $this->getVehicleAgeMultiplier($vehicleAge);
        $breakdown['vehicle_age'] = [
            'category' => $this->getVehicleAgeName($vehicleAge),
            'multiplier' => $ageMultiplier,
            'age_years' => $vehicleAge
        ];
        $totalMultiplier *= $ageMultiplier;

        return [
            'total_multiplier' => round($totalMultiplier, 3),
            'breakdown' => $breakdown
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // PRYWATNE METODY - Pomocnicze
    // ═══════════════════════════════════════════════════════════

    /**
     * Zwraca mnożnik dla segmentu (według marki)
     */
    private function getSegmentMultiplier(string $brand): float {
        foreach (self::SEGMENTS as $segment => $data) {
            if (in_array($brand, $data['brands'])) {
                return $data['multiplier'];
            }
        }
        return self::SEGMENTS['MAINSTREAM']['multiplier']; // Default
    }

    /**
     * Zwraca nazwę segmentu dla marki
     */
    private function getSegmentName(string $brand): string {
        foreach (self::SEGMENTS as $segment => $data) {
            if (in_array($brand, $data['brands'])) {
                return $segment . ' (' . $data['description'] . ')';
            }
        }
        return 'MAINSTREAM (Popularne marki)';
    }

    /**
     * Zwraca mnożnik dla typu nadwozia
     */
    private function getBodyTypeMultiplier(string $bodyType): float {
        foreach (self::BODY_TYPES as $category => $data) {
            if (in_array($bodyType, $data['types'])) {
                return $data['multiplier'];
            }
        }
        return self::BODY_TYPES['PRACTICAL']['multiplier']; // Default
    }

    /**
     * Zwraca nazwę kategorii typu nadwozia
     */
    private function getBodyTypeName(string $bodyType): string {
        foreach (self::BODY_TYPES as $category => $data) {
            if (in_array($bodyType, $data['types'])) {
                return $category . ' (' . $data['description'] . ')';
            }
        }
        return 'PRACTICAL (Praktyczne)';
    }

    /**
     * Zwraca mnożnik dla pojemności silnika
     */
    private function getEngineCapacityMultiplier(int $capacity): float {
        foreach (self::ENGINE_CAPACITY as $category => $data) {
            if ($capacity >= $data['range'][0] && $capacity <= $data['range'][1]) {
                return $data['multiplier'];
            }
        }
        return self::ENGINE_CAPACITY['MEDIUM']['multiplier']; // Default
    }

    /**
     * Zwraca nazwę kategorii pojemności
     */
    private function getEngineCapacityName(int $capacity): string {
        foreach (self::ENGINE_CAPACITY as $category => $data) {
            if ($capacity >= $data['range'][0] && $capacity <= $data['range'][1]) {
                return $category . ' (' . $data['description'] . ')';
            }
        }
        return 'MEDIUM (Średnie silniki)';
    }

    /**
     * Zwraca mnożnik dla wieku pojazdu
     */
    private function getVehicleAgeMultiplier(int $age): float {
        foreach (self::VEHICLE_AGE as $category => $data) {
            if ($age >= $data['range'][0] && $age <= $data['range'][1]) {
                return $data['multiplier'];
            }
        }
        return self::VEHICLE_AGE['VERY_OLD']['multiplier']; // Default dla >100 lat
    }

    /**
     * Zwraca nazwę kategorii wieku
     */
    private function getVehicleAgeName(int $age): string {
        foreach (self::VEHICLE_AGE as $category => $data) {
            if ($age >= $data['range'][0] && $age <= $data['range'][1]) {
                return $category . ' (' . $data['description'] . ')';
            }
        }
        return 'VERY_OLD (Bardzo stare)';
    }
}
