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
INSERT INTO `autore_libro` VALUES
(3,9788804668231),
(4,9788807900359),
(5,9788804667920),
(6,9788807900441),
(7,9788811360500),
(8,9788806203018),
(9,9788806226161),
(11,9788806218449),
(12,9788806206019),
(14,9788807901301),
(15,9788806225881),
(16,9788807900601),
(18,9788845292613),
(19,9788820063225),
(20,9788804719230),
(21,9788804666688),
(21,9788804666985),
(22,9788804600293),
(23,9788845269554),
(24,9788845293672),
(28,9788806216445),
(29,9788804682497),
(31,9788804628334),
(32,9788834739505),
(33,9788842916659),
(34,9788804711951),
(35,9788804672375),
(37,9788817064439),
(38,9788804616898),
(39,9788856667103),
(40,9788806220039),
(41,9788834742215),
(42,9788804736343),
(43,9788804665292),
(44,9788854189355),
(45,9788834734364);
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
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `autori`
--

LOCK TABLES `autori` WRITE;
/*!40000 ALTER TABLE `autori` DISABLE KEYS */;
INSERT INTO `autori` VALUES
(1,'J.K.','Rowling'),
(2,'George','Orwell'),
(3,'Jane','Austen'),
(4,'Mark','Twain'),
(5,'Ernest','Hemingway'),
(6,'F. Scott','Fitzgerald'),
(7,'Leo','Tolstoy'),
(8,'Charles','Dickens'),
(9,'Fyodor','Dostoevsky'),
(10,'Virginia','Woolf'),
(11,'Gabriel','Garcia Marquez'),
(12,'Haruki','Murakami'),
(13,'Isabel','Allende'),
(14,'Franz','Kafka'),
(15,'Herman','Melville'),
(16,'Oscar','Wilde'),
(17,'Kurt','Vonnegut'),
(18,'J.R.R.','Tolkien'),
(19,'Stephen','King'),
(20,'Agatha','Christie'),
(21,'Dan','Brown'),
(22,'Suzanne','Collins'),
(23,'Paulo','Coelho'),
(24,'John','Steinbeck'),
(25,'Margaret','Atwood'),
(26,'Arthur','Conan Doyle'),
(27,'H.G.','Wells'),
(28,'Emily','Bronte'),
(29,'Aldous','Huxley'),
(30,'Toni','Morrison'),
(31,'Patrick','Rothfuss'),
(32,'Brandon','Sanderson'),
(33,'Andrzej','Sapkowski'),
(34,'George R.R.','Martin'),
(35,'Neil','Gaiman'),
(36,'Dan','Brown'),
(37,'Gillian','Flynn'),
(38,'Thomas','Harris'),
(39,'Paula','Hawkins'),
(40,'Jo','Nesbo'),
(41,'Frank','Herbert'),
(42,'William','Gibson'),
(43,'Ray','Bradbury'),
(44,'Andy','Weir'),
(45,'Philip K.','Dick'),
(46,'Patrick','Rothfuss'),
(47,'Brandon','Sanderson'),
(48,'Andrzej','Sapkowski'),
(49,'George R.R.','Martin'),
(50,'Neil','Gaiman'),
(51,'Dan','Brown'),
(52,'Gillian','Flynn'),
(53,'Thomas','Harris'),
(54,'Paula','Hawkins'),
(55,'Jo','Nesbo'),
(56,'Frank','Herbert'),
(57,'William','Gibson'),
(58,'Ray','Bradbury'),
(59,'Andy','Weir'),
(60,'Philip K.','Dick'),
(61,'Patrick','Rothfuss'),
(62,'Brandon','Sanderson'),
(63,'Andrzej','Sapkowski'),
(64,'George R.R.','Martin'),
(65,'Neil','Gaiman'),
(66,'Dan','Brown'),
(67,'Gillian','Flynn'),
(68,'Thomas','Harris'),
(69,'Paula','Hawkins'),
(70,'Jo','Nesbo'),
(71,'Frank','Herbert'),
(72,'William','Gibson'),
(73,'Ray','Bradbury'),
(74,'Andy','Weir'),
(75,'Philip K.','Dick'),
(76,'Patrick','Rothfuss'),
(77,'Brandon','Sanderson'),
(78,'Andrzej','Sapkowski'),
(79,'George R.R.','Martin'),
(80,'Neil','Gaiman'),
(81,'Dan','Brown'),
(82,'Gillian','Flynn'),
(83,'Thomas','Harris'),
(84,'Paula','Hawkins'),
(85,'Jo','Nesbo'),
(86,'Frank','Herbert'),
(87,'William','Gibson'),
(88,'Ray','Bradbury'),
(89,'Andy','Weir'),
(90,'Philip K.','Dick');
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
-- Table structure for table `biblioteche`
--

DROP TABLE IF EXISTS `biblioteche`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `biblioteche` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `latitudine` decimal(10,8) DEFAULT NULL,
  `longitudine` decimal(11,8) DEFAULT NULL,
  `indirizzo` varchar(255) DEFAULT NULL,
  `orari` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`orari`)),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biblioteche`
--

LOCK TABLES `biblioteche` WRITE;
/*!40000 ALTER TABLE `biblioteche` DISABLE KEYS */;
INSERT INTO `biblioteche` VALUES
(1,'Biblioteca Civica Bertoliana (Sede Centrale)',45.54836900,11.54453900,'Contrà Riale 5, 36100 Vicenza','{\n        \"lun\": \"08:00-19:00\",\n        \"mar\": \"08:00-19:00\",\n        \"mer\": \"08:00-19:00\",\n        \"gio\": \"08:00-19:00\",\n        \"ven\": \"08:00-19:00\",\n        \"sab\": \"08:00-12:30\",\n        \"dom\": \"Chiuso\"\n    }'),
(2,'Biblioteca di Palazzo Costantini',45.54825800,11.54383100,'Contrà Riale 13, 36100 Vicenza','{\n        \"lun\": \"09:00-21:00\",\n        \"mar\": \"09:00-21:00\",\n        \"mer\": \"09:00-21:00\",\n        \"gio\": \"09:00-21:00\",\n        \"ven\": \"09:00-21:00\",\n        \"sab\": \"09:00-12:30, 15:00-19:00\",\n        \"dom\": \"09:00-12:30, 15:00-19:00\"\n    }'),
(3,'Biblioteca di Villa Tacchi',45.54250000,11.56500000,'Viale della Pace 89, 36100 Vicenza','{\n        \"lun\": \"14:30-19:00\",\n        \"mar\": \"14:30-19:00\",\n        \"mer\": \"14:30-19:00\",\n        \"gio\": \"14:30-19:00\",\n        \"ven\": \"14:30-19:00\",\n        \"sab\": \"Chiuso\",\n        \"dom\": \"Chiuso\"\n    }'),
(4,'Biblioteca di Riviera Berica',45.49550000,11.58100000,'Viale Riviera Berica 631, 36100 Vicenza','{\n        \"lun\": \"14:30-19:00\",\n        \"mar\": \"14:30-19:00\",\n        \"mer\": \"14:30-19:00\",\n        \"gio\": \"14:30-19:00\",\n        \"ven\": \"14:30-19:00\",\n        \"sab\": \"Chiuso\",\n        \"dom\": \"Chiuso\"\n    }'),
(5,'Biblioteca Villaggio del Sole',45.55580000,11.52250000,'Via Cristoforo Colombo 41/A, 36100 Vicenza','{\n        \"lun\": \"14:30-19:00\",\n        \"mar\": \"14:30-19:00\",\n        \"mer\": \"14:30-19:00\",\n        \"gio\": \"14:30-19:00\",\n        \"ven\": \"14:30-19:00\",\n        \"sab\": \"Chiuso\",\n        \"dom\": \"Chiuso\"\n    }'),
(6,'Biblioteca di Laghetto',45.56850000,11.53600000,'Via Lago di Pusiano 3, 36100 Vicenza','{\n        \"lun\": \"14:30-19:00\",\n        \"mar\": \"14:30-19:00\",\n        \"mer\": \"14:30-19:00\",\n        \"gio\": \"14:30-19:00\",\n        \"ven\": \"14:30-19:00\",\n        \"sab\": \"Chiuso\",\n        \"dom\": \"Chiuso\"\n    }'),
(7,'Biblioteca di Anconetta',45.56520000,11.56650000,'Via Aurelio dall\'Acqua 16, 36100 Vicenza','{\n        \"lun\": \"14:30-19:00\",\n        \"mar\": \"14:30-19:00\",\n        \"mer\": \"14:30-19:00\",\n        \"gio\": \"14:30-19:00\",\n        \"ven\": \"14:30-19:00\",\n        \"sab\": \"Chiuso\",\n        \"dom\": \"Chiuso\"\n    }');
/*!40000 ALTER TABLE `biblioteche` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `categorie`
--

LOCK TABLES `categorie` WRITE;
/*!40000 ALTER TABLE `categorie` DISABLE KEYS */;
INSERT INTO `categorie` VALUES
(1,'Classico'),
(2,'Fantascienza'),
(3,'Distopico'),
(4,'Avventura'),
(5,'Giallo'),
(6,'Horror'),
(7,'Romanzo Storico'),
(8,'Realismo Magico'),
(9,'Psicologico'),
(10,'Fantasy'),
(11,'Thriller'),
(12,'Romanzo Rosa'),
(13,'Biografico'),
(14,'Saggistica'),
(15,'Letteratura per Ragazzi'),
(16,'Filosofico'),
(17,'Teatrale'),
(18,'Umoristico'),
(19,'Epico'),
(20,'Gotico'),
(21,'Fiaba'),
(22,'Satirico');
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
  `id_biblioteca` int(11) DEFAULT NULL,
  `anno_edizione` smallint(6) DEFAULT NULL,
  `editore` varchar(100) NOT NULL,
  `taf_rfid` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_copia`),
  KEY `isbn` (`isbn`),
  KEY `fk_copie_biblioteche` (`id_biblioteca`),
  CONSTRAINT `copie_ibfk_1` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`),
  CONSTRAINT `fk_copie_biblioteche` FOREIGN KEY (`id_biblioteca`) REFERENCES `biblioteche` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `copie`
--

LOCK TABLES `copie` WRITE;
/*!40000 ALTER TABLE `copie` DISABLE KEYS */;
INSERT INTO `copie` VALUES
(1,9788804523363,'9788804523363-01',3,1,2,2006,'Einaudi','409B542FE'),
(2,9788804523363,'9788804523363-02',1,1,7,2006,'Mondadori','5DB0257C1'),
(3,9788804666985,'9788804666985-01',2,1,4,2005,'Mondadori','122F32DCC0'),
(4,9788804667920,'9788804667920-01',1,1,6,2012,'Feltrinelli','16929FFEF2'),
(5,9788804667920,'9788804667920-02',3,1,1,2019,'Mondadori','9EEF7331F'),
(6,9788804667982,'9788804667982-01',3,1,1,1827,'Adelphi','1A4B8B806'),
(7,9788804667982,'9788804667982-02',3,1,3,1827,'Einaudi','5AE0B8776'),
(8,9788804667982,'9788804667982-03',2,1,3,1827,'Mondadori','B2BBE55AB'),
(9,9788804668231,'9788804668231-01',3,1,1,1813,'Adelphi','EBDDA317B'),
(10,9788804668231,'9788804668231-02',1,1,6,1813,'Feltrinelli','72505269C'),
(11,9788804668231,'9788804668231-03',1,1,5,1813,'Einaudi','D730BF381'),
(12,9788804682497,'9788804682497-01',1,1,3,2014,'Feltrinelli','D0971E56B'),
(13,9788804682497,'9788804682497-02',1,1,1,2002,'Einaudi','113653994A'),
(14,9788804682497,'9788804682497-03',1,1,1,2010,'Mondadori','8B63206A3'),
(15,9788804683838,'9788804683838-01',3,1,6,1986,'Mondadori','112DAE6160'),
(17,9788804702003,'9788804702003-01',2,1,6,1954,'Einaudi','170D155722'),
(18,9788804702003,'9788804702003-02',3,1,1,1954,'Rizzoli','13BDBAAE5E'),
(19,9788804702003,'9788804702003-03',3,1,7,1954,'Mondadori','AE3F3FB5F'),
(20,9788804702027,'9788804702027-01',2,1,7,1955,'Adelphi','267F101AC'),
(21,9788804702027,'9788804702027-02',1,1,5,1955,'Feltrinelli','865063193'),
(22,9788804719230,'9788804719230-01',1,1,2,2013,'Einaudi','EDF1D4377'),
(23,9788806173762,'9788806173762-01',1,1,5,2001,'Mondadori','14F5E64108'),
(24,9788806173762,'9788806173762-02',3,1,6,2001,'Feltrinelli','8E0C2C923'),
(25,9788806173762,'9788806173762-03',1,1,2,2001,'Feltrinelli','13B038C788'),
(26,9788806203018,'9788806203018-01',1,1,2,2004,'Mondadori','1546FC5170'),
(27,9788806203018,'9788806203018-02',2,1,1,2011,'Rizzoli','16C3FFC9BD'),
(28,9788806203018,'9788806203018-03',2,1,7,2006,'Adelphi','10BE7E880E'),
(29,9788806206019,'9788806206019-01',1,1,6,2005,'Einaudi','13C8D5D00F'),
(30,9788806206019,'9788806206019-02',1,1,4,2003,'Feltrinelli','C4C3D6098'),
(31,9788806206019,'9788806206019-03',2,1,6,2017,'Adelphi','16A04C42F6'),
(32,9788806218449,'9788806218449-01',3,1,2,2019,'Rizzoli','154E52DCC4'),
(33,9788806218449,'9788806218449-02',3,1,5,2012,'Feltrinelli','7E5C22DE0'),
(34,9788806218449,'9788806218449-03',2,1,6,2018,'Mondadori','12E655C72D'),
(35,9788806219378,'9788806219378-01',3,1,5,1980,'Feltrinelli','11FB3E1732'),
(36,9788806219378,'9788806219378-02',1,1,7,1980,'Adelphi','8462C8DC4'),
(37,9788806225881,'9788806225881-01',2,1,4,2005,'Feltrinelli','126A766179'),
(38,9788806225911,'9788806225911-01',2,1,5,1943,'Feltrinelli','A468CA9AD'),
(39,9788806226161,'9788806226161-01',2,1,4,2003,'Rizzoli','1643078FBB'),
(40,9788806226161,'9788806226161-02',2,1,5,2014,'Rizzoli','2E274D66A'),
(41,9788806227441,'9788806227441-01',3,1,1,1957,'Mondadori','35F2F3BEB'),
(42,9788806227441,'9788806227441-02',2,1,3,1957,'Einaudi','12C24BB979'),
(44,9788807013936,'9788807013936-01',2,1,6,1996,'Feltrinelli','822125F51'),
(45,9788807880866,'9788807880866-01',3,1,7,1984,'Einaudi','13E7FF5321'),
(46,9788807882204,'9788807882204-01',3,1,6,1926,'Feltrinelli','742432FA3'),
(47,9788807900359,'9788807900359-01',2,1,2,1876,'Feltrinelli','B6C2CA177'),
(48,9788807900359,'9788807900359-02',3,1,6,1876,'Mondadori','10D94F8339'),
(49,9788807900359,'9788807900359-03',3,1,6,1876,'Rizzoli','10F41FF5F7'),
(50,9788807900441,'9788807900441-01',3,1,7,2002,'Adelphi','777D6C39C'),
(51,9788807900441,'9788807900441-02',1,1,4,2014,'Rizzoli','10B3F9225'),
(52,9788807900441,'9788807900441-03',3,1,3,2002,'Rizzoli','10C62E941A'),
(53,9788807900601,'9788807900601-01',2,1,2,2001,'Adelphi','87FE5AB7B'),
(54,9788807900601,'9788807900601-02',3,1,1,2015,'Mondadori','F283197A1'),
(55,9788811360500,'9788811360500-01',2,1,4,2019,'Einaudi','83759B273'),
(62,9788834728560,'9788834728560-01',2,1,3,1965,'Adelphi','15D04388D4'),
(66,9788845293672,'9788845293672-01',3,1,5,2018,'Mondadori','607E50ADA'),
(67,9788845293672,'9788845293672-02',3,1,6,2013,'Einaudi','AE078A785'),
(68,9788845293672,'9788845293672-03',1,1,6,2009,'Einaudi','A758956A5'),
(84,9788804628334,'',0,1,NULL,NULL,'Mondadori',NULL),
(85,9788834739505,'',0,1,NULL,NULL,'Fanucci',NULL),
(86,9788842916659,'',0,1,NULL,NULL,'Nord',NULL),
(87,9788804711951,'',0,1,NULL,NULL,'Mondadori',NULL),
(88,9788804672375,'',0,1,NULL,NULL,'Mondadori',NULL),
(89,9788804666688,'',0,1,NULL,NULL,'Mondadori',NULL),
(90,9788817064439,'',0,1,NULL,NULL,'Rizzoli',NULL),
(91,9788804616898,'',0,1,NULL,NULL,'Mondadori',NULL),
(92,9788856667103,'',0,1,NULL,NULL,'Piemme',NULL),
(93,9788806220039,'',0,1,NULL,NULL,'Einaudi',NULL),
(94,9788834742215,'',0,1,NULL,NULL,'Fanucci',NULL),
(95,9788804736343,'',0,1,NULL,NULL,'Mondadori',NULL),
(96,9788804665292,'',0,1,NULL,NULL,'Mondadori',NULL),
(97,9788854189355,'',0,1,NULL,NULL,'Newton Compton',NULL),
(98,9788834734364,'',0,1,NULL,NULL,'Fanucci',NULL);
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
  `anno_pubblicazione` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `libri`
--

LOCK TABLES `libri` WRITE;
/*!40000 ALTER TABLE `libri` DISABLE KEYS */;
INSERT INTO `libri` VALUES
(9780451524935,'1984','Romanzo distopico di George Orwell.',1949),
(9788804523363,'Gomorra','Viaggio nell\'impero economico e nel sogno di dominio della camorra.',2006),
(9788804600293,'Hunger Games','Il primo capitolo della saga distopica di Suzanne Collins.',2008),
(9788804616898,'Il silenzio degli innocenti','Clarice Starling deve chiedere aiuto a Hannibal Lecter per catturare un serial killer.',1988),
(9788804628334,'Il nome del vento','Kvothe racconta la sua storia di mago, ladro e musicista leggendario.',2007),
(9788804665292,'Fahrenheit 451','Un futuro dove i pompieri appiccano incendi per bruciare i libri.',1953),
(9788804666688,'Il codice da Vinci','Robert Langdon indaga su un omicidio al Louvre che nasconde un segreto millenario.',2003),
(9788804666985,'Il Codice da Vinci','Il thriller best-seller di Dan Brown.',2003),
(9788804667920,'Il vecchio e il mare','La lotta epica tra un vecchio pescatore e un gigantesco marlin.',1952),
(9788804667982,'I promessi sposi','Renzo e Lucia nella Lombardia del Seicento.',1827),
(9788804668231,'Orgoglio e pregiudizio','Il capolavoro di Jane Austen sulle sorelle Bennet.',1813),
(9788804672375,'American Gods','Gli antichi dei vivono tra noi, dimenticati e arrabbiati.',2001),
(9788804682497,'Il mondo nuovo','Una società futura controllata tramite condizionamento e droghe.',1932),
(9788804683838,'It','Il club dei perdenti affronta il male puro a Derry.',1986),
(9788804702003,'Il Signore degli Anelli - La Compagnia dell\'Anello','L\'inizio del viaggio per distruggere l\'Unico Anello.',1954),
(9788804702010,'Il Signore degli Anelli - Le due torri','La compagnia si divide e la guerra incombe.',1954),
(9788804702027,'Il Signore degli Anelli - Il ritorno del re','La battaglia finale per la Terra di Mezzo.',1955),
(9788804711951,'Il trono di spade','L\'inverno sta arrivando. Le casate di Westeros lottano per il potere.',1996),
(9788804719230,'Assassinio sull\'Orient Express','Uno dei casi più celebri di Hercule Poirot.',1934),
(9788804736343,'Neuromante','Il libro che ha inventato il Cyberpunk. Hacker, IA e corporazioni spietate.',1984),
(9788806143048,'Bar sport','Racconti umoristici sulla vita di provincia italiana.',1976),
(9788806173762,'Io non ho paura','Un bambino scopre un terribile segreto in un buco nel terreno.',2001),
(9788806203018,'Oliver Twist','La storia di un orfano nella Londra vittoriana.',1838),
(9788806206019,'Norwegian Wood','Un racconto nostalgico sulla perdita e la sessualità.',1987),
(9788806216445,'Cime tempestose','La passione distruttiva tra Heathcliff e Catherine.',1847),
(9788806218449,'Cent\'anni di solitudine','La saga della famiglia Buendía a Macondo.',1967),
(9788806219378,'Il nome della rosa','Giallo medievale in un monastero benedettino.',1980),
(9788806220039,'Il pettirosso','Harry Hole indaga su un traffico d\'armi che risale alla Seconda Guerra Mondiale.',2000),
(9788806225881,'Moby Dick','La caccia ossessiva del Capitano Achab alla balena bianca.',1851),
(9788806225911,'Il piccolo principe','Un pilota incontra un bambino venuto dalle stelle.',1943),
(9788806226161,'Delitto e castigo','Il tormento psicologico di Raskolnikov dopo aver commesso un omicidio.',1866),
(9788806227441,'Il barone rampante','La vita di Cosimo che decide di vivere sugli alberi.',1957),
(9788807013936,'Seta','Il viaggio di un mercante francese in Giappone.',1996),
(9788807880019,'Cecità','Una misteriosa epidemia rende cieca l\'intera popolazione.',1995),
(9788807880866,'L\'insostenibile leggerezza dell\'essere','Amore e politica nella Praga del 1968.',1984),
(9788807882204,'Uno, nessuno e centomila','La crisi di identità di Vitangelo Moscarda.',1926),
(9788807900359,'Le avventure di Tom Sawyer','Le peripezie di un ragazzo vivace sulle rive del Mississippi.',1876),
(9788807900441,'Il grande Gatsby','Il romanzo simbolo dell’Età del Jazz, scritto da Fitzgerald.',1925),
(9788807900601,'Il ritratto di Dorian Gray','Un giovane vende l\'anima per l\'eterna giovinezza.',1890),
(9788807901301,'La metamorfosi','Il celebre racconto di Kafka sul risveglio di Gregor Samsa.',1915),
(9788811360500,'Anna Karenina','Una delle più grandi storie d\'amore e tragedia della letteratura russa.',1877),
(9788817061759,'Mille splendidi soli','Due donne afghane unite dalla guerra e dal destino.',2007),
(9788817064439,'L\'amore bugiardo (Gone Girl)','Sua moglie è scomparsa. Lui è il sospettato numero uno. Ma nulla è come sembra.',2012),
(9788820063225,'Shining','Il classico horror di Stephen King ambientato nell\'Overlook Hotel.',1977),
(9788830104719,'La divina commedia','Il viaggio di Dante attraverso Inferno, Purgatorio e Paradiso.',1320),
(9788834728560,'Dune','Il pianeta deserto Arrakis e la spezia melange.',1965),
(9788834734364,'Ma gli androidi sognano pecore elettriche?','Il romanzo che ha ispirato Blade Runner. Cosa ci rende umani?',1968),
(9788834739505,'La via dei re','In un mondo flagellato da tempeste, le antiche armature shardplate sono l\'unica salvezza.',2010),
(9788834742215,'Dune','Su Arrakis, il pianeta deserto, si gioca il destino dell\'universo.',1965),
(9788842916659,'Il guardiano degli innocenti','Geralt di Rivia è uno strigo, un mutante cacciatore di mostri.',1993),
(9788845269554,'L\'alchimista','Il viaggio di un pastore andaluso alla ricerca di un tesoro.',1988),
(9788845292613,'Lo Hobbit','Il preludio al Signore degli Anelli di Tolkien.',1937),
(9788845293672,'Furore','La disperata migrazione di una famiglia durante la Grande Depressione.',1939),
(9788854189355,'L\'uomo di Marte','Un astronauta viene abbandonato su Marte e deve sopravvivere con la scienza.',2011),
(9788856667103,'La ragazza del treno','Ogni mattina Rachel guarda fuori dal treno. Un giorno vede qualcosa che non dovrebbe.',2015);
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
INSERT INTO `libro_categoria` VALUES
(9788804616898,11),
(9788804628334,10),
(9788804665292,2),
(9788804666688,11),
(9788804667920,1),
(9788804672375,10),
(9788804682497,3),
(9788804711951,10),
(9788804736343,2),
(9788806203018,7),
(9788806206019,9),
(9788806206019,16),
(9788806216445,1),
(9788806216445,20),
(9788806220039,11),
(9788806225881,4),
(9788806226161,9),
(9788807900359,4),
(9788807900359,15),
(9788807900601,1),
(9788811360500,1),
(9788817064439,11),
(9788834734364,2),
(9788834739505,10),
(9788834742215,2),
(9788842916659,10),
(9788845269554,4),
(9788845269554,16),
(9788845292613,10),
(9788845293672,7),
(9788854189355,2),
(9788856667103,11);
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
) ENGINE=InnoDB AUTO_INCREMENT=446 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recensioni`
--

LOCK TABLES `recensioni` WRITE;
/*!40000 ALTER TABLE `recensioni` DISABLE KEYS */;
INSERT INTO `recensioni` VALUES
(11,9780451524935,'000005',5,'Un capolavoro distopico che rimane sempre attuale. Inquietante.','2024-01-10',12,0),
(13,9788804668231,'000007',3,'Un classico indubbio, ma ho trovato la prima parte un po\' lenta.','2024-03-01',5,2),
(14,9788807900441,'000005',5,'La prosa è elegante e malinconica. Il ritratto perfetto degli anni \'20.','2024-03-05',8,0),
(15,9788806218449,'000006',5,'Un viaggio onirico e complesso. Macondo sembra un luogo reale.','2024-03-10',15,0),
(16,9788807901301,'000007',4,'Angosciante e assurdo, esattamente come dovrebbe essere Kafka.','2024-03-12',6,1),
(17,9788845292613,'000005',5,'Una fiaba perfetta, molto più leggera del Signore degli Anelli.','2024-03-20',30,0),
(18,9788820063225,'000006',5,'Ho dovuto dormire con la luce accesa. King è il re dell\'horror.','2024-04-01',18,2),
(19,9788804719230,'000005',4,'Poirot non delude mai. Il finale mi ha colto di sorpresa.','2024-04-05',10,0),
(20,9788804666985,'000007',2,'Trama avvincente ma scritto in modo troppo semplicistico per i miei gusti.','2024-04-10',3,10),
(21,9788804600293,'000006',4,'Ritmo incalzante, impossibile smettere di leggere.','2024-04-12',22,3),
(23,9788807900359,'000006',4,'Divertente e nostalgico, un inno all\'avventura giovanile.','2024-05-01',7,0),
(24,9788804667920,'000007',5,'Essenziale, potente, commovente. Hemingway al suo meglio.','2024-05-03',11,1),
(25,9788811360500,'000005',5,'Personaggi di una profondità psicologica incredibile.','2024-05-10',14,0),
(26,9788806203018,'000006',3,'Bello, ma a tratti eccessivamente melodrammatico.','2024-05-15',4,1),
(27,9788806226161,'000007',5,'Un\'analisi lucida e terrificante della mente umana e della colpa.','2024-05-20',20,0),
(28,9788806206019,'000005',3,'Atmosfera interessante ma ho trovato il protagonista apatico.','2024-06-01',6,4),
(30,9788806225881,'000007',2,'Troppe digressioni enciclopediche sulle balene, ho fatto fatica a finirlo.','2024-06-10',2,8),
(31,9788807900601,'000005',5,'Oscar Wilde scrive divinamente. Un libro pieno di aforismi geniali.','2024-06-12',19,1),
(32,9788845269554,'000006',2,'Concetti un po\' banali spacciati per profonda filosofia.','2024-06-15',5,12),
(33,9788845293672,'000007',5,'Un pugno nello stomaco. Una storia di miseria ma anche di dignità.','2024-06-20',16,0),
(36,9788806216445,'000007',4,'Amore e odio si mescolano in modo violento. Heathcliff è indimenticabile.','2024-07-10',12,2),
(37,9788804682497,'000005',3,'Interessante visione del futuro, ma ho preferito 1984.','2024-07-15',7,3),
(38,9780451524935,'000006',4,'Molto attuale, ma in alcuni punti l\'ho trovato un po\' pesante.','2024-08-01',5,1),
(39,9780451524935,'000007',3,'Non il mio genere preferito, troppo cupo per i miei gusti.','2024-08-02',2,4),
(40,9780451524935,'000005',5,'Assolutamente geniale. Orwell aveva previsto tutto.','2024-08-05',20,0),
(41,9780451524935,'000006',2,'Idea interessante ma lo stile di scrittura mi ha annoiato.','2024-08-10',1,8),
(46,9788804668231,'000006',5,'Complesso, erudito, affascinante. Un giallo medievale perfetto.','2024-08-04',18,0),
(47,9788804668231,'000005',2,'Troppo latino, mi sono perso dopo 50 pagine.','2024-08-09',3,5),
(48,9788804668231,'000007',4,'Superato lo scoglio iniziale, la trama giallo è avvincente.','2024-08-11',10,1),
(49,9788804668231,'000006',5,'Eco è un maestro indiscusso.','2024-08-18',22,0),
(50,9788807900441,'000007',3,'Mi aspettavo di più visti i film, ma è comunque scorrevole.','2024-08-02',6,2),
(51,9788807900441,'000006',5,'Il finale è pura poesia. Commovente.','2024-08-06',15,0),
(52,9788807900441,'000005',4,'Una critica sociale tagliente mascherata da storia d\'amore.','2024-08-13',9,1),
(53,9788807900441,'000007',2,'Personaggi odiosi, nessuno escluso. Non sono riuscito a empatizzare.','2024-08-20',1,10),
(54,9788806218449,'000005',5,'Il realismo magico al suo apice. Indimenticabile.','2024-08-01',25,0),
(55,9788806218449,'000007',1,'Non ci ho capito nulla. Troppi nomi uguali!','2024-08-05',0,20),
(56,9788806218449,'000006',5,'Una saga familiare che ti entra dentro. Scrittura magnifica.','2024-08-08',19,1),
(57,9788806218449,'000005',4,'Richiede impegno, ma ripaga ogni minuto speso a leggerlo.','2024-08-14',11,2),
(58,9788807901301,'000006',5,'Un incubo burocratico che fa riflettere sulla società moderna.','2024-08-03',14,0),
(59,9788807901301,'000005',3,'Interessante ma mi ha lasciato un senso di frustrazione.','2024-08-07',5,3),
(60,9788807901301,'000007',4,'Surreale e grottesco. Un classico imprescindibile.','2024-08-12',8,1),
(61,9788807901301,'000006',2,'Troppo strano per i miei gusti, non ha né capo né coda.','2024-08-16',2,7),
(62,9788845292613,'000007',5,'Un\'avventura meravigliosa, perfetta per tutte le età.','2024-08-02',35,0),
(63,9788845292613,'000006',4,'Più scorrevole del Signore degli Anelli, si legge in un attimo.','2024-08-09',20,1),
(64,9788845292613,'000005',5,'Bilbo è il miglior personaggio di Tolkien.','2024-08-15',28,0),
(65,9788845292613,'000007',4,'Bella storia, anche se le canzoni sono un po\' lunghe.','2024-08-22',12,3),
(66,9788820063225,'000005',5,'Tensione pura dalla prima all\'ultima pagina.','2024-08-04',21,0),
(67,9788820063225,'000007',3,'La parte centrale è un po\' lenta, ma il finale è esplosivo.','2024-08-10',8,4),
(68,9788820063225,'000006',5,'Maestro del brivido. I personaggi sono descritti benissimo.','2024-08-17',17,1),
(69,9788820063225,'000005',4,'Fa davvero paura, sconsigliato la notte!','2024-08-25',13,2),
(70,9788804719230,'000006',5,'La regina del giallo non si smentisce mai. Trama perfetta.','2024-09-01',15,0),
(71,9788804719230,'000007',3,'Bello, ma avevo intuito il colpevole a metà libro.','2024-09-02',4,1),
(72,9788804719230,'000005',4,'Poirot è un personaggio fantastico, anche se un po\' vanitoso.','2024-09-05',10,0),
(73,9788804719230,'000006',2,'Un po\' lento nello svolgimento, preferisco i thriller moderni.','2024-09-08',2,8),
(74,9788804666985,'000005',4,'Atmosfera parigina descritta magistralmente. Molto malinconico.','2024-09-03',12,1),
(75,9788804666985,'000007',5,'Non è solo un giallo, è una profonda indagine umana.','2024-09-10',8,0),
(76,9788804666985,'000006',3,'Si legge velocemente, ma la trama è un po\' esile.','2024-09-12',5,2),
(77,9788804666985,'000005',2,'Manca un po\' di azione, troppe riflessioni.','2024-09-15',1,6),
(78,9788804600293,'000007',5,'Un\'epopea straordinaria. 1000 pagine che volano via.','2024-09-01',50,2),
(79,9788804600293,'000006',4,'Accurato storicamente e personaggi indimenticabili.','2024-09-05',22,1),
(80,9788804600293,'000005',2,'Troppo lungo e a tratti ripetitivo nelle disgrazie dei protagonisti.','2024-09-10',5,12),
(81,9788804600293,'000007',3,'Bello, ma c\'è troppa violenza gratuita per i miei gusti.','2024-09-20',8,5),
(86,9788807900359,'000005',5,'Un inno alla libertà e all\'infanzia. Divertentissimo.','2024-09-01',18,0),
(87,9788807900359,'000007',4,'Satira sociale pungente nascosta in un libro per ragazzi.','2024-09-08',10,1),
(88,9788807900359,'000006',2,'Il linguaggio dialettale nella traduzione è faticoso.','2024-09-12',3,9),
(89,9788807900359,'000005',3,'Classico americano, ma ho preferito Tom Sawyer a Huck Finn.','2024-09-15',7,2),
(90,9788804667920,'000006',5,'La lotta eterna tra uomo e natura. Scrittura asciutta e potente.','2024-09-04',25,1),
(91,9788804667920,'000007',1,'Una noia mortale. Un vecchio che pesca per 100 pagine.','2024-09-10',2,30),
(92,9788804667920,'000005',4,'Breve ma intenso. Ti lascia un senso di rispetto profondo.','2024-09-15',14,2),
(93,9788804667920,'000006',3,'Bello il messaggio, ma forse un po\' sopravvalutato.','2024-09-20',5,5),
(94,9788811360500,'000007',5,'Scavo psicologico ineguagliabile. Un capolavoro assoluto.','2024-09-05',33,0),
(95,9788811360500,'000005',4,'Impegnativo, richiede concentrazione, ma ne vale la pena.','2024-09-12',12,1),
(96,9788811360500,'000006',2,'Troppo pesante e deprimente per leggerlo d\'estate.','2024-09-18',4,10),
(97,9788811360500,'000007',5,'Ogni personaggio è un universo a sé.','2024-09-25',20,0),
(98,9788806203018,'000005',5,'La perfezione stilistica. Flaubert cura ogni parola.','2024-09-06',15,0),
(99,9788806203018,'000006',1,'Ho odiato la protagonista dall\'inizio alla fine. Insopportabile.','2024-09-10',10,15),
(100,9788806203018,'000007',4,'Critica feroce alla borghesia e alle illusioni romantiche.','2024-09-15',8,2),
(101,9788806203018,'000005',3,'Ben scritto, ma la storia mi ha lasciato indifferente.','2024-09-20',6,4),
(102,9788806226161,'000006',5,'Il tema della colpa è trattato in modo magistrale.','2024-09-01',22,1),
(103,9788806226161,'000007',4,'Raskolnikov è uno dei personaggi più complessi di sempre.','2024-09-08',14,0),
(104,9788806226161,'000005',3,'La parte centrale con il giudice istruttore è la migliore.','2024-09-15',7,3),
(105,9788806226161,'000006',4,'Un thriller psicologico scritto nell\'800. Incredibile.','2024-09-22',11,0),
(106,9788806206019,'000007',5,'L\'assurdo esistenziale spiegato in poche pagine.','2024-09-05',18,0),
(107,9788806206019,'000005',2,'Il protagonista non prova emozioni e questo mi ha infastidito.','2024-09-10',3,8),
(108,9788806206019,'000006',4,'Scrittura gelida e tagliente, perfetta per la storia.','2024-09-15',9,1),
(109,9788806206019,'000007',3,'Interessante filosoficamente, ma narrativamente debole.','2024-09-20',5,4),
(114,9788806225881,'000006',5,'Non è un libro sulle balene, è un libro su Dio e il destino.','2024-09-01',20,1),
(115,9788806225881,'000007',1,'Capitoli interi sulla classificazione dei cetacei... illegibile.','2024-09-08',2,25),
(116,9788806225881,'000005',4,'Il finale è epico e ripaga di tutta la fatica.','2024-09-15',10,3),
(117,9788806225881,'000006',3,'Alterna momenti di genio assoluto a noia totale.','2024-09-22',15,8),
(118,9788807900601,'000005',5,'Ogni frase potrebbe essere una citazione. Geniale.','2024-09-03',40,0),
(119,9788807900601,'000007',4,'Il patto col diavolo in chiave estetica. Molto attuale.','2024-09-10',15,1),
(120,9788807900601,'000006',5,'Dorian Gray è l\'icona della vanità. Scritto divinamente.','2024-09-17',22,0),
(121,9788807900601,'000005',3,'La morale è un po\' datata, ma lo stile è impeccabile.','2024-09-24',5,2),
(122,9788845269554,'000007',5,'Un libro che ti cambia la vita e ti dà speranza.','2024-09-01',50,5),
(123,9788845269554,'000006',1,'Pieno di banalità new age. Non capisco il successo.','2024-09-08',5,30),
(124,9788845269554,'000005',4,'Una favola semplice ma con un significato profondo.','2024-09-15',20,2),
(125,9788845269554,'000007',2,'Carino per un adolescente, troppo semplice per un adulto.','2024-09-22',3,12),
(126,9788845293672,'000005',5,'Crudo, realista, commovente. Uno spaccato d\'America.','2024-09-02',18,0),
(127,9788845293672,'000006',4,'Personaggi che lottano per la dignità. Molto potente.','2024-09-09',12,1),
(128,9788845293672,'000007',5,'Il finale mi ha fatto piangere.','2024-09-16',25,0),
(129,9788845293672,'000005',3,'Molto triste, bisogna essere dell\'umore giusto.','2024-09-23',6,2),
(138,9788806216445,'000007',5,'Passione distruttiva e brughiera inglese. Atmosfera unica.','2024-09-03',25,1),
(139,9788806216445,'000006',2,'Tutti i personaggi sono cattivi e isterici. Non mi è piaciuto.','2024-09-10',4,15),
(140,9788806216445,'000005',4,'Una storia d\'amore gotica e oscura, non per tutti.','2024-09-17',12,2),
(141,9788806216445,'000007',3,'Scritto bene, ma molto angosciante.','2024-09-24',8,3),
(142,9788804682497,'000005',5,'Forse ancora più profetico di 1984. Il controllo tramite il piacere.','2024-09-05',20,0),
(143,9788804682497,'000006',4,'Un mondo asettico che fa paura. Fa riflettere molto.','2024-09-12',14,1),
(144,9788804682497,'000007',2,'Ho trovato i personaggi poco approfonditi e la trama debole.','2024-09-19',3,9),
(145,9788804682497,'000005',4,'Un classico della distopia che va letto insieme a Orwell.','2024-09-26',10,1),
(371,9788804628334,'000005',5,'Il miglior fantasy degli ultimi 20 anni. Prosa poetica.','2025-12-14',0,0),
(372,9788804628334,'000006',5,'Kvothe è un personaggio incredibile.','2025-12-14',0,0),
(373,9788804628334,'000007',4,'Un po\' lento all\'inizio ma poi esplode.','2025-12-14',0,0),
(374,9788804628334,'000005',5,'Non vedo l\'ora che esca il terzo libro... se mai uscirà.','2025-12-14',0,0),
(375,9788804628334,'000006',4,'Sistema magico molto originale.','2025-12-14',0,0),
(376,9788834739505,'000007',5,'Epico è riduttivo. Sanderson è un genio del worldbuilding.','2025-12-14',0,0),
(377,9788834739505,'000005',5,'Kaladin Stormblessed! Personaggi profondissimi.','2025-12-14',0,0),
(378,9788834739505,'000006',3,'Troppo lungo, 1000 pagine sono eccessive.','2025-12-14',0,0),
(379,9788834739505,'000007',4,'Il finale ripaga di tutta la lentezza iniziale.','2025-12-14',0,0),
(380,9788834739505,'000005',5,'Un capolavoro dell\'high fantasy.','2025-12-14',0,0),
(381,9788842916659,'000006',4,'Molto diverso dalla serie TV, decisamente meglio.','2025-12-14',0,0),
(382,9788842916659,'000007',3,'Sono racconti slegati, mi aspettavo un romanzo.','2025-12-14',0,0),
(383,9788842916659,'000005',5,'Geralt e Ranuncolo sono una coppia fantastica.','2025-12-14',0,0),
(384,9788842916659,'000006',4,'Crudo e ironico al punto giusto.','2025-12-14',0,0),
(385,9788842916659,'000007',3,'La traduzione a volte mi sembra un po\' legnosa.','2025-12-14',0,0),
(386,9788804711951,'000005',5,'Intrighi politici scritti divinamente.','2025-12-14',0,0),
(387,9788804711951,'000006',5,'Nessuno è al sicuro. Martin è spietato.','2025-12-14',0,0),
(388,9788804711951,'000007',5,'Molto più complesso della serie TV.','2025-12-14',0,0),
(389,9788804711951,'000005',4,'Tyrion Lannister è il miglior personaggio di sempre.','2025-12-14',0,0),
(390,9788804711951,'000006',5,'Un classico moderno.','2025-12-14',0,0),
(391,9788804672375,'000007',3,'Idea geniale ma svolgimento molto onirico e confuso.','2025-12-14',0,0),
(392,9788804672375,'000005',5,'Gaiman riesce a rendere magica la modernità.','2025-12-14',0,0),
(393,9788804672375,'000006',2,'Non sono riuscito a finirlo, troppo strano.','2025-12-14',0,0),
(394,9788804672375,'000007',4,'Shadow è un protagonista un po\' passivo, ma il mondo è affascinante.','2025-12-14',0,0),
(395,9788804672375,'000005',3,'Bello, ma preferisco Coraline o Stardust.','2025-12-14',0,0),
(396,9788804666688,'000006',5,'Puro intrattenimento. Letto in una notte.','2025-12-14',0,0),
(397,9788804666688,'000007',2,'Storicamente inaccurato e scritto male.','2025-12-14',0,0),
(398,9788804666688,'000005',4,'La caccia al tesoro è avvincente, non prendetelo come un saggio.','2025-12-14',0,0),
(399,9788804666688,'000006',3,'Un po\' ripetitivo se hai letto Angeli e Demoni.','2025-12-14',0,0),
(400,9788804666688,'000007',4,'Tensione sempre alta.','2025-12-14',0,0),
(401,9788817064439,'000005',5,'Amy Dunne è la villain perfetta.','2025-12-14',0,0),
(402,9788817064439,'000006',4,'Il colpo di scena a metà libro mi ha scioccato.','2025-12-14',0,0),
(403,9788817064439,'000007',3,'I protagonisti sono entrambi odiosi.','2025-12-14',0,0),
(404,9788817064439,'000005',5,'Thriller psicologico magistrale.','2025-12-14',0,0),
(405,9788817064439,'000006',4,'Finale controverso, ma coerente.','2025-12-14',0,0),
(406,9788804616898,'000007',5,'Lecter fa paura anche solo con le parole.','2025-12-14',0,0),
(407,9788804616898,'000005',5,'Tensione insostenibile. Capolavoro del thriller.','2025-12-14',0,0),
(408,9788804616898,'000006',5,'Molto meglio del film, che pure è ottimo.','2025-12-14',0,0),
(409,9788804616898,'000007',5,'Scavo psicologico profondissimo.','2025-12-14',0,0),
(410,9788804616898,'000005',5,'Perfetto in ogni dettaglio.','2025-12-14',0,0),
(411,9788856667103,'000006',3,'Si legge bene ma avevo indovinato il colpevole subito.','2025-12-14',0,0),
(412,9788856667103,'000007',2,'Protagonista insopportabile, sempre ubriaca.','2025-12-14',0,0),
(413,9788856667103,'000005',4,'Mi ha tenuto incollato alle pagine.','2025-12-14',0,0),
(414,9788856667103,'000006',3,'Un thriller da ombrellone, senza pretese.','2025-12-14',0,0),
(415,9788856667103,'000007',3,'Niente di eccezionale.','2025-12-14',0,0),
(416,9788806220039,'000005',4,'Harry Hole è un grande detective.','2025-12-14',0,0),
(417,9788806220039,'000006',5,'Intreccio storico-poliziesco fantastico.','2025-12-14',0,0),
(418,9788806220039,'000007',3,'Un po\' troppi salti temporali.','2025-12-14',0,0),
(419,9788806220039,'000005',4,'Scrittura nordica fredda e tagliente.','2025-12-14',0,0),
(420,9788806220039,'000006',4,'Consigliatissimo agli amanti del noir.','2025-12-14',0,0),
(421,9788834742215,'000007',5,'La bibbia della fantascienza.','2025-12-14',0,0),
(422,9788834742215,'000005',5,'Politica, religione ed ecologia. Attualissimo.','2025-12-14',0,0),
(423,9788834742215,'000006',4,'Inizio un po\' ostico per i tanti termini nuovi.','2025-12-14',0,0),
(424,9788834742215,'000007',5,'Un\'esperienza mistica.','2025-12-14',0,0),
(425,9788834742215,'000005',5,'Non esiste nulla come Dune.','2025-12-14',0,0),
(426,9788804736343,'000006',5,'Visionario. Ha previsto internet e la VR.','2025-12-14',0,0),
(427,9788804736343,'000007',2,'Stile troppo frenetico, faticoso da seguire.','2025-12-14',0,0),
(428,9788804736343,'000005',4,'Atmosfera cyberpunk inimitabile.','2025-12-14',0,0),
(429,9788804736343,'000006',3,'Importante storicamente, ma invecchiato un po\' male.','2025-12-14',0,0),
(430,9788804736343,'000007',5,'Il cielo sopra il porto... incipit leggendario.','2025-12-14',0,0),
(431,9788804665292,'000005',5,'Breve e terrificante.','2025-12-14',0,0),
(432,9788804665292,'000006',4,'La prosa di Bradbury è poetica.','2025-12-14',0,0),
(433,9788804665292,'000007',4,'Un monito contro l\'ignoranza.','2025-12-14',0,0),
(434,9788804665292,'000005',5,'Tutti dovrebbero leggerlo a scuola.','2025-12-14',0,0),
(435,9788804665292,'000006',4,'Il finale mi ha commosso.','2025-12-14',0,0),
(436,9788854189355,'000007',5,'Divertente, tecnico e avvincente.','2025-12-14',0,0),
(437,9788854189355,'000005',5,'Ho riso e ho tifato per Watney tutto il tempo.','2025-12-14',0,0),
(438,9788854189355,'000006',4,'Tanti calcoli, ma spiegati bene.','2025-12-14',0,0),
(439,9788854189355,'000007',5,'Un inno all\'ingegno umano.','2025-12-14',0,0),
(440,9788854189355,'000005',4,'Molto meglio del film.','2025-12-14',0,0),
(441,9788834734364,'000006',4,'Diverso dal film, più filosofico.','2025-12-14',0,0),
(442,9788834734364,'000007',5,'Dick era un visionario paranoico.','2025-12-14',0,0),
(443,9788834734364,'000005',3,'Un po\' datato in alcuni aspetti tecnologici.','2025-12-14',0,0),
(444,9788834734364,'000006',5,'L\'atmosfera di decadenza è palpabile.','2025-12-14',0,0),
(445,9788834734364,'000007',4,'Il mercerismo è un concetto affascinante.','2025-12-14',0,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tokenemail`
--

LOCK TABLES `tokenemail` WRITE;
/*!40000 ALTER TABLE `tokenemail` DISABLE KEYS */;
INSERT INTO `tokenemail` VALUES
(2,'1lgwMu','ccf2c3064a437ff1032a8fd08588a70c459cd06c4fc042d33a7990b27bfaa41e','2025-12-12 19:25:57'),
(3,'000001','002144977e21d59f82688024eb8859d2ff081a7fc8633d352ad9899d0af0042f','2025-12-12 19:38:44'),
(5,'000003','7604447344b74133d1d98d03d37df3996641e07f660bdc408d5e30947db88141','2025-12-13 09:54:19'),
(6,'000004','0173672dbfc235bf6e55375020b1a33f965f6544f2ebd8b8ff83a3951afcaa11','2025-12-13 09:56:00'),
(7,'000004','53f76ad26d3f97a2965563585615215502514c3988baa3953a6e90d9fa85fbce','2025-12-13 09:56:54'),
(8,'000004','10765326bdb2d61ef9443a7d0bc05dce8dca49ca0c3c3e1da275fe819fdd293f','2025-12-13 09:57:26'),
(9,'000004','ea358a7a6ed44809fd199d43fcd39ad51a3fe38940d4f48007230dc5a15aa8a8','2025-12-13 09:58:03'),
(10,'000001','e727f5b3a8641b3702cdebe02ad70f5999559495d53e1d6875c2bcda40619c8f','2025-12-13 10:12:26');
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
('000002','Fede','Federico','Femia','FMEFRC00T12H501M','federico.femia121007@gmail.com','$2y$10$.GKBzGj/nzUhryJkCMV.RONUWBvYJ5dkT1ZEZUjB8IUYhs6rm1lm.',0,0,0,0,1,'2025-12-12'),
('000003','admin','Luca','Colombara','CLMMMSKLSJJSJS','colombaramarco21@gmail.com','$2y$10$TpqBefxsjSaSqIVojd0PL.Y9Y.ceBYR6.ERYDwyLIcRrhsIEjOine',0,0,0,0,0,'2025-12-13'),
('000004','TestUsernameMio','Gino','Carrato','CLMMMSKLSJJSJS','nope@gmail.com','$2y$10$Ds64TYp5bDk7SaVqPQ725.MqgxUYYunlRyS7IZrc0IoN3MWsWFJ0i',0,0,0,0,0,'2025-12-13'),
('000005','LettoreVorace','Mario','Rossi','RSSMRA80A01H501Z','mario.rossi@example.com','hash1',0,0,0,0,0,'2025-12-14'),
('000006','BookLover99','Luca','Bianchi','BNCLCU90B02F205K','luca.bianchi@example.com','hash2',0,0,0,0,0,'2025-12-14'),
('000007','CriticoSevero','Giulia','Verdi','VRDGLI85C45L219X','giulia.verdi@example.com','hash3',0,0,0,0,0,'2025-12-14');
/*!40000 ALTER TABLE `utenti` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'database_sito'
--
/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;
/*!50106 DROP EVENT IF EXISTS `delete_expired_email_tokens` */;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = utf8mb3 */ ;;
/*!50003 SET character_set_results = utf8mb3 */ ;;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = 'SYSTEM' */ ;;
/*!50106 CREATE*/ /*!50117 DEFINER=`root`@`localhost`*/ /*!50106 EVENT `delete_expired_email_tokens` ON SCHEDULE EVERY 1 MINUTE STARTS '2025-12-12 15:34:38' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM tokenemail
    WHERE created_at < NOW() - INTERVAL 15 MINUTE */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
DELIMITER ;
/*!50106 SET TIME_ZONE= @save_time_zone */ ;

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

-- Dump completed on 2025-12-14 18:17:56
