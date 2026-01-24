<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Includiamo la configurazione
require_once 'db_config.php';

// Inizializziamo il messaggio per evitare errori "Undefined variable"
$messaggio_db = '';

// --- 1. TEST SCRITTURA (INSERT) ---
// Eseguiamo l'INSERT solo se la connessione ($pdo) esiste
if (isset($pdo)) {
    try {
        // Se l'utente è loggato, usiamo il suo nome nel DB, altrimenti "Utente Web"
        $nome_visitatore = isset($_SESSION['username']) ? $_SESSION['username'] . ' (Logged)' : 'Utente Web';

        $stmt = $pdo->prepare('select l.titolo, a.nome, a.cognome, ct.categoria  from copie as c
                                join libri as l on c.isbn = l.isbn
                                join autore_libro as al on al.isbn = l.isbn 
                                join autori as a on a.id_autore = al.id_autore 
                                join libro_categoria as cl on cl.isbn = l.isbn
                                join categorie as ct on ct.id_categoria = cl.id_categoria
                                order by rand() limit 4;'
                            );
        $stmt->execute();
    } catch (PDOException $e) {
        $messaggio_db = 'Errore Scrittura: ' . $e->getMessage();
        $class_messaggio = 'error';
    }
} else {
    $messaggio_db = 'Connessione al Database non riuscita (controlla db_config.php).';
    $class_messaggio = 'error';
}
?>

<?php
// ---------------- HTML HEADER ----------------
$title = 'Contatti - Biblioteca Scrum';
$path = './';
$page_css = './public/css/style_index.css';
require './src/includes/header.php';
require './src/includes/navbar.php';
?>

<div>
    <div id='bookscover'>
        <div id='book1'></div>
        <div id='book2'></div>
        <div id='book3'></div>
        <div id='book4'></div>
    </div>

    <div id='playcontainer'>
        <div id='cont1'></div>
        <div id='cont2'></div>
        <div id='cont3'></div>
        <div id='cont4'></div>
    </div>

    <div id='titoli'>
        <div id='title1'></div>
        <div id='title2'></div>
        <div id='title3'></div>
        <div id='title4'></div>
    </div>
    
    <div id='autori'>
        <div id='auth1'></div>
        <div id='auth2'></div>
        <div id='auth3'></div>
        <div id='auth4'></div>
    </div>
    
    <div id='generi'>
        <div id='gen1'></div>
        <div id='gen2'></div>
        <div id='gen3'></div>
        <div id='gen4'></div>
    </div>
</div>

<?php require_once './src/includes/footer.php'; ?>
