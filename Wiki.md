# Wiki
Questa é la wiki del progetto, qui ci sono tutte le informazioni per sviluppare e modificare il database, se manca qualcosa scrivetemi in privato e aggiungeró le parti
### Il sito si aggiorna in automatico facendo il pull da github ad ogni modifica del main tramite webhook

## Come clonare il progetto e modificarlo in locale
1. clonare il progetto dentro la cartella htdocs o qualsiasi cartella accessibile da un web server php come Apache
2. installare [composer](<https://getcomposer.org/>) a livello global o locale, e poi eseguire **composer install**
3. Con composer settato e la libreria di vlucas dotenv installata rinominare il file .env-base in .env e modificare i rispettivi campi per la connessione al database locale (localhost)
4. avviare mysql e dentro un database di vostra creazione collegato a .env e usare il file backup_db.sql

## Database
il database é impostato per backupare alle 2:00 UTC+1 sul file backup_db.sql

### Come connettersi al database
1. Installare DBeaver o qualsiasi interfaccia per la connessione ad un database remoto (porta 3306)
2. Connettersi al database come URL **7.tcp.eu.ngrok.io:12748** (jdbc:mysql://7.tcp.eu.ngrok.io:12748) usando le credenziali fornite
   
## Creare nuove pagine
1. Per creare nuove pagine piazzare il file .php della pagina dentro la cartella /pages
2. Modificare il dizionario $whitelist di router.php aggiungendo '/nomecollegamento' => 'pages/nomefile.php'

## Informazioni utili
tutti i file presenti nella root sono indicizzati o serviti passando per .htdocs 

la cartella src contiene i file utilizzabili nelle pagine

## Interfacciarsi col database
### Creare nuovi utenti
Per creare nuovi utenti richiamare la procedura sp_crea_utente_alfanumerico, tramite 

CALL sp_crea_utente_alfanumerico(USERNAME, NONE, COGNOME, CODICE_FISCALE, EMAIL, PASSWORD_HASH);

la procedure restituirá un campo nuovo_id con l'id incrementante generato automaticamente

<br>

utente giá inserito:

CALL sp_crea_utente_alfanumerico('TestUsername1', 'Cobra', 'Ivi', 'GRRRMN07S01A655L', 'prova@mail.com', 'passwordhash1');

OUTPUT: nuovo_id 000001

**ATTENZIONE** la tabella utenti contiene un check per l'username di tipo CHECK (username NOT REGEXP '^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$'); per impedire login errati


### Controllare utenti per login

per gestire il login (o qualsiasi ricerca tramite password e username, codice fiscale o email):

CALL CheckLoginUser('mario@email.it', 'passwordSegreta', @esito);

SELECT @esito;

### Per vedere il profilo utente sulla pagina iniziale (index)

``` sql
SELECT u.username, u.codice_fiscale
FROM utenti
WHERE codice_fiscale = :codice_fiscale
-- oppure
-- WHERE codice_alfanumerico = :codice_alfanumerico;
```

### Per vedere i Prestiti attuali attivi

``` sql
SELECT p.id_prestito, l.titolo, c.id_copia, c.copertina,
       p.data_prestito, p.data_scadenza
FROM prestiti p
JOIN copie c ON p.ic_copia = c.id_copia
JOIN libri l ON c.isbn = l.isbn
WHERE p.codice_alfanumerico = :codice_alfanumerico
  AND p.data_restituzione IS NULL;
```

### Per vedere le ultime uscite che hanno copie disponibili

``` sql
SELECT DISTINCT l.isbn, l.titolo, l.descrizione
FROM libri l
JOIN copie c ON c.isbn = l.isbn
WHERE c.disponibile = TRUE
LIMIT 10;
```

### Per vedere libri consigliati se utente è loggato

``` sql
SELECT lc.isbn, l.titolo
FROM libri_consigliati lc
JOIN libri l ON lc.isbn = l.isbn
WHERE lc.codice_alfanumerico = :codice_alfanumerico
ORDER BY lc.n_consigli DESC
HAVING n_consigli <= 2
LIMIT 10;
```

### Per vedere libri popolari se utente non è loggato

``` sql
SELECT r.isbn, l.titolo,
       AVG(r.voto) AS voto_medio,
       COUNT(*) AS totale_recensioni
FROM recensioni r
JOIN libri l ON r.isbn = l.isbn
GROUP BY r.isbn
ORDER BY voto_medio DESC, totale_recensioni DESC
LIMIT 10;
```

### Libri popolari con soglia minima di recensioni

``` sql
SELECT r.isbn, l.titolo,
       AVG(r.voto) AS voto_medio,
       COUNT(*) AS totale_recensioni
FROM recensioni r
JOIN libri l ON r.isbn = l.isbn
GROUP BY r.isbn
HAVING COUNT(*) >= 3
ORDER BY voto_medio DESC, totale_recensioni DESC
LIMIT 10;
```


