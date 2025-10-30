/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.8-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: hotones
-- ------------------------------------------------------
-- Server version	11.4.8-MariaDB-ubu2404

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `contributor_profiles`
--

DROP TABLE IF EXISTS `contributor_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contributor_profiles` (
  `contributor_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  PRIMARY KEY (`contributor_id`,`profile_id`),
  KEY `IDX_BDF600067A19A357` (`contributor_id`),
  KEY `IDX_BDF60006CCFA12B8` (`profile_id`),
  CONSTRAINT `FK_BDF600067A19A357` FOREIGN KEY (`contributor_id`) REFERENCES `contributors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_BDF60006CCFA12B8` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contributor_profiles`
--

LOCK TABLES `contributor_profiles` WRITE;
/*!40000 ALTER TABLE `contributor_profiles` DISABLE KEYS */;
INSERT INTO `contributor_profiles` VALUES
(12,18),
(13,11),
(14,20),
(15,21),
(17,18);
/*!40000 ALTER TABLE `contributor_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contributors`
--

DROP TABLE IF EXISTS `contributors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contributors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(180) NOT NULL,
  `cjm` decimal(10,2) DEFAULT NULL,
  `tjm` decimal(10,2) DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_72D26262A76ED395` (`user_id`),
  CONSTRAINT `FK_72D26262A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contributors`
--

LOCK TABLES `contributors` WRITE;
/*!40000 ALTER TABLE `contributors` DISABLE KEYS */;
INSERT INTO `contributors` VALUES
(1,'Jean Dupont',450.00,750.00,1,2,'jean.dupont@yopmail.com','',''),
(12,'Emma Développeuse',450.00,550.00,1,NULL,'','',''),
(13,'Lucas Backend',500.00,550.00,1,NULL,'','',''),
(14,'Sophie Designer',400.00,650.00,1,NULL,'','',''),
(15,'Thomas DevOps',550.00,850.00,1,NULL,'','',''),
(17,'Julie Frontend',480.00,NULL,1,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `contributors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dim_contributor`
--

DROP TABLE IF EXISTS `dim_contributor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_contributor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name_value` varchar(180) NOT NULL,
  `role_value` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `composite_key` varchar(250) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8BC20A2C13775659` (`composite_key`),
  KEY `IDX_8BC20A2CA76ED395` (`user_id`),
  CONSTRAINT `FK_8BC20A2CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dim_contributor`
--

LOCK TABLES `dim_contributor` WRITE;
/*!40000 ALTER TABLE `dim_contributor` DISABLE KEYS */;
INSERT INTO `dim_contributor` VALUES
(3,NULL,'Alice Dupont','project_manager',1,'null_project_manager_active_1327d27d4578177fb70b946846708ebd'),
(4,NULL,'Bob Martin','project_manager',1,'null_project_manager_active_133ed0031b57916bdffcf0c8d89b636a'),
(5,NULL,'Claire Rousseau','sales_person',1,'null_sales_person_active_6df10506d04b9dbf5dc2d7fe9130ac71'),
(6,NULL,'David Moreau','sales_person',1,'null_sales_person_active_3e1f2a5e94450c8371562c74671ace76'),
(7,NULL,'Emma Bernard','project_director',1,'null_project_director_active_1f2dc6543007952440fb29acb2d466ec'),
(8,NULL,'François Petit','key_account_manager',1,'null_key_account_manager_active_a50f43aee357a8f06badfdf3cbae77a8'),
(9,15,'Alice Martin','project_manager',1,'15_project_manager_active_57ff9c45e733f7398a839dba78d3e116'),
(10,17,'Claire Moreau','project_director',1,'17_project_director_active_910526d72cb3e69b05fc3d24347857d8');
/*!40000 ALTER TABLE `dim_contributor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dim_project_type`
--

DROP TABLE IF EXISTS `dim_project_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_project_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_type` varchar(20) NOT NULL,
  `service_category` varchar(50) DEFAULT NULL,
  `status_value` varchar(20) NOT NULL,
  `is_internal` tinyint(1) NOT NULL,
  `composite_key` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_EEC09CB113775659` (`composite_key`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dim_project_type`
--

LOCK TABLES `dim_project_type` WRITE;
/*!40000 ALTER TABLE `dim_project_type` DISABLE KEYS */;
INSERT INTO `dim_project_type` VALUES
(7,'forfait','E-commerce','active',0,'forfait_E-commerce_active_external'),
(8,'forfait','Brand','active',0,'forfait_Brand_active_external'),
(9,'regie','E-commerce','active',0,'regie_E-commerce_active_external'),
(10,'regie','Brand','active',0,'regie_Brand_active_external'),
(11,'forfait',NULL,'completed',0,'forfait_null_completed_external'),
(12,'forfait','E-commerce','active',1,'forfait_E-commerce_active_internal'),
(14,'regie','Mobile','active',0,'regie_Mobile_active_external'),
(15,'',NULL,'active',0,'_null_active_external'),
(16,'forfait','Application métier','active',0,'forfait_Application métier_active_external');
/*!40000 ALTER TABLE `dim_project_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dim_time`
--

DROP TABLE IF EXISTS `dim_time`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_time` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_value` date NOT NULL,
  `year_value` int(11) NOT NULL,
  `quarter_value` int(11) NOT NULL,
  `month_value` int(11) NOT NULL,
  `period_year_month` varchar(20) NOT NULL,
  `period_year_quarter` varchar(20) NOT NULL,
  `month_name` varchar(50) NOT NULL,
  `quarter_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6F547BD9A787B0B8` (`date_value`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dim_time`
--

LOCK TABLES `dim_time` WRITE;
/*!40000 ALTER TABLE `dim_time` DISABLE KEYS */;
INSERT INTO `dim_time` VALUES
(13,'2024-01-01',2024,1,1,'2024-01','2024-Q1','Janvier 2024','Q1 2024'),
(14,'2024-02-01',2024,1,2,'2024-02','2024-Q1','Février 2024','Q1 2024'),
(15,'2024-03-01',2024,1,3,'2024-03','2024-Q1','Mars 2024','Q1 2024'),
(16,'2024-04-01',2024,2,4,'2024-04','2024-Q2','Avril 2024','Q2 2024'),
(17,'2024-05-01',2024,2,5,'2024-05','2024-Q2','Mai 2024','Q2 2024'),
(18,'2024-06-01',2024,2,6,'2024-06','2024-Q2','Juin 2024','Q2 2024'),
(19,'2024-07-01',2024,3,7,'2024-07','2024-Q3','Juillet 2024','Q3 2024'),
(20,'2024-08-01',2024,3,8,'2024-08','2024-Q3','Août 2024','Q3 2024'),
(21,'2024-09-01',2024,3,9,'2024-09','2024-Q3','Septembre 2024','Q3 2024'),
(22,'2024-10-01',2024,4,10,'2024-10','2024-Q4','Octobre 2024','Q4 2024'),
(23,'2024-11-01',2024,4,11,'2024-11','2024-Q4','Novembre 2024','Q4 2024'),
(24,'2024-12-01',2024,4,12,'2024-12','2024-Q4','Décembre 2024','Q4 2024'),
(46,'2025-01-01',2025,1,1,'2025-01','2025-Q1','Janvier 2025','Q1 2025'),
(47,'2025-02-01',2025,1,2,'2025-02','2025-Q1','Février 2025','Q1 2025'),
(48,'2025-03-01',2025,1,3,'2025-03','2025-Q1','Mars 2025','Q1 2025'),
(49,'2025-04-01',2025,2,4,'2025-04','2025-Q2','Avril 2025','Q2 2025'),
(50,'2025-05-01',2025,2,5,'2025-05','2025-Q2','Mai 2025','Q2 2025'),
(51,'2025-06-01',2025,2,6,'2025-06','2025-Q2','Juin 2025','Q2 2025'),
(52,'2025-07-01',2025,3,7,'2025-07','2025-Q3','Juillet 2025','Q3 2025'),
(53,'2025-08-01',2025,3,8,'2025-08','2025-Q3','Août 2025','Q3 2025'),
(54,'2025-09-01',2025,3,9,'2025-09','2025-Q3','Septembre 2025','Q3 2025'),
(55,'2025-10-01',2025,4,10,'2025-10','2025-Q4','Octobre 2025','Q4 2025'),
(56,'2025-11-01',2025,4,11,'2025-11','2025-Q4','Novembre 2025','Q4 2025'),
(57,'2025-12-01',2025,4,12,'2025-12','2025-Q4','Décembre 2025','Q4 2025');
/*!40000 ALTER TABLE `dim_time` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctrine_migration_versions`
--

LOCK TABLES `doctrine_migration_versions` WRITE;
/*!40000 ALTER TABLE `doctrine_migration_versions` DISABLE KEYS */;
INSERT INTO `doctrine_migration_versions` VALUES
('DoctrineMigrations\\Version20251015134957','2025-10-15 13:50:14',21),
('DoctrineMigrations\\Version20251015140457','2025-10-15 14:05:30',61),
('DoctrineMigrations\\Version20251015140742','2025-10-15 14:07:57',7),
('DoctrineMigrations\\Version20251016054226','2025-10-16 05:42:32',60),
('DoctrineMigrations\\Version20251016060656','2025-10-16 06:07:02',123),
('DoctrineMigrations\\Version20251017063124','2025-10-17 06:31:33',191),
('DoctrineMigrations\\Version20251017075300','2025-10-17 07:55:09',98),
('DoctrineMigrations\\Version20251019155000','2025-10-19 20:22:30',2),
('DoctrineMigrations\\Version20251019180800',NULL,NULL),
('DoctrineMigrations\\Version20251020060724','2025-10-20 06:07:57',88),
('DoctrineMigrations\\Version20251020145307','2025-10-20 14:54:37',72),
('DoctrineMigrations\\Version20251022142033','2025-10-22 14:20:34',70),
('DoctrineMigrations\\Version20251025125106','2025-10-25 12:51:13',34),
('DoctrineMigrations\\Version20251025125640','2025-10-25 12:56:53',58),
('DoctrineMigrations\\Version20251025125743','2025-10-25 12:57:50',6);
/*!40000 ALTER TABLE `doctrine_migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employment_period_profiles`
--

DROP TABLE IF EXISTS `employment_period_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employment_period_profiles` (
  `employment_period_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  PRIMARY KEY (`employment_period_id`,`profile_id`),
  KEY `IDX_A643DBB15A128608` (`employment_period_id`),
  KEY `IDX_A643DBB1CCFA12B8` (`profile_id`),
  CONSTRAINT `FK_A643DBB15A128608` FOREIGN KEY (`employment_period_id`) REFERENCES `employment_periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A643DBB1CCFA12B8` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employment_period_profiles`
--

LOCK TABLES `employment_period_profiles` WRITE;
/*!40000 ALTER TABLE `employment_period_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `employment_period_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employment_periods`
--

DROP TABLE IF EXISTS `employment_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employment_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contributor_id` int(11) NOT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `cjm` decimal(10,2) DEFAULT NULL,
  `weekly_hours` decimal(5,2) NOT NULL DEFAULT 35.00,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `tjm` decimal(10,2) DEFAULT NULL,
  `work_time_percentage` decimal(5,2) NOT NULL DEFAULT 100.00,
  PRIMARY KEY (`id`),
  KEY `IDX_B996D77B7A19A357` (`contributor_id`),
  CONSTRAINT `FK_B996D77B7A19A357` FOREIGN KEY (`contributor_id`) REFERENCES `contributors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employment_periods`
--

LOCK TABLES `employment_periods` WRITE;
/*!40000 ALTER TABLE `employment_periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `employment_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fact_project_metrics`
--

DROP TABLE IF EXISTS `fact_project_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fact_project_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dim_time_id` int(11) NOT NULL,
  `dim_project_type_id` int(11) NOT NULL,
  `dim_project_manager_id` int(11) DEFAULT NULL,
  `dim_sales_person_id` int(11) DEFAULT NULL,
  `dim_project_director_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `project_count` int(11) NOT NULL,
  `active_project_count` int(11) NOT NULL,
  `completed_project_count` int(11) NOT NULL,
  `order_count` int(11) NOT NULL,
  `pending_order_count` int(11) NOT NULL,
  `won_order_count` int(11) NOT NULL,
  `contributor_count` int(11) NOT NULL,
  `total_revenue` decimal(15,2) NOT NULL,
  `total_costs` decimal(15,2) NOT NULL,
  `gross_margin` decimal(15,2) NOT NULL,
  `margin_percentage` decimal(5,2) NOT NULL,
  `pending_revenue` decimal(15,2) NOT NULL,
  `average_order_value` decimal(15,2) NOT NULL,
  `total_sold_days` decimal(10,2) NOT NULL,
  `total_worked_days` decimal(10,2) NOT NULL,
  `utilization_rate` decimal(5,2) NOT NULL,
  `calculated_at` datetime NOT NULL,
  `granularity` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_27991A9444D4FE30` (`dim_time_id`),
  KEY `IDX_27991A94E44D565F` (`dim_project_type_id`),
  KEY `IDX_27991A94EC5B4665` (`dim_project_manager_id`),
  KEY `IDX_27991A94AA2A35A7` (`dim_sales_person_id`),
  KEY `IDX_27991A9460687321` (`dim_project_director_id`),
  KEY `IDX_27991A94166D1F9C` (`project_id`),
  KEY `IDX_27991A948D9F6D38` (`order_id`),
  CONSTRAINT `FK_27991A94166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `FK_27991A9444D4FE30` FOREIGN KEY (`dim_time_id`) REFERENCES `dim_time` (`id`),
  CONSTRAINT `FK_27991A9460687321` FOREIGN KEY (`dim_project_director_id`) REFERENCES `dim_contributor` (`id`),
  CONSTRAINT `FK_27991A948D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `FK_27991A94AA2A35A7` FOREIGN KEY (`dim_sales_person_id`) REFERENCES `dim_contributor` (`id`),
  CONSTRAINT `FK_27991A94E44D565F` FOREIGN KEY (`dim_project_type_id`) REFERENCES `dim_project_type` (`id`),
  CONSTRAINT `FK_27991A94EC5B4665` FOREIGN KEY (`dim_project_manager_id`) REFERENCES `dim_contributor` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fact_project_metrics`
--

LOCK TABLES `fact_project_metrics` WRITE;
/*!40000 ALTER TABLE `fact_project_metrics` DISABLE KEYS */;
INSERT INTO `fact_project_metrics` VALUES
(1,13,7,3,5,NULL,NULL,NULL,3,2,2,4,3,1,6,11622.40,8949.25,2673.15,22.99,16769.00,7097.85,57.00,69.00,121.05,'2025-10-19 16:01:03','monthly'),
(2,13,8,3,6,NULL,NULL,NULL,1,1,2,7,1,5,7,14111.20,11147.85,2963.35,20.99,10172.00,3469.02,75.00,72.00,96.00,'2025-10-19 16:01:03','monthly'),
(3,13,9,4,5,NULL,NULL,NULL,1,1,2,6,2,4,7,27632.00,17131.84,10500.16,38.00,10612.00,6374.00,56.00,93.00,166.07,'2025-10-19 16:01:03','monthly'),
(4,13,10,3,5,NULL,NULL,NULL,3,2,1,4,0,4,8,27800.80,16958.49,10842.31,38.99,20336.00,12034.20,24.00,44.00,183.33,'2025-10-19 16:01:03','monthly'),
(5,13,11,4,6,NULL,NULL,NULL,4,2,2,8,3,5,3,38285.60,22971.36,15314.24,40.00,10356.00,6080.20,79.00,32.00,40.50,'2025-10-19 16:01:03','monthly'),
(6,13,12,3,6,NULL,NULL,NULL,1,2,0,5,3,1,3,17699.20,13451.39,4247.81,24.00,23120.00,8163.84,92.00,40.00,43.47,'2025-10-19 16:01:03','monthly'),
(7,14,7,4,5,NULL,NULL,NULL,3,3,2,7,1,5,8,21035.00,13883.10,7151.90,34.00,23538.00,6367.57,22.00,61.00,277.27,'2025-10-19 16:01:03','monthly'),
(8,14,8,3,5,NULL,NULL,NULL,3,3,2,7,0,4,8,24071.00,14442.60,9628.40,40.00,16228.00,5757.00,67.00,26.00,38.80,'2025-10-19 16:01:03','monthly'),
(9,14,9,3,6,NULL,NULL,NULL,2,2,2,4,2,1,8,10593.00,7732.89,2860.11,27.00,7502.00,4523.75,67.00,18.00,26.86,'2025-10-19 16:01:03','monthly'),
(10,14,10,4,6,NULL,NULL,NULL,2,2,1,7,2,1,6,24465.00,16391.55,8073.45,33.00,15555.00,5717.14,25.00,31.00,124.00,'2025-10-19 16:01:03','monthly'),
(11,14,11,4,5,NULL,NULL,NULL,2,2,2,1,2,3,2,35230.00,27479.40,7750.60,22.00,22450.00,57680.00,65.00,79.00,121.53,'2025-10-19 16:01:03','monthly'),
(12,14,12,3,6,NULL,NULL,NULL,1,2,1,7,0,4,3,44070.00,32611.80,11458.20,26.00,10801.00,7838.71,65.00,85.00,130.76,'2025-10-19 16:01:03','monthly'),
(13,15,7,3,6,NULL,NULL,NULL,2,2,2,3,3,3,8,36782.00,26850.86,9931.14,27.00,14030.00,16937.33,45.00,61.00,135.55,'2025-10-19 16:01:03','monthly'),
(14,15,8,3,6,NULL,NULL,NULL,3,3,1,2,2,1,2,26989.00,18352.52,8636.48,32.00,11436.00,19212.50,80.00,21.00,26.25,'2025-10-19 16:01:03','monthly'),
(15,15,9,4,6,NULL,NULL,NULL,5,1,2,7,2,3,8,40622.00,25591.86,15030.14,37.00,21651.00,8896.14,90.00,45.00,50.00,'2025-10-19 16:01:03','monthly'),
(16,15,10,3,6,NULL,NULL,NULL,3,3,0,8,3,4,5,43738.00,31053.98,12684.02,29.00,12078.00,6977.00,94.00,76.00,80.85,'2025-10-19 16:01:03','monthly'),
(17,15,11,4,6,NULL,NULL,NULL,2,2,0,1,3,5,8,37066.00,28540.82,8525.18,23.00,22501.00,59567.00,52.00,83.00,159.61,'2025-10-19 16:01:03','monthly'),
(18,15,12,4,6,NULL,NULL,NULL,4,1,1,3,3,3,4,30563.00,21088.47,9474.53,31.00,24759.00,18440.66,77.00,72.00,93.50,'2025-10-19 16:01:03','monthly'),
(19,16,7,4,5,NULL,NULL,NULL,1,2,2,8,1,4,6,35821.00,28656.80,7164.20,20.00,21337.00,7144.75,68.00,31.00,45.58,'2025-10-19 16:01:03','monthly'),
(20,16,8,3,6,NULL,NULL,NULL,5,1,1,1,3,2,7,24012.00,18249.12,5762.88,24.00,5627.00,29639.00,52.00,18.00,34.61,'2025-10-19 16:01:03','monthly'),
(21,16,9,4,6,NULL,NULL,NULL,4,2,1,6,3,3,5,48653.00,34543.63,14109.37,29.00,19719.00,11395.33,66.00,80.00,121.21,'2025-10-19 16:01:03','monthly'),
(22,16,10,4,5,NULL,NULL,NULL,3,2,1,3,1,4,2,48301.00,38157.79,10143.21,21.00,23096.00,23799.00,25.00,60.00,240.00,'2025-10-19 16:01:03','monthly'),
(23,16,11,3,6,NULL,NULL,NULL,2,1,0,3,1,3,6,16531.00,10745.15,5785.85,35.00,5489.00,7340.00,71.00,66.00,92.95,'2025-10-19 16:01:03','monthly'),
(24,16,12,3,6,NULL,NULL,NULL,3,1,0,8,0,5,8,24354.00,18021.96,6332.04,26.00,23597.00,5993.87,36.00,64.00,177.77,'2025-10-19 16:01:03','monthly'),
(25,17,7,3,6,NULL,NULL,NULL,1,3,2,1,3,4,2,39120.00,28166.40,10953.60,28.00,24064.00,63184.00,71.00,33.00,46.47,'2025-10-19 16:01:03','monthly'),
(26,17,8,4,6,NULL,NULL,NULL,1,2,1,8,3,3,7,29395.00,17637.00,11758.00,40.00,23587.00,6622.75,65.00,21.00,32.30,'2025-10-19 16:01:03','monthly'),
(27,17,9,3,5,NULL,NULL,NULL,2,2,2,2,3,1,8,39689.00,30163.64,9525.36,24.00,7917.00,23803.00,65.00,61.00,93.84,'2025-10-19 16:01:03','monthly'),
(28,17,10,3,5,NULL,NULL,NULL,3,1,0,3,3,2,6,32609.00,23804.57,8804.43,27.00,12961.00,15190.00,22.00,69.00,313.63,'2025-10-19 16:01:03','monthly'),
(29,17,11,4,6,NULL,NULL,NULL,4,2,0,7,0,2,8,28347.00,20126.37,8220.63,29.00,15691.00,6291.14,69.00,33.00,47.82,'2025-10-19 16:01:03','monthly'),
(30,17,12,3,5,NULL,NULL,NULL,2,1,2,3,1,5,4,33459.00,23421.30,10037.70,30.00,18607.00,17355.33,33.00,32.00,96.96,'2025-10-19 16:01:03','monthly'),
(31,18,7,4,5,NULL,NULL,NULL,2,1,2,2,3,4,3,49499.00,38609.22,10889.78,22.00,15613.00,32556.00,27.00,15.00,55.55,'2025-10-19 16:01:03','monthly'),
(32,18,8,3,6,NULL,NULL,NULL,2,2,1,8,1,4,5,46236.00,29591.04,16644.96,36.00,13757.00,7499.12,36.00,91.00,252.77,'2025-10-19 16:01:03','monthly'),
(33,18,9,3,5,NULL,NULL,NULL,4,1,2,5,2,4,5,45650.00,35150.50,10499.50,23.00,22203.00,13570.60,78.00,19.00,24.35,'2025-10-19 16:01:03','monthly'),
(34,18,10,3,5,NULL,NULL,NULL,2,3,2,4,3,4,7,26114.00,20368.92,5745.08,22.00,9853.00,8991.75,73.00,43.00,58.90,'2025-10-19 16:01:03','monthly'),
(35,18,11,4,6,NULL,NULL,NULL,2,3,0,6,2,4,2,31550.00,22085.00,9465.00,30.00,13079.00,7438.16,34.00,92.00,270.58,'2025-10-19 16:01:03','monthly'),
(36,18,12,3,5,NULL,NULL,NULL,4,1,0,8,2,1,7,49199.00,33947.31,15251.69,31.00,15012.00,8026.37,22.00,75.00,340.90,'2025-10-19 16:01:03','monthly'),
(37,19,7,3,5,NULL,NULL,NULL,4,1,0,4,3,4,2,11773.60,8712.46,3061.14,26.00,9688.00,5365.40,25.00,78.00,312.00,'2025-10-19 16:01:03','monthly'),
(38,19,8,3,6,NULL,NULL,NULL,2,1,0,2,0,4,4,16398.40,10658.96,5739.44,35.00,15238.00,15818.20,91.00,71.00,78.02,'2025-10-19 16:01:03','monthly'),
(39,19,9,3,6,NULL,NULL,NULL,4,2,1,2,2,4,8,33352.80,23346.96,10005.84,30.00,23123.00,28237.90,97.00,69.00,71.13,'2025-10-19 16:01:03','monthly'),
(40,19,10,4,5,NULL,NULL,NULL,4,2,1,2,2,2,3,9346.40,7383.66,1962.74,20.99,21237.00,15291.70,77.00,34.00,44.15,'2025-10-19 16:01:03','monthly'),
(41,19,11,4,5,NULL,NULL,NULL,5,3,2,4,0,5,5,8376.00,5193.12,3182.88,38.00,14701.00,5769.25,26.00,32.00,123.07,'2025-10-19 16:01:03','monthly'),
(42,19,12,3,5,NULL,NULL,NULL,3,1,2,7,1,3,4,22058.40,14337.96,7720.44,35.00,21508.00,6223.77,55.00,62.00,112.72,'2025-10-19 16:01:03','monthly'),
(43,20,7,3,5,NULL,NULL,NULL,2,1,1,6,2,5,5,15899.40,11447.57,4451.83,27.99,12301.00,4700.06,96.00,59.00,61.45,'2025-10-19 16:01:03','monthly'),
(44,20,8,3,6,NULL,NULL,NULL,2,3,0,3,3,3,2,19055.40,12004.90,7050.50,37.00,11376.00,10143.80,69.00,23.00,33.33,'2025-10-19 16:01:03','monthly'),
(45,20,9,3,6,NULL,NULL,NULL,3,1,1,8,2,4,3,14345.40,11045.96,3299.44,22.99,14097.00,3555.30,32.00,56.00,175.00,'2025-10-19 16:01:03','monthly'),
(46,20,10,4,5,NULL,NULL,NULL,3,3,0,1,2,1,4,23391.60,14034.96,9356.64,40.00,19548.00,42939.60,71.00,51.00,71.83,'2025-10-19 16:01:03','monthly'),
(47,20,11,3,5,NULL,NULL,NULL,2,1,0,6,1,5,5,11135.40,7015.30,4120.10,37.00,12496.00,3938.56,60.00,51.00,85.00,'2025-10-19 16:01:03','monthly'),
(48,20,12,3,6,NULL,NULL,NULL,4,3,2,6,0,4,2,28529.40,18829.40,9700.00,34.00,18972.00,7916.90,24.00,44.00,183.33,'2025-10-19 16:01:03','monthly'),
(49,21,7,3,5,NULL,NULL,NULL,3,1,1,3,0,3,3,26681.00,20811.18,5869.82,22.00,18057.00,14912.66,70.00,93.00,132.85,'2025-10-19 16:01:03','monthly'),
(50,21,8,4,6,NULL,NULL,NULL,2,2,0,5,0,2,7,15304.00,10253.68,5050.32,33.00,20121.00,7085.00,27.00,47.00,174.07,'2025-10-19 16:01:03','monthly'),
(51,21,9,3,6,NULL,NULL,NULL,5,3,1,6,0,3,8,22758.00,15475.44,7282.56,32.00,12210.00,5828.00,62.00,29.00,46.77,'2025-10-19 16:01:03','monthly'),
(52,21,10,3,5,NULL,NULL,NULL,3,3,1,6,1,5,5,42518.00,25935.98,16582.02,39.00,23848.00,11061.00,83.00,57.00,68.67,'2025-10-19 16:01:03','monthly'),
(53,21,11,4,6,NULL,NULL,NULL,3,3,0,5,1,5,6,20718.00,12845.16,7872.84,38.00,19190.00,7981.60,64.00,88.00,137.50,'2025-10-19 16:01:03','monthly'),
(54,21,12,4,5,NULL,NULL,NULL,2,3,1,3,0,2,8,30114.00,19875.24,10238.76,34.00,9598.00,13237.33,63.00,74.00,117.46,'2025-10-19 16:01:03','monthly'),
(55,22,7,3,6,NULL,NULL,NULL,4,1,0,6,3,4,8,17116.00,10783.08,6332.92,37.00,5893.00,3834.83,44.00,41.00,93.18,'2025-10-19 16:01:03','monthly'),
(56,22,8,3,6,NULL,NULL,NULL,2,1,2,2,2,4,8,44134.00,26921.74,17212.26,39.00,23989.00,34061.50,83.00,74.00,89.15,'2025-10-19 16:01:03','monthly'),
(57,22,9,3,6,NULL,NULL,NULL,4,2,1,4,1,4,7,17129.00,12332.88,4796.12,28.00,7282.00,6102.75,49.00,64.00,130.61,'2025-10-19 16:01:03','monthly'),
(58,22,10,4,5,NULL,NULL,NULL,3,1,0,6,3,4,8,39037.00,25764.42,13272.58,34.00,21248.00,10047.50,38.00,18.00,47.36,'2025-10-19 16:01:03','monthly'),
(59,22,11,3,5,NULL,NULL,NULL,1,1,0,2,0,3,3,28429.00,21321.75,7107.25,25.00,12259.00,20344.00,74.00,69.00,93.24,'2025-10-19 16:01:03','monthly'),
(60,22,12,4,5,NULL,NULL,NULL,4,3,1,6,0,1,2,11273.00,7440.18,3832.82,34.00,22954.00,5704.50,78.00,35.00,44.87,'2025-10-19 16:01:03','monthly'),
(61,23,7,3,6,NULL,NULL,NULL,3,2,0,5,0,1,7,28781.00,23024.80,5756.20,20.00,9185.00,7593.20,34.00,62.00,182.35,'2025-10-19 16:01:03','monthly'),
(62,23,8,4,5,NULL,NULL,NULL,5,2,0,6,3,3,3,39863.00,28302.73,11560.27,29.00,17317.00,9530.00,68.00,37.00,54.41,'2025-10-19 16:01:03','monthly'),
(63,23,9,3,6,NULL,NULL,NULL,1,3,2,4,1,4,2,48182.00,37100.14,11081.86,23.00,11699.00,14970.25,76.00,45.00,59.21,'2025-10-19 16:01:03','monthly'),
(64,23,10,4,5,NULL,NULL,NULL,3,3,2,2,1,2,8,49469.00,38091.13,11377.87,23.00,18743.00,34106.00,38.00,93.00,244.73,'2025-10-19 16:01:03','monthly'),
(65,23,11,3,6,NULL,NULL,NULL,1,3,2,3,2,2,6,16135.00,12746.65,3388.35,21.00,8075.00,8070.00,58.00,52.00,89.65,'2025-10-19 16:01:03','monthly'),
(66,23,12,3,6,NULL,NULL,NULL,2,2,0,6,0,1,6,29459.00,18559.17,10899.83,37.00,19757.00,8202.66,79.00,16.00,20.25,'2025-10-19 16:01:03','monthly'),
(67,24,7,4,5,NULL,NULL,NULL,1,2,0,7,0,2,5,28354.80,19281.26,9073.54,32.00,6771.00,5017.97,46.00,92.00,200.00,'2025-10-19 16:01:03','monthly'),
(68,24,8,3,5,NULL,NULL,NULL,3,3,1,8,2,4,4,15588.60,9664.93,5923.67,38.00,16553.00,4017.70,40.00,56.00,140.00,'2025-10-19 16:01:03','monthly'),
(69,24,9,3,6,NULL,NULL,NULL,1,3,2,2,2,5,5,16042.80,12352.96,3689.84,22.99,7738.00,11890.40,72.00,75.00,104.16,'2025-10-19 16:01:03','monthly'),
(70,24,10,4,5,NULL,NULL,NULL,1,3,2,6,3,2,6,12008.40,8285.80,3722.60,30.99,11158.00,3861.06,32.00,23.00,71.87,'2025-10-19 16:01:03','monthly'),
(71,24,11,3,5,NULL,NULL,NULL,3,2,1,2,3,1,8,12037.20,9389.02,2648.18,21.99,18920.00,15478.60,86.00,48.00,55.81,'2025-10-19 16:01:03','monthly'),
(72,24,12,3,6,NULL,NULL,NULL,4,2,0,7,2,2,3,19762.80,14031.59,5731.21,28.99,24727.00,6355.68,88.00,57.00,64.77,'2025-10-19 16:01:03','monthly'),
(89,46,14,9,NULL,10,4,NULL,2,2,0,0,0,0,0,0.00,11066.85,-11066.85,0.00,0.00,0.00,0.00,46.58,0.00,'2025-10-25 12:53:55','monthly'),
(90,47,14,9,NULL,10,4,NULL,2,2,0,0,0,0,0,0.00,11066.85,-11066.85,0.00,0.00,0.00,0.00,46.58,0.00,'2025-10-25 12:53:55','monthly'),
(91,48,14,9,NULL,10,4,NULL,2,2,0,0,0,0,0,0.00,11066.85,-11066.85,0.00,0.00,0.00,0.00,46.58,0.00,'2025-10-25 12:53:55','monthly'),
(92,55,15,NULL,NULL,NULL,1,NULL,2,2,0,0,0,0,0,0.00,2165.61,-2165.61,0.00,0.00,0.00,0.00,9.34,0.00,'2025-10-25 12:53:55','monthly'),
(93,55,16,NULL,NULL,NULL,2,NULL,2,2,0,0,0,0,0,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2025-10-25 12:53:55','monthly'),
(94,56,15,NULL,NULL,NULL,1,NULL,2,2,0,0,0,0,0,0.00,2165.61,-2165.61,0.00,0.00,0.00,0.00,9.34,0.00,'2025-10-25 12:53:55','monthly'),
(95,56,16,NULL,NULL,NULL,2,NULL,2,2,0,0,0,0,0,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2025-10-25 12:53:55','monthly'),
(96,57,15,NULL,NULL,NULL,1,NULL,2,2,0,0,0,0,0,0.00,2165.61,-2165.61,0.00,0.00,0.00,0.00,9.34,0.00,'2025-10-25 12:53:55','monthly'),
(97,57,16,NULL,NULL,NULL,2,NULL,2,2,0,0,0,0,0,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2025-10-25 12:53:55','monthly'),
(98,46,14,9,NULL,10,4,NULL,1,1,0,0,0,0,0,0.00,11066.85,-11066.85,0.00,0.00,0.00,0.00,23.29,0.00,'2025-10-25 12:53:42','quarterly'),
(99,55,15,NULL,NULL,NULL,1,NULL,1,1,0,0,0,0,0,0.00,2165.61,-2165.61,0.00,0.00,0.00,0.00,4.67,0.00,'2025-10-25 12:53:42','quarterly'),
(100,55,16,NULL,NULL,NULL,2,NULL,1,1,0,0,0,0,0,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2025-10-25 12:53:42','quarterly'),
(101,46,15,NULL,NULL,NULL,1,NULL,1,1,0,0,0,0,0,0.00,2165.61,-2165.61,0.00,0.00,0.00,0.00,4.67,0.00,'2025-10-25 12:53:42','yearly'),
(102,46,16,NULL,NULL,NULL,2,NULL,1,1,0,0,0,0,0,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'2025-10-25 12:53:42','yearly'),
(103,46,14,9,NULL,10,4,NULL,1,1,0,0,0,0,0,0.00,11066.85,-11066.85,0.00,0.00,0.00,0.00,23.29,0.00,'2025-10-25 12:53:42','yearly');
/*!40000 ALTER TABLE `fact_project_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_lines`
--

DROP TABLE IF EXISTS `order_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_id` int(11) NOT NULL,
  `profile_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `position` int(11) NOT NULL,
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `days` decimal(8,2) DEFAULT NULL,
  `direct_amount` decimal(12,2) DEFAULT NULL,
  `attached_purchase_amount` decimal(12,2) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `notes` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CC9FF86BD823E37A` (`section_id`),
  KEY `IDX_CC9FF86BCCFA12B8` (`profile_id`),
  CONSTRAINT `FK_CC9FF86BCCFA12B8` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`),
  CONSTRAINT `FK_CC9FF86BD823E37A` FOREIGN KEY (`section_id`) REFERENCES `order_sections` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_lines`
--

LOCK TABLES `order_lines` WRITE;
/*!40000 ALTER TABLE `order_lines` DISABLE KEYS */;
INSERT INTO `order_lines` VALUES
(1,1,NULL,'Immersion métier',1,1100.00,1.50,NULL,NULL,'service',NULL),
(2,1,NULL,'Atelier offre',2,1100.00,2.50,NULL,NULL,'service',NULL),
(3,1,11,'Atelier infrastructure & architecture technique',3,750.00,2.00,NULL,NULL,'service',NULL),
(4,2,19,'Immersion métier',1,750.00,1.00,NULL,NULL,'service',NULL),
(5,2,20,'immersion métier',2,1000.00,1.00,NULL,NULL,'service',NULL),
(6,2,20,'Atelier 1',3,1000.00,2.50,NULL,NULL,'service',NULL),
(7,2,19,'Atelier 1',4,750.00,1.50,NULL,NULL,'service',NULL),
(8,3,19,'Rédaction dossier de spécifications fonctionnelles',1,750.00,5.00,NULL,NULL,'service',NULL),
(9,4,11,'Développement Backend',1,550.00,75.00,NULL,NULL,'service',NULL);
/*!40000 ALTER TABLE `order_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_sections`
--

DROP TABLE IF EXISTS `order_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CA6EA1298D9F6D38` (`order_id`),
  CONSTRAINT `FK_CA6EA1298D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_sections`
--

LOCK TABLES `order_sections` WRITE;
/*!40000 ALTER TABLE `order_sections` DISABLE KEYS */;
INSERT INTO `order_sections` VALUES
(1,1,'Cadrage','',1),
(2,2,'Cadrage','',1),
(3,2,'Conception','',2),
(4,2,'Réalisation','',3);
/*!40000 ALTER TABLE `order_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_tasks`
--

DROP TABLE IF EXISTS `order_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `name` varchar(180) NOT NULL,
  `description` longtext DEFAULT NULL,
  `sold_days` decimal(8,2) NOT NULL,
  `sold_daily_rate` decimal(10,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `profile_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D3C6116A8D9F6D38` (`order_id`),
  KEY `IDX_D3C6116ACCFA12B8` (`profile_id`),
  CONSTRAINT `FK_D3C6116A8D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D3C6116ACCFA12B8` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_tasks`
--

LOCK TABLES `order_tasks` WRITE;
/*!40000 ALTER TABLE `order_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `name` varchar(180) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `created_at` date NOT NULL,
  `validated_at` date DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `contingence_amount` decimal(12,2) DEFAULT NULL,
  `contingence_reason` longtext DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `description` longtext DEFAULT NULL,
  `contingency_percentage` decimal(5,2) DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_E52FFDEE551F0F81` (`order_number`),
  KEY `IDX_E52FFDEE166D1F9C` (`project_id`),
  CONSTRAINT `FK_E52FFDEE166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES
(1,1,NULL,'',0.00,'2025-10-25',NULL,'signe',NULL,NULL,'D202510001','',2.00,NULL),
(2,3,NULL,'',9125.00,'2025-10-25',NULL,'gagne',NULL,NULL,'D202510002','Devis de réalisation d\'un site e-commerce de vente en ligne d\'une boutique de mode',5.00,'2025-12-01');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `planning`
--

DROP TABLE IF EXISTS `planning`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contributor_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `profile_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `daily_hours` decimal(4,2) NOT NULL,
  `notes` longtext DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D499BFF67A19A357` (`contributor_id`),
  KEY `IDX_D499BFF6166D1F9C` (`project_id`),
  KEY `IDX_D499BFF6CCFA12B8` (`profile_id`),
  CONSTRAINT `FK_D499BFF6166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `FK_D499BFF67A19A357` FOREIGN KEY (`contributor_id`) REFERENCES `contributors` (`id`),
  CONSTRAINT `FK_D499BFF6CCFA12B8` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `planning`
--

LOCK TABLES `planning` WRITE;
/*!40000 ALTER TABLE `planning` DISABLE KEYS */;
INSERT INTO `planning` VALUES
(1,12,3,NULL,'2025-10-25','2025-10-29',6.00,'Bloc prévisionnel','confirmed','2025-10-25 07:36:10',NULL),
(2,12,3,NULL,'2025-11-01','2025-11-10',6.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL),
(3,12,4,NULL,'2025-10-26','2025-11-01',7.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL),
(4,12,4,NULL,'2025-11-03','2025-11-07',7.00,'Bloc prévisionnel','confirmed','2025-10-25 07:36:10',NULL),
(5,12,5,NULL,'2025-10-29','2025-10-31',8.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL),
(6,12,5,NULL,'2025-11-02','2025-11-08',7.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL),
(7,13,3,NULL,'2025-10-22','2025-10-27',6.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL),
(8,13,5,NULL,'2025-10-25','2025-10-28',7.00,'Bloc prévisionnel','confirmed','2025-10-25 07:36:10',NULL),
(9,14,5,NULL,'2025-11-02','2025-11-07',6.00,'Bloc prévisionnel','confirmed','2025-10-25 07:36:10',NULL),
(10,14,5,NULL,'2025-11-08','2025-11-16',7.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL),
(11,15,4,NULL,'2025-11-01','2025-11-07',7.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL),
(12,15,4,NULL,'2025-11-08','2025-11-11',6.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL),
(13,15,5,NULL,'2025-10-22','2025-10-29',6.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL),
(14,15,5,NULL,'2025-11-01','2025-11-03',7.00,'Bloc prévisionnel','confirmed','2025-10-25 07:36:10',NULL),
(15,15,6,NULL,'2025-10-23','2025-11-01',7.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL),
(16,15,6,NULL,'2025-11-03','2025-11-06',6.00,'Bloc prévisionnel','confirmed','2025-10-25 07:36:10',NULL),
(17,17,3,NULL,'2025-10-22','2025-10-24',8.00,'Bloc prévisionnel','confirmed','2025-10-25 07:36:10',NULL),
(18,17,3,NULL,'2025-10-25','2025-10-31',7.00,'Bloc prévisionnel','confirmed','2025-10-25 07:36:10',NULL),
(19,17,5,NULL,'2025-10-21','2025-10-25',7.00,'Bloc prévisionnel','confirmed','2025-10-25 07:36:10',NULL),
(20,17,5,NULL,'2025-10-26','2025-10-31',6.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL),
(21,17,6,NULL,'2025-10-28','2025-11-04',8.00,'Bloc prévisionnel','confirmed','2025-10-25 07:36:10',NULL),
(22,17,6,NULL,'2025-11-07','2025-11-16',7.00,'Bloc prévisionnel','planned','2025-10-25 07:36:10',NULL);
/*!40000 ALTER TABLE `planning` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `profiles`
--

DROP TABLE IF EXISTS `profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL,
  `default_daily_rate` decimal(10,2) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8B3085305E237E06` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profiles`
--

LOCK TABLES `profiles` WRITE;
/*!40000 ALTER TABLE `profiles` DISABLE KEYS */;
INSERT INTO `profiles` VALUES
(11,'Développeur backend','',350.00,'#28a745',1),
(18,'Développeur Frontend','Spécialisé React, Vue, Angular',NULL,NULL,1),
(19,'Chef de projet','Gestion de projet et équipe',NULL,NULL,1),
(20,'Designer UX/UI','Interface utilisateur et expérience',NULL,NULL,1),
(21,'DevOps','Déploiement et infrastructure',NULL,NULL,1);
/*!40000 ALTER TABLE `profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_tasks`
--

DROP TABLE IF EXISTS `project_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  `is_default` tinyint(1) NOT NULL,
  `counts_for_profitability` tinyint(1) NOT NULL,
  `position` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `assigned_contributor_id` int(11) DEFAULT NULL,
  `required_profile_id` int(11) DEFAULT NULL,
  `estimated_hours_sold` int(11) DEFAULT NULL,
  `estimated_hours_revised` int(11) DEFAULT NULL,
  `progress_percentage` int(11) NOT NULL,
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_430D6C09166D1F9C` (`project_id`),
  KEY `IDX_430D6C097C1524E1` (`assigned_contributor_id`),
  KEY `IDX_430D6C09509DE452` (`required_profile_id`),
  CONSTRAINT `FK_430D6C09166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `FK_430D6C09509DE452` FOREIGN KEY (`required_profile_id`) REFERENCES `profiles` (`id`),
  CONSTRAINT `FK_430D6C097C1524E1` FOREIGN KEY (`assigned_contributor_id`) REFERENCES `contributors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_tasks`
--

LOCK TABLES `project_tasks` WRITE;
/*!40000 ALTER TABLE `project_tasks` DISABLE KEYS */;
INSERT INTO `project_tasks` VALUES
(1,1,'Kick-off projet','C\'est une réunion avec le client','Tâche vendue',0,1,1,1,1,NULL,4,4,0,750.00,'2025-10-22','2025-10-22','Non démarrée'),
(2,1,'Kick-off projet','C\'est une réunion avec le client','Tâche vendue',0,1,2,1,1,NULL,4,4,0,750.00,'2025-10-22','2025-10-22','Non démarrée'),
(3,1,'[Atelier 1] Préparation de l\'atelier',NULL,'Tâche vendue',0,1,3,1,1,NULL,16,16,0,NULL,'2025-10-23','2025-10-24','Non démarrée'),
(4,1,'Développement Frontend','Interface utilisateur React','regular',0,1,1,1,12,NULL,80,90,65,500.00,'2024-10-01','2024-11-15','in_progress'),
(5,1,'Développement Backend','API REST et base de données','regular',0,1,2,1,13,NULL,60,70,80,520.00,'2024-09-15','2024-10-30','in_progress'),
(6,1,'Tests et validation','Tests unitaires et validation','regular',0,1,3,1,14,NULL,40,35,30,450.00,'2024-11-01','2024-11-20','not_started'),
(7,1,'AVV - Avant-vente','Temps passé en avant-vente','avv',1,0,4,1,NULL,NULL,0,16,100,0.00,NULL,NULL,'completed'),
(8,3,'Analyse et spécifications',NULL,'regular',0,1,1,1,12,NULL,40,35,100,523.00,NULL,NULL,'completed'),
(9,3,'Maquettage et design',NULL,'regular',0,1,2,1,12,NULL,80,75,90,576.00,NULL,NULL,'in_progress'),
(10,3,'Développement Frontend',NULL,'regular',0,1,3,1,12,NULL,120,140,60,422.00,NULL,NULL,'in_progress'),
(11,3,'Développement Backend',NULL,'regular',0,1,4,1,13,NULL,100,110,70,542.00,NULL,NULL,'in_progress'),
(12,3,'Tests et validation',NULL,'regular',0,1,5,1,15,NULL,40,45,30,539.00,NULL,NULL,'in_progress'),
(13,3,'Déploiement',NULL,'regular',0,1,6,1,15,NULL,20,25,0,441.00,NULL,NULL,'not_started'),
(14,4,'Analyse et spécifications',NULL,'regular',0,1,1,1,15,NULL,40,35,100,588.00,NULL,NULL,'completed'),
(15,4,'Maquettage et design',NULL,'regular',0,1,2,1,12,NULL,80,75,90,449.00,NULL,NULL,'in_progress'),
(16,4,'Développement Frontend',NULL,'regular',0,1,3,1,12,NULL,120,140,60,452.00,NULL,NULL,'in_progress'),
(17,4,'Développement Backend',NULL,'regular',0,1,4,1,13,NULL,100,110,70,538.00,NULL,NULL,'in_progress'),
(18,4,'Tests et validation',NULL,'regular',0,1,5,1,15,NULL,40,45,30,465.00,NULL,NULL,'in_progress'),
(19,4,'Déploiement',NULL,'regular',0,1,6,1,15,NULL,20,25,0,466.00,NULL,NULL,'not_started'),
(20,5,'Analyse et spécifications',NULL,'regular',0,1,1,1,17,NULL,40,35,100,543.00,NULL,NULL,'completed'),
(21,5,'Maquettage et design',NULL,'regular',0,1,2,1,12,NULL,80,75,90,594.00,NULL,NULL,'in_progress'),
(22,5,'Développement Frontend',NULL,'regular',0,1,3,1,12,NULL,120,140,60,403.00,NULL,NULL,'in_progress'),
(23,5,'Développement Backend',NULL,'regular',0,1,4,1,13,NULL,100,110,70,508.00,NULL,NULL,'in_progress'),
(24,5,'Tests et validation',NULL,'regular',0,1,5,1,15,NULL,40,45,30,592.00,NULL,NULL,'in_progress'),
(25,5,'Déploiement',NULL,'regular',0,1,6,1,15,NULL,20,25,0,478.00,NULL,NULL,'not_started'),
(26,6,'Analyse et spécifications',NULL,'regular',0,1,1,1,14,NULL,40,35,100,571.00,NULL,NULL,'completed'),
(27,6,'Maquettage et design',NULL,'regular',0,1,2,1,12,NULL,80,75,90,420.00,NULL,NULL,'in_progress'),
(28,6,'Développement Frontend',NULL,'regular',0,1,3,1,12,NULL,120,140,60,437.00,NULL,NULL,'in_progress'),
(29,6,'Développement Backend',NULL,'regular',0,1,4,1,13,NULL,100,110,70,584.00,NULL,NULL,'in_progress'),
(30,6,'Tests et validation',NULL,'regular',0,1,5,1,17,NULL,40,45,30,421.00,NULL,NULL,'in_progress'),
(31,6,'Déploiement',NULL,'regular',0,1,6,1,15,NULL,20,25,0,418.00,NULL,NULL,'not_started');
/*!40000 ALTER TABLE `project_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_technologies`
--

DROP TABLE IF EXISTS `project_technologies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_technologies` (
  `project_id` int(11) NOT NULL,
  `technology_id` int(11) NOT NULL,
  PRIMARY KEY (`project_id`,`technology_id`),
  KEY `IDX_666C1F7B166D1F9C` (`project_id`),
  KEY `IDX_666C1F7B4235D463` (`technology_id`),
  CONSTRAINT `FK_666C1F7B166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_666C1F7B4235D463` FOREIGN KEY (`technology_id`) REFERENCES `technologies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_technologies`
--

LOCK TABLES `project_technologies` WRITE;
/*!40000 ALTER TABLE `project_technologies` DISABLE KEYS */;
INSERT INTO `project_technologies` VALUES
(1,1),
(2,1),
(2,6),
(3,1),
(3,8),
(3,14),
(4,10),
(4,15),
(4,16),
(5,2),
(5,3),
(6,1),
(6,9),
(6,15);
/*!40000 ALTER TABLE `project_technologies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(180) NOT NULL,
  `client` varchar(180) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `purchases_amount` decimal(12,2) DEFAULT NULL,
  `purchases_description` longtext DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `service_category_id` int(11) DEFAULT NULL,
  `is_internal` tinyint(1) NOT NULL,
  `key_account_manager_id` int(11) DEFAULT NULL,
  `project_manager_id` int(11) DEFAULT NULL,
  `project_director_id` int(11) DEFAULT NULL,
  `sales_person_id` int(11) DEFAULT NULL,
  `project_type` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5C93B3A4DEDCBB4E` (`service_category_id`),
  KEY `IDX_5C93B3A44DDC9A02` (`key_account_manager_id`),
  KEY `IDX_5C93B3A460984F51` (`project_manager_id`),
  KEY `IDX_5C93B3A44150449D` (`project_director_id`),
  KEY `IDX_5C93B3A41D35E30E` (`sales_person_id`),
  CONSTRAINT `FK_5C93B3A41D35E30E` FOREIGN KEY (`sales_person_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_5C93B3A44150449D` FOREIGN KEY (`project_director_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_5C93B3A44DDC9A02` FOREIGN KEY (`key_account_manager_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_5C93B3A460984F51` FOREIGN KEY (`project_manager_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_5C93B3A4DEDCBB4E` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES
(1,'Test1','Client1','2025-10-06','2026-03-20','houjou',NULL,'','active',NULL,0,NULL,NULL,NULL,NULL,''),
(2,'Test Symfony','Astre','2025-10-01','2026-03-03','',NULL,'','active',3,0,NULL,NULL,NULL,NULL,'forfait'),
(3,'E-shop Mode Parisienne','Fashion Store Paris','2024-09-01','2024-12-15','Refonte complète de la boutique en ligne avec système de recommandation',NULL,NULL,'active',2,0,16,15,17,NULL,'forfait'),
(4,'App Mobile Banking','CreditCorp','2024-10-01','2025-03-30','Application mobile pour la gestion des comptes bancaires',NULL,NULL,'active',17,0,16,15,17,NULL,'regie'),
(5,'Site Vitrine Avocat','Cabinet Juridique Associés','2024-08-15','2024-11-30','Site vitrine moderne avec système de prise de rendez-vous',NULL,NULL,'active',15,0,16,15,17,NULL,'forfait'),
(6,'Plateforme SaaS RH','HRTech Solutions','2024-06-01','2024-09-30','Plateforme de gestion des ressources humaines en mode SaaS',NULL,NULL,'completed',16,0,16,15,17,NULL,'forfait');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_categories`
--

DROP TABLE IF EXISTS `service_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_categories`
--

LOCK TABLES `service_categories` WRITE;
/*!40000 ALTER TABLE `service_categories` DISABLE KEYS */;
INSERT INTO `service_categories` VALUES
(1,'Brand','Sites vitrine et institutionnels','#6C5CE7',1),
(2,'E-commerce','Sites marchands et places de marché','#00B894',1),
(3,'Application métier','Applications sur mesure','#0984E3',1),
(15,'Corporate','Sites vitrine et institutionnels',NULL,1),
(16,'SaaS','Applications métier et logiciels',NULL,1),
(17,'Mobile','Applications mobiles natives et hybrides',NULL,1);
/*!40000 ALTER TABLE `service_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `technologies`
--

DROP TABLE IF EXISTS `technologies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `technologies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `color` varchar(7) DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `technologies`
--

LOCK TABLES `technologies` WRITE;
/*!40000 ALTER TABLE `technologies` DISABLE KEYS */;
INSERT INTO `technologies` VALUES
(1,'Symfony','framework','#6f42c1',1),
(2,'Laravel','framework','#ff2d20',1),
(3,'Vue.js','framework','#42b883',1),
(4,'Drupal','cms','#30d0f8',1),
(5,'Wordpress','cms','#666666',1),
(6,'MariaDB','database','#6f42c1',1),
(7,'PHP','language','#004db3',1),
(8,'React','framework','#61dafb',1),
(9,'Angular','framework','#dd0031',1),
(10,'Node.js','runtime','#3c873a',1),
(11,'Python','language','#3776ab',1),
(12,'Docker','infra','#2496ed',1),
(13,'AWS','hosting','#ff9900',1),
(14,'MySQL','database','#00758f',1),
(15,'PostgreSQL','database','#336791',1),
(16,'Redis','cache','#dc382d',1);
/*!40000 ALTER TABLE `technologies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timesheets`
--

DROP TABLE IF EXISTS `timesheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `timesheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contributor_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `notes` longtext DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9AC77D2E7A19A357` (`contributor_id`),
  KEY `IDX_9AC77D2E166D1F9C` (`project_id`),
  KEY `IDX_9AC77D2E8DB60186` (`task_id`),
  CONSTRAINT `FK_9AC77D2E166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9AC77D2E7A19A357` FOREIGN KEY (`contributor_id`) REFERENCES `contributors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_9AC77D2E8DB60186` FOREIGN KEY (`task_id`) REFERENCES `project_tasks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timesheets`
--

LOCK TABLES `timesheets` WRITE;
/*!40000 ALTER TABLE `timesheets` DISABLE KEYS */;
INSERT INTO `timesheets` VALUES
(1,1,1,'2025-10-13',0.50,'Réunion de spécifications et point hebdomadaire',NULL),
(2,1,1,'2025-10-22',0.50,'',NULL),
(3,12,1,'2024-10-15',7.50,'Développement interface React',NULL),
(4,12,1,'2024-10-16',8.00,'Composants utilisateur',NULL),
(5,13,1,'2024-10-15',7.00,'API REST development',NULL),
(6,13,1,'2024-10-16',8.00,'Base de données',NULL),
(7,14,1,'2024-10-17',6.00,'Préparation tests',NULL),
(8,12,5,'2024-09-02',5.10,'Travail sur le projet Site Vitrine Avocat',NULL),
(9,13,4,'2024-09-02',7.20,'Travail sur le projet App Mobile Banking',NULL),
(10,14,3,'2024-09-02',6.90,'Travail sur le projet E-shop Mode Parisienne',NULL),
(11,17,5,'2024-09-02',5.00,'Travail sur le projet Site Vitrine Avocat',NULL),
(12,12,5,'2024-09-03',4.00,'Travail sur le projet Site Vitrine Avocat',NULL),
(13,13,4,'2024-09-03',6.10,'Travail sur le projet App Mobile Banking',NULL),
(14,14,4,'2024-09-03',6.80,'Travail sur le projet App Mobile Banking',NULL),
(15,15,5,'2024-09-03',4.90,'Travail sur le projet Site Vitrine Avocat',NULL),
(16,12,6,'2024-09-04',6.50,'Travail sur le projet Plateforme SaaS RH',NULL),
(17,13,4,'2024-09-04',4.30,'Travail sur le projet App Mobile Banking',NULL),
(18,14,4,'2024-09-04',6.10,'Travail sur le projet App Mobile Banking',NULL),
(19,17,5,'2024-09-04',7.70,'Travail sur le projet Site Vitrine Avocat',NULL),
(20,14,4,'2024-09-05',4.00,'Travail sur le projet App Mobile Banking',NULL),
(21,15,4,'2024-09-05',8.00,'Travail sur le projet App Mobile Banking',NULL),
(22,17,6,'2024-09-05',7.00,'Travail sur le projet Plateforme SaaS RH',NULL),
(23,12,3,'2024-09-06',5.00,'Travail sur le projet E-shop Mode Parisienne',NULL),
(24,13,5,'2024-09-06',5.80,'Travail sur le projet Site Vitrine Avocat',NULL),
(25,14,3,'2024-09-06',6.80,'Travail sur le projet E-shop Mode Parisienne',NULL),
(26,17,4,'2024-09-06',4.90,'Travail sur le projet App Mobile Banking',NULL),
(27,12,3,'2024-09-09',5.00,'Travail sur le projet E-shop Mode Parisienne',NULL),
(28,13,3,'2024-09-09',5.40,'Travail sur le projet E-shop Mode Parisienne',NULL),
(29,12,6,'2024-09-10',6.00,'Travail sur le projet Plateforme SaaS RH',NULL),
(30,13,4,'2024-09-10',5.80,'Travail sur le projet App Mobile Banking',NULL),
(31,17,5,'2024-09-10',4.20,'Travail sur le projet Site Vitrine Avocat',NULL),
(32,12,6,'2024-09-11',7.40,'Travail sur le projet Plateforme SaaS RH',NULL),
(33,13,3,'2024-09-11',5.50,'Travail sur le projet E-shop Mode Parisienne',NULL),
(34,15,6,'2024-09-11',4.10,'Travail sur le projet Plateforme SaaS RH',NULL),
(35,17,6,'2024-09-11',4.60,'Travail sur le projet Plateforme SaaS RH',NULL),
(36,12,6,'2024-09-12',4.20,'Travail sur le projet Plateforme SaaS RH',NULL),
(37,14,5,'2024-09-12',4.80,'Travail sur le projet Site Vitrine Avocat',NULL),
(38,17,3,'2024-09-12',5.30,'Travail sur le projet E-shop Mode Parisienne',NULL),
(39,12,4,'2024-09-13',5.70,'Travail sur le projet App Mobile Banking',NULL),
(40,13,6,'2024-09-13',6.80,'Travail sur le projet Plateforme SaaS RH',NULL),
(41,14,4,'2024-09-13',5.40,'Travail sur le projet App Mobile Banking',NULL),
(42,12,4,'2024-09-16',4.90,'Travail sur le projet App Mobile Banking',NULL),
(43,14,3,'2024-09-16',4.60,'Travail sur le projet E-shop Mode Parisienne',NULL),
(44,15,5,'2024-09-16',6.20,'Travail sur le projet Site Vitrine Avocat',NULL),
(45,17,3,'2024-09-16',7.30,'Travail sur le projet E-shop Mode Parisienne',NULL),
(46,12,5,'2024-09-17',5.40,'Travail sur le projet Site Vitrine Avocat',NULL),
(47,17,6,'2024-09-17',4.50,'Travail sur le projet Plateforme SaaS RH',NULL),
(48,12,5,'2024-09-18',7.80,'Travail sur le projet Site Vitrine Avocat',NULL),
(49,14,3,'2024-09-18',7.10,'Travail sur le projet E-shop Mode Parisienne',NULL),
(50,15,6,'2024-09-18',4.80,'Travail sur le projet Plateforme SaaS RH',NULL),
(51,13,4,'2024-09-19',5.70,'Travail sur le projet App Mobile Banking',NULL),
(52,14,5,'2024-09-19',6.00,'Travail sur le projet Site Vitrine Avocat',NULL),
(53,17,5,'2024-09-19',5.50,'Travail sur le projet Site Vitrine Avocat',NULL),
(54,13,6,'2024-09-20',5.00,'Travail sur le projet Plateforme SaaS RH',NULL),
(55,14,5,'2024-09-20',5.20,'Travail sur le projet Site Vitrine Avocat',NULL),
(56,15,6,'2024-09-20',5.30,'Travail sur le projet Plateforme SaaS RH',NULL),
(57,12,6,'2024-09-23',6.70,'Travail sur le projet Plateforme SaaS RH',NULL),
(58,13,6,'2024-09-23',4.30,'Travail sur le projet Plateforme SaaS RH',NULL),
(59,15,4,'2024-09-23',7.40,'Travail sur le projet App Mobile Banking',NULL),
(60,17,4,'2024-09-23',6.80,'Travail sur le projet App Mobile Banking',NULL),
(61,12,5,'2024-09-24',7.30,'Travail sur le projet Site Vitrine Avocat',NULL),
(62,13,5,'2024-09-24',7.00,'Travail sur le projet Site Vitrine Avocat',NULL),
(63,14,6,'2024-09-24',7.20,'Travail sur le projet Plateforme SaaS RH',NULL),
(64,17,4,'2024-09-24',5.10,'Travail sur le projet App Mobile Banking',NULL),
(65,13,5,'2024-09-25',6.10,'Travail sur le projet Site Vitrine Avocat',NULL),
(66,17,6,'2024-09-25',5.90,'Travail sur le projet Plateforme SaaS RH',NULL),
(67,12,3,'2024-09-26',7.40,'Travail sur le projet E-shop Mode Parisienne',NULL),
(68,13,4,'2024-09-26',6.10,'Travail sur le projet App Mobile Banking',NULL),
(69,14,3,'2024-09-26',5.50,'Travail sur le projet E-shop Mode Parisienne',NULL),
(70,15,3,'2024-09-26',5.60,'Travail sur le projet E-shop Mode Parisienne',NULL),
(71,17,5,'2024-09-26',6.10,'Travail sur le projet Site Vitrine Avocat',NULL),
(72,12,6,'2024-09-27',7.20,'Travail sur le projet Plateforme SaaS RH',NULL),
(73,13,5,'2024-09-27',4.00,'Travail sur le projet Site Vitrine Avocat',NULL),
(74,14,6,'2024-09-27',8.00,'Travail sur le projet Plateforme SaaS RH',NULL),
(75,15,5,'2024-09-27',5.40,'Travail sur le projet Site Vitrine Avocat',NULL),
(76,17,4,'2024-09-27',4.20,'Travail sur le projet App Mobile Banking',NULL),
(77,12,6,'2024-09-30',7.20,'Travail sur le projet Plateforme SaaS RH',NULL),
(78,13,5,'2024-09-30',7.80,'Travail sur le projet Site Vitrine Avocat',NULL),
(79,14,3,'2024-09-30',7.70,'Travail sur le projet E-shop Mode Parisienne',NULL),
(80,15,5,'2024-09-30',5.40,'Travail sur le projet Site Vitrine Avocat',NULL),
(81,17,4,'2024-09-30',5.40,'Travail sur le projet App Mobile Banking',NULL),
(82,12,3,'2024-10-01',5.90,'Travail sur le projet E-shop Mode Parisienne',NULL),
(83,13,5,'2024-10-01',5.20,'Travail sur le projet Site Vitrine Avocat',NULL),
(84,14,4,'2024-10-01',5.70,'Travail sur le projet App Mobile Banking',NULL),
(85,15,6,'2024-10-01',7.00,'Travail sur le projet Plateforme SaaS RH',NULL),
(86,17,5,'2024-10-01',6.90,'Travail sur le projet Site Vitrine Avocat',NULL),
(87,12,3,'2024-10-02',6.30,'Travail sur le projet E-shop Mode Parisienne',NULL),
(88,13,3,'2024-10-02',5.00,'Travail sur le projet E-shop Mode Parisienne',NULL),
(89,14,3,'2024-10-02',4.70,'Travail sur le projet E-shop Mode Parisienne',NULL),
(90,15,3,'2024-10-02',8.00,'Travail sur le projet E-shop Mode Parisienne',NULL),
(91,17,5,'2024-10-02',7.00,'Travail sur le projet Site Vitrine Avocat',NULL),
(92,12,4,'2024-10-03',5.70,'Travail sur le projet App Mobile Banking',NULL),
(93,13,3,'2024-10-03',6.50,'Travail sur le projet E-shop Mode Parisienne',NULL),
(94,14,6,'2024-10-03',5.80,'Travail sur le projet Plateforme SaaS RH',NULL),
(95,12,3,'2024-10-04',5.10,'Travail sur le projet E-shop Mode Parisienne',NULL),
(96,13,3,'2024-10-04',5.30,'Travail sur le projet E-shop Mode Parisienne',NULL),
(97,12,6,'2024-10-07',7.50,'Travail sur le projet Plateforme SaaS RH',NULL),
(98,13,3,'2024-10-07',4.20,'Travail sur le projet E-shop Mode Parisienne',NULL),
(99,14,4,'2024-10-07',7.90,'Travail sur le projet App Mobile Banking',NULL),
(100,15,5,'2024-10-07',6.20,'Travail sur le projet Site Vitrine Avocat',NULL),
(101,15,5,'2024-10-08',4.40,'Travail sur le projet Site Vitrine Avocat',NULL),
(102,12,6,'2024-10-09',7.00,'Travail sur le projet Plateforme SaaS RH',NULL),
(103,13,4,'2024-10-09',5.30,'Travail sur le projet App Mobile Banking',NULL),
(104,14,6,'2024-10-09',6.10,'Travail sur le projet Plateforme SaaS RH',NULL),
(105,15,3,'2024-10-09',5.20,'Travail sur le projet E-shop Mode Parisienne',NULL),
(106,17,5,'2024-10-09',4.00,'Travail sur le projet Site Vitrine Avocat',NULL),
(107,12,6,'2024-10-10',6.20,'Travail sur le projet Plateforme SaaS RH',NULL),
(108,13,4,'2024-10-10',8.00,'Travail sur le projet App Mobile Banking',NULL),
(109,14,5,'2024-10-10',6.20,'Travail sur le projet Site Vitrine Avocat',NULL),
(110,17,4,'2024-10-10',4.60,'Travail sur le projet App Mobile Banking',NULL),
(111,12,5,'2024-10-11',6.10,'Travail sur le projet Site Vitrine Avocat',NULL),
(112,14,4,'2024-10-11',5.80,'Travail sur le projet App Mobile Banking',NULL),
(113,15,4,'2024-10-11',6.80,'Travail sur le projet App Mobile Banking',NULL),
(114,13,5,'2024-10-14',6.40,'Travail sur le projet Site Vitrine Avocat',NULL),
(115,14,4,'2024-10-14',5.70,'Travail sur le projet App Mobile Banking',NULL),
(116,15,6,'2024-10-14',4.50,'Travail sur le projet Plateforme SaaS RH',NULL),
(117,17,5,'2024-10-14',5.00,'Travail sur le projet Site Vitrine Avocat',NULL),
(118,12,3,'2024-10-15',7.20,'Travail sur le projet E-shop Mode Parisienne',NULL),
(119,13,4,'2024-10-15',7.20,'Travail sur le projet App Mobile Banking',NULL),
(120,14,6,'2024-10-15',6.00,'Travail sur le projet Plateforme SaaS RH',NULL),
(121,15,6,'2024-10-15',6.20,'Travail sur le projet Plateforme SaaS RH',NULL),
(122,17,6,'2024-10-15',6.10,'Travail sur le projet Plateforme SaaS RH',NULL),
(123,12,5,'2024-10-16',7.60,'Travail sur le projet Site Vitrine Avocat',NULL),
(124,14,5,'2024-10-16',4.80,'Travail sur le projet Site Vitrine Avocat',NULL),
(125,15,4,'2024-10-16',4.90,'Travail sur le projet App Mobile Banking',NULL),
(126,12,5,'2024-10-17',5.90,'Travail sur le projet Site Vitrine Avocat',NULL),
(127,14,6,'2024-10-17',4.80,'Travail sur le projet Plateforme SaaS RH',NULL),
(128,15,4,'2024-10-17',5.10,'Travail sur le projet App Mobile Banking',NULL),
(129,17,6,'2024-10-17',7.90,'Travail sur le projet Plateforme SaaS RH',NULL),
(130,12,6,'2024-10-18',5.80,'Travail sur le projet Plateforme SaaS RH',NULL),
(131,13,6,'2024-10-18',5.60,'Travail sur le projet Plateforme SaaS RH',NULL),
(132,14,4,'2024-10-18',4.50,'Travail sur le projet App Mobile Banking',NULL),
(133,17,6,'2024-10-18',5.60,'Travail sur le projet Plateforme SaaS RH',NULL),
(134,1,1,'2025-10-20',1.00,'',1),
(135,1,1,'2025-10-20',0.25,'',2),
(136,1,1,'2025-10-20',4.00,'',3);
/*!40000 ALTER TABLE `timesheets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(180) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `address` longtext DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `totp_secret` varchar(255) DEFAULT NULL,
  `totp_enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9E7927C74` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'admin@example.com','[\"ROLE_USER\"]','$2y$13$PKCdT.WrGDOv5tC/sjfFbe/aUvSFUR1cOJEQCQNgxSA0jaiNkLLHa','Admin','User',NULL,NULL,NULL,NULL,0),
(2,'thibaut.monier@gmail.com','[\"ROLE_USER\", \"ROLE_INTERVENANT\", \"ROLE_CHEF_PROJET\", \"ROLE_MANAGER\"]','$2y$13$yl8n9rklnMDxm5pccqjXDeaNi6TIkDyWnyetMYTtd5NtO1NH0nJGS','Thibaut','MONIER',NULL,NULL,NULL,'AAOEI77M5PDWY5BOJ3E2LOK5G5AUBP3AZDWRPQAZEXTIODN3SIV5VPCHYGZE2FE4AWBEJ2N5HVF4YZKLM2V7GRHCWL6OC3OPW5XKFZA',1),
(15,'chef.projet@test.com','[\"ROLE_CHEF_PROJET\"]','$2y$13$anmsbGLqx7eaCa8PwbUFfuIoLe.BA8KpCfTK8yF/VLhgVip/DLp4a','Alice','Martin',NULL,NULL,NULL,NULL,0),
(16,'commercial@test.com','[\"ROLE_COMMERCIAL\"]','$2y$13$n7XB2n3uiItLjDwEF/UVOO8GjKP1Oh51E0m0PWF0c64qpQ5/kqC3u','Bob','Durand',NULL,NULL,NULL,NULL,0),
(17,'directeur@test.com','[\"ROLE_DIRECTEUR\"]','$2y$13$r4OubhkLr5wFVmbti11UMOiRuuer3Vw8SjSG/8F8VEeVzuhlySK8K','Claire','Moreau',NULL,NULL,NULL,NULL,0),
(18,'admin@test.com','[\"ROLE_ADMIN\"]','$2y$13$QEFEt9iILtXPSJHSD4gis.f5/wYS0PWFaCMQNloR7N/q81yhPv6my','David','Admin',NULL,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vacations`
--

DROP TABLE IF EXISTS `vacations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vacations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contributor_id` int(11) NOT NULL,
  `approved_by_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `type` varchar(50) NOT NULL,
  `reason` longtext DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `daily_hours` decimal(4,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3B8290677A19A357` (`contributor_id`),
  KEY `IDX_3B8290672D234F6A` (`approved_by_id`),
  CONSTRAINT `FK_3B8290672D234F6A` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_3B8290677A19A357` FOREIGN KEY (`contributor_id`) REFERENCES `contributors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vacations`
--

LOCK TABLES `vacations` WRITE;
/*!40000 ALTER TABLE `vacations` DISABLE KEYS */;
/*!40000 ALTER TABLE `vacations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-10-30 10:47:19
