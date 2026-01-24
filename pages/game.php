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

        $query = 'select distinct l.isbn, l.titolo, a.nome, a.cognome, ct.categoria  from copie as c
                                join libri as l on c.isbn = l.isbn
                                join autore_libro as al on al.isbn = l.isbn 
                                join autori as a on a.id_autore = al.id_autore 
                                join libro_categoria as cl on cl.isbn = l.isbn
                                join categorie as ct on ct.id_categoria = cl.id_categoria
                                order by rand() limit 4;';
        $stmtCons = $pdo->prepare($query);

        $stmtCons->execute();
        $output = $stmtCons->fetchAll(PDO::FETCH_ASSOC);

        $readyarr[0] = $randomarr[0]= array($output[0]["isbn"], $output[1]["isbn"], $output[2]["isbn"], $output[3]["isbn"]);
        $readyarr[1] =$randomarr[1]= array($output[0]["titolo"], $output[1]["titolo"], $output[2]["titolo"], $output[3]["titolo"]);
        $readyarr[2] =$randomarr[2]= array($output[0]["nome"]." ".$output[0]["cognome"], $output[1]["nome"]." ".$output[1]["cognome"], $output[2]["nome"]." ".$output[2]["cognome"], $output[3]["nome"]." ".$output[3]["cognome"]);
        $readyarr[3] =$randomarr[3]= array($output[0]["categoria"], $output[1]["categoria"], $output[2]["categoria"], $output[3]["categoria"]);
        shuffle($randomarr[0]);
        shuffle($randomarr[1]);
        shuffle($randomarr[2]);
        shuffle($randomarr[3]);

        print_r($randomarr);
        echo "<br>";
        print_r($readyarr);
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
$page_css = './public/css/style_game.css';
require './src/includes/header.php';
require './src/includes/navbar.php';
?>

<style>
    .tile_img {
        width: 100px;
        height: 100px;
        pointer-events: none;
    }
</style>

<div class="game_con instrument-sans">
    <div>
        <div id='bookscover'>
            <div id='book1'><?= $randomarr[0][0] ?></div>
            <div id='book2'><?= $randomarr[0][1] ?></div>
            <div id='book3'><?= $randomarr[0][2] ?></div>
            <div id='book4'><?= $randomarr[0][3] ?></div>
        </div>

        <br>

        <div id='playcontainer'>
            <div class="tile" id='cont1'>
                <img class="tile_img" alt="place" src="./public/assets/copertina_misteriosa.png">
            </div>
            <div class="tile" id='cont2'>
                <img class="tile_img" alt="place" src="./public/assets/copertina_misteriosa.png">
            </div>
            <div class="tile" id='cont3'>
                <img class="tile_img" alt="place" src="./public/assets/copertina_misteriosa.png">
            </div>
            <div class="tile" id='cont4'>
                <img class="tile_img" alt="place" src="./public/assets/copertina_misteriosa.png">
            </div>
        </div>

        <br>

        <div id='titoli'>
            <div class="choise" draggable="true" id='title1'><?= $randomarr[1][0] ?></div>
            <div class="choise" draggable="true" id='title2'><?= $randomarr[1][1] ?></div>
            <div class="choise" draggable="true"  id='title3'><?= $randomarr[1][2] ?></div>
            <div class="choise" draggable="true" id='title4'><?= $randomarr[1][3] ?></div>
        </div>

        <br>

        <div id='autori'>
            <div class="choise" draggable="true" id='auth1'><?= $randomarr[2][0] ?></div>
            <div class="choise" draggable="true" id='auth2'><?= $randomarr[2][1] ?></div>
            <div class="choise" draggable="true" id='auth3'><?= $randomarr[2][2] ?></div>
            <div class="choise" draggable="true" id='auth4'><?= $randomarr[2][3] ?></div>
        </div>

        <br>

        <div id='generi'>
            <div class="choise" draggable="true" id='gen1'><?= $randomarr[3][0] ?></div>
            <div class="choise" draggable="true" id='gen2'><?= $randomarr[3][1] ?></div>
            <div class="choise" draggable="true" id='gen3'><?= $randomarr[3][2] ?></div>
            <div class="choise" draggable="true" id='gen4'><?= $randomarr[3][3] ?></div>
        </div>
    </div>
</div>


<script src="./public/scripts/game_logic.js"></script>

<?php require_once './src/includes/footer.php'; ?>
