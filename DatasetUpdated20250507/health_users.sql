-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
--
-- Host: localhost    Database: health
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` text NOT NULL,
  `role` enum('admin','doctor') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `phone_number` varchar(15) DEFAULT NULL,
  `specialist` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (100,'admin1','admin1@example.com','hashedpassword1','admin','2023-01-01 09:00:00','2024-04-10 22:15:00',1,NULL,NULL),(101,'EmilyTan01','drEmilyTan01@example.com','hashedpassword2','doctor','2023-01-01 09:00:00','2024-01-10 08:30:00',1,'+60133477616','Cardiologist'),(102,'DanialLim02','drDanialLim02@example.com','hashedpassword3','doctor','2023-01-03 11:25:00','2024-04-14 09:00:00',1,'+60137493537','Endocrinologist'),(103,'NurAisya03','drNurAisya03@example.com','hashedpassword4','doctor','2023-01-05 14:10:00','2023-12-15 17:40:00',1,'+60151078693','Cardiologist'),(104,'JonathanKumar04','drJonathanKumar04@example.com','hashedpassword5','doctor','2023-01-05 14:10:00','2024-02-22 12:10:00',1,'+60193053360','Endocrinologist'),(105,'MarcusReddy05','drMarcusReddy05@gmail.com','hashedpassword6','doctor','2023-01-07 10:45:00','2024-03-05 07:55:00',1,'+60148305627','Cardiologist'),(106,'ChanMeiLing06','drMeiLing06@gmail.com','hashedpassword7','doctor','2023-01-09 16:30:00','2024-04-15 08:00:00',1,'+60180326707','Neurologist'),(107,'ClarissaNg07','drClarissaNg07@gmail.com','hashedpassword8','doctor','2023-01-10 08:20:00','2024-04-01 16:00:00',1,'+60111456823','General Practitioner'),(108,'AhmadZainal08','drAhmadZainal08@gmail.com','hashedpassword9','doctor','2023-01-10 08:20:00','2024-04-13 22:45:00',1,'+60134739296','General Practitioner'),(109,'NurYana09','drNurYana09@gmail.com','hashedpassword10','doctor','2023-01-11 11:55:00','2024-01-15 09:20:00',1,'+60116935250','Diabetes Specialist'),(110,'JasonLee10','drJasonLee10@gmail.com','hashedpassword11','doctor','2023-01-12 12:40:00','2024-04-10 18:30:00',1,'+60199116107','Cardiologist'),(111,'BenjaminGoh11','drBenjaminGog11@gmail.com','hashedpassword12','doctor','2023-01-13 13:30:00','2024-02-20 11:00:00',1,'+60120788810','Endocrinologist'),(112,'NicoleLau12','drNicoleLau12@gmail.com','hashedpassword13','doctor','2023-01-13 13:30:00','2024-04-11 10:15:00',1,'+60142942467','Neurologist'),(113,'VanessaTeo13','drVanessaTeo13@gmail.com','hashedpassword14','doctor','2023-01-15 09:15:00','2024-03-01 14:25:00',1,'+60188807617','Diabetes Specialist'),(114,'FirdausIsmail14','drFirdausIsmail14@gmail.com','hashedpassword15','doctor','2023-01-16 07:50:00','2024-04-14 23:00:00',1,'+60140862546','Neurologist'),(115,'SamualChong15','drSamualChong15@gmail.com','hashedpassword16','doctor','2023-01-16 17:00:00','2024-04-02 12:00:00',1,'+60167119848','Diabetes Specialist'),(116,'newDoctor01','newDoctor01@example.com','password123','doctor','2025-04-28 23:12:23','2025-04-28 23:12:23',1,'+60197514222','Diabetes Specialist');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-07 19:58:31
