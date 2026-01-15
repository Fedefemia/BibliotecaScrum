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

if(isset($_POST["logout"])){
    session_unset();
    session_destroy();
    header("Location: login");
    exit;
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
        cursor: pointer;
    }

    #navbar_pfp {
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        position: relative;
    }

    /* STILI DROPDOWN MENU */
    .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        top: 55px; /* Spostato leggermente più in basso */
        background-color: #eae3d2;
        min-width: 180px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1000;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e0e0e0;
    }

    /* UNIFORMAZIONE TOTALE PULSANTI E LINK */
    .dropdown-content a, 
    .dropdown-content button {
        display: block;
        width: 100%;
        padding: 14px 20px; /* Padding aumentato per clic più facile */
        text-align: left;
        border: none;
        background: none;
        background-color: transparent;
        cursor: pointer;
        
        /* Font settings identici per entrambi */
        font-family: 'Instrument Sans', sans-serif; 
        font-size: 15px;
        font-weight: 500; /* O 'normal' a seconda delle preferenze */
        color: #333333;
        text-decoration: none;
        
        box-sizing: border-box;
        margin: 0;
        outline: none;
        -webkit-appearance: none; /* Rimuove stili default iOS */
    }

    .dropdown-content a:hover, 
    .dropdown-content button:hover {
        background-color: #f5f5f5;
        color: #000;
    }

    /* Separatore opzionale tra gli elementi se lo vuoi */
    .dropdown-content a {
        border-bottom: 1px solid #f0f0f0;
    }
    .dropdown-content form {
        margin: 0;
        padding: 0;
    }

    .show {
        display: block;
    }
</style>

<nav class="navbar">
    <div class="navbar_left">
        <a href="<?= $path ?>" class="navbar_link_img instrument-sans-semibold" id="navbar_logo">
            <img src="<?= $path ?>public/assets/logo_ligth.png" class="navbar_logo" alt="Biblioteca Scrum">
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
        <?php if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) { ?>
            <a href="#"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="navbar_link instrument-sans-semibold">
                Logout
            </a>

            <form id="logout-form" action="<?= $path ?>logout" method="post" style="display: none;">
                <input type="hidden" name="logout" value="1">
            </form>
        <?php }?>
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
                
                <div class="dropdown">
                    <div id="navbar_pfp" onclick="toggleDropdown()">
                        <img src="<?= $pfpPath ?>" alt="pfp" class="navbar_pfp">
                    </div>

                    <div id="myDropdown" class="dropdown-content">
                        <a href="./profilo">Profilo</a>
                        
                        <?php if (checkAccess('amministratore') || checkAccess('bibliotecario')) { ?>
                            <a href="<?= $path ?>dashboard">Dashboard</a>
                        <?php } ?>

                        <form action="" method="post">
                            <input type="hidden" name="logout" value="1">
                            <button type="submit">Logout</button>
                        </form>
                    </div>
                </div>

            <?php } else { ?>
                <a href="./login" class="navbar_link instrument-sans-semibold text_underline">Accedi</a>
            <?php } ?>

        </div>
    </div>
</nav>

<script>
    function toggleDropdown() {
        document.getElementById("myDropdown").classList.toggle("show");
    }

    window.onclick = function(event) {
        if (!event.target.matches('.navbar_pfp')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
</script>