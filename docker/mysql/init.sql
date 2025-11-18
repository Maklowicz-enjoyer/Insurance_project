-- ============================================
-- SKANPOLIS - Database Schema
-- ============================================
-- This script initializes the database schema
-- for the SkanPolis insurance platform
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
  `SUser` TINYINT(1) NOT NULL DEFAULT 0,  -- 0 = user, 1 = admin
  `Wiek` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Users_ID`),
  UNIQUE KEY `email_unique` (`email`),
  KEY `idx_email` (`email`)
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
  `Engine_capacity` INT DEFAULT NULL,        -- dla motocykli (cm³)
  `Power_HP` INT DEFAULT NULL,
  `VIN` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Vehicle_ID`),
  KEY `idx_users_id` (`Users_ID`),
  KEY `idx_vehicle_type` (`Vehicle_type`),
  CONSTRAINT `Vehicle_User_FK`
      FOREIGN KEY (`Users_ID`)
      REFERENCES `User` (`Users_ID`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. Tabela historii wyszukiwań
-- ============================================

CREATE TABLE IF NOT EXISTS `SearchHistory` (
  `Search_ID` INT NOT NULL AUTO_INCREMENT,
  `Users_ID` INT NOT NULL,
  `Vehicle_ID` INT DEFAULT NULL,
  `Insurance_type` VARCHAR(20) NOT NULL,   -- np. OC, AC, OC/AC
  `Use_type` VARCHAR(20) NOT NULL,         -- leasing / prywatnie
  `Date_of_search` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Planned_mileage` INT DEFAULT NULL,
  `Last_accident` TINYINT(1) DEFAULT NULL,
  PRIMARY KEY (`Search_ID`),
  KEY `idx_users_id` (`Users_ID`),
  KEY `idx_vehicle_id` (`Vehicle_ID`),
  KEY `idx_date_search` (`Date_of_search`),
  CONSTRAINT `SearchHistory_User_FK`
      FOREIGN KEY (`Users_ID`)
      REFERENCES `User` (`Users_ID`)
      ON DELETE CASCADE,
  CONSTRAINT `SearchHistory_Vehicle_FK`
      FOREIGN KEY (`Vehicle_ID`)
      REFERENCES `Vehicle` (`Vehicle_ID`)
      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. POLISA DLA SAMOCHODU
-- ============================================

CREATE TABLE IF NOT EXISTS `CarInsurance` (
  `CarInsurance_ID` INT NOT NULL AUTO_INCREMENT,
  `Users_ID` INT NOT NULL,
  `Vehicle_ID` INT NOT NULL,
  `Insurance_name` VARCHAR(50) NOT NULL,
  `Insurance_type` VARCHAR(20) NOT NULL,   -- OC / AC / OC+AC
  `Use_type` VARCHAR(20) NOT NULL,         -- LEASING / PRYWATNIE
  `License_release_date` DATE NOT NULL,
  `Last_accident` TINYINT(1) DEFAULT NULL,
  `Date_of_last_collision` DATE DEFAULT NULL,
  `Planned_mileage` INT DEFAULT NULL,
  `Body_type` VARCHAR(50) NOT NULL,        -- Sedan, SUV, Coupe itd.
  `Price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CarInsurance_ID`),
  KEY `idx_users_id` (`Users_ID`),
  KEY `idx_vehicle_id` (`Vehicle_ID`),
  KEY `idx_insurance_type` (`Insurance_type`),
  CONSTRAINT `CarInsurance_User_FK`
      FOREIGN KEY (`Users_ID`)
      REFERENCES `User` (`Users_ID`)
      ON DELETE CASCADE,
  CONSTRAINT `CarInsurance_Vehicle_FK`
      FOREIGN KEY (`Vehicle_ID`)
      REFERENCES `Vehicle` (`Vehicle_ID`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. POLISA DLA MOTOCYKLA
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
  `Date_of_last_collision` DATE DEFAULT NULL,
  `Engine_capacity` INT NOT NULL,          -- OBOWIĄZKOWE dla motocykli
  `Motorcycle_type` VARCHAR(20) DEFAULT NULL,  -- naked, cruiser, bobber, cross, etc.
  `Power_HP` INT DEFAULT NULL,
  `Price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`MotorcycleInsurance_ID`),
  KEY `idx_users_id` (`Users_ID`),
  KEY `idx_vehicle_id` (`Vehicle_ID`),
  KEY `idx_insurance_type` (`Insurance_type`),
  KEY `idx_engine_capacity` (`Engine_capacity`),
  CONSTRAINT `MotorcycleInsurance_User_FK`
      FOREIGN KEY (`Users_ID`)
      REFERENCES `User` (`Users_ID`)
      ON DELETE CASCADE,
  CONSTRAINT `MotorcycleInsurance_Vehicle_FK`
      FOREIGN KEY (`Vehicle_ID`)
      REFERENCES `Vehicle` (`Vehicle_ID`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. VIEW Insurance (UNION of CarInsurance and MotorcycleInsurance)
-- ============================================

-- This will be created after sample data is inserted
-- See section after data insertion

-- ============================================
-- 7. Tabela sesji (opcjonalna - Redis jest głównym)
-- ============================================

CREATE TABLE IF NOT EXISTS `Sessions` (
  `session_id` VARCHAR(128) NOT NULL,
  `user_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `payload` TEXT NOT NULL,
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. Przykładowe dane testowe
-- ============================================

-- Admin user (password: Admin123!@#)
INSERT INTO `User` (`email`, `haslo`, `SUser`, `Wiek`) VALUES
('admin@skanpolis.pl', '$2y$12$Gqg4RIFVvgt/9flsF4u0NOftY5eoflFk0xCyy0D.77I1DLi3t8zFm', 1, 30);

-- Regular user (password: User123!@#)
INSERT INTO `User` (`email`, `haslo`, `SUser`, `Wiek`) VALUES
('user@example.pl', '$2y$12$NdOgRUQ.9wNb5W0McX3rFenGDnUvMcD.D5zeb57TnafDDhD7oXosu', 0, 25);

-- Sample vehicles
INSERT INTO `Vehicle` (`Users_ID`, `Vehicle_type`, `Brand`, `Model`, `Year`, `Engine_capacity`, `Power_HP`) VALUES
(2, 'CAR', 'Toyota', 'Corolla', 2020, 1800, 140),
(2, 'MOTORCYCLE', 'Yamaha', 'MT-07', 2021, 689, 75);

-- Sample car insurance offers
INSERT INTO `CarInsurance` (`Users_ID`, `Vehicle_ID`, `Insurance_name`, `Insurance_type`, `Use_type`, `License_release_date`, `Body_type`, `Price`, `Planned_mileage`) VALUES
(2, 1, 'PZU', 'OC', 'PRYWATNIE', '2025-12-31', 'Sedan', 450.00, 15000),
(2, 1, 'Warta', 'OC/AC', 'PRYWATNIE', '2025-12-31', 'Sedan', 1200.00, 15000),
(2, 1, 'Allianz', 'OC', 'PRYWATNIE', '2025-12-31', 'Sedan', 420.00, 15000);

-- Sample motorcycle insurance offers
INSERT INTO `MotorcycleInsurance` (`Users_ID`, `Vehicle_ID`, `Insurance_name`, `Insurance_type`, `Use_type`, `License_release_date`, `Engine_capacity`, `Motorcycle_type`, `Power_HP`, `Price`) VALUES
-- 125cc - dla początkujących
(2, 2, 'PZU', 'OC', 'PRYWATNIE', '2025-12-31', 125, 'naked', 15, 280.00),
(2, 2, 'Warta', 'OC', 'PRYWATNIE', '2025-12-31', 125, 'cross', 15, 320.00),
(2, 2, 'Allianz', 'OC/AC', 'PRYWATNIE', '2025-12-31', 125, 'naked', 15, 650.00),

-- 300cc
(2, 2, 'Link4', 'OC', 'PRYWATNIE', '2025-12-31', 300, 'naked', 39, 420.00),
(2, 2, 'Ergo Hestia', 'OC', 'PRYWATNIE', '2025-12-31', 300, 'cruiser', 35, 450.00),
(2, 2, 'PZU', 'OC/AC', 'PRYWATNIE', '2025-12-31', 300, 'naked', 39, 920.00),

-- 600cc - sportowe
(2, 2, 'Allianz', 'OC', 'PRYWATNIE', '2025-12-31', 600, 'naked', 95, 680.00),
(2, 2, 'PZU', 'OC', 'PRYWATNIE', '2025-12-31', 600, 'cruiser', 50, 620.00),
(2, 2, 'Generali', 'OC/AC', 'PRYWATNIE', '2025-12-31', 600, 'naked', 95, 1450.00),

-- 900cc
(2, 2, 'PZU', 'OC', 'PRYWATNIE', '2025-12-31', 900, 'naked', 115, 890.00),
(2, 2, 'Ergo Hestia', 'OC', 'PRYWATNIE', '2025-12-31', 900, 'cruiser', 75, 850.00),
(2, 2, 'Allianz', 'OC/AC', 'PRYWATNIE', '2025-12-31', 900, 'naked', 115, 1850.00),

-- 1200cc
(2, 2, 'PZU', 'OC', 'PRYWATNIE', '2025-12-31', 1200, 'cruiser', 100, 1250.00),
(2, 2, 'Generali', 'OC', 'PRYWATNIE', '2025-12-31', 1200, 'bobber', 105, 1280.00),
(2, 2, 'Allianz', 'OC/AC', 'PRYWATNIE', '2025-12-31', 1200, 'cruiser', 100, 2450.00),

-- 1500cc - największe
(2, 2, 'PZU', 'OC', 'PRYWATNIE', '2025-12-31', 1500, 'cruiser', 130, 1580.00),
(2, 2, 'Warta', 'OC', 'PRYWATNIE', '2025-12-31', 1500, 'bobber', 135, 1620.00),
(2, 2, 'Link4', 'OC/AC', 'PRYWATNIE', '2025-12-31', 1500, 'cruiser', 130, 3050.00);

-- ============================================
-- CREATE Insurance VIEW (UNION of CarInsurance and MotorcycleInsurance)
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
    created_at,
    updated_at
FROM MotorcycleInsurance;

-- ============================================
-- Indexes for performance optimization
-- ============================================

-- Already created inline with table definitions

-- ============================================
-- Database initialization complete
-- ============================================
