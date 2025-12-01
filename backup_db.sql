/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: database_sito
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `visitatori`
--

DROP TABLE IF EXISTS `visitatori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitatori` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `data_visita` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitatori`
--

LOCK TABLES `visitatori` WRITE;
/*!40000 ALTER TABLE `visitatori` DISABLE KEYS */;
INSERT INTO `visitatori` VALUES
(1,'Primo Test Manuale','2025-11-27 23:45:23'),
(2,'Utente Web','2025-11-27 23:46:21'),
(3,'Utente Web','2025-11-27 23:46:27'),
(4,'Utente Web','2025-11-28 00:16:19'),
(5,'Utente Web','2025-11-28 06:31:55'),
(6,'Utente Web','2025-11-28 06:31:58'),
(7,'Utente Web','2025-11-28 06:31:58'),
(8,'Utente Web','2025-11-28 06:31:58'),
(9,'Utente Web','2025-11-28 06:55:44'),
(10,'Utente Web','2025-11-28 06:55:47'),
(11,'Utente Web','2025-11-28 09:29:32'),
(12,'Utente Web','2025-11-28 09:31:22'),
(13,'Utente Web','2025-11-28 09:34:48'),
(14,'Utente Web','2025-11-28 09:35:02'),
(15,'Utente Web','2025-11-28 09:37:24'),
(16,'Utente Web','2025-11-28 09:37:32'),
(17,'Utente Web','2025-11-28 09:37:33'),
(18,'Utente Web','2025-11-28 09:37:35'),
(19,'Utente Web','2025-11-28 09:37:45'),
(20,'Utente Web','2025-11-28 09:38:22'),
(21,'Utente Web','2025-11-28 09:38:44'),
(22,'Utente Web','2025-11-28 09:39:00'),
(23,'Utente Web','2025-11-28 09:39:10'),
(24,'Utente Web','2025-11-28 09:39:11'),
(25,'Utente Web','2025-11-28 09:39:12'),
(26,'Utente Web','2025-11-28 09:39:13'),
(27,'Utente Web','2025-11-28 09:39:13'),
(28,'Utente Web','2025-11-28 09:39:13'),
(29,'Utente Web','2025-11-28 09:39:14'),
(30,'Utente Web','2025-11-28 09:39:14'),
(31,'Utente Web','2025-11-28 09:39:14'),
(32,'Utente Web','2025-11-28 09:39:15'),
(33,'Utente Web','2025-11-28 09:39:15'),
(34,'Utente Web','2025-11-28 09:39:15'),
(35,'Utente Web','2025-11-28 09:39:16'),
(36,'Utente Web','2025-11-28 09:39:16'),
(37,'Utente Web','2025-11-28 09:39:17'),
(38,'Utente Web','2025-11-28 09:39:17'),
(39,'Utente Web','2025-11-28 09:39:17'),
(40,'Utente Web','2025-11-28 09:39:17'),
(41,'Utente Web','2025-11-28 09:39:17'),
(42,'Utente Web','2025-11-28 09:39:18'),
(43,'Utente Web','2025-11-28 09:39:18'),
(44,'Utente Web','2025-11-28 09:39:18'),
(45,'Utente Web','2025-11-28 09:39:19'),
(46,'Utente Web','2025-11-28 09:39:19'),
(47,'Utente Web','2025-11-28 09:39:19'),
(48,'Utente Web','2025-11-28 09:39:20'),
(49,'Utente Web','2025-11-28 09:39:20'),
(50,'Utente Web','2025-11-28 09:39:20'),
(51,'Utente Web','2025-11-28 09:39:21'),
(52,'Utente Web','2025-11-28 09:39:21'),
(53,'Utente Web','2025-11-28 09:39:21'),
(54,'Utente Web','2025-11-28 09:39:22'),
(55,'Utente Web','2025-11-28 09:39:22'),
(56,'Utente Web','2025-11-28 09:39:22'),
(57,'Utente Web','2025-11-28 09:39:23'),
(58,'Utente Web','2025-11-28 09:39:23'),
(59,'Utente Web','2025-11-28 09:39:23'),
(60,'Utente Web','2025-11-28 09:39:23'),
(61,'Utente Web','2025-11-28 09:39:24'),
(62,'Utente Web','2025-11-28 09:39:24'),
(63,'Utente Web','2025-11-28 09:39:24'),
(64,'Utente Web','2025-11-28 09:39:24'),
(65,'Utente Web','2025-11-28 09:39:25'),
(66,'Utente Web','2025-11-28 09:39:25'),
(67,'Utente Web','2025-11-28 09:39:25'),
(68,'Utente Web','2025-11-28 09:39:25'),
(69,'Utente Web','2025-11-28 09:39:25'),
(70,'Utente Web','2025-11-28 09:39:25'),
(71,'Utente Web','2025-11-28 09:39:26'),
(72,'Utente Web','2025-11-28 09:39:26'),
(73,'Utente Web','2025-11-28 09:39:26'),
(74,'Utente Web','2025-11-28 09:39:26'),
(75,'Utente Web','2025-11-28 09:39:26'),
(76,'Utente Web','2025-11-28 09:39:26'),
(77,'Utente Web','2025-11-28 09:39:27'),
(78,'Utente Web','2025-11-28 09:39:27'),
(79,'Utente Web','2025-11-28 09:39:27'),
(80,'Utente Web','2025-11-28 09:39:27'),
(81,'Utente Web','2025-11-28 09:39:28'),
(82,'Utente Web','2025-11-28 09:39:28'),
(83,'Utente Web','2025-11-28 09:39:28'),
(84,'Utente Web','2025-11-28 09:39:29'),
(85,'Utente Web','2025-11-28 09:39:29'),
(86,'Utente Web','2025-11-28 09:39:29'),
(87,'Utente Web','2025-11-28 09:39:29'),
(88,'Utente Web','2025-11-28 09:39:30'),
(89,'Utente Web','2025-11-28 09:39:30'),
(90,'Utente Web','2025-11-28 09:39:39'),
(91,'Utente Web','2025-11-28 09:39:46'),
(92,'Utente Web','2025-11-28 09:40:09'),
(93,'Utente Web','2025-11-28 09:41:59'),
(94,'Utente Web','2025-11-28 09:41:59'),
(95,'Utente Web','2025-11-28 09:42:09'),
(96,'Utente Web','2025-11-28 09:42:50'),
(97,'Utente Web','2025-11-28 09:46:41'),
(98,'Utente Web','2025-11-28 09:46:52'),
(99,'Utente Web','2025-11-28 09:48:33'),
(100,'Utente Web','2025-11-28 09:48:40'),
(101,'Utente Web','2025-11-28 09:48:41'),
(102,'Utente Web','2025-11-28 09:52:40'),
(103,'Utente Web','2025-11-28 09:58:40'),
(104,'Utente Web','2025-11-28 10:00:42'),
(105,'Utente Web','2025-11-28 10:06:53'),
(106,'Utente Web','2025-11-28 10:09:01'),
(107,'Utente Web','2025-11-28 10:10:51'),
(108,'Utente Web','2025-11-28 10:11:22'),
(109,'Utente Web','2025-11-28 10:13:54'),
(110,'Utente Web','2025-11-28 10:20:59'),
(111,'Utente Web','2025-11-28 10:26:53'),
(112,'Utente Web','2025-11-28 10:27:09'),
(113,'Utente Web','2025-11-28 10:27:16'),
(114,'Utente Web','2025-11-28 10:28:31'),
(115,'Utente Web','2025-11-28 10:28:34'),
(116,'Utente Web','2025-11-28 10:28:37'),
(117,'Utente Web','2025-11-28 10:39:49'),
(118,'Utente Web','2025-11-28 10:40:21'),
(119,'Utente Web','2025-11-28 10:41:53'),
(120,'Utente Web','2025-11-28 10:42:01'),
(121,'Utente Web','2025-11-28 10:43:27'),
(122,'Utente Web','2025-11-28 10:44:29'),
(123,'Utente Web','2025-11-28 10:46:20'),
(124,'Utente Web','2025-11-28 10:46:23'),
(125,'Utente Web','2025-11-28 10:47:48'),
(126,'Utente Web','2025-11-28 10:56:01'),
(127,'Utente Web','2025-11-28 11:01:35'),
(128,'Utente Web','2025-11-28 11:01:42'),
(129,'Utente Web','2025-11-28 11:11:53'),
(130,'Utente Web','2025-11-28 11:11:55'),
(131,'Utente Web','2025-11-28 11:28:27'),
(132,'Utente Web','2025-11-28 11:29:49'),
(133,'Utente Web','2025-11-28 11:44:35'),
(134,'Utente Web','2025-11-28 11:50:10'),
(135,'Utente Web','2025-11-28 11:50:38'),
(136,'Utente Web','2025-11-28 11:52:09'),
(137,'Utente Web','2025-11-28 19:18:21'),
(138,'Utente Web','2025-11-28 23:55:29'),
(139,'Utente Web','2025-11-29 07:26:17'),
(140,'Utente Web','2025-11-29 07:49:04'),
(141,'Utente Web','2025-11-29 07:49:14'),
(142,'Utente Web','2025-11-29 07:49:16'),
(143,'Utente Web','2025-11-29 07:49:18'),
(144,'Utente Web','2025-11-29 07:49:19'),
(145,'Utente Web','2025-11-29 07:49:20'),
(146,'Utente Web','2025-11-29 07:49:21'),
(147,'Utente Web','2025-11-29 08:47:25'),
(148,'Utente Web','2025-11-29 10:07:07'),
(149,'Utente Web','2025-11-29 11:07:04'),
(150,'Utente Web','2025-11-30 04:50:50');
/*!40000 ALTER TABLE `visitatori` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-01  2:00:02
