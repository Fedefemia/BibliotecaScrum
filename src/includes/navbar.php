<?php
require_once 'security.php';
require_once 'db_config.php';

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

$nome_visualizzato = 'Utente';
if (isset($_SESSION['nome_utente'])) {
    $nome_visualizzato = $_SESSION['nome_utente'];
}

if(isset($_POST["logout"])){
    session_unset();
    session_destroy();
    header("Location: login");
    exit;
}

// --- LOGICA GESTIONE NOTIFICHE (POST) ---
if (isset($_SESSION['codice_utente']) && isset($pdo) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. SEGNA TUTTE COME LETTE
    if (isset($_POST['azione']) && $_POST['azione'] === 'segna_tutte') {
        try {
            $stmt_all = $pdo->prepare("UPDATE notifiche SET visualizzato = 1 WHERE codice_alfanumerico = ?");
            $stmt_all->execute([$_SESSION['codice_utente']]);
            header("Refresh:0"); // Ricarica pagina
        } catch (PDOException $e) {
            error_log("Errore mark all: " . $e->getMessage());
        }
    }

    // 2. SEGNA SINGOLA COME LETTA (Pulsante X)
    if (isset($_POST['azione']) && $_POST['azione'] === 'segna_singola' && isset($_POST['id_notifica'])) {
        try {
            $stmt_one = $pdo->prepare("UPDATE notifiche SET visualizzato = 1 WHERE id_notifica = ? AND codice_alfanumerico = ?");
            $stmt_one->execute([$_POST['id_notifica'], $_SESSION['codice_utente']]);
            header("Refresh:0"); // Ricarica pagina
        } catch (PDOException $e) {
            error_log("Errore mark one: " . $e->getMessage());
        }
    }
}

// --- LOGICA RECUPERO NOTIFICHE (SOLO NON VISUALIZZATE) ---
$lista_notifiche = [];

if (isset($_SESSION['codice_utente']) && isset($pdo)) {
    try {
        // Query: prende SOLO quelle con visualizzato = 0
        $sql_nav_notifiche = "SELECT * FROM notifiche 
                              WHERE codice_alfanumerico = ? 
                              AND visualizzato = 0
                              AND (dataora_scadenza IS NULL OR dataora_scadenza > NOW())
                              ORDER BY dataora_invio DESC LIMIT 5";
        
        $stmt_nav = $pdo->prepare($sql_nav_notifiche);
        $stmt_nav->execute([$_SESSION['codice_utente']]);
        $lista_notifiche = $stmt_nav->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Errore notifiche navbar: " . $e->getMessage());
    }
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    .navbar_icon {
        width: 24px;
        height: 24px;
        object-fit: contain;
        cursor: pointer;
        display: block;
    }

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

    .dropdown {
        position: relative;
        display: inline-block;
        margin-left: 15px; 
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        top: 55px;
        background-color: #fff; /* Sfondo bianco più pulito */
        min-width: 180px;
        box-shadow: 0px 4px 20px rgba(0,0,0,0.15); /* Ombra più morbida */
        z-index: 1000;
        border-radius: 12px; /* Arrotondamento più moderno */
        overflow: hidden;
        border: 1px solid #f0f0f0;
        /* APPLICA FONT GLOBALE AL DROPDOWN */
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    /* STILI SPECIFICI NOTIFICHE */
    .dropdown-content.notifications {
        min-width: 360px; 
        right: 0; 
    }

    /* HEADER */
    .notifica-header-title {
        display: block;
        padding: 12px 16px;
        font-size: 15px;
        font-weight: 600;
        color: #3f5135;
        background-color: #fcfbf5;
        border-bottom: 1px solid #ececec;
    }

    /* --- LAYOUT RIGA NOTIFICA (FLEX) --- */
    .notifica-row {
        display: flex;
        align-items: flex-start; 
        justify-content: space-between;
        border-bottom: 1px solid #f0f0f0;
        background-color: #fff;
        transition: background-color 0.2s;
    }
    
    .notifica-row:hover {
        background-color: #f9f9f9;
    }

    /* CONTENUTO TESTUALE (LINK) */
    .notifica-link-content {
        flex-grow: 1; 
        padding: 14px 10px 14px 16px;
        text-decoration: none;
        color: #333;
        display: block;
    }

    /* PULSANTE X (CHIUDI) */
    .form-close-notifica {
        margin: 0;
        padding: 12px 10px; 
        display: flex;
        align-items: center;
        height: 100%;
    }

    .btn-close-notifica {
        background: none;
        border: none;
        color: #aaa;
        font-size: 18px;
        cursor: pointer;
        padding: 5px;
        line-height: 1;
        transition: color 0.2s;
    }
    .btn-close-notifica:hover {
        color: #dc3545; 
    }

    /* --- FOOTER (Mostra tutte + Pulisci) --- */
    .notifica-footer {
        display: flex;
        justify-content: space-between; 
        align-items: center;
        background-color: #fafafa;
        padding: 8px 16px;
        border-top: 1px solid #ececec;
    }

    .link-mostra-tutte {
        font-weight: 600;
        color: #3f5135;
        text-decoration: none;
        font-size: 13px;
        padding: 5px 0;
    }
    .link-mostra-tutte:hover {
        text-decoration: underline;
    }

    /* PULSANTE PULISCI TUTTO */
    .btn-clean-all {
        background: none;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        color: #555;
        cursor: pointer;
        font-size: 12px;
        padding: 4px 10px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 5px;
        font-family: inherit;
        font-weight: 500;
    }
    .btn-clean-all:hover {
        background-color: #3f5135;
        color: #fff;
        border-color: #3f5135;
    }

    /* TESTO INTERNO NOTIFICA */
    .n-titolo { font-weight: 600; font-size: 14px; display: block; margin-bottom: 4px; color: #111; }
    .n-preview { font-size: 13px; color: #666; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px; line-height: 1.4; }
    .n-data { font-size: 11px; color: #999; display: block; margin-top: 6px; font-weight: 500; }

    /* UNIFORMAZIONE DROPDOWN PROFILE */
    .dropdown-content a, 
    .dropdown-content button {
        font-family: 'Inter', sans-serif;
    }
    
    /* Stili generici bottoni e link nel dropdown */
    .dropdown-content > a,
    .dropdown-content > form > button {
        display: block;
        width: 100%;
        padding: 12px 20px;
        text-align: left;
        border: none;
        background: none;
        cursor: pointer;
        font-size: 14px;
        color: #333;
        text-decoration: none;
        box-sizing: border-box;
    }
    .dropdown-content > a:hover,
    .dropdown-content > form > button:hover {
        background-color: #f5f5f5;
    }

    .show { display: block; }
</style>

<nav class="navbar">
    <div class="navbar_left">
        <a href="<?= $path ?>" class="navbar_link_img instrument-sans-semibold" id="navbar_logo">
            <img src="<?= $path ?>public/assets/logo_ligth.png" class="navbar_logo" alt="Biblioteca Scrum">
        </a>
        <div class="search_container">
            <form class="search_container" action="<?= $path ?>search" method="GET">
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
        <div class="navbar_rigth_left" style="display: flex; align-items: center;">

            <?php if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) { ?>
                <div class="dropdown">
                    <div onclick="toggleNotifiche()" style="cursor: pointer; display: flex; align-items: center; position: relative;">
                        <img src="<?= $path ?>public/assets/icon_notification.png" alt="notifica" class="navbar_icon">
                        
                        <?php if (count($lista_notifiche) > 0): ?>
                            <span style="position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; background-color: #dc3545; border-radius: 50%; border: 2px solid #fff;"></span>
                        <?php endif; ?>
                    </div>

                    <div id="dropdownNotifiche" class="dropdown-content notifications">
                        
                        <div class="notifica-header-title">Nuove Notifiche</div>

                        <?php if (count($lista_notifiche) > 0): ?>
                            <?php foreach ($lista_notifiche as $notifica): ?>
                                <div class="notifica-row">
                                    <a href="<?= $path ?>notifiche" class="notifica-link-content">
                                        <span class="n-titolo"><?= htmlspecialchars($notifica['titolo']) ?></span>
                                        <span class="n-preview"><?= htmlspecialchars($notifica['messaggio']) ?></span>
                                        <span class="n-data"><?= date('d/m H:i', strtotime($notifica['dataora_invio'])) ?></span>
                                    </a>

                                    <form action="" method="POST" class="form-close-notifica">
                                        <input type="hidden" name="azione" value="segna_singola">
                                        <input type="hidden" name="id_notifica" value="<?= $notifica['id_notifica'] ?>">
                                        <button type="submit" class="btn-close-notifica" title="Segna come letta">&times;</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding: 30px 20px; text-align: center; color: #888; font-size: 14px;">
                                Nessuna nuova notifica
                            </div>
                        <?php endif; ?>
                        
                        <div class="notifica-footer">
                            <a href="<?= $path ?>notifiche" class="link-mostra-tutte">Mostra tutte</a>
                            
                            <?php if (count($lista_notifiche) > 0): ?>
                                <form action="" method="POST" style="margin:0;">
                                    <input type="hidden" name="azione" value="segna_tutte">
                                    <button type="submit" class="btn-clean-all" title="Segna tutte come lette">
                                        &#10003; Pulisci
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php } else { ?>
                <a href="<?= $path ?>login" class="navbar_link_img instrument-sans-semibold" style="margin-right: 15px;">
                    <img src="<?= $path ?>public/assets/icon_notification.png" alt="notifica" class="navbar_icon">
                </a>
            <?php } ?>

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
                    <div id="navbar_pfp" onclick="toggleProfilo()">
                        <img src="<?= $pfpPath ?>" alt="pfp" class="navbar_pfp">
                    </div>

                    <div id="navbarDropdown" class="dropdown-content">
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
                <a href="./login" class="navbar_link instrument-sans-semibold text_underline" style="margin-left: 15px;">Accedi</a>
            <?php } ?>

        </div>
    </div>
</nav>

<script>
    function toggleNotifiche() {
        var notifDropdown = document.getElementById("dropdownNotifiche");
        var profDropdown = document.getElementById("dropdownProfilo");
        
        if (profDropdown && profDropdown.classList.contains('show')) {
            profDropdown.classList.remove('show');
        }
        if (notifDropdown) {
            notifDropdown.classList.toggle("show");
        }
    }

    function toggleProfilo() {
        var notifDropdown = document.getElementById("dropdownNotifiche");
        var profDropdown = document.getElementById("dropdownProfilo");
        
        if (notifDropdown && notifDropdown.classList.contains('show')) {
            notifDropdown.classList.remove('show');
        }
        if (profDropdown) {
            profDropdown.classList.toggle("show");
        }
    }

    window.onclick = function(event) {
        if (!event.target.closest('.dropdown') && !event.target.matches('.navbar_pfp') && !event.target.matches('.navbar_icon')) {
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