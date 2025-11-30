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
-- Table structure for table `health_data`
--

DROP TABLE IF EXISTS `health_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `health_data` (
  `data_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `timestamp` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cgm_level` float DEFAULT NULL,
  `blood_pressure` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `heart_rate` int DEFAULT NULL,
  `cholesterol` float DEFAULT NULL,
  `insulin_intake` float DEFAULT NULL,
  `food_intake` text COLLATE utf8mb4_general_ci,
  `activity_level` text COLLATE utf8mb4_general_ci,
  `weight` float DEFAULT NULL,
  `hb1ac` float DEFAULT NULL,
  `is_synced` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`data_id`),
  KEY `patient_id` (`patient_id`),
  KEY `idx_is_synced` (`is_synced`),
  CONSTRAINT `health_data_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_data`
--

LOCK TABLES `health_data` WRITE;
/*!40000 ALTER TABLE `health_data` DISABLE KEYS */;
INSERT INTO `health_data` VALUES (10000,1000,'19/1/2023 8:30',8.2,'134/88',73,210,3,'Smocked Salmon, Lemon Juice','No Activity',88,7.4,1),(10001,1001,'21/1/2023 8:30',7.8,'135/88',72,216,6,'Milo, Butter Cake','Tai Chi 30 minutes',78,7.5,1),(10002,1002,'22/1/2023 8:45',10.2,'142/92',69,218,3,'Oatmeal, Milk','Jogging 20 minutes',50,7.4,1),(10003,1003,'24/1/2023 9:30',8.2,'138/85',72,210,3,'Porridge with Salted Egg','Walking 30 minutes',70,7.6,1),(10004,1002,'26/1/2023 8:00',8.4,'136/88',66,212,4,'Brocolli with Mushroom, Chicken Corn, Rice','Walking 40 minutes',50,7.4,1),(10005,1000,'26/1/2023 9:30',7.8,'130/85',70,205,4,'Original flavour Yogurt and Berries','Gym workout 30 minutes',88,7.4,1),(10007,1001,'28/1/2023 13:30',8.2,'140/90',70,220,6,'Chicken Rice','Walking 30 minutes',78,7.5,1),(10008,1004,'29/1/2023 9:00',6.5,'120/78',83,200,4,'Chicken sandwich','Jogging 30 minutes',56,6.8,1),(10009,1003,'30/1/2023 9:30',7.7,'132/82',70,208,3,'Oatmeal, Milk','Tai Chi 30 minutes',70,7.6,1),(10011,1006,'31/1/2023 9:00',8,'135/88',72,205,4,'Oatmeal, Milk','Gym workout 45 minutes',56,7.8,1),(10012,1002,'2/2/2023 9:00',7.8,'133/85',67,208,3,'Oatmeal, Milk','Walking 40 minutes',51,7.4,1),(10013,1007,'3/2/2023 10:00',6.8,'120/78',87,190,2,'Original flavour Yogurt and Berries','Yoga 60 minutes',56,6.8,1),(10014,1000,'5/2/2023 8:30',8,'132/86',73,208,3,'Chicken Chop, Lemon Juice','Swimming 30 minutes',88,7.4,1),(10017,1001,'5/2/2023 9:00',7,'125/82',74,213,5,'Oatmeal and milk','No Activity',77,7.4,1),(10018,1003,'6/2/2023 12:30',8.5,'136/86',74,212,3,'Fried Noodle','Walking 30 minutes',71,7.6,1),(10019,1006,'7/2/2023 14:30',7.5,'130/85',74,205,4,'Tomato Mee, Lemon Juice','Gym workout 45 minutes',56,7.8,1),(10020,1002,'9/2/2023 9:30',7.5,'130/84',64,205,2,'Milo, Egg','Jogging 30 minutes',51,7.4,1),(10021,1001,'12/2/2023 8:00',6.8,'125/82',68,209,5,'Broccoli, Tofu, Rice','Walking 35 minutes',78,7.4,1),(10022,1000,'12/2/2023 8:30',7.6,'128/84',75,205,3,'Brocolli with Mushroom, Chicken Corn, Rice','Jogging 30 minutes',87,7.3,1),(10023,1003,'13/2/2023 8:00',10.2,'140/87',76,215,4,'Chicken Chop','No Activity',71,7.7,1),(10024,1008,'13/2/2023 10:30',9,'130/85',87,213,0,'Beef Noodle','No activity',64,7.1,1),(10025,1004,'14/2/2023 9:00',6.2,'118/76',78,198,4,'Fruit Salad','Jogging 40 minutes',57,6.7,1),(10026,1008,'20/2/2023 8:00',8.5,'128/83',90,209,2,'Noodles with eggs','Gym workout 45 minutes',64,7.1,1),(10027,1003,'20/2/2023 13:30',8.6,'137/85',73,210,3,'Chicken Rice','Walking 30 minutes',71,7.7,1),(10028,1002,'23/2/2023 12:50',7.2,'126/81',64,203,2,'Bihun Soup','Yoga 30 minutes',52,7.4,1),(10030,1010,'24/2/2023 10:00',6.8,'128/82',65,198,2,'Milk, Red Bean Bun','Cycling 35 minutes',50,6.9,1),(10032,1000,'26/2/2023 13:30',7.4,'126/83',74,203,2,'Kueh Tiaw Soup','Jogging 30 minutes',87,7.3,1),(10033,1003,'27/2/2023 10:30',7.4,'133/82',70,208,3,'Coffee, Wheat Biscuit','Tai Chi 30 minutes',70,7.7,1),(10034,1011,'2/3/2023 8:00',8.5,'135/87',80,212,0,'Chicken Rice','Walking 30 minutes',57,7.4,1),(10035,1003,'6/3/2023 9:30',7.7,'134/83',71,207,3,'Porridge with Salted Egg','Tai Chi 30 minutes',70,7.7,1),(10036,1011,'10/3/2023 9:00',7.3,'130/85',78,210,2,'Oatmeal, Milk','Jogging 30 minutes',57,7.4,1),(10037,1001,'10/3/2023 9:30',7.2,'128/84',69,213,5,'Noodles with eggs','Walking 35 minutes',79,7.5,1),(10038,1003,'13/3/2023 8:00',8.1,'136/84',72,210,3,'Grilled Fish with Brocolli','No Activity',70,7.8,1),(10039,1008,'15/3/2023 8:20',8,'127/83',87,207,3,'Egg Mayo Sandwich','Yoga 50 minutes',66,6.8,1),(10040,1011,'18/3/2023 9:30',7.1,'128/82',76,208,2,'Sandwich, Egg','Yoga 30 minutes',56,7.3,1),(10041,1011,'2/4/2023 12:00',8.2,'133/85',81,211,3,'Fried Noodle','No Activity',56,7.3,1),(10042,1007,'4/4/2023 8:00',6.9,'122/80',88,192,2,'Brocolli with Mushroom and Prawn, Vegetable Juice','Yoga 60 minutes',56,6.9,1),(10043,1013,'5/4/2023 8:50',8.2,'128/84',72,202,3,'Oatmeal, Milk','Walking 30 minutes',68,7.2,1),(10044,1014,'6/4/2023 8:30',7.1,'128/82',66,197,3,'Smocked Duck','Gym workout 60 minutes',83,6.9,1),(10045,1006,'10/4/2023 8:30',7.2,'126/83',70,201,3,'Egg Mayo Sandwich','Yoga 40 minutes',57,7.6,1),(10046,1002,'10/4/2023 13:30',7.2,'126/82',66,204,2,'Kolo Mee','Jogging 30 minutes',51,7.5,1),(10047,1004,'11/4/2023 9:30',7,'122/80',85,205,5,'Laksa','Yoga 30 minutes',59,6.9,1),(10048,1013,'12/4/2023 9:30',7.5,'126/82',70,201,3,'Chicken Sandwich','Yoga 30 minutes',68,7.2,1),(10049,1013,'26/4/2023 8:00',10,'132/86',76,205,4,'Fried Noodle','No Activity',69,7.3,1),(10052,1013,'3/5/2023 8:05',10.5,'135/88',74,206,5,'Pizza, Sprite','No Activity',69,7.3,1),(10053,1010,'15/5/2023 13:30',6.9,'130/83',67,200,2,'Chicken Rice','Jogging 30 minutes',52,7,1),(10054,1008,'16/5/2023 12:00',7.5,'124/81',88,204,3,'Salad Chicken Rice','Yoga 50 minutes',65,6.8,1),(10055,1015,'20/5/2023 9:00',6.9,'119/77',86,195,2,'Eggs, Tofu','Gym workout 60 minutes',62,6.9,1),(10056,1006,'8/6/2023 10:30',7,'126/82',76,197,3,'Tofu Chicken Bihun Soup','Cycling 60 minutes',57,7.5,1),(10057,1014,'10/6/2023 10:30',7.2,'126/81',67,195,3,'Coffee, Egg','Jogging 50 minutes',85,6.8,1),(10058,1004,'27/6/2023 8:00',6.4,'119/77',82,202,4,'Grilled Fish, Broccoli, Rice','No activity',58,6.8,1),(10059,1007,'2/7/2023 9:00',6.7,'121/79',85,188,2,'Chicken Breast Salad','Gym workout 60 minutes',55,6.8,1),(10060,1016,'3/7/2023 10:30',12,'135/88',92,210,0,'Chicken Chop, Sprite','No Activity',77,8.1,1),(10061,1016,'10/7/2023 8:00',10.5,'132/86',88,208,2,'Smocked Salmon, Lemon Juice','Walking 30 minutes',77,8,1),(10062,1008,'13/7/2023 9:00',7.2,'120/80',85,203,3,'Coffee, Wheat Biscuit','Jogging 45 minutes',65,6.7,1),(10063,1016,'17/7/2023 10:00',9.2,'130/85',90,207,3,'Porridge with Salted Egg','Jogging 20 minutes',78,7.9,1),(10064,1016,'24/7/2023 9:15',8,'128/84',85,204,4,'Milo, Butter Cake','Walking 30 minutes',78,7.8,1),(10065,1016,'7/8/2023 8:00',7.2,'126/82',86,202,4,'Fried Chicken, Lemon Juice','Gym workout 30 minutes',77,7.7,1),(10066,1015,'14/8/2023 8:00',7,'119/77',82,198,2,'Chicken Breast, Fruit Juice','Swimming 50 minutes',74,6.9,1),(10067,1014,'20/8/2023 8:30',6.9,'125/80',66,196,3,'Chicken Breast, Fruit Juice','Cycling 60 minutes',87,6.8,1),(10068,1016,'4/9/2023 8:00',7.5,'127/83',90,208,5,'Grilled fish','Walking 30 minutes',78,7.8,1),(10069,1017,'4/9/2023 13:00',8.1,'120/78',82,190,0,'Tomato Mee, Lemon Juice','Cycling 45 minutes',55,6.8,1),(10071,1017,'11/9/2023 8:45',7.4,'118/76',78,188,1,'Oatmeal, Milk','Walking 30 minutes',55,6.8,1),(10072,1018,'16/9/2023 9:30',8.4,'132/86',72,208,3,'Chicken sandwich','Jogging 20 minutes',89,7.4,1),(10073,1017,'18/9/2023 8:45',7.2,'119/77',77,188,1,'Chicken Sandwich','Yoga 30 Minutes',55,6.8,1),(10074,1016,'18/9/2023 9:00',8.2,'130/85',87,207,5,'Chicken sandwich','Walking 30 minutes',78,7.8,1),(10075,1016,'21/9/2023 8:30',6.8,'125/80',88,206,4,'Chicken Sandwich','No Activity',79,7.7,1),(10076,1019,'21/9/2023 13:00',10.3,'142/92',82,220,0,'Kolo Mee','Walking 30 minutes',60,7.8,1),(10077,1018,'22/9/2023 9:30',7.9,'128/83',76,206,4,'Smocked Duck','Cycling 40 minutes',88,7.3,1),(10078,1017,'25/9/2023 12:30',8,'121/79',80,190,2,'Fried Noodle','No Activity',55,6.8,1),(10079,1019,'28/9/2023 8:00',8.8,'138/88',78,215,2,'Brocolli with Mushroom, Chicken Corn, Rice','Jogging 20 minutes',60,7.8,1),(10080,1007,'1/10/2023 8:00',6.8,'119/77',86,185,2,'Broccoli, Tofu','Yoga 60 minutes',55,6.8,1),(10082,1017,'2/10/2023 8:00',7.5,'120/78',79,189,1,'Grilled Fish, Rice','Cycling 30 minutes',55,6.8,1),(10083,1016,'2/10/2023 9:30',7.9,'126/83',85,204,5,'Milo, Butter Cake','Cycling 30 minutes',78,7.7,1),(10084,1019,'5/10/2023 9:30',7.7,'131/84',77,210,3,'Oatmeal, Milk','Jogging 30 minutes',61,7.8,1),(10085,1017,'9/10/2023 9:45',7.3,'120/79',77,188,1,'Oatmeal, Egg','Jogging 40 minutes',56,6.8,1),(10086,1019,'12/10/2023 8:00',7.5,'128/82',78,208,3,'Chicken Chop, Lemon Juice','Tabata 20 minutes',61,7.7,1),(10087,1016,'16/10/2023 8:00',10.2,'130/86',87,205,5,'Broccoli with Prawn, Smocked Duck, Rice','Jogging 20 minutes',78,7.9,1),(10088,1017,'16/10/2023 10:00',8.2,'122/82',81,189,2,'Milo, Cheese Cake','No Activity',56,6.8,1),(10089,1018,'19/10/2023 11:30',7.5,'127/83',77,204,3,'Porridge with Salted Egg','Cycling 40 minutes',88,7.3,1),(10091,1015,'25/10/2023 13:00',6.8,'119/77',83,196,2,'Brocolli with Corn and Beef','Cycling 60 minutes',84,6.8,1),(10092,1018,'3/11/2023 10:30',7.4,'125/81',75,200,3,'Egg Mayo Sandwich, Coffee','Gym workout 45 minutes',89,7.2,1),(10093,1019,'12/11/2023 8:00',7.4,'125/82',80,206,3,'Grilled Fish, Rice','Cycling 35 minutes',62,7.7,1),(10094,1019,'15/1/2024 12:00',7.2,'125/80',77,204,3,'Bihun Soup','Tabata 20 minutes',61,7.6,1),(10124,1034,'25/11/2025 15:19',8.5,'128/82',74,205,4,'Toast and teh tarik','No activity',79,7.6,1),(10125,1034,'26/11/2025 15:20',10.2,'132/85',78,210,5,'Chicken Rice and Ice lemon tea','No activity',79,7.6,1),(10126,1034,'27/11/2025 15:20',12.8,'138/88',82,214,6,'Nasi Lemak and Sweet Coffee','5 minutes slow walk',79,7.7,1),(10127,1034,'28/11/2025 15:21',15.3,'142/90',87,218,7,'Fried Noodles and Cola Drink','No activity',80,7.7,1);
/*!40000 ALTER TABLE `health_data` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-30 18:03:53
