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
                <input type="text" placeholder="Cerca..." name="search"
                    class="navbar_search_input instrument-sans-semibold"
                    value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
            </form>
        </div>
    </div>
    
    <div class="navbar_rigth">
        <div class="navbar_rigth_left" style="display: flex; align-items: center;">

            <?php if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) { ?>
                <div class="dropdown">
                    <div onclick="toggleNotifiche()"">
                        <img src="<?= $path ?>public/assets/icon_notification.png" alt="notifica" class="notifica_icon">
                        
                        <?php if (count($lista_notifiche) > 0): ?>
                            <span style="position: absolute; top: -2px; right: -2px; width: 10px; height: 10px; background-color: #dc3545; border-radius: 50%; border: 2px solid #fff;"></span>
                        <?php endif; ?>
                    </div>

                    <div id="dropdownNotifiche" class="dropdown_content notifications">
                        
                        <div class="notifica_header_title">Nuove Notifiche</div>

                        <?php if (count($lista_notifiche) > 0): ?>
                            <?php foreach ($lista_notifiche as $notifica): ?>
                                <div class="notifica_row">
                                    <a href="<?= $path ?>notifiche" class="notifica-link-content">
                                        <span class="notifica_titolo"><?= htmlspecialchars($notifica['titolo']) ?></span>
                                        <span class="nnotifica_preview"><?= htmlspecialchars($notifica['messaggio']) ?></span>
                                        <span class="notifica_data"><?= date('d/m H:i', strtotime($notifica['dataora_invio'])) ?></span>
                                    </a>

                                    <form action="" method="POST" class="form-close-notifica">
                                        <input type="hidden" name="azione" value="segna_singola">
                                        <input type="hidden" name="id_notifica" value="<?= $notifica['id_notifica'] ?>">
                                        <button type="submit" class="notifica_btn_close" title="Segna come letta">&times;</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding: 30px 20px; text-align: center; color: #888; font-size: 14px;">
                                Nessuna nuova notifica
                            </div>
                        <?php endif; ?>
                        
                        <div class="notifica_footer">
                            <a href="<?= $path ?>notifiche" class="link-mostra-tutte">Mostra tutte</a>
                            
                            <?php if (count($lista_notifiche) > 0): ?>
                                <form action="" method="POST" style="margin:0;">
                                    <input type="hidden" name="azione" value="segna_tutte">
                                    <button type="submit" class="notifica_btn_clean" title="Segna tutte come lette">
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

                    <div id="dropdownProfilo" class="dropdown_content">
                        <a href="<?= $path ?>profilo">Profilo</a>
                        
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
                <a href="<?= $path ?>login" class="navbar_link instrument-sans-semibold text_underline">Accedi</a>
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

    /*
    window.onclick = function(event) {
        if (!event.target.matches('.navbar_pfp')) {
            var dropdowns = document.getElementsByClassName("dropdown_content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }*/
</script>

<style>
    .show {
        display: block;
    }
</style>