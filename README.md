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
