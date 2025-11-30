-- MySQL dump 10.13  Distrib 8.0.36, for Win64 (x86_64)
--
-- Host: localhost    Database: health
-- ------------------------------------------------------
-- Server version	8.0.32

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
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `patient_id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `age` int NOT NULL,
  `gender` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('critical','recovered','stable','warning') COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1035 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1000,105,'Mini Cream',38,'Female','2023-01-19 08:30:00','stable'),(1001,101,'Fang Ng',75,'Female','2023-01-20 13:00:00','stable'),(1002,104,'Arjun a/l Subramaniam',47,'Female','2023-01-21 14:00:00','stable'),(1003,105,'Jie Chong',63,'Female','2023-01-23 12:00:00','stable'),(1004,101,'Fatimah Binti Yusof',54,'Female','2023-01-29 08:00:00','stable'),(1006,103,'Kavitha a/p Sundaram',43,'Female','2023-01-30 11:21:00','stable'),(1007,105,'Yasmin Abdullah',29,'Female','2023-02-02 16:00:00','stable'),(1008,101,'Priya a/p Selvam',32,'Female','2023-02-13 08:00:00','stable'),(1010,103,'Shanti a/p Maniam',51,'Female','2023-02-24 08:17:00','warning'),(1011,104,'Tan Yi Ping',36,'Female','2023-03-02 08:00:00','stable'),(1013,103,'Nurul Ain',34,'Female','2023-04-04 10:17:00','stable'),(1014,104,'Syed Ahmad',45,'Male','2023-04-06 08:00:00','stable'),(1015,105,'Aaron Ng',33,'Male','2023-05-20 08:00:00','stable'),(1016,101,'Hui Ying',27,'Female','2023-07-03 09:00:00','warning'),(1017,103,'Aisyah Hanim',26,'Female','2023-09-04 08:37:00','stable'),(1018,104,'Kumar a/l Suresh',39,'Male','2023-09-15 12:00:00','stable'),(1019,105,'Puteri Izzati',31,'Female','2023-09-21 10:30:00','warning'),(1034,101,'John Clark',48,'Male','2025-11-28 23:19:13','warning');
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-30 18:03:52
