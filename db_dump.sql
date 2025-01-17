-- MySQL dump 10.13  Distrib 8.0.40, for Linux (x86_64)
--
-- Host: localhost    Database: insurance_db
-- ------------------------------------------------------
-- Server version	8.0.40-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Insurance`
--

DROP TABLE IF EXISTS `Insurance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Insurance` (
  `Insurance_ID` int NOT NULL AUTO_INCREMENT,
  `Users_ID` int DEFAULT NULL,
  `Insurance_name` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Insurance_type` varchar(9) NOT NULL,
  `Use_type` varchar(12) NOT NULL,
  `License_release_date` date NOT NULL,
  `Last_accident` tinyint(1) DEFAULT NULL,
  `Date_of_last_collision` date DEFAULT NULL,
  `Planned_mileage` int DEFAULT NULL,
  `Typ_nadwozia` varchar(255) NOT NULL,
  `Price` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`Insurance_ID`),
  KEY `Users_ID` (`Users_ID`),
  CONSTRAINT `Insurance_ibfk_1` FOREIGN KEY (`Users_ID`) REFERENCES `User` (`Users_ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Insurance`
--

LOCK TABLES `Insurance` WRITE;
/*!40000 ALTER TABLE `Insurance` DISABLE KEYS */;
INSERT INTO `Insurance` VALUES (1,NULL,'Warta. S.A','OC/AC','LEASING','2025-10-02',NULL,NULL,20000,'Sedan',NULL),(2,NULL,'Warta. S.A','OC/AC','LEASING','2025-10-02',NULL,NULL,20000,'Sedan',NULL),(4,11,'Twoja Stara. S.A','OC/AC','LEASING','2002-09-10',NULL,NULL,6969,'SUV',298.87),(5,NULL,'Hestia Leasing Plus','OC','LEASING','2020-08-14',NULL,NULL,12000,'Kabriolet',NULL),(7,NULL,'Warta. S.A','OC','PRYWATNIE','2025-10-10',NULL,NULL,15000,'Hatchback',NULL),(8,NULL,'Aviva Basic','OC','PRYWATNIE','2015-06-10',NULL,NULL,20000,'SUV',NULL),(9,NULL,'PZU Premium','OC/AC','LEASING','2018-03-12',NULL,NULL,15000,'Kompakt',NULL),(10,NULL,'Allianz Standard','OC','PRYWATNIE','2010-11-20',NULL,NULL,25000,'Sedan',NULL),(11,1,'Warta Komfort','OC/AC','PRYWATNIE','2017-09-05',NULL,NULL,30000,'Coupe',348.35);
/*!40000 ALTER TABLE `Insurance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `User`
--

DROP TABLE IF EXISTS `User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `User` (
  `Users_ID` int NOT NULL AUTO_INCREMENT,
  `email` char(50) NOT NULL,
  `haslo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `SUser` tinyint(1) DEFAULT NULL,
  `Wiek` int NOT NULL,
  `kod` int NOT NULL,
  PRIMARY KEY (`Users_ID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `User`
--

LOCK TABLES `User` WRITE;
/*!40000 ALTER TABLE `User` DISABLE KEYS */;
INSERT INTO `User` VALUES (1,'test@example.com','$2y$10$6dd6qWF7DrYNJi021WeVn.nz4WWwGhHmeJt088Ws8.73w5ZEFrv6m',1,18,0),(2,'ddd@example.com','$2y$10$fp5H/Nf0UcvaVrz1DU2NLOliUoldbgGYKpIP4ZQ/.RiSikxWWFURe',0,18,0),(3,'julian@example.com','$2y$10$zft9QbBNK9FHFc6CK0658O6NGw6VptGRIMZbMarpc3lJKsy1QTjUS',0,18,0),(4,'test123@example.com','$2y$10$jisOrVgEIRd.mVodNL.9le8tsdAeXtCLo9ejL02MZOl/0RYDZc89C',0,18,0),(5,'Karolina@example.com','$2y$10$HL1Jia7Q4Gda0K9wvk80/.Xh.JWW7nTaPYMv7yjp1rEPSL72Iaosi',0,18,0),(6,'Karolinakc@example.com','$2y$10$YIJERu0ohLw31xPqGVb7suaZCDbb63A2p.nlkIrse69FIJxtlbCui',0,18,0),(7,'dupa@example.com','$2y$10$MGejCYwj6fZPPvntrL0fkeyq0368JrYfGOFFlkps7DeVw4LxrztUC',0,18,0),(8,'dziekanrobiloda@example.com','$2y$10$M.qw/X2kTJegmGlxSHyHPuefD9SprJgZgzAZJwkA3CHzLdtqTkpl.',0,18,123456),(9,'filip@example.com','$2y$10$JkHqSEd79PN5OdVHUPYQSu3GNthENiNiH9OF3T/DRcLEG2SVfbzve',0,18,90912),(10,'karolinazs@example.com','$2y$10$qGq9V17zHG9VcWG2K2vcROjNVf20PwHy/88fA/oThYSCYWBn4S3me',0,18,666666),(11,'john@doe.com','$2y$10$YxtFkdg/5ndqIfvRIdGSlu4XWEf7sFqx7QZGfnWRAyZZ6r8cX60mK',0,18,123456),(12,'jane@doe.com','$2y$10$1/7vEBlQBmF0S9Dts6Im1ep3eJEiGqU/1V0yb204tBUBEUItQ0rB6',0,18,123456),(13,'alice@bob.com','$2y$10$B.i4kSoU8DlksAOu32hs/.S1ctF41jmRTjDY2PpTM2kJY84Ja4dSa',0,18,123456);
/*!40000 ALTER TABLE `User` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-01-17 16:12:32
