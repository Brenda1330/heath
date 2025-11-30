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
-- Table structure for table `patient_summaries`
--

DROP TABLE IF EXISTS `patient_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_summaries` (
  `summary_id` int unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `avg_cgm_level` decimal(5,2) DEFAULT NULL,
  `food_summary` text COLLATE utf8mb4_unicode_ci,
  `activity_summary` text COLLATE utf8mb4_unicode_ci,
  `recommendation_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_used` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `algorithm_used` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`summary_id`),
  KEY `fk_summary_to_patient` (`patient_id`),
  CONSTRAINT `fk_summary_to_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_summaries`
--

LOCK TABLES `patient_summaries` WRITE;
/*!40000 ALTER TABLE `patient_summaries` DISABLE KEYS */;
INSERT INTO `patient_summaries` VALUES (35,1001,'2025-11-25 17:20:36',7.40,'Broccoli, Tofu, Rice, Chicken Rice, Milo, Butter Cake, Noodles with eggs, Oatmeal and milk','No Activity, Tai Chi 30 minutes, Walking 30 minutes, Walking 35 minutes','7 similar patients were found with successful long-term health outcomes.\n\nBased on the proven long-term approaches of these individuals, specific diet strategies have shown to be highly effective. Your current food intake already incorporates Rice, Oatmeal, and Milk, all of which were among the most effective dietary components for the successful cohort. To further support your long-term health goals, consistently prioritizing and building meals around these foods, similar to the patterns observed, is recommended.\n\nFor sustainable physical activity, the successful cohort demonstrated positive outcomes with routines like Walking for 30 minutes and Jogging for 20 minutes. Your current practice of Walking for 30-35 minutes is already well-aligned with a key strategy that worked for many. Continuing this consistent walking regimen and considering the incorporation of Jogging for 20 minutes can be a highly effective, proven long-term approach to maintaining and improving your health.','Gemini (Summary)','PPR'),(36,1001,'2025-11-25 17:22:59',7.40,'Broccoli, Tofu, Rice, Chicken Rice, Milo, Butter Cake, Noodles with eggs, Oatmeal and milk','No Activity, Tai Chi 30 minutes, Walking 30 minutes, Walking 35 minutes','Your overall lifestyle fits the \'High Glycemic Load, Inconsistent Movement\' archetype. This suggests that your dietary patterns frequently include foods that rapidly elevate blood glucose, such as sugary beverages and refined carbohydrates (e.g., Milo, Butter Cake, Chicken Rice, noodles), which, when combined with your varied but generally low-to-moderate intensity physical activity (walking, Tai Chi, and periods of no activity), contribute to your elevated average blood sugar levels. To improve long-term balance, focus on consistently choosing whole, unprocessed foods, especially those rich in fiber and lean protein, and aim to increase the frequency and intensity of your physical activity, perhaps by adding short bursts of vigorous exercise or dedicated strength training sessions to improve glucose metabolism and overall cardiovascular health.','Gemini (Summary)','Node2Vec'),(37,1001,'2025-11-25 17:25:23',7.40,'Broccoli, Tofu, Rice, Chicken Rice, Milo, Butter Cake, Noodles with eggs, Oatmeal and milk','No Activity, Tai Chi 30 minutes, Walking 30 minutes, Walking 35 minutes','Long-term risk assessment: Moderate. An average glucose of 7.4 mmol/L indicates sustained glucose dysregulation, suggesting significant metabolic strain. While this is an average and not a single fasting or diagnostic reading, it consistently places the patient in a zone of elevated risk. If this trend continues, the primary risk over the next 5-10 years is the progression to Type 2 Diabetes, further insulin resistance, and an increased likelihood of associated cardiovascular or microvascular complications. Proactively reducing intake of high-sugar items like Milo and Butter Cake, moderating refined carbohydrate portions from Rice and Noodles, and consistently integrating physical activity beyond intermittent walking or Tai Chi are crucial preventative steps.','Gemini (Summary)','GAT');
/*!40000 ALTER TABLE `patient_summaries` ENABLE KEYS */;
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
