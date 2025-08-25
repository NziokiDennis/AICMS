-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: localhost    Database: counseling_system
-- ------------------------------------------------------
-- Server version	8.0.39

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
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `student_id` bigint NOT NULL,
  `counselor_id` bigint NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('PENDING','APPROVED','DECLINED','CANCELLED','COMPLETED') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `counselor_id` (`counselor_id`,`start_time`),
  KEY `student_id` (`student_id`,`start_time`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,2,5,'2025-09-02 09:00:00','2025-09-02 10:00:00','APPROVED','Experiencing anxiety about upcoming exams','2025-08-24 22:42:04','2025-08-25 12:53:57'),(2,3,6,'2025-09-02 10:00:00','2025-09-02 11:00:00','CANCELLED','Need help with time management and study strategies','2025-08-24 22:42:04',NULL),(3,4,7,'2025-09-02 13:00:00','2025-09-02 14:00:00','APPROVED','Having relationship issues with roommates','2025-08-24 22:42:04','2025-08-25 12:53:57'),(4,2,6,'2025-09-05 12:00:00','2025-09-05 13:00:00','APPROVED','','2025-08-25 00:47:36','2025-08-25 12:53:57'),(5,2,7,'2025-08-25 15:00:00','2025-08-25 16:00:00','APPROVED','','2025-08-25 01:24:28','2025-08-25 12:53:57'),(6,4,5,'2025-08-25 06:00:00','2025-08-25 07:00:00','COMPLETED','','2025-08-25 02:48:41',NULL),(7,8,7,'2025-08-28 09:00:00','2025-08-28 10:00:00','APPROVED','','2025-08-25 03:31:13','2025-08-25 13:39:01'),(8,8,5,'2025-08-25 09:00:00','2025-08-25 10:00:00','COMPLETED','','2025-08-25 03:31:46',NULL),(9,8,7,'2025-09-02 10:00:00','2025-09-02 11:00:00','APPROVED','','2025-08-25 08:57:32','2025-08-25 13:39:03');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `availability_slots`
--

DROP TABLE IF EXISTS `availability_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `availability_slots` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `counselor_id` bigint NOT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `status` enum('OPEN','BLOCKED') COLLATE utf8mb4_unicode_ci DEFAULT 'OPEN',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `counselor_id` (`counselor_id`,`start_at`,`end_at`),
  CONSTRAINT `availability_slots_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `availability_slots`
--

LOCK TABLES `availability_slots` WRITE;
/*!40000 ALTER TABLE `availability_slots` DISABLE KEYS */;
INSERT INTO `availability_slots` VALUES (1,5,'2024-09-02 09:00:00','2024-09-02 10:00:00','OPEN','2025-08-24 22:41:57'),(2,5,'2024-09-02 10:00:00','2024-09-02 11:00:00','OPEN','2025-08-24 22:41:57'),(3,5,'2024-09-02 14:00:00','2024-09-02 15:00:00','OPEN','2025-08-24 22:41:57'),(4,5,'2024-09-03 09:00:00','2024-09-03 10:00:00','OPEN','2025-08-24 22:41:57'),(5,5,'2024-09-03 11:00:00','2024-09-03 12:00:00','OPEN','2025-08-24 22:41:57'),(6,6,'2024-09-02 10:00:00','2024-09-02 11:00:00','OPEN','2025-08-24 22:41:57'),(7,6,'2024-09-02 11:00:00','2024-09-02 12:00:00','OPEN','2025-08-24 22:41:57'),(8,6,'2024-09-02 13:00:00','2024-09-02 14:00:00','OPEN','2025-08-24 22:41:57'),(9,6,'2024-09-04 09:00:00','2024-09-04 10:00:00','OPEN','2025-08-24 22:41:57'),(10,6,'2024-09-04 15:00:00','2024-09-04 16:00:00','OPEN','2025-08-24 22:41:57'),(11,7,'2024-09-02 13:00:00','2024-09-02 14:00:00','OPEN','2025-08-24 22:41:57'),(12,7,'2024-09-02 15:00:00','2024-09-02 16:00:00','OPEN','2025-08-24 22:41:57'),(13,7,'2024-09-03 10:00:00','2024-09-03 11:00:00','OPEN','2025-08-24 22:41:57'),(14,7,'2024-09-05 14:00:00','2024-09-05 15:00:00','OPEN','2025-08-24 22:41:57'),(15,7,'2024-09-05 16:00:00','2024-09-05 17:00:00','OPEN','2025-08-24 22:41:57'),(16,5,'2025-08-25 09:00:00','2025-08-25 10:00:00','OPEN','2025-08-25 00:44:05'),(17,5,'2025-08-25 10:00:00','2025-08-25 11:00:00','OPEN','2025-08-25 00:44:05'),(18,5,'2025-08-27 14:00:00','2025-08-27 15:00:00','OPEN','2025-08-25 00:44:05'),(19,5,'2025-08-29 09:00:00','2025-08-29 10:00:00','OPEN','2025-08-25 00:44:05'),(20,6,'2025-08-26 10:00:00','2025-08-26 11:00:00','OPEN','2025-08-25 00:44:05'),(21,6,'2025-08-26 11:00:00','2025-08-26 12:00:00','OPEN','2025-08-25 00:44:05'),(22,6,'2025-08-28 13:00:00','2025-08-28 14:00:00','OPEN','2025-08-25 00:44:05'),(23,6,'2025-08-28 15:00:00','2025-08-28 16:00:00','OPEN','2025-08-25 00:44:05'),(24,7,'2025-08-25 15:00:00','2025-08-25 16:00:00','OPEN','2025-08-25 00:44:05'),(25,7,'2025-08-28 09:00:00','2025-08-28 10:00:00','OPEN','2025-08-25 00:44:05'),(26,7,'2025-08-28 14:00:00','2025-08-28 15:00:00','OPEN','2025-08-25 00:44:05'),(27,5,'2025-09-01 09:00:00','2025-09-01 10:00:00','OPEN','2025-08-25 00:44:05'),(28,5,'2025-09-01 10:00:00','2025-09-01 11:00:00','OPEN','2025-08-25 00:44:05'),(29,5,'2025-09-02 14:00:00','2025-09-02 15:00:00','OPEN','2025-08-25 00:44:05'),(30,6,'2025-09-03 10:00:00','2025-09-03 11:00:00','OPEN','2025-08-25 00:44:05'),(31,6,'2025-09-03 11:00:00','2025-09-03 12:00:00','OPEN','2025-08-25 00:44:05'),(32,6,'2025-09-05 13:00:00','2025-09-05 14:00:00','OPEN','2025-08-25 00:44:05'),(33,7,'2025-09-02 10:00:00','2025-09-02 11:00:00','OPEN','2025-08-25 00:44:05'),(34,7,'2025-09-04 16:00:00','2025-09-04 17:00:00','OPEN','2025-08-25 00:44:05'),(35,5,'2025-08-25 06:00:00','2025-08-25 07:00:00','OPEN','2025-08-25 02:47:41');
/*!40000 ALTER TABLE `availability_slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `counselor_profiles`
--

DROP TABLE IF EXISTS `counselor_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `counselor_profiles` (
  `user_id` bigint NOT NULL,
  `specialty` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_mode` enum('IN_PERSON','VIDEO','PHONE') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `counselor_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `counselor_profiles`
--

LOCK TABLES `counselor_profiles` WRITE;
/*!40000 ALTER TABLE `counselor_profiles` DISABLE KEYS */;
INSERT INTO `counselor_profiles` VALUES (5,'Anxiety & Depression','VIDEO','Licensed clinical psychologist specializing in anxiety disorders and depression treatment for university students.','Psychology Building, Room 201'),(6,'Academic Stress','IN_PERSON','Educational counselor with 10+ years experience helping students manage academic pressure and study habits.','Student Services Center, Room 105'),(7,'Relationships & Social Issues','PHONE','Marriage and family therapist focusing on relationship counseling and social anxiety in young adults.','Counseling Center, Room 302'),(11,'Psychodynamic','IN_PERSON','Trained and taught psychodynamics for 10 years','Nairobi');
/*!40000 ALTER TABLE `counselor_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `student_id` bigint NOT NULL,
  `counselor_id` bigint NOT NULL,
  `session_id` bigint NOT NULL,
  `rating` int DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`,`counselor_id`,`session_id`),
  KEY `counselor_id` (`counselor_id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  CONSTRAINT `feedback_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (1,2,5,1,5,'Dr. Wilson was very understanding and provided excellent coping strategies. Highly recommend!','2025-08-24 22:42:23'),(2,4,5,5,4,'She was attentive to my questions and gave comforting advice','2025-08-25 03:28:19'),(3,8,5,6,2,'Dr Sarah didn\'t give me time to explain myself fully','2025-08-25 03:34:34');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `session_id` bigint NOT NULL,
  `counselor_id` bigint NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `visibility` enum('PRIVATE','PUBLISHED') COLLATE utf8mb4_unicode_ci DEFAULT 'PRIVATE',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `counselor_id` (`counselor_id`),
  CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
INSERT INTO `notes` VALUES (1,1,5,'Initial consultation completed. Student shows signs of mild anxiety. Recommended breathing exercises and scheduled follow-up.','PUBLISHED','2025-08-24 22:42:18'),(2,2,7,'First session focused on communication skills. Student is receptive to feedback.','PUBLISHED','2025-08-24 22:42:18'),(3,5,5,'He was responsive and listened keenly. He needs to work with recommended steps I gave him.','PUBLISHED','2025-08-25 03:26:44'),(4,6,5,'Dominic listens keenly but very adamant about his beliefs','PUBLISHED','2025-08-25 03:33:16');
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint NOT NULL,
  `status` enum('SCHEDULED','IN_PROGRESS','COMPLETED','CANCELLED') COLLATE utf8mb4_unicode_ci DEFAULT 'SCHEDULED',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (1,1,'SCHEDULED',NULL,NULL),(2,3,'SCHEDULED',NULL,NULL),(3,5,'SCHEDULED',NULL,NULL),(4,4,'SCHEDULED',NULL,NULL),(5,6,'COMPLETED','2025-08-25 06:25:29','2025-08-25 06:25:37'),(6,8,'COMPLETED','2025-08-25 06:32:28','2025-08-25 06:32:36'),(7,7,'SCHEDULED',NULL,NULL),(8,9,'SCHEDULED',NULL,NULL);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `role` enum('STUDENT','COUNSELOR','ADMIN') COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'ADMIN','System Administrator','admin@counseling.edu','$2y$10$36qEP4s6JtFYbXCsN/H6Iua4Xb8e.rmdXTw45msKVaBm4FicQOfO.','+1-555-0001','2025-08-24 22:40:59'),(2,'STUDENT','John Smith','john.smith@student.edu','$2y$10$36qEP4s6JtFYbXCsN/H6Iua4Xb8e.rmdXTw45msKVaBm4FicQOfO.','+1-555-1001','2025-08-24 22:40:59'),(3,'STUDENT','Emiy Johnson','emiy.johnson@student.edu','$2y$10$36qEP4s6JtFYbXCsN/H6Iua4Xb8e.rmdXTw45msKVaBm4FicQOfO.','+1-555-1002','2025-08-24 22:40:59'),(4,'STUDENT','Michael Davis','michael.davis@student.edu','$2y$10$36qEP4s6JtFYbXCsN/H6Iua4Xb8e.rmdXTw45msKVaBm4FicQOfO.','+1-555-1003','2025-08-24 22:40:59'),(5,'COUNSELOR','Dr. Sarah Wilson','dr.wilson@counseling.edu','$2y$10$36qEP4s6JtFYbXCsN/H6Iua4Xb8e.rmdXTw45msKVaBm4FicQOfO.','+1-555-2001','2025-08-24 22:40:59'),(6,'COUNSELOR','Dr. Robert Chen','dr.chen@counseling.edu','$2y$10$36qEP4s6JtFYbXCsN/H6Iua4Xb8e.rmdXTw45msKVaBm4FicQOfO.','+1-555-2002','2025-08-24 22:40:59'),(7,'COUNSELOR','Dr. Maria Garcia','dr.garcia@counseling.edu','$2y$10$36qEP4s6JtFYbXCsN/H6Iua4Xb8e.rmdXTw45msKVaBm4FicQOfO.','+1-555-2003','2025-08-24 22:40:59'),(8,'STUDENT','Dominic Orenge','dominic.orenge@student.edu','$2y$10$36qEP4s6JtFYbXCsN/H6Iua4Xb8e.rmdXTw45msKVaBm4FicQOfO.','+1-555-2004','2025-08-24 22:48:00'),(9,'STUDENT','Nzioki Dennis','nzioki.dennis@student.edu','$2y$10$0mlUYri3C2gXk5oDVt/5UuUys76.cAy18DccE9JHSa2pQNmk0B3UO','+1-555-2005','2025-08-25 09:15:03'),(10,'STUDENT','Mutua Jirani','mutua.jirani@student.edu','$2y$10$aw0aLjVln9tfmEIRpx04beKkv4ZbXc4KPzV87H.t/9nDQMB67gjAS','+1-555-2007','2025-08-25 11:12:29'),(11,'COUNSELOR','Dr. John Doe','dr.john@counseling.edu','$2y$10$fSYXUkd6ZUYAynOdbT6s6eCT3XAZq9u8YRjkK16O0SQNEUoPn9kma','+1-555-2008','2025-08-25 11:15:16');
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

-- Dump completed on 2025-08-25 18:39:47
