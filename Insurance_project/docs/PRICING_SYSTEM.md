# System Wyceniania Polis Ubezpieczeniowych

**Data:** 2025-12-02
**Wersja:** 2.0
**Status:** ✅ Zaimplementowany i przetestowany

---

## 📋 Spis Treści

1. [Przegląd Systemu](#przegląd-systemu)
2. [Klasyfikacja Ryzyka Pojazdu](#klasyfikacja-ryzyka-pojazdu)
3. [Opcje Dodatkowe](#opcje-dodatkowe)
4. [Struktura Bazy Danych](#struktura-bazy-danych)
5. [Przykłady Wyceny](#przykłady-wyceny)
6. [API i Użycie](#api-i-użycie)

---

## Przegląd Systemu

Nowy system wyceniania polis ubezpieczeniowych wykorzystuje **wieloczynnikową analizę ryzyka** do obliczania składek. System składa się z dwóch głównych komponentów:

### 1. VehicleRiskClassifier

Klasyfikuje pojazd według 4 kryteriów:

#### a) Segment Marki (Brand Segment)
- **BUDGET** (0.85x) - Dacia, Skoda, Seat, Kia, Hyundai, Renault, Citroen, Peugeot, Fiat
- **MAINSTREAM** (1.0x) - Toyota, VW, Ford, Opel, Mazda, Honda, Nissan, Subaru
- **PREMIUM** (1.4x) - BMW, Audi, Mercedes-Benz, Volvo, Lexus, Infiniti, Land Rover, Jaguar
- **LUXURY** (2.2x) - Porsche, Ferrari, Lamborghini, Bentley, Rolls-Royce, Maserati, Aston Martin

#### b) Typ Nadwozia (Body Type)
- **FAMILY** (1.0x) - Sedan, Kombi (rodzinna jazda, ostrożna)
- **PRACTICAL** (1.1x) - SUV, Hatchback, Kompakt (miejska jazda)
- **SPORT** (1.35x) - Coupe, Kabriolet (dynamiczna jazda)

#### c) Pojemność Silnika (Engine Capacity)
- **SMALL** (0.90x) - 0-1400 cm³ (małe, ekonomiczne)
- **MEDIUM** (1.15x) - 1401-2000 cm³ (średnie, popularne)
- **LARGE** (1.35x) - 2001-3000 cm³ (duże, mocne)
- **VERY_LARGE** (1.8x) - 3001+ cm³ (bardzo mocne, sportowe)

#### d) Wiek Pojazdu (Vehicle Age)
- **NEW** (0.95x) - 0-3 lata (nowy, gwarancja)
- **USED** (1.10x) - 4-7 lat (używany, sprawny)
- **OLD** (1.25x) - 8-15 lat (stary, częste naprawy)
- **VERY_OLD** (1.45x) - 16+ lat (bardzo stary, wysokie ryzyko)

**Formuła:**
`Vehicle Risk Multiplier = Segment × Body Type × Engine Capacity × Vehicle Age`

### 2. InsuranceCalculator

Oblicza składkę bazową (OC/AC) na podstawie:

#### Czynniki Bazowe
- **Bazowa stawka:** 500 zł (CAR) lub 300 zł (MOTORCYCLE)
- **Klasyfikacja pojazdu:** Mnożnik z VehicleRiskClassifier
- **Wiek kierowcy:**
  - <26 lat: +50% (młody kierowca)
  - 26-65 lat: 0% (neutralny)
  - >65 lat: +10% (senior)
- **Staż prawa jazdy:**
  - <3 lata: +30%
  - ≥3 lata: 0%
- **Historia szkodowa (Bonus-Malus):**
  - Każdy rok bez szkody: -10% (max -60%)
- **Typ ubezpieczenia:**
  - OC: 1.0x
  - OC/AC: 2.5x (AC jest 150% ceny OC)

**Formuła:**
`Base Premium = Base Rate × Vehicle Risk × Age Factor × License Factor × Bonus-Malus × Insurance Type`

---

## Opcje Dodatkowe

System oferuje 3 opcje dodatkowe, które zwiększają składkę:

### 1. Assistance (Pomoc Drogowa)

| Poziom | Cena | Zakres |
|--------|------|--------|
| **NONE** | 0 zł | Brak pomocy |
| **BASIC** | 120 zł/rok | Holowanie do 50km, uruchomienie auta, wymiana koła |
| **STANDARD** | 240 zł/rok | Holowanie do 200km, auto zastępcze 24h, pomoc 24/7, tankowanie |
| **PREMIUM** | 480 zł/rok | Holowanie bez limitu, auto zastępcze 7 dni, pomoc w UE, hotel/nocleg |

### 2. Accident Cover (GAP Insurance)

**Cena:** +15% składki bazowej
**Opis:** Pokrywa różnicę między wartością rynkową pojazdu a kwotą do spłaty (leasing/kredyt)
**Rekomendowane dla:** Leasing, nowe auto, kredyt

### 3. Discount Protection (Ochrona Zniżek)

**Cena:** +12% składki bazowej
**Opis:** Chroni bonus-malus przed utratą po pierwszej kolizji w roku polisowym
**Rekomendowane dla:** Wysoki bonus (50%+), młodzi kierowcy, duży ruch miejski

**Formuła końcowa:**
`Total Price = Base Premium + Assistance + (Base Premium × Accident Cover) + (Base Premium × Discount Protection)`

---

## Struktura Bazy Danych

### Nowe Kolumny w `CarInsurance` i `MotorcycleInsurance`

```sql
ALTER TABLE CarInsurance ADD (
    Assistance_level ENUM('NONE', 'BASIC', 'STANDARD', 'PREMIUM') DEFAULT 'NONE',
    Accident_cover TINYINT(1) DEFAULT 0,
    Discount_protection TINYINT(1) DEFAULT 0,
    Assistance_cost DECIMAL(10,2) DEFAULT 0.00,
    Accident_cover_cost DECIMAL(10,2) DEFAULT 0.00,
    Discount_protection_cost DECIMAL(10,2) DEFAULT 0.00,
    Base_premium DECIMAL(10,2) DEFAULT 0.00
);
```

### Nowe Kolumny w `SearchHistory`

```sql
ALTER TABLE SearchHistory ADD (
    Assistance_level ENUM('NONE', 'BASIC', 'STANDARD', 'PREMIUM') DEFAULT 'NONE',
    Accident_cover TINYINT(1) DEFAULT 0,
    Discount_protection TINYINT(1) DEFAULT 0
);
```

---

## Przykłady Wyceny

### Przykład 1: Młody Kierowca + BMW (Wysokie Ryzyko)

**Dane:**
- Kierowca: 24 lata, prawo jazdy 2.5 roku
- Pojazd: BMW 320d (2015r, 2000cc, Sedan)
- Ubezpieczenie: OC/AC
- Opcje: Standard Assistance + Accident Cover + Discount Protection

**Wycena:**
```
Składka bazowa:         3,774.38 zł
+ Assistance Standard:    240.00 zł
+ Accident Cover (15%):   566.16 zł
+ Discount Prot. (12%):   452.93 zł
─────────────────────────────────
SUMA:                   5,033.46 zł/rok
                         (419.46 zł/miesiąc)
```

### Przykład 2: Doświadczony Kierowca + Toyota (Niskie Ryzyko)

**Dane:**
- Kierowca: 45 lat, prawo jazdy 27 lat, 5 lat bez szkody
- Pojazd: Toyota Corolla (2020r, 1500cc, Sedan)
- Ubezpieczenie: OC tylko
- Opcje: Basic Assistance

**Wycena:**
```
Składka bazowa:         316.25 zł
+ Assistance Basic:     120.00 zł
─────────────────────────────────
SUMA:                   436.25 zł/rok
                         (36.35 zł/miesiąc)
```

### Przykład 3: Luksusowy Samochód (Najwyższe Ryzyko)

**Dane:**
- Kierowca: 35 lat, prawo jazdy 17 lat, 3 lata bez szkody
- Pojazd: Porsche 911 (2023r, 3800cc, Coupe)
- Ubezpieczenie: OC/AC
- Opcje: Premium Assistance + Accident Cover + Discount Protection

**Wycena:**
```
Składka bazowa:         4,444.13 zł
+ Assistance Premium:     480.00 zł
+ Accident Cover (15%):   666.62 zł
+ Discount Prot. (12%):   533.30 zł
─────────────────────────────────
SUMA:                   6,124.05 zł/rok
                         (510.34 zł/miesiąc)
```

### Przykład 4: Budget Car (Minimalna Ochrona)

**Dane:**
- Kierowca: 30 lat, prawo jazdy 12 lat, 2 lata bez szkody
- Pojazd: Dacia Sandero (2018r, 900cc, Hatchback)
- Ubezpieczenie: OC tylko
- Opcje: Brak

**Wycena:**
```
Składka bazowa:         370.40 zł
─────────────────────────────────
SUMA:                   370.40 zł/rok
                         (30.87 zł/miesiąc)
```

---

## API i Użycie

### Przykład Użycia w PHP

```php
<?php
require_once 'InsuranceCalculator.php';

$calculator = new InsuranceCalculator();

$data = [
    'dob' => '1990-05-15',
    'license_date' => '2010-06-01',
    'year' => 2020,
    'capacity' => 1600,
    'damage' => 3,
    'typ_ubezpieczenia' => 'OC/AC',
    'brand' => 'Toyota',
    'typ_nadwozia' => 'Sedan',
    'assistance' => 'STANDARD',
    'accident_cover' => 1,
    'discount_protection' => 0
];

$result = $calculator->calculatePremiumWithBreakdown($data, 'CAR');

echo "Składka bazowa: " . $result['breakdown']['base_premium'] . " zł\n";
echo "Assistance: " . $result['breakdown']['assistance']['cost'] . " zł\n";
echo "Accident Cover: " . $result['breakdown']['accident_cover']['cost'] . " zł\n";
echo "Discount Protection: " . $result['breakdown']['discount_protection']['cost'] . " zł\n";
echo "SUMA: " . $result['total_price'] . " zł\n";
?>
```

### Metody Pomocnicze

```php
// Pobierz informacje o poziomach assistance
$levels = InsuranceCalculator::getAssistanceLevels();

// Pobierz informacje o Accident Cover
$accidentInfo = InsuranceCalculator::getAccidentCoverInfo();

// Pobierz informacje o Discount Protection
$discountInfo = InsuranceCalculator::getDiscountProtectionInfo();
```

---

## Pliki Systemu

### Nowe Pliki
- `scripts/VehicleRiskClassifier.php` - Klasyfikacja ryzyka pojazdu
- `scripts/test_pricing.php` - Testy systemu wyceniania
- `migrations/add_additional_insurance_options.sql` - Migracja bazy danych
- `docs/PRICING_SYSTEM.md` - Niniejsza dokumentacja

### Zmodyfikowane Pliki
- `scripts/InsuranceCalculator.php` - Rozszerzona logika wyceniania
- `scripts/manage_insurance.php` - Integracja z nowym systemem
- `html/main.php` - Formularz z nowymi opcjami
- `css/main.css` - Style dla nowych pól

---

## Testowanie

Aby przetestować system, uruchom:

```bash
docker exec skanpolis_php php /var/www/html/scripts/test_pricing.php
```

---

## Kontakt i Wsparcie

W razie pytań lub problemów, sprawdź:
- Logi PHP: `docker logs skanpolis_php`
- Dokumentację w `CLAUDE.md`
- Testy w `scripts/test_pricing.php`

---

**Wersja dokumentacji:** 1.0
**Ostatnia aktualizacja:** 2025-12-02
**Autor:** Claude Code (Anthropic)
