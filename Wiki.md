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
2. Connettersi al database con l'Url e le credenziali fornite in privato
   
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
L'utente non esiste più, lascio la righa per esempio

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


# Calcolo Codice Fiscale
<details>
  <summary>Visualizza la procedura</summary>
  
Articolo 1)  Sistemi di codificazione.

Le persone fisiche, le persone giuridiche e le società, associazioni ed altre organizzazioni di persone o di beni prive di personalità giuridica sono iscritte all'anagrafe tributaria secondo appositi sistemi di codificazione.


Articolo 2)  Numero di codice fiscale delle persone fisiche.

Il numero di codice fiscale delle persone fisiche è costituito da un'espressione alfanumerica di sedici caratteri. I primi quindici caratteri sono indicativi dei dati anagrafici di ciascun soggetto secondo l'ordine seguente: tre caratteri alfabetici per il cognome; tre caratteri alfabetici per il nome; due caratteri numerici per l'anno di nascita; un carattere alfabetico per il mese di nascita; due caratteri numerici per il giorno di nascita ed il sesso; quattro caratteri (uno alfabetico e tre numerici) per il comune italiano o per lo Stato estero di nascita. Il sedicesimo carattere, alfabetico, ha funzione di controllo.


Articolo 3)  Caratteri indicativi del cognome.

I cognomi che risultano composti da più parti o comunque separati od interrotti, vengono considerati come se fossero scritti secondo un'unica ed ininterrotta successione di caratteri. Per i soggetti di sesso femminile coniugati si prende in considerazione soltanto il cognome da nubile. Se il cognome contiene tre o più consonanti, i tre caratteri da rilevare sono, nell'ordine, la prima, la seconda e la terza consonante. Se il cognome contiene due consonanti, i tre caratteri da rilevare sono, nell'ordine, la prima e la seconda consonante e la prima vocale. Se il cognome contiene una consonante e due vocali, si rilevano, nell'ordine, quella consonante e quindi la prima e la seconda vocale. Se il cognome contiene una consonante e una vocale, si rilevano la consonante e la vocale, nell'ordine, e si assume come terzo carattere la lettera x (ics). Se il cognome è costituito da due sole vocali, esse si rilevano, nell'ordine, e si assume come terzo carattere la lettera x (ics).


Articolo 4)  Caratteri indicativi del nome.

I nomi doppi, multipli o comunque composti, vengono considerati come scritti per esteso in ogni loro parte e secondo un'unica ed ininterrotta successione di caratteri. Se il nome contiene quattro o più consonanti, i tre caratteri da rilevare sono, nell'ordine, la prima, la terza e la quarta consonante. Se il nome contiene tre consonanti, i tre caratteri da rilevare sono, nell'ordine, la prima, la seconda e la terza consonante. Se il nome contiene due consonanti, i tre caratteri da rilevare sono, nell'ordine, la prima e la seconda consonante e la prima vocale. Se il nome contiene una consonante e due vocali, i tre caratteri da rilevare sono, nell'ordine, quella consonante e quindi la prima e la seconda vocale. Se il nome contiene una consonante e una vocale, si rilevano la consonante e la vocale, nell'ordine, e si assume come terzo carattere la lettera x (ics). Se il nome è costituito da due sole vocali, esse si rilevano nell'ordine, e si assume come terzo carattere la lettera x (ics).


Articolo 5)  Data, sesso e luogo di nascita.

I due caratteri numerici indicativi dell'anno di nascita sono, nell'ordine, la cifra delle decine e la cifra delle unità dell'anno stesso. Il carattere alfabetico corrispondente al mese di nascita è quello stabilito per ciascun mese nella seguente tabella:

Gennaio   = A           Luglio     = L
Febbraio  = B           Agosto     = M
Marzo     = C           Settembre  = P
Aprile    = D           Ottobre    = R
Maggio    = E           Novembre   = S
Giugno    = H           Dicembre   = T

I due caratteri numerici indicativi del giorno di nascita e del sesso vengono determinati nel modo seguente: per i soggetti maschili il giorno di nascita figura invariato, con i numeri da uno a trentuno, facendo precedere dalla cifra zero i giorni del mese dall'uno al nove. Per i soggetti femminili il giorno di nascita viene aumentato di quaranta unità, per cui esso figura con i numeri da quarantuno a settantuno. I quattro caratteri alfanumerici indicativi del comune italiano o dello Stato estero di nascita, costituiti da un carattere alfabetico seguito da tre caratteri numerici, si rilevano rispettivamente dal volume "Codice dei comuni d'Italia" o dal volume "Codice degli Stati esteri", redatti a cura della Direzione generale del catasto e dei servizi tecnici erariali. All'aggiornamento dei volumi di cui al precedente comma provvede la Direzione generale del catasto e dei servizi tecnici erariali.


Articolo 6)  Persone fisiche con identica espressione alfanumerica.

Quando l'espressione alfanumerica relativa ai primi quindici caratteri del codice risulta comune a due o più soggetti, si provvede a differenziarla per ciascuno dei soggetti successivi al primo soggetto codificato. A tal fine, si effettuano, nell'ambito dei sette caratteri numerici contenuti nel codice, sistematiche sostituzioni di una o più cifre a partire da quella di destra, con corrispondenti caratteri alfabetici secondo la seguente tabella:

0 = L   5 = R   1 = M   6 = S   2 = N   7 = T   3 = P   8 = U   4 = Q   9 = V

Articolo 7)  Carattere alfabetico di controllo.

Il sedicesimo carattere ha funzione di controllo dell'esatta trascrizione dei primi quindici caratteri. Esso viene determinato nel modo seguente: ciascuno degli anzidetti quindici caratteri, a seconda che occupi posizione di ordine pari o posizione di ordine dispari, viene convertito in un valore numerico in base alle corrispondenze indicate rispettivamente ai successivi punti 1) e 2).


1) Per la conversione dei sette caratteri con posizione di ordine pari:
A o zero  = zero           O   = 14
B o 1     = 1              P   = 15
C o 2     = 2              Q   = 16
D o 3     = 3              R   = 17
E o 4     = 4              S   = 18
F o 5     = 5              T   = 19
G o 6     = 6              U   = 20
H o 7     = 7              V   = 21
I o 8     = 8              W   = 22
J o 9     = 9              X   = 23
K         = 10             Y   = 24
L         = 11             Z   = 25
M         = 12             -      -
N         = 13             -      -
2) Per la conversione degli otto caratteri con posizione di ordine dispari:
A o zero  = 1              O   = 11
B o 1     = 0              P   = 3
C o 2     = 5              Q   = 6
D o 3     = 7              R   = 8
E o 4     = 9              S   = 12
F o 5     = 13             T   = 14
G o 6     = 15             U   = 16
H o 7     = 17             V   = 10
I o 8     = 19             W   = 22
J o 9     = 21             X   = 25
K         = 2              Y   = 24
L         = 4              Z   = 23
M         = 18             -      -
N         = 20             -      -

I valori numerici così determinati vengono addizionati e la somma si divide per il numero 26. Il carattere di controllo si ottiene convertendo il resto di tale divisione nel carattere alfabetico ad esso corrispondente nella sottoindicata tabella:

zero = A             14 = O
  1 = B             15 = P
  2 = C             16 = Q
  3 = D             17 = R
  4 = E             18 = S
  5 = F             19 = T
  6 = G             20 = U
  7 = H             21 = V
  8 = I             22 = W
  9 = J             23 = X
 10 = K             24 = Y
 11 = L             25 = Z
 12 = M             -    -
 13 = N             -    -
Articolo 8)  Numero di codice fiscale dei soggetti diversi dalle persone fisiche.

Il numero di codice fiscale dei soggetti diversi dalle persone fisiche è costituito da una espressione numerica di undici cifre. Le prime sette cifre rappresentano il numero di matricola del soggetto nell'ambito della provincia in cui ha sede l'ufficio che attribuisce il numero di codice fiscale; esso si ottiene, per ciascun soggetto, incrementando di una unità il numero di matricola stabilito per il soggetto che immediatamente lo precede. Le tre cifre dall'ottava alla decima rappresentano il codice identificativo della provincia in cui ha sede l'ufficio che attribuisce il numero di codice fiscale. L'undicesimo carattere ha funzione di controllo dell'esatta trascrizione delle prime dieci cifre.


Articolo 9)  Carattere numerico di controllo.

Il carattere di controllo viene determinato nel modo seguente: si sommano i valori di ciascuna delle cinque cifre di ordine dispari, partendo da sinistra; si raddoppia ogni cifra di ordine pari e, se il risultato è un numero di due cifre, esso si riduce ad una sola sommando la cifra relativa alle decine e quella relativa alle unità; si sommano quindi tutti i precedenti risultati; si determina il totale delle due somme di cui sopra; si sottrae da dieci la cifra relativa alle unità del precedente totale. Il carattere di controllo è la cifra relativa alle unità del risultato.


Articolo 10)  Numero di codice fiscale provvisorio.

L'Amministrazione finanziaria può attribuire un numero di codice fiscale provvisorio. Il numero di codice fiscale provvisorio delle persone fisiche ha struttura e composizione uguali a quelle di cui al precedente art. 8. Le prime sette cifre rappresentano il numero di matricola del soggetto; le cifre dall'ottava alla decima identificano l'ufficio che attribuisce il numero di codice fiscale provvisorio e l'undicesima è il carattere di controllo, che viene determinato con le modalità di cui all'art. 9. Ha inoltre validità di numero di codice fiscale provvisorio il numero di codice fiscale attribuito a soggetti persone fisiche, avente struttura e composizione conformi agli articoli da 2 a 7 del presente decreto, in corrispondenza del quale siano errati uno o più dati anagrafici che concorrono alla formazione del numero di codice fiscale stesso. Si applicano in tal caso le disposizioni di cui agli articoli 5, secondo comma, e 19 del decreto del Presidente della Repubblica 2 novembre 1976, n. 784. Il numero di codice fiscale provvisorio dei soggetti diversi dalle persone fisiche ha struttura uguale a quella del numero di codice fiscale definitivo.

</details>


