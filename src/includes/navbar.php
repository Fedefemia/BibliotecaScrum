<?php
require_once 'security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- LOGICA MESSAGGI CENTRALIZZATA ---
$display_status = null;

if (isset($_SESSION['status'])) {
    $display_status = $_SESSION['status'];
    unset($_SESSION['status']);
}

if (isset($status) && !empty($status)) {
    $display_status = $status;
}

$nome_visualizzato = 'Utente';  // username da database

if (isset($_SESSION['nome_utente'])) {
    $nome_visualizzato = $_SESSION['nome_utente'];
}
?>

<style>
    /* FIX IMMAGINE PROFILO NAVBAR */
    .navbar_pfp {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        object-position: center;
        aspect-ratio: 1 / 1;
        border: 2px solid #3f5135;
        display: block;
    }

    #navbar_pfp {
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
</style>

<nav class="navbar">
    <div class="navbar_left">
        <a href="<?= $path ?>" class="navbar_link_img instrument-sans-semibold" id="navbar_logo">
            <img src="<?= $path ?>public/assets/logo_ligth.png" class="navbar_logo" alt="Biblioteca Scrum">
        </a>
        <a href="./search" class="navbar_search_mobile_link">
            <img src="<?= $path ?>public/assets/icon_search_ligth.png" alt="Cerca" class="navbar_search_icon_mobile">
        </a>
        <div class="search_container">
            <form class="search_container" action="search" method="GET">
                <button type="submit" class="search_icon_button">
                    <img src="<?= $path ?>public/assets/icon_search_dark.png" alt="Cerca" class="navbar_search_icon">
                </button>
                <input type="text" placeholder="Search.." name="search"
                    class="navbar_search_input instrument-sans-semibold"
                    value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
            </form>
        </div>
    </div>
    <div class="navbar_rigth">

        <?php if (checkAccess('amministratore') || checkAccess('bibliotecario')) { ?>
            <div class="navbar_rigth_rigth">
                <a href="<?= $path ?>dashboard" class="navbar_link instrument-sans-semibold">Dashboard <?php echo checkAccess('amministratore') ? 'Amministratore' : 'Bibliotecario'?></a>
            </div>
        <?php } ?>


        <div class="navbar_rigth_left">
            <a href="#" class="navbar_link_img instrument-sans-semibold">
                <img src="<?= $path ?>public/assets/icon_notification.png" alt="notifica" class="navbar_icon">
            </a>

            <?php
            if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) { 
                $pfpPath = $path . 'public/pfp/' . $_SESSION['codice_utente'] . '.png';
                if (!file_exists($pfpPath)) {
                    $pfpPath = $path . 'public/assets/base_pfp.png';
                } else {
                    $pfpPath .= '?v=' . time();
                }
                ?>
                <a href="./profilo" class="navbar_link_img instrument-sans-semibold" id="navbar_pfp">
                    <img src="<?= $pfpPath ?>" alt="pfp" class="navbar_pfp">
                </a>
            <?php } else { ?>
                <a href="./login" class="navbar_link instrument-sans-semibold text_underline">Accedi</a>
            <?php } ?>

        </div>

    </div>
</nav>