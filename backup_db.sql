-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Dic 12, 2025 alle 22:08
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bibliotecascrum_db`
--

DELIMITER $$
--
-- Procedure
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CheckLoginUser` (IN `p_input_login` VARCHAR(255), IN `p_password_input` VARCHAR(255), OUT `p_result` VARCHAR(50))   BEGIN
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

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_crea_utente_alfanumerico` (IN `p_username` VARCHAR(50), IN `p_nome` VARCHAR(50), IN `p_cognome` VARCHAR(100), IN `p_codice_fiscale` CHAR(16), IN `p_email` VARCHAR(255), IN `p_password_hash` VARCHAR(255))   BEGIN

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

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struttura della tabella `accessi_falliti`
--

CREATE TABLE `accessi_falliti` (
                                   `id_accessi` int(11) NOT NULL,
                                   `codice_alfanumerico` varchar(6) NOT NULL,
                                   `dataora` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `autore_libro`
--

CREATE TABLE `autore_libro` (
                                `id_autore` int(11) NOT NULL,
                                `isbn` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `autori`
--

CREATE TABLE `autori` (
                          `id_autore` int(11) NOT NULL,
                          `nome` varchar(100) NOT NULL,
                          `cognome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `badge`
--

CREATE TABLE `badge` (
                         `id_badge` int(11) NOT NULL,
                         `nome` varchar(255) NOT NULL,
                         `icona` varchar(255) NOT NULL,
                         `descrizione` text DEFAULT NULL,
                         `tipo` varchar(100) DEFAULT NULL,
                         `target_numerico` smallint(6) NOT NULL,
                         `data_fine` date DEFAULT NULL,
                         `root` smallint(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `categorie`
--

CREATE TABLE `categorie` (
                             `id_categoria` int(11) NOT NULL,
                             `categoria` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `consensi`
--

CREATE TABLE `consensi` (
                            `id_consenso` int(11) NOT NULL,
                            `codice_alfanumerico` varchar(6) NOT NULL,
                            `tipo_consenso` varchar(50) DEFAULT NULL,
                            `data_consenso` date DEFAULT NULL,
                            `indirizzo_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `copie`
--

CREATE TABLE `copie` (
                         `id_copia` int(11) NOT NULL,
                         `isbn` bigint(20) DEFAULT NULL,
                         `ean` varchar(50) NOT NULL,
                         `condizione` smallint(6) NOT NULL,
                         `disponibile` tinyint(1) NOT NULL,
                         `anno_pubblicazione` year(4) DEFAULT NULL,
                         `conferma_anno_pubblicazione` tinyint(1) DEFAULT 1,
                         `editore` varchar(100) NOT NULL,
                         `copertina` varchar(255) NOT NULL,
                         `taf_rfid` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `libri`
--

CREATE TABLE `libri` (
                         `isbn` bigint(20) NOT NULL,
                         `titolo` varchar(255) NOT NULL,
                         `descrizione` text DEFAULT NULL,
                         `data` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `libri_consigliati`
--

CREATE TABLE `libri_consigliati` (
                                     `id` bigint(20) NOT NULL,
                                     `isbn` bigint(20) NOT NULL,
                                     `codice_alfanumerico` varchar(6) DEFAULT NULL,
                                     `n_consigli` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `libro_categoria`
--

CREATE TABLE `libro_categoria` (
                                   `isbn` bigint(20) NOT NULL,
                                   `id_categoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `log_monitoraggi`
--

CREATE TABLE `log_monitoraggi` (
                                   `id_log` int(11) NOT NULL,
                                   `codice_alfanumerico` varchar(6) NOT NULL,
                                   `tipo_evento` varchar(50) NOT NULL,
                                   `descrizione` text DEFAULT NULL,
                                   `indirizzo_ip` varchar(45) DEFAULT NULL,
                                   `dataora_evento` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `multe`
--

CREATE TABLE `multe` (
                         `id_multa` int(11) NOT NULL,
                         `id_prestito` int(11) NOT NULL,
                         `codice_alfanumerico` varchar(6) NOT NULL,
                         `importo` decimal(10,2) NOT NULL,
                         `causale` text NOT NULL,
                         `data_creata` date DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `notifiche`
--

CREATE TABLE `notifiche` (
                             `id_notifica` int(11) NOT NULL,
                             `codice_alfanumerico` varchar(6) NOT NULL,
                             `titolo` varchar(255) NOT NULL,
                             `messaggio` text NOT NULL,
                             `tipo` varchar(50) NOT NULL,
                             `dataora_invio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `pagamenti`
--

CREATE TABLE `pagamenti` (
                             `id_pagamento` int(11) NOT NULL,
                             `codice_alfanumerico` varchar(6) NOT NULL,
                             `data_apertura` date DEFAULT NULL,
                             `data_chiusura` date DEFAULT NULL,
                             `importo` decimal(10,2) NOT NULL,
                             `causale` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `prenotazioni`
--

CREATE TABLE `prenotazioni` (
                                `id_prenotazione` int(11) NOT NULL,
                                `codice_alfanumerico` varchar(6) NOT NULL,
                                `isbn` bigint(20) DEFAULT NULL,
                                `data_prenotazione` date DEFAULT NULL,
                                `data_assegnazione` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `prestiti`
--

CREATE TABLE `prestiti` (
                            `id_prestito` int(11) NOT NULL,
                            `codice_alfanumerico` varchar(6) NOT NULL,
                            `id_copia` int(11) DEFAULT NULL,
                            `data_prestito` date DEFAULT NULL,
                            `data_scadenza` date DEFAULT NULL,
                            `data_restituzione` date DEFAULT NULL,
                            `num_rinnovi` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `recensioni`
--

CREATE TABLE `recensioni` (
                              `id_recensione` int(11) NOT NULL,
                              `isbn` bigint(20) DEFAULT NULL,
                              `codice_alfanumerico` varchar(6) NOT NULL,
                              `voto` smallint(6) NOT NULL,
                              `commento` text NOT NULL,
                              `data_commento` date DEFAULT current_timestamp(),
                              `like_count` int(11) DEFAULT 0,
                              `dislike_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `ruoli`
--

CREATE TABLE `ruoli` (
                         `codice_alfanumerico` varchar(6) DEFAULT NULL,
                         `studente` tinyint(1) DEFAULT 0,
                         `docente` tinyint(1) DEFAULT 0,
                         `bibliotecario` tinyint(1) DEFAULT 0,
                         `amministratore` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `utente_badge`
--

CREATE TABLE `utente_badge` (
                                `id_ub` int(11) NOT NULL,
                                `id_badge` int(11) DEFAULT NULL,
                                `codice_alfanumerico` varchar(6) NOT NULL,
                                `livello` smallint(6) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

CREATE TABLE `utenti` (
                          `codice_alfanumerico` varchar(6) NOT NULL,
                          `username` varchar(50) DEFAULT NULL,
                          `nome` varchar(50) NOT NULL,
                          `cognome` varchar(100) NOT NULL,
                          `codice_fiscale` char(16) NOT NULL,
                          `email` varchar(255) NOT NULL,
                          `password_hash` varchar(255) NOT NULL,
                          `livello_privato` tinyint(3) UNSIGNED DEFAULT 0,
                          `login_bloccato` tinyint(1) DEFAULT 0,
                          `account_bloccato` tinyint(1) DEFAULT 0,
                          `affidabile` tinyint(1) DEFAULT 0,
                          `email_confermata` tinyint(1) DEFAULT 0,
                          `data_creazione` date DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`codice_alfanumerico`, `username`, `nome`, `cognome`, `codice_fiscale`, `email`, `password_hash`, `livello_privato`, `login_bloccato`, `account_bloccato`, `affidabile`, `email_confermata`, `data_creazione`) VALUES
    ('000001', 'TestUsername1', 'Cobra', 'Ivi', 'GRRRMN07S01A655L', 'prova@mail.com', 'c0934c19bfe8216c60ef23168c040168caa17037fa4c06c5a3c1100e0c9d0663', 0, 0, 0, 0, 0, '2025-12-08');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `accessi_falliti`
--
ALTER TABLE `accessi_falliti`
    ADD PRIMARY KEY (`id_accessi`),
  ADD KEY `codice_alfanumerico` (`codice_alfanumerico`),
  ADD KEY `idx_accessi_dataora` (`dataora`);

--
-- Indici per le tabelle `autore_libro`
--
ALTER TABLE `autore_libro`
    ADD PRIMARY KEY (`id_autore`,`isbn`),
  ADD KEY `autore_libro_ibfk_2` (`isbn`);

--
-- Indici per le tabelle `autori`
--
ALTER TABLE `autori`
    ADD PRIMARY KEY (`id_autore`),
  ADD UNIQUE KEY `nome` (`nome`),
  ADD UNIQUE KEY `cognome` (`cognome`);

--
-- Indici per le tabelle `badge`
--
ALTER TABLE `badge`
    ADD PRIMARY KEY (`id_badge`);

--
-- Indici per le tabelle `categorie`
--
ALTER TABLE `categorie`
    ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `categoria` (`categoria`);

--
-- Indici per le tabelle `consensi`
--
ALTER TABLE `consensi`
    ADD PRIMARY KEY (`id_consenso`),
  ADD KEY `codice_alfanumerico` (`codice_alfanumerico`);

--
-- Indici per le tabelle `copie`
--
ALTER TABLE `copie`
    ADD PRIMARY KEY (`id_copia`),
  ADD UNIQUE KEY `editore` (`editore`),
  ADD UNIQUE KEY `anno_pubblicazione` (`anno_pubblicazione`),
  ADD KEY `isbn` (`isbn`);

--
-- Indici per le tabelle `libri`
--
ALTER TABLE `libri`
    ADD PRIMARY KEY (`isbn`);

--
-- Indici per le tabelle `libri_consigliati`
--
ALTER TABLE `libri_consigliati`
    ADD PRIMARY KEY (`id`),
  ADD KEY `isbn` (`isbn`),
  ADD KEY `codice_alfanumerico` (`codice_alfanumerico`);

--
-- Indici per le tabelle `libro_categoria`
--
ALTER TABLE `libro_categoria`
    ADD PRIMARY KEY (`isbn`,`id_categoria`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indici per le tabelle `log_monitoraggi`
--
ALTER TABLE `log_monitoraggi`
    ADD PRIMARY KEY (`id_log`),
  ADD KEY `codice_alfanumerico` (`codice_alfanumerico`);

--
-- Indici per le tabelle `multe`
--
ALTER TABLE `multe`
    ADD PRIMARY KEY (`id_multa`),
  ADD KEY `id_prestito` (`id_prestito`),
  ADD KEY `codice_alfanumerico` (`codice_alfanumerico`);

--
-- Indici per le tabelle `notifiche`
--
ALTER TABLE `notifiche`
    ADD PRIMARY KEY (`id_notifica`),
  ADD KEY `codice_alfanumerico` (`codice_alfanumerico`);

--
-- Indici per le tabelle `pagamenti`
--
ALTER TABLE `pagamenti`
    ADD PRIMARY KEY (`id_pagamento`),
  ADD KEY `codice_alfanumerico` (`codice_alfanumerico`);

--
-- Indici per le tabelle `prenotazioni`
--
ALTER TABLE `prenotazioni`
    ADD PRIMARY KEY (`id_prenotazione`),
  ADD KEY `codice_alfanumerico` (`codice_alfanumerico`),
  ADD KEY `isbn` (`isbn`);

--
-- Indici per le tabelle `prestiti`
--
ALTER TABLE `prestiti`
    ADD PRIMARY KEY (`id_prestito`),
  ADD KEY `codice_alfanumerico` (`codice_alfanumerico`),
  ADD KEY `id_copia` (`id_copia`);

--
-- Indici per le tabelle `recensioni`
--
ALTER TABLE `recensioni`
    ADD PRIMARY KEY (`id_recensione`),
  ADD KEY `isbn` (`isbn`),
  ADD KEY `codice_alfanumerico` (`codice_alfanumerico`);

--
-- Indici per le tabelle `ruoli`
--
ALTER TABLE `ruoli`
    ADD KEY `codice_alfanumerico` (`codice_alfanumerico`);

--
-- Indici per le tabelle `utente_badge`
--
ALTER TABLE `utente_badge`
    ADD PRIMARY KEY (`id_ub`),
  ADD KEY `id_badge` (`id_badge`),
  ADD KEY `codice_alfanumerico` (`codice_alfanumerico`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
    ADD PRIMARY KEY (`codice_alfanumerico`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `accessi_falliti`
--
ALTER TABLE `accessi_falliti`
    MODIFY `id_accessi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `autori`
--
ALTER TABLE `autori`
    MODIFY `id_autore` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `badge`
--
ALTER TABLE `badge`
    MODIFY `id_badge` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `categorie`
--
ALTER TABLE `categorie`
    MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `consensi`
--
ALTER TABLE `consensi`
    MODIFY `id_consenso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `copie`
--
ALTER TABLE `copie`
    MODIFY `id_copia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `libri_consigliati`
--
ALTER TABLE `libri_consigliati`
    MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `log_monitoraggi`
--
ALTER TABLE `log_monitoraggi`
    MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `multe`
--
ALTER TABLE `multe`
    MODIFY `id_multa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `notifiche`
--
ALTER TABLE `notifiche`
    MODIFY `id_notifica` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `pagamenti`
--
ALTER TABLE `pagamenti`
    MODIFY `id_pagamento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `prenotazioni`
--
ALTER TABLE `prenotazioni`
    MODIFY `id_prenotazione` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `prestiti`
--
ALTER TABLE `prestiti`
    MODIFY `id_prestito` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `recensioni`
--
ALTER TABLE `recensioni`
    MODIFY `id_recensione` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `utente_badge`
--
ALTER TABLE `utente_badge`
    MODIFY `id_ub` int(11) NOT NULL AUTO_INCREMENT;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `accessi_falliti`
--
ALTER TABLE `accessi_falliti`
    ADD CONSTRAINT `accessi_falliti_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`);

--
-- Limiti per la tabella `autore_libro`
--
ALTER TABLE `autore_libro`
    ADD CONSTRAINT `autore_libro_ibfk_1` FOREIGN KEY (`id_autore`) REFERENCES `autori` (`id_autore`),
  ADD CONSTRAINT `autore_libro_ibfk_2` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`);

--
-- Limiti per la tabella `consensi`
--
ALTER TABLE `consensi`
    ADD CONSTRAINT `consensi_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`);

--
-- Limiti per la tabella `copie`
--
ALTER TABLE `copie`
    ADD CONSTRAINT `copie_ibfk_1` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`);

--
-- Limiti per la tabella `libri_consigliati`
--
ALTER TABLE `libri_consigliati`
    ADD CONSTRAINT `libri_consigliati_ibfk_1` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`),
  ADD CONSTRAINT `libri_consigliati_ibfk_2` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`);

--
-- Limiti per la tabella `libro_categoria`
--
ALTER TABLE `libro_categoria`
    ADD CONSTRAINT `libro_categoria_ibfk_1` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`),
  ADD CONSTRAINT `libro_categoria_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorie` (`id_categoria`);

--
-- Limiti per la tabella `log_monitoraggi`
--
ALTER TABLE `log_monitoraggi`
    ADD CONSTRAINT `log_monitoraggi_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`);

--
-- Limiti per la tabella `multe`
--
ALTER TABLE `multe`
    ADD CONSTRAINT `multe_ibfk_1` FOREIGN KEY (`id_prestito`) REFERENCES `prestiti` (`id_prestito`),
  ADD CONSTRAINT `multe_ibfk_2` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`);

--
-- Limiti per la tabella `notifiche`
--
ALTER TABLE `notifiche`
    ADD CONSTRAINT `notifiche_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`);

--
-- Limiti per la tabella `pagamenti`
--
ALTER TABLE `pagamenti`
    ADD CONSTRAINT `pagamenti_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`);

--
-- Limiti per la tabella `prenotazioni`
--
ALTER TABLE `prenotazioni`
    ADD CONSTRAINT `prenotazioni_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`),
  ADD CONSTRAINT `prenotazioni_ibfk_2` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`);

--
-- Limiti per la tabella `prestiti`
--
ALTER TABLE `prestiti`
    ADD CONSTRAINT `prestiti_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`),
  ADD CONSTRAINT `prestiti_ibfk_2` FOREIGN KEY (`id_copia`) REFERENCES `copie` (`id_copia`);

--
-- Limiti per la tabella `recensioni`
--
ALTER TABLE `recensioni`
    ADD CONSTRAINT `recensioni_ibfk_1` FOREIGN KEY (`isbn`) REFERENCES `libri` (`isbn`),
  ADD CONSTRAINT `recensioni_ibfk_2` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`);

--
-- Limiti per la tabella `ruoli`
--
ALTER TABLE `ruoli`
    ADD CONSTRAINT `ruoli_ibfk_1` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`);

--
-- Limiti per la tabella `utente_badge`
--
ALTER TABLE `utente_badge`
    ADD CONSTRAINT `utente_badge_ibfk_1` FOREIGN KEY (`id_badge`) REFERENCES `badge` (`id_badge`),
  ADD CONSTRAINT `utente_badge_ibfk_2` FOREIGN KEY (`codice_alfanumerico`) REFERENCES `utenti` (`codice_alfanumerico`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
