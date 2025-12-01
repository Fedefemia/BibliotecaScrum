# ⚠️NON SETTARE PUBBLICA QUESTA REPO E NON CONDIVIDERE QUESTO README⚠️


# Biblioteca Scrum

## Link Sito
### Il sito si aggiorna in automatico facendo il pull da github ad ogni modifica del main (webhook)
http://according-role.gl.at.ply.gg:27881/

## Database
il database é impostato per backupare alle 2:00 UTC+1 sul file backup_db.sql

### Come connettersi al database
1 - Installare DBeaver o qualsiasi interfaccia per la connessione ad un database remoto (porta 3306)

2 - Connettersi al database come URL **force-scoring.gl.at.ply.gg:26455** (jdbc:mysql://force-scoring.gl.at.ply.gg:26455) usando le credenziali fornite

Password Mysql:  PWBiblioteca2007

## Membri
- Federico Femia
- Gianluca Grammatica
- Fabio Giuriato
- Marco Colombara
- Ivan Viero

## Whiteboard
<a href="https://lucid.app/lucidspark/ae61a960-262b-4a02-baf6-5c5d7e4cf0a0/edit?viewport_loc=-1013%2C39%2C2048%2C975%2C0_0&invitationId=inv_6856dabe-63fd-4872-aab1-c89c3f2e529d">Lucidspark</a>

# Epic 1
### Creazione del Database
- Definizione necessità database
- Diagramma ER
- SQL
  
### Creazione Account
- Form di signin
- Validazione account via email di conferma

### Login
- Form di login
- Gestione delle sesioni

### Area personale
- Pagina profilo di ogni utente
- Gestione delle funzionalità della pagina in base alla sessione loggata
- Funzioni di gestione profilo e di modifica delle informazioni profilo

### Tessera utente
- Creazione pdf con la card
- Creazione codice a barre
- Scansione codice a barre

### Sviluppo sito web php
- Grafica sito web
- Sviluppo pagine Index, Login/Signup, Profilo utente
- Hosting web server
- Connessione al database


# Calcolo Codice Fiscale

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
