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
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1000,111,'Lucus Kho','1998-04-17','Male','2023-01-19 08:00:00','critical'),(1001,105,'Mini Cream','1987-05-26','Female','2023-01-19 08:30:00','recovered'),(1002,113,'Nini Tan','2001-12-09','Female','2023-01-19 10:00:00','stable'),(1003,114,'Sophia Jones','1941-01-13','Male','2023-01-20 08:30:00','critical'),(1004,115,'Jie Lim','1947-11-25','Female','2023-01-20 11:30:00','stable'),(1005,101,'Fang Ng','1950-12-17','Female','2023-01-20 13:00:00','stable'),(1006,115,'Anil a/l Krishnan','1972-07-09','Male','2023-01-21 10:00:00','recovered'),(1007,103,'Aiman Bin Ismail','2013-05-07','Female','2023-01-21 10:30:00','recovered'),(1008,104,'Arjun a/l Subramaniam','1978-12-03','Female','2023-01-21 14:00:00','recovered'),(1009,105,'Jie Chong','1962-03-16','Female','2023-01-23 12:00:00','critical'),(1010,106,'Rashid Binti Hassan','1994-11-29','Female','2023-01-23 13:00:00','recovered'),(1011,107,'Aisyah Binti Ahmad','1967-12-14','Female','2023-01-23 15:30:00','critical'),(1012,108,'Hao Lim','1958-10-06','Female','2023-01-24 08:00:00','warning'),(1013,109,'Siti Nurhaliza','1990-02-10','Female','2023-01-24 08:25:00','critical'),(1014,110,'Manjeet Kaur','1983-06-12','Female','2023-01-25 08:45:00','critical'),(1015,111,'Zhi Wei Tan','2005-03-03','Male','2023-01-25 12:30:00','critical'),(1016,112,'Mei Lin Goh','1992-09-30','Female','2023-01-26 08:00:00','recovered'),(1017,113,'Raj a/l Raman','1965-04-15','Male','2023-01-26 09:14:00','warning'),(1018,114,'Chen Wei','1979-11-22','Male','2023-01-26 14:35:00','critical'),(1019,115,'Irfan Bin Salleh','1987-05-05','Male','2023-01-27 08:00:00','stable'),(1020,101,'Fatimah Binti Yusof','1971-08-17','Female','2023-01-29 08:00:00','critical'),(1021,102,'Thinesh a/l Muniandy','1990-09-09','Male','2023-01-30 08:30:00','stable'),(1022,103,'Kavitha a/p Sundaram','1982-10-19','Female','2023-01-30 11:21:00','critical'),(1023,104,'Aiman Syahmi','1999-01-24','Male','2023-02-02 09:30:00','critical'),(1024,105,'Yasmin Abdullah','1996-12-08','Female','2023-02-02 16:00:00','stable'),(1025,106,'Amira Binti Rahman','1985-07-27','Female','2023-02-02 16:30:00','recovered'),(1026,107,'Daniel Wong','1991-03-11','Male','2023-02-03 08:00:00','warning'),(1027,108,'Fiona Tan','1994-06-14','Female','2023-02-05 08:00:00','critical'),(1028,109,'Suresh a/l Kumar','1986-02-20','Male','2023-02-05 10:55:00','critical'),(1029,110,'Nur Izzati','2000-04-07','Female','2023-02-06 08:00:00','critical'),(1030,111,'Mohammad Hafiz','1975-01-01','Male','2023-02-06 10:37:00','critical'),(1031,112,'Navin a/l Rajan','1983-05-02','Male','2023-02-07 08:18:00','critical'),(1032,113,'Chong Wei Lun','1979-09-09','Male','2023-02-12 08:00:00','recovered'),(1033,114,'Lee Mei Yi','1988-06-06','Female','2023-02-14 11:00:00','stable'),(1034,115,'Gopal a/l Siva','1966-07-13','Male','2023-02-19 08:05:00','recovered'),(1035,101,'Priya a/p Selvam','1993-04-04','Female','2023-02-13 08:00:00','recovered'),(1036,102,'Liyana Binti Khalid','1990-10-10','Female','2023-02-20 09:50:00','stable'),(1037,103,'Shanti a/p Maniam','1974-08-29','Female','2023-02-24 08:17:00','critical'),(1038,104,'Tan Yi Ping','1989-11-05','Female','2023-03-02 08:00:00','recovered'),(1039,105,'Wei Liang Lim','1981-12-12','Male','2023-03-03 11:20:00','stable'),(1040,106,'Samantha Tan','1995-03-18','Female','2023-03-03 12:00:00','critical'),(1041,107,'Kelvin Ong','1998-06-23','Male','2023-03-05 08:00:00','critical'),(1042,108,'Harvinder a/l Singh','1977-09-01','Male','2023-03-05 10:00:00','critical'),(1043,109,'Maisarah Binti Hamzah','2001-02-15','Female','2023-03-10 08:00:00','stable'),(1044,110,'Kiran a/p Devi','1982-05-26','Female','2023-03-15 08:00:00','warning'),(1045,111,'Tengku Amir','1993-07-20','Male','2023-03-15 15:00:00','stable'),(1046,112,'Hafiza Binti Ahmad','1990-03-09','Female','2023-03-24 08:00:00','critical'),(1047,113,'Yong Kai Xian','1984-08-03','Male','2023-03-24 08:30:00','recovered'),(1048,113,'Roslan Bin Hussin','1968-11-06','Male','2023-03-26 08:00:00','critical'),(1049,115,'Asha a/p Rajah','1976-04-11','Female','2023-03-26 08:10:00','critical'),(1050,101,'Datin Salmah','1965-12-25','Female','2023-04-01 08:00:00','stable'),(1051,102,'Vinod a/l Arumugam','1988-02-22','Male','2023-04-04 08:00:00','recovered'),(1052,103,'Nurul Ain','1991-10-30','Female','2023-04-04 10:17:00','recovered'),(1053,104,'Syed Ahmad','1980-06-01','Male','2023-04-06 08:00:00','stable'),(1054,105,'Meera a/p Narayan','1987-09-17','Female','2023-04-09 08:40:00','recovered'),(1055,106,'Tan Sri Lim','1955-01-21','Male','2023-04-09 14:00:00','stable'),(1056,107,'Nur Liyana','2002-02-02','Female','2023-04-11 12:00:00','recovered'),(1057,108,'Zulhilmi Bin Zakaria','1995-07-07','Male','2023-04-15 08:00:00','recovered'),(1058,109,'Leong Kah Mun','1973-03-19','Male','2023-04-19 10:00:00','warning'),(1059,110,'Aina Farzana','1998-05-14','Female','2023-04-22 08:00:00','recovered'),(1060,111,'Neela a/p Kumar','1983-08-24','Female','2023-04-26 08:00:00','stable'),(1061,112,'Ismail Bin Azman','1990-12-13','Male','2023-05-02 08:00:00','recovered'),(1062,113,'Joanna Chin','1997-06-15','Female','2023-05-02 11:50:00','recovered'),(1063,114,'Hassan Bin Omar','1970-10-10','Male','2023-05-03 08:00:00','recovered'),(1064,115,'Rajesh a/l Anand','1978-09-09','Male','2023-05-07 08:00:00','recovered'),(1065,114,'Liew Bee Ling','1989-07-03','Female','2023-05-09 08:00:00','critical'),(1066,102,'Sharifah Zahra','1996-04-08','Female','2023-05-14 08:04:00','recovered'),(1067,103,'Muhammad Faiz','2000-11-28','Male','2023-05-15 09:00:00','warning'),(1068,104,'Lim Shu Wen','1984-01-16','Female','2023-05-19 08:00:00','stable'),(1069,105,'Aaron Ng','1992-12-26','Male','2023-05-20 08:00:00','recovered'),(1070,106,'Vijay a/l Balakrishnan','1981-06-20','Male','2023-05-25 09:17:00','critical'),(1071,107,'Nur Atiqah','1993-03-05','Female','2023-05-26 08:00:00','recovered'),(1072,108,'Ethan Lee','2004-08-14','Male','2023-05-28 15:00:00','critical'),(1073,109,'Hanisah Binti Rahmat','1985-09-12','Female','2023-05-30 08:07:00','recovered'),(1074,110,'Nandini a/p Selvan','1999-06-30','Female','2023-06-05 08:00:00','recovered'),(1075,111,'Nicholas Toh','1979-11-11','Male','2023-06-09 11:00:00','recovered'),(1076,112,'Sufian Bin Zainal','1990-01-17','Male','2023-06-09 12:15:00','stable'),(1077,113,'Yee Mei Teng','1996-02-25','Female','2023-06-15 08:00:00','stable'),(1078,114,'Saraswathy a/p Mani','1980-07-04','Female','2023-06-16 08:00:00','recovered'),(1079,115,'Haziq Bin Roslan','1994-10-13','Male','2023-06-19 09:27:00','warning'),(1080,101,'Fazira Binti Azmi','1992-11-07','Female','2023-06-23 08:00:00','stable'),(1081,102,'Dinesh a/l Govind','1987-05-22','Male','2023-06-26 10:00:00','critical'),(1082,103,'Jessie Liew','1991-12-18','Female','2023-06-30 08:00:00','recovered'),(1083,104,'Ahmad Danial','1983-06-06','Male','2023-07-01 08:00:00','critical'),(1084,105,'Hui Ying','1998-09-09','Female','2023-07-03 09:00:00','warning'),(1085,106,'Karthik a/l Ramasamy','1986-01-31','Male','2023-07-08 10:37:00','stable'),(1086,107,'Rohana Binti Said','1975-08-21','Female','2023-07-14 08:00:00','recovered'),(1087,108,'Chia Wei Jing','1997-03-27','Female','2023-07-19 10:00:00','stable'),(1088,109,'Ravi a/l Chandran','1969-05-23','Male','2023-07-19 14:15:00','recovered'),(1089,110,'Sharmila a/p Nadarajah','1984-10-01','Female','2023-07-25 08:00:00','stable'),(1090,111,'Daniel Raj','1992-02-12','Male','2023-08-02 16:07:00','stable'),(1091,112,'Wong Sze Ming','1993-03-08','Female','2023-08-11 08:00:00','critical'),(1092,113,'Zulqarnain Bin Musa','1990-05-05','Male','2023-08-12 11:00:00','recovered'),(1093,114,'Nur Afifah','1988-07-16','Female','2023-08-14 08:00:00','stable'),(1094,115,'Alan Tan','1981-06-09','Male','2023-08-16 08:00:00','critical'),(1095,101,'Sabrina Binti Ariffin','1995-04-11','Female','2023-08-22 08:00:00','critical'),(1096,102,'Jay a/l Vijayan','1978-02-03','Male','2023-08-29 08:13:00','warning'),(1097,103,'Aisyah Hanim','1999-08-29','Female','2023-09-04 08:37:00','recovered'),(1098,104,'Kumar a/l Suresh','1986-09-19','Male','2023-09-15 12:00:00','recovered'),(1099,105,'Puteri Izzati','1994-01-26','Female','2023-09-21 10:30:00','critical');
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

-- Dump completed on 2025-05-07 19:58:31
