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
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `biblioteche`
--

DROP TABLE IF EXISTS `biblioteche`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `biblioteche` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lon` decimal(11,8) DEFAULT NULL,
  `indirizzo` varchar(255) DEFAULT NULL,
  `orari` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`orari`)),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categorie`
--

DROP TABLE IF EXISTS `categorie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorie` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `classifica`
--

DROP TABLE IF EXISTS `classifica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `classifica` (
  `codice_alfanumerico` varchar(6) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `tempo` int(11) DEFAULT NULL,
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  CONSTRAINT `classifica_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `id_biblioteca` int(11) DEFAULT NULL,
  `anno_edizione` smallint(6) DEFAULT NULL,
  `editore` varchar(100) NOT NULL,
  `taf_rfid` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_copia`),
  KEY `isbn` (`isbn`),
  KEY `fk_copie_biblioteche` (`id_biblioteca`),
  CONSTRAINT `copie_ibfk_1` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`),
  CONSTRAINT `fk_copie_biblioteche` FOREIGN KEY (`id_biblioteca`) REFERENCES `biblioteche` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10200 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `anno_pubblicazione` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
-- Table structure for table `multe`
--

DROP TABLE IF EXISTS `multe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `multe` (
  `id_multa` int(11) NOT NULL AUTO_INCREMENT,
  `id_prestito` int(11) DEFAULT NULL,
  `importo` decimal(10,2) NOT NULL,
  `causale` text NOT NULL,
  `data_creata` date DEFAULT curdate(),
  `pagata` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_multa`),
  KEY `idx_prestito` (`id_prestito`),
  CONSTRAINT `fk_multe_prestiti` FOREIGN KEY (`id_prestito`) REFERENCES `prestiti` (`id_prestito`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `link_riferimento` varchar(255) DEFAULT NULL,
  `tipo` varchar(50) NOT NULL,
  `dataora_invio` datetime DEFAULT current_timestamp(),
  `dataora_scadenza` datetime DEFAULT NULL,
  `visualizzato` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_notifica`),
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  KEY `idx_dataora_scadenza` (`dataora_scadenza`),
  CONSTRAINT `notifiche_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
-- Table structure for table `prenotazioni`
--

DROP TABLE IF EXISTS `prenotazioni`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prenotazioni` (
  `id_prenotazione` int(11) NOT NULL AUTO_INCREMENT,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `id_copia` int(11) NOT NULL,
  `data_prenotazione` date DEFAULT curdate(),
  `data_assegnazione` date DEFAULT NULL,
  PRIMARY KEY (`id_prenotazione`),
  KEY `idx_utente_pren` (`codice_alfanumerico`),
  KEY `idx_copia_pren` (`id_copia`),
  CONSTRAINT `fk_prenotazioni_copie` FOREIGN KEY (`id_copia`) REFERENCES `copie` (`id_copia`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prenotazioni_utenti` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prestiti`
--

DROP TABLE IF EXISTS `prestiti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestiti` (
  `id_prestito` int(11) NOT NULL AUTO_INCREMENT,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `id_copia` int(11) NOT NULL,
  `data_prestito` date DEFAULT curdate(),
  `data_scadenza` date DEFAULT NULL,
  `data_restituzione` date DEFAULT NULL,
  `num_rinnovi` int(11) DEFAULT 0,
  PRIMARY KEY (`id_prestito`),
  KEY `idx_utente` (`codice_alfanumerico`),
  KEY `idx_copia` (`id_copia`),
  CONSTRAINT `fk_prestiti_copie` FOREIGN KEY (`id_copia`) REFERENCES `copie` (`id_copia`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prestiti_utenti` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  KEY `codice_alfanumerico` (`codice_alfanumerico`),
  KEY `idx_recensioni_isbn_pop` (`isbn`,`like_count`,`dislike_count`,`data_commento`),
  CONSTRAINT `recensioni_ibfk_1` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`),
  CONSTRAINT `recensioni_ibfk_2` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB AUTO_INCREMENT=591 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recensioni_voti`
--

DROP TABLE IF EXISTS `recensioni_voti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recensioni_voti` (
  `id_recensione` int(11) NOT NULL,
  `codice_alfanumerico` varchar(6) NOT NULL,
  `tipo_voto` enum('like','dislike') NOT NULL,
  PRIMARY KEY (`id_recensione`,`codice_alfanumerico`),
  KEY `idx_voti_recensione` (`id_recensione`),
  KEY `idx_voti_utente` (`codice_alfanumerico`),
  CONSTRAINT `recensioni_voti_ibfk_1` FOREIGN KEY (`id_recensione`) REFERENCES `recensioni` (`id_recensione`) ON DELETE CASCADE,
  CONSTRAINT `recensioni_voti_ibfk_2` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `richieste_bibliotecario`
--

DROP TABLE IF EXISTS `richieste_bibliotecario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `richieste_bibliotecario` (
  `id_richiesta` int(11) NOT NULL AUTO_INCREMENT,
  `id_prestito` int(11) NOT NULL,
  `tipo_richiesta` enum('estensione_prestito','altro') NOT NULL DEFAULT 'estensione_prestito',
  `data_richiesta` datetime DEFAULT current_timestamp(),
  `data_scadenza_richiesta` date DEFAULT NULL,
  `stato` enum('in_attesa','approvata','rifiutata') DEFAULT 'in_attesa',
  `note_admin` text DEFAULT NULL,
  PRIMARY KEY (`id_richiesta`),
  KEY `idx_prestito` (`id_prestito`),
  CONSTRAINT `fk_richieste_prestiti` FOREIGN KEY (`id_prestito`) REFERENCES `prestiti` (`id_prestito`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  KEY `fk_ruoli_utenti` (`codice_alfanumerico`),
  CONSTRAINT `fk_ruoli_utenti` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ruoli_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
    
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    
    START TRANSACTION;

    
    
    SELECT MAX(codice_alfanumerico) 
    INTO v_ultimo_codice 
    FROM utenti 
    FOR UPDATE;

    
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
    
    
    COMMIT;
    
    
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

-- Dump completed on 2026-02-21  2:00:03
