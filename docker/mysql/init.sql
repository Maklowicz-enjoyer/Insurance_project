-- ============================================
-- SKANPOLIS - Database Schema (FULL FIX)
-- ============================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ============================================
-- 1. Tabela użytkowników
-- ============================================
CREATE TABLE IF NOT EXISTS `User` (
  `Users_ID` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `haslo` VARCHAR(255) NOT NULL,
  `SUser` TINYINT(1) NOT NULL DEFAULT 0,
  `Wiek` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Users_ID`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. Tabela pojazdów użytkownika
-- ============================================
CREATE TABLE IF NOT EXISTS `Vehicle` (
  `Vehicle_ID` INT NOT NULL AUTO_INCREMENT,
  `Users_ID` INT NOT NULL,
  `Vehicle_type` ENUM('CAR','MOTORCYCLE') NOT NULL,
  `Brand` VARCHAR(50) NOT NULL,
  `Model` VARCHAR(50) NOT NULL,
  `Year` INT NOT NULL,
  `Engine_capacity` INT DEFAULT NULL,
  `Power_HP` INT DEFAULT NULL,
  `VIN` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Vehicle_ID`),
  KEY `idx_users_id` (`Users_ID`),
  CONSTRAINT `Vehicle_User_FK` FOREIGN KEY (`Users_ID`) REFERENCES `User` (`Users_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. Tabela historii wyszukiwań (POPRAWIONA)
-- ============================================
CREATE TABLE IF NOT EXISTS `SearchHistory` (
  `Search_ID` INT NOT NULL AUTO_INCREMENT,
  `Users_ID` INT NOT NULL,
  `Vehicle_ID` INT DEFAULT NULL,
  `Vehicle_Type` ENUM('CAR', 'MOTORCYCLE') NOT NULL DEFAULT 'CAR', -- NOWE
  `Brand_Name` VARCHAR(50) DEFAULT NULL,        -- NOWE
  `Body_Type` VARCHAR(50) DEFAULT NULL,         -- NOWE
  `Production_Year` INT DEFAULT NULL,           -- NOWE
  `DOB` DATE DEFAULT NULL,                      -- NOWE
  `Insurance_Start_Date` DATE DEFAULT NULL,     -- NOWE
  `License_Date` DATE DEFAULT NULL,             -- NOWE
  `Engine_Capacity` INT DEFAULT NULL,           -- NOWE
  `Fuel_Type` VARCHAR(20) DEFAULT NULL,         -- NOWE
  `Power_HP` INT DEFAULT NULL,                  -- NOWE
  `Motorcycle_Type` VARCHAR(20) DEFAULT NULL,   -- NOWE
  `Insurance_type` VARCHAR(20) NOT NULL,
  `Use_type` VARCHAR(20) NOT NULL,
  `Date_of_search` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Planned_mileage` INT DEFAULT NULL,
  `Last_accident` TINYINT(1) DEFAULT NULL,
  `Assistance_level` VARCHAR(20) DEFAULT 'NONE', -- NOWE
  `Accident_cover` TINYINT(1) DEFAULT 0,         -- NOWE
  `Discount_protection` TINYINT(1) DEFAULT 0,    -- NOWE
  PRIMARY KEY (`Search_ID`),
  KEY `idx_users_id` (`Users_ID`),
  CONSTRAINT `SearchHistory_User_FK` FOREIGN KEY (`Users_ID`) REFERENCES `User` (`Users_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. POLISA DLA SAMOCHODU (POPRAWIONA)
-- ============================================
CREATE TABLE IF NOT EXISTS `CarInsurance` (
  `CarInsurance_ID` INT NOT NULL AUTO_INCREMENT,
  `Users_ID` INT NOT NULL,
  `Vehicle_ID` INT NOT NULL,
  `Insurance_name` VARCHAR(50) NOT NULL,
  `Insurance_type` VARCHAR(20) NOT NULL,
  `Use_type` VARCHAR(20) NOT NULL,
  `License_release_date` DATE NOT NULL,
  `Last_accident` TINYINT(1) DEFAULT NULL,
  `Planned_mileage` INT DEFAULT NULL,
  `Body_type` VARCHAR(50) NOT NULL,
  `Price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  -- Opcje dodatkowe (NOWE KOLUMNY)
  `Assistance_level` ENUM('NONE', 'BASIC', 'STANDARD', 'PREMIUM') DEFAULT 'NONE',
  `Accident_cover` TINYINT(1) DEFAULT 0,
  `Discount_protection` TINYINT(1) DEFAULT 0,
  `Assistance_cost` DECIMAL(10,2) DEFAULT 0.00,
  `Accident_cover_cost` DECIMAL(10,2) DEFAULT 0.00,
  `Discount_protection_cost` DECIMAL(10,2) DEFAULT 0.00,
  `Base_premium` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CarInsurance_ID`),
  KEY `idx_users_id` (`Users_ID`),
  CONSTRAINT `CarInsurance_User_FK` FOREIGN KEY (`Users_ID`) REFERENCES `User` (`Users_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. POLISA DLA MOTOCYKLA (POPRAWIONA)
-- ============================================
CREATE TABLE IF NOT EXISTS `MotorcycleInsurance` (
  `MotorcycleInsurance_ID` INT NOT NULL AUTO_INCREMENT,
  `Users_ID` INT NOT NULL,
  `Vehicle_ID` INT NOT NULL,
  `Insurance_name` VARCHAR(50) NOT NULL,
  `Insurance_type` VARCHAR(20) NOT NULL,
  `Use_type` VARCHAR(20) NOT NULL,
  `License_release_date` DATE NOT NULL,
  `Last_accident` TINYINT(1) DEFAULT NULL,
  `Engine_capacity` INT NOT NULL,
  `Motorcycle_type` VARCHAR(20) DEFAULT NULL,
  `Power_HP` INT DEFAULT NULL,
  `Price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  -- Opcje dodatkowe (NOWE KOLUMNY)
  `Assistance_level` ENUM('NONE', 'BASIC', 'STANDARD', 'PREMIUM') DEFAULT 'NONE',
  `Accident_cover` TINYINT(1) DEFAULT 0,
  `Discount_protection` TINYINT(1) DEFAULT 0,
  `Assistance_cost` DECIMAL(10,2) DEFAULT 0.00,
  `Accident_cover_cost` DECIMAL(10,2) DEFAULT 0.00,
  `Discount_protection_cost` DECIMAL(10,2) DEFAULT 0.00,
  `Base_premium` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`MotorcycleInsurance_ID`),
  KEY `idx_users_id` (`Users_ID`),
  CONSTRAINT `MotorcycleInsurance_User_FK` FOREIGN KEY (`Users_ID`) REFERENCES `User` (`Users_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Marki
-- ============================================
CREATE TABLE IF NOT EXISTS CarBrands (
    Brand_ID INT AUTO_INCREMENT PRIMARY KEY,
    Brand_Name VARCHAR(100) NOT NULL UNIQUE,
    Country VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS MotorcycleBrands (
    Brand_ID INT AUTO_INCREMENT PRIMARY KEY,
    Brand_Name VARCHAR(100) NOT NULL UNIQUE,
    Country VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6. VIEW Insurance (ZAKTUALIZOWANY)
-- ============================================
CREATE OR REPLACE VIEW `Insurance` AS
SELECT
    CarInsurance_ID as Insurance_ID,
    Insurance_name,
    Insurance_type,
    Use_type,
    License_release_date,
    Planned_mileage,
    Body_type as Typ_nadwozia,
    Price,
    'CAR' as Vehicle_Category,
    Users_ID,
    Vehicle_ID,
    Assistance_level,
    Accident_cover,
    Discount_protection,
    created_at,
    updated_at
FROM CarInsurance
UNION ALL
SELECT
    MotorcycleInsurance_ID as Insurance_ID,
    Insurance_name,
    Insurance_type,
    Use_type,
    License_release_date,
    NULL as Planned_mileage,
    'MOTORCYCLE' as Typ_nadwozia,
    Price,
    'MOTORCYCLE' as Vehicle_Category,
    Users_ID,
    Vehicle_ID,
    Assistance_level,
    Accident_cover,
    Discount_protection,
    created_at,
    updated_at
FROM MotorcycleInsurance;

-- ============================================
-- 7. Tabela ULUBIONYCH OFERT (POPRAWIONA)
-- ============================================
CREATE TABLE IF NOT EXISTS `FavoriteInsurance` (
  `Favorite_ID` INT NOT NULL AUTO_INCREMENT,
  `Users_ID` INT NOT NULL,
  `Insurance_ID` INT NOT NULL,
  `Insurance_Type` ENUM('CAR', 'MOTORCYCLE') NOT NULL,
  `Added_Date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `Notes` TEXT DEFAULT NULL,
  -- Snapshot oferty (NOWE KOLUMNY)
  `Brand` VARCHAR(50) DEFAULT NULL,
  `Body_type` VARCHAR(50) DEFAULT NULL,
  `Production_year` INT DEFAULT NULL,
  `Engine_capacity` INT DEFAULT NULL,
  `DOB` DATE DEFAULT NULL,
  `License_date` DATE DEFAULT NULL,
  `Damage_free_years` INT DEFAULT 0,
  `Assistance_level` VARCHAR(20) DEFAULT 'NONE',
  `Accident_cover` TINYINT(1) DEFAULT 0,
  `Discount_protection` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`Favorite_ID`),
  UNIQUE KEY `unique_favorite` (`Users_ID`, `Insurance_ID`, `Insurance_Type`),
  CONSTRAINT `FavoriteInsurance_User_FK` FOREIGN KEY (`Users_ID`) REFERENCES `User` (`Users_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. Tabela tokenów resetowania hasła
-- ============================================
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `password_resets_email_fk` FOREIGN KEY (`email`) REFERENCES `User` (`email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DANE TESTOWE
-- ============================================
INSERT INTO `User` (`email`, `haslo`, `SUser`, `Wiek`) VALUES
('admin@skanpolis.pl', '$2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0xCyy0D.77I1DLi3t8zFm', 1, 30),
('user@example.pl', '$2y$12$NdOgRUQ.9wNb5W0McX3rFenGDnUvMcD.D5zeb57TnafDDhD7oXosu', 0, 25);

INSERT INTO `Vehicle` (`Users_ID`, `Vehicle_type`, `Brand`, `Model`, `Year`, `Engine_capacity`, `Power_HP`) VALUES
(2, 'CAR', 'Toyota', 'Corolla', 2020, 1800, 140),
(2, 'MOTORCYCLE', 'Yamaha', 'MT-07', 2021, 689, 75);

INSERT INTO `CarInsurance` (`Users_ID`, `Vehicle_ID`, `Insurance_name`, `Insurance_type`, `Use_type`, `License_release_date`, `Body_type`, `Price`, `Planned_mileage`, `Base_premium`, `Assistance_level`) VALUES
(2, 1, 'PZU', 'OC', 'PRYWATNIE', '2025-12-31', 'Sedan', 450.00, 15000, 400.00, 'BASIC'),
(2, 1, 'Warta', 'OC/AC', 'PRYWATNIE', '2025-12-31', 'Sedan', 1200.00, 15000, 1000.00, 'STANDARD'),
(2, 1, 'Allianz', 'OC', 'PRYWATNIE', '2025-12-31', 'Sedan', 420.00, 15000, 420.00, 'NONE');

INSERT INTO `MotorcycleInsurance` (`Users_ID`, `Vehicle_ID`, `Insurance_name`, `Insurance_type`, `Use_type`, `License_release_date`, `Engine_capacity`, `Motorcycle_type`, `Power_HP`, `Price`) VALUES
(2, 2, 'PZU', 'OC', 'PRYWATNIE', '2025-12-31', 125, 'naked', 15, 280.00),
(2, 2, 'Warta', 'OC', 'PRYWATNIE', '2025-12-31', 125, 'cross', 15, 320.00),
(2, 2, 'Allianz', 'OC/AC', 'PRYWATNIE', '2025-12-31', 125, 'naked', 15, 650.00);

-- Dodaj marki samochodów
INSERT INTO CarBrands (Brand_Name, Country) VALUES
('Audi', 'Niemcy'),
('BMW', 'Niemcy'),
('Mercedes-Benz', 'Niemcy'),
('Volkswagen', 'Niemcy'),
('Opel', 'Niemcy'),
('Ford', 'USA'),
('Chevrolet', 'USA'),
('Toyota', 'Japonia'),
('Honda', 'Japonia'),
('Mazda', 'Japonia'),
('Nissan', 'Japonia'),
('Subaru', 'Japonia'),
('Mitsubishi', 'Japonia'),
('Lexus', 'Japonia'),
('Hyundai', 'Korea Płd.'),
('Kia', 'Korea Płd.'),
('Peugeot', 'Francja'),
('Renault', 'Francja'),
('Citroen', 'Francja'),
('Fiat', 'Włochy'),
('Alfa Romeo', 'Włochy'),
('Volvo', 'Szwecja'),
('Skoda', 'Czechy'),
('Seat', 'Hiszpania'),
('Tesla', 'USA'),
('Dacia', 'Rumunia'),
('Porsche', 'Niemcy')
ON DUPLICATE KEY UPDATE Brand_Name=Brand_Name;

-- Dodaj marki motocykli
INSERT INTO MotorcycleBrands (Brand_Name, Country) VALUES
('Honda', 'Japonia'),
('Yamaha', 'Japonia'),
('Kawasaki', 'Japonia'),
('Suzuki', 'Japonia'),
('Harley-Davidson', 'USA'),
('BMW', 'Niemcy'),
('KTM', 'Austria'),
('Ducati', 'Włochy'),
('Triumph', 'Wielka Brytania'),
('Indian', 'USA'),
('Aprilia', 'Włochy'),
('MV Agusta', 'Włochy'),
('Royal Enfield', 'Indie'),
('Husqvarna', 'Szwecja'),
('Benelli', 'Włochy'),
('Can-Am', 'Kanada'),
('Moto Guzzi', 'Włochy'),
('Norton', 'Wielka Brytania'),
('Buell', 'USA'),
('CFMoto', 'Chiny')
ON DUPLICATE KEY UPDATE Brand_Name=Brand_Name;