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
-- Table structure for table `accessi_falliti`
--

DROP TABLE IF EXISTS `accessi_falliti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accessi_falliti` (
  `id_accessi` int(11) NOT NULL AUTO_INCREMENT,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `dataora` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_accessi`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  KEY `idx_accessi_dataora` (`dataora`),
  CONSTRAINT `accessi_falliti_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accessi_falliti`
--

LOCK TABLES `accessi_falliti` WRITE;
/*!40000 ALTER TABLE `accessi_falliti` DISABLE KEYS */;
/*!40000 ALTER TABLE `accessi_falliti` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `autore_libro`
--

DROP TABLE IF EXISTS `autore_libro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `autore_libro` (
  `id_autore` int(11) NOT NULL,
  `isbn` bigint(20) NOT NULL,
  PRIMARY KEY (`id_autore`,`isbn`),
  KEY `isbn` (`isbn`),
  CONSTRAINT `autore_libro_ibfk_1` FOREIGN KEY (`id_autore`) REFERENCES `autori` (`id_autore`),
  CONSTRAINT `autore_libro_ibfk_2` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `autore_libro`
--

LOCK TABLES `autore_libro` WRITE;
/*!40000 ALTER TABLE `autore_libro` DISABLE KEYS */;
/*!40000 ALTER TABLE `autore_libro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `autori`
--

DROP TABLE IF EXISTS `autori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `autori` (
  `id_autore` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  PRIMARY KEY (`id_autore`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `autori`
--

LOCK TABLES `autori` WRITE;
/*!40000 ALTER TABLE `autori` DISABLE KEYS */;
/*!40000 ALTER TABLE `autori` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `badge`
--

DROP TABLE IF EXISTS `badge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `badge` (
  `id_badge` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `icona` varchar(255) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `target_numerico` smallint(6) NOT NULL,
  `data_fine` date DEFAULT NULL,
  `root` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id_badge`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `badge`
--

LOCK TABLES `badge` WRITE;
/*!40000 ALTER TABLE `badge` DISABLE KEYS */;
/*!40000 ALTER TABLE `badge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorie`
--

DROP TABLE IF EXISTS `categorie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorie` (
  `id_categoria` int(11) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorie`
--

LOCK TABLES `categorie` WRITE;
/*!40000 ALTER TABLE `categorie` DISABLE KEYS */;
/*!40000 ALTER TABLE `categorie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consensi`
--

DROP TABLE IF EXISTS `consensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consensi` (
  `id_consenso` int(11) NOT NULL AUTO_INCREMENT,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `tipo_consenso` varchar(50) DEFAULT NULL,
  `data_consenso` date DEFAULT NULL,
  `indirizzo_ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_consenso`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  CONSTRAINT `consensi_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consensi`
--

LOCK TABLES `consensi` WRITE;
/*!40000 ALTER TABLE `consensi` DISABLE KEYS */;
/*!40000 ALTER TABLE `consensi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `copie`
--

DROP TABLE IF EXISTS `copie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `copie` (
  `id_copia` int(11) NOT NULL AUTO_INCREMENT,
  `isbn` bigint(20) DEFAULT NULL,
  `ean` varchar(50) NOT NULL,
  `condizione` smallint(6) NOT NULL,
  `disponibile` tinyint(1) NOT NULL,
  `anno_pubblicazione` year(4) DEFAULT NULL,
  `conferma_anno_pubblicazione` tinyint(1) DEFAULT 1,
  `editore` varchar(100) NOT NULL,
  `copertina` varchar(255) NOT NULL,
  `taf_rfid` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_copia`),
  KEY `isbn` (`isbn`),
  CONSTRAINT `copie_ibfk_1` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `copie`
--

LOCK TABLES `copie` WRITE;
/*!40000 ALTER TABLE `copie` DISABLE KEYS */;
/*!40000 ALTER TABLE `copie` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `libri`
--

DROP TABLE IF EXISTS `libri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `libri` (
  `isbn` bigint(20) NOT NULL,
  `titolo` varchar(255) NOT NULL,
  `descrizione` text DEFAULT NULL,
  PRIMARY KEY (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `libri`
--

LOCK TABLES `libri` WRITE;
/*!40000 ALTER TABLE `libri` DISABLE KEYS */;
/*!40000 ALTER TABLE `libri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `libri_consigliati`
--

DROP TABLE IF EXISTS `libri_consigliati`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `libri_consigliati` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `isbn` bigint(20) NOT NULL,
  `codice_alfanumerico` varchar(6) DEFAULT NULL,
  `n_consigli` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `isbn` (`isbn`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  CONSTRAINT `libri_consigliati_ibfk_1` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`),
  CONSTRAINT `libri_consigliati_ibfk_2` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `libri_consigliati`
--

LOCK TABLES `libri_consigliati` WRITE;
/*!40000 ALTER TABLE `libri_consigliati` DISABLE KEYS */;
/*!40000 ALTER TABLE `libri_consigliati` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `libro_categoria`
--

DROP TABLE IF EXISTS `libro_categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `libro_categoria` (
  `isbn` bigint(20) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  PRIMARY KEY (`isbn`,`id_categoria`),
  KEY `id_categoria` (`id_categoria`),
  CONSTRAINT `libro_categoria_ibfk_1` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`),
  CONSTRAINT `libro_categoria_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorie` (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `libro_categoria`
--

LOCK TABLES `libro_categoria` WRITE;
/*!40000 ALTER TABLE `libro_categoria` DISABLE KEYS */;
/*!40000 ALTER TABLE `libro_categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_monitoraggi`
--

DROP TABLE IF EXISTS `log_monitoraggi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_monitoraggi` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `tipo_evento` varchar(50) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `indirizzo_ip` varchar(45) DEFAULT NULL,
  `dataora_evento` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  CONSTRAINT `log_monitoraggi_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_monitoraggi`
--

LOCK TABLES `log_monitoraggi` WRITE;
/*!40000 ALTER TABLE `log_monitoraggi` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_monitoraggi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `multe`
--

DROP TABLE IF EXISTS `multe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `multe` (
  `id_multa` int(11) NOT NULL AUTO_INCREMENT,
  `id_prestito` int(11) NOT NULL,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `importo` decimal(10,2) NOT NULL,
  `causale` text NOT NULL,
  `data_creata` date DEFAULT current_timestamp(),
  PRIMARY KEY (`id_multa`),
  KEY `id_prestito` (`id_prestito`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  CONSTRAINT `multe_ibfk_1` FOREIGN KEY (`id_prestito`) REFERENCES `prestiti` (`id_prestito`),
  CONSTRAINT `multe_ibfk_2` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `multe`
--

LOCK TABLES `multe` WRITE;
/*!40000 ALTER TABLE `multe` DISABLE KEYS */;
/*!40000 ALTER TABLE `multe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifiche`
--

DROP TABLE IF EXISTS `notifiche`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifiche` (
  `id_notifica` int(11) NOT NULL AUTO_INCREMENT,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `titolo` varchar(255) NOT NULL,
  `messaggio` text NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `dataora_invio` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notifica`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  CONSTRAINT `notifiche_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifiche`
--

LOCK TABLES `notifiche` WRITE;
/*!40000 ALTER TABLE `notifiche` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifiche` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagamenti`
--

DROP TABLE IF EXISTS `pagamenti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagamenti` (
  `id_pagamento` int(11) NOT NULL AUTO_INCREMENT,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `data_apertura` date DEFAULT NULL,
  `data_chiusura` date DEFAULT NULL,
  `importo` decimal(10,2) NOT NULL,
  `causale` text NOT NULL,
  PRIMARY KEY (`id_pagamento`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  CONSTRAINT `pagamenti_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagamenti`
--

LOCK TABLES `pagamenti` WRITE;
/*!40000 ALTER TABLE `pagamenti` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagamenti` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prenotazioni`
--

DROP TABLE IF EXISTS `prenotazioni`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prenotazioni` (
  `id_prenotazione` int(11) NOT NULL AUTO_INCREMENT,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `isbn` bigint(20) DEFAULT NULL,
  `data_prenotazione` date DEFAULT NULL,
  `data_assegnazione` date DEFAULT NULL,
  PRIMARY KEY (`id_prenotazione`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  KEY `isbn` (`isbn`),
  CONSTRAINT `prenotazioni_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`),
  CONSTRAINT `prenotazioni_ibfk_2` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prenotazioni`
--

LOCK TABLES `prenotazioni` WRITE;
/*!40000 ALTER TABLE `prenotazioni` DISABLE KEYS */;
/*!40000 ALTER TABLE `prenotazioni` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prestiti`
--

DROP TABLE IF EXISTS `prestiti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestiti` (
  `id_prestito` int(11) NOT NULL AUTO_INCREMENT,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `id_copia` int(11) DEFAULT NULL,
  `data_prestito` date DEFAULT NULL,
  `data_scadenza` date DEFAULT NULL,
  `data_restituzione` date DEFAULT NULL,
  `num_rinnovi` int(11) DEFAULT 0,
  PRIMARY KEY (`id_prestito`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  KEY `id_copia` (`id_copia`),
  CONSTRAINT `prestiti_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`),
  CONSTRAINT `prestiti_ibfk_2` FOREIGN KEY (`id_copia`) REFERENCES `copie` (`id_copia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prestiti`
--

LOCK TABLES `prestiti` WRITE;
/*!40000 ALTER TABLE `prestiti` DISABLE KEYS */;
/*!40000 ALTER TABLE `prestiti` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recensioni`
--

DROP TABLE IF EXISTS `recensioni`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recensioni` (
  `id_recensione` int(11) NOT NULL AUTO_INCREMENT,
  `isbn` bigint(20) DEFAULT NULL,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `voto` smallint(6) NOT NULL,
  `commento` text NOT NULL,
  `data_commento` date DEFAULT current_timestamp(),
  `like_count` int(11) DEFAULT 0,
  `dislike_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id_recensione`),
  KEY `isbn` (`isbn`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  CONSTRAINT `recensioni_ibfk_1` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`),
  CONSTRAINT `recensioni_ibfk_2` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recensioni`
--

LOCK TABLES `recensioni` WRITE;
/*!40000 ALTER TABLE `recensioni` DISABLE KEYS */;
/*!40000 ALTER TABLE `recensioni` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ruoli`
--

DROP TABLE IF EXISTS `ruoli`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ruoli` (
  `codice_alfanumerico` varchar(6) DEFAULT NULL,
  `studente` tinyint(1) DEFAULT 0,
  `docente` tinyint(1) DEFAULT 0,
  `bibliotecario` tinyint(1) DEFAULT 0,
  `amministratore` tinyint(1) DEFAULT 0,
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  CONSTRAINT `ruoli_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ruoli`
--

LOCK TABLES `ruoli` WRITE;
/*!40000 ALTER TABLE `ruoli` DISABLE KEYS */;
/*!40000 ALTER TABLE `ruoli` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tokenemail`
--

DROP TABLE IF EXISTS `tokenemail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tokenemail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codice_alfanumerico` varchar(64) NOT NULL,
  `token` char(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tokenemail`
--

LOCK TABLES `tokenemail` WRITE;
/*!40000 ALTER TABLE `tokenemail` DISABLE KEYS */;
INSERT INTO `tokenemail` VALUES
(2,'1lgwMu','ccf2c3064a437ff1032a8fd08588a70c459cd06c4fc042d33a7990b27bfaa41e','2025-12-12 19:25:57'),
(3,'000001','002144977e21d59f82688024eb8859d2ff081a7fc8633d352ad9899d0af0042f','2025-12-12 19:38:44');
/*!40000 ALTER TABLE `tokenemail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utente_badge`
--

DROP TABLE IF EXISTS `utente_badge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `utente_badge` (
  `id_ub` int(11) NOT NULL AUTO_INCREMENT,
  `id_badge` int(11) DEFAULT NULL,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `livello` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_ub`),
  KEY `id_badge` (`id_badge`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  CONSTRAINT `utente_badge_ibfk_1` FOREIGN KEY (`id_badge`) REFERENCES `badge` (`id_badge`),
  CONSTRAINT `utente_badge_ibfk_2` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utente_badge`
--

LOCK TABLES `utente_badge` WRITE;
/*!40000 ALTER TABLE `utente_badge` DISABLE KEYS */;
/*!40000 ALTER TABLE `utente_badge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utenti`
--

DROP TABLE IF EXISTS `utenti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `utenti` (
  `codice_alfanumerico` varchar(6) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `codice_fiscale` char(16) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `livello_privato` tinyint(3) unsigned DEFAULT 0,
  `login_bloccato` tinyint(1) DEFAULT 0,
  `account_bloccato` tinyint(1) DEFAULT 0,
  `affidabile` tinyint(1) DEFAULT 0,
  `email_confermata` tinyint(1) DEFAULT 0,
  `data_creazione` date DEFAULT current_timestamp(),
  PRIMARY KEY (`codice_alfanumerico`),
  CONSTRAINT `chk_no_cf_username` CHECK (!(`username` regexp '^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utenti`
--

LOCK TABLES `utenti` WRITE;
/*!40000 ALTER TABLE `utenti` DISABLE KEYS */;
INSERT INTO `utenti` VALUES
('000001','TestUsername1','Cobra','Ivi','VIICBR12T12H501U','prova@gmail.com','$2y$10$vmp6ONFoxulRwKcM/G.Yaewrc/0lSxawThsu45WooudOAzIvhXG1q',0,0,0,0,0,'2025-12-12'),
('000002','Fede','Federico','Femia','FMEFRC00T12H501M','federico.femia121007@gmail.com','$2y$10$.GKBzGj/nzUhryJkCMV.RONUWBvYJ5dkT1ZEZUjB8IUYhs6rm1lm.',0,0,0,0,1,'2025-12-12');
/*!40000 ALTER TABLE `utenti` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'database_sito'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `CheckLoginUser` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `CheckLoginUser`(
    IN p_input_login VARCHAR(255),  
    IN p_password_input VARCHAR(255), 
    OUT p_result VARCHAR(50)
)
BEGIN
    DECLARE v_codice VARCHAR(6);
    DECLARE v_stored_pass VARCHAR(255);
    DECLARE v_login_bloccato BOOLEAN;
    DECLARE v_failed_attempts INT;

    
    
    
    
    
    DELETE FROM accessi_falliti 
    WHERE dataora < (NOW() - INTERVAL 15 MINUTE);

    
    SELECT codice_alfanumerico, password_hash, login_bloccato
    INTO v_codice, v_stored_pass, v_login_bloccato
    FROM utenti
    WHERE email = p_input_login 
       OR username = p_input_login 
       OR codice_fiscale = p_input_login
    LIMIT 1;

    
    IF v_codice IS NULL THEN
        SET p_result = 'utente_non_trovato';
    ELSE
        
        IF v_login_bloccato = 1 THEN
            SET p_result = 'blocked:1';
        ELSE
            
            
            SELECT COUNT(*)
            INTO v_failed_attempts
            FROM accessi_falliti
            WHERE codice_alfanumerico = v_codice;

            IF v_failed_attempts >= 3 THEN
                SET p_result = 'blocked:2';
            ELSE
                
                IF p_password_input = v_stored_pass THEN
                    
                    
                    DELETE FROM accessi_falliti WHERE codice_alfanumerico = v_codice;
                    
                    SET p_result = v_codice;
                ELSE
                    
                    SET p_result = 'password_sbagliata';
                    
                    
                    INSERT INTO accessi_falliti (codice_alfanumerico, dataora) 
                    VALUES (v_codice, NOW());
                END IF;
            END IF;
        END IF;
    END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_crea_utente_alfanumerico` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_crea_utente_alfanumerico`(
    IN p_username VARCHAR(50),
    IN p_nome VARCHAR(50),
    IN p_cognome VARCHAR(100),
    IN p_codice_fiscale CHAR(16),
    IN p_email VARCHAR(255),
    IN p_password_hash VARCHAR(255)
)
BEGIN
    
    DECLARE v_ultimo_codice VARCHAR(6);
    DECLARE v_nuovo_valore_decimale BIGINT;
    DECLARE v_nuovo_codice VARCHAR(6);

    
    
    SELECT MAX(codice_alfanumerico) 
    INTO v_ultimo_codice 
    FROM utenti;

    
    IF v_ultimo_codice IS NULL THEN
        
        SET v_nuovo_codice = '000001';
    ELSE
        
        
        SET v_nuovo_valore_decimale = CONV(v_ultimo_codice, 36, 10) + 1;
        
        
        
        
        SET v_nuovo_codice = LPAD(UPPER(CONV(v_nuovo_valore_decimale, 10, 36)), 6, '0');
    END IF;

    
    INSERT INTO utenti (
        codice_alfanumerico, username, nome, cognome, 
        codice_fiscale, email, password_hash
    ) VALUES (
        v_nuovo_codice, p_username, p_nome, p_cognome, 
        p_codice_fiscale, p_email, p_password_hash
    );
    
    
    SELECT v_nuovo_codice as nuovo_id;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-13  2:00:03
