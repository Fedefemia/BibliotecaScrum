<?php
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

<nav class="navbar">
    <div class="navbar_left">
        <a href="./" class="navbar_link_img instrument-sans-semibold" id="navbar_logo">
            <img src="./public/assets/logo_ligth.png" class="navbar_logo" alt="Biblioteca Scrum">
        </a>
        <a href="./search_page.html" class="navbar_search_mobile_link">
            <img src="./public/assets/icon_search_ligth.png" alt="Cerca" class="navbar_search_icon_mobile">
        </a>
        <div class="search_container">
            <form class="search_container" action="">
                <button type="submit" class="search_icon_button">
                    <img src="./public/assets/icon_search_dark.png" alt="Cerca" class="navbar_search_icon">
                </button>
                <input type="text" placeholder="Search.." name="search" class="navbar_search_input instrument-sans-semibold">
            </form>
        </div>
    </div>
    <div class="navbar_rigth">
        <div class="navbar_rigth_rigth">
            <a href="./dashboard" class="navbar_link instrument-sans-semibold">Dashboard</a>
        </div>

        <div class="navbar_rigth_left">
            <a href="#" class="navbar_link_img instrument-sans-semibold">
                <img src="./public/assets/icon_notification.png" alt="notifica" class="navbar_icon">
            </a>

            <?php
            if (isset($_SESSION['logged']) && $_SESSION['logged'] === true){?>
                    <a href="#" class="navbar_link_img instrument-sans-semibold" id="navbar_pfp">
                        <img src="./public/assets/base_pfp.png" alt="pfp" class="navbar_icon navbar_pfp">
                    </a>
            <?php    } else { ?>
                <a href="./login" class="navbar_link instrument-sans-semibold text_underline">Log-In</a>
            <?php     } ?>

        </div>

    </div>
</nav>