<?php
require_once 'security.php';
require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- LOGICA CAMBIO BIBLIOTECA (SOLO BIBLIOTECARI) ---
if (isset($_POST['cambia_biblioteca']) && checkAccess('bibliotecario')) {
    $_SESSION['id_biblioteca_operativa'] = $_POST['id_biblioteca_selezionata'];
    header("Refresh:0");
}

// Recupero dati
$biblioteche_disponibili = [];
$id_biblio_attuale = $_SESSION['id_biblioteca_operativa'] ?? null;
$nome_biblio_attuale = "Seleziona Sede";

if (isset($_SESSION['logged']) && checkAccess('bibliotecario')) {
    try {
        $stmt = $pdo->query("SELECT id, nome FROM biblioteche ORDER BY nome");
        $biblioteche_disponibili = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$id_biblio_attuale && count($biblioteche_disponibili) > 0) {
            $id_biblio_attuale = $biblioteche_disponibili[0]['id'];
            $_SESSION['id_biblioteca_operativa'] = $id_biblio_attuale;
        }

        foreach ($biblioteche_disponibili as $b) {
            if ($b['id'] == $id_biblio_attuale) {
                $nome_biblio_attuale = $b['nome'];
                break;
            }
        }
    } catch (PDOException $e) { error_log($e->getMessage()); }
}

// --- NOTIFICHE ---
$lista_notifiche = [];
if (isset($_SESSION['codice_utente']) && isset($pdo)) {
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['azione']) && $_POST['azione'] === 'segna_tutte') {
                $pdo->prepare("UPDATE notifiche SET visualizzato = 1 WHERE codice_alfanumerico = ?")->execute([$_SESSION['codice_utente']]);
                header("Refresh:0");
            }
            if (isset($_POST['azione']) && $_POST['azione'] === 'segna_singola' && isset($_POST['id_notifica'])) {
                $pdo->prepare("UPDATE notifiche SET visualizzato = 1 WHERE id_notifica = ? AND codice_alfanumerico = ?")->execute([$_POST['id_notifica'], $_SESSION['codice_utente']]);
                header("Refresh:0");
            }
        }
        $sql_nav_notifiche = "SELECT * FROM notifiche 
                              WHERE codice_alfanumerico = ? AND visualizzato = 0
                              AND (dataora_scadenza IS NULL OR dataora_scadenza > NOW())
                              ORDER BY dataora_invio DESC LIMIT 5";
        $stmt_nav = $pdo->prepare($sql_nav_notifiche);
        $stmt_nav->execute([$_SESSION['codice_utente']]);
        $lista_notifiche = $stmt_nav->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { error_log($e->getMessage()); }
}

if(isset($_POST["logout"])){
    session_unset();
    session_destroy();
    header("Location: login");
    exit;
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

            <a href="<?= $path ?>game" class="navbar_link_img" style="margin-right: 15px;">
                <img src="<?= $path ?>public/assets/icon_joycon.png" alt="notifica" class="navbar_icon">
            </a>

            <?php if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) { ?>
                <div class="dropdown">
                    <div onclick="toggleNotifiche()">
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
                                    </a>
                                    <form action="" method="POST" class="form-close-notifica">
                                        <input type="hidden" name="azione" value="segna_singola">
                                        <input type="hidden" name="id_notifica" value="<?= $notifica['id_notifica'] ?>">
                                        <button type="submit" class="notifica_btn_close" title="Segna letta">&times;</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding: 20px; text-align: center; color: #888; font-size: 13px;">Nessuna notifica</div>
                        <?php endif; ?>
                        <div class="notifica_footer">
                            <a href="<?= $path ?>notifiche" class="link-mostra-tutte">Vedi tutte</a>
                        </div>
                    </div>
                </div>
            <?php } else { ?>
                <a href="<?= $path ?>login" class="navbar_link_img" style="margin-right: 15px;">
                    <img src="<?= $path ?>public/assets/icon_notification.png" alt="notifica" class="navbar_icon">
                </a>
            <?php } ?>

            <?php
            if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) {
                $pfpPath =  $path . 'public/pfp/' . $_SESSION['codice_utente'] . '.png';
                //if (!file_exists($pfpPath)) $pfpPath = $path . 'public/assets/base_pfp.png';
                //else $pfpPath .= '?v=' . time();
                //Buggava la pfp
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

                        <?php if (checkAccess('bibliotecario') && !empty($biblioteche_disponibili)): ?>
                            <div class="biblio-selector-container">
                                <button type="button" class="biblio-btn" onclick="toggleBiblioList(this)">
                                    <span class="biblio-text"><?= htmlspecialchars($nome_biblio_attuale) ?></span>
                                    <span class="biblio-arrow">▼</span>
                                </button>
                                
                                <div id="biblioList" class="biblio-list">
                                    <?php foreach ($biblioteche_disponibili as $biblio): ?>
                                        <form action="" method="POST" style="margin:0;">
                                            <input type="hidden" name="cambia_biblioteca" value="1">
                                            <input type="hidden" name="id_biblioteca_selezionata" value="<?= $biblio['id'] ?>">
                                            <button type="submit" class="biblio-item <?= ($id_biblio_attuale == $biblio['id']) ? 'active' : '' ?>">
                                                <?= htmlspecialchars($biblio['nome']) ?>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form action="<?= $path ?>logout" method="post">
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
    // Chiude i menu se si clicca fuori
    window.onclick = function(event) {
        if (!event.target.closest('.dropdown') && !event.target.closest('.biblio-selector-container')) {
            var dropdowns = document.getElementsByClassName("dropdown_content");
            for (var i = 0; i < dropdowns.length; i++) {
                dropdowns[i].classList.remove('show');
            }
        }
    }

    function toggleNotifiche() {
        var notif = document.getElementById("dropdownNotifiche");
        var prof = document.getElementById("dropdownProfilo");
        if (prof) prof.classList.remove('show');
        if (notif) notif.classList.toggle("show");
    }

    function toggleProfilo() {
        var notif = document.getElementById("dropdownNotifiche");
        var prof = document.getElementById("dropdownProfilo");
        if (notif) notif.classList.remove('show');
        if (prof) prof.classList.toggle("show");
    }

    function toggleBiblioList(btn) {
        var list = document.getElementById("biblioList");
        var arrow = btn.querySelector(".biblio-arrow");
        
        // Toggle classe open e rotate
        if (list.classList.contains("open")) {
            list.classList.remove("open");
            arrow.classList.remove("rotate");
        } else {
            list.classList.add("open");
            arrow.classList.add("rotate");
        }
        event.stopPropagation();
    }
</script>

<style>
    /* STILI DROPDOWN BASE */
    .show { display: block !important; }

    /* SELETTORE BIBLIOTECA */
    .biblio-selector-container {
        border-top: 1px solid #EAE3D2;
        background-color: #EAE3D2; /* Sfondo bianco come il resto del menu */
        padding: 5px 0;
        margin-top: 5px;
    }

    .biblio-btn {
        width: 100%;
        padding: 12px 16px; /* Padding standard dei link */
        text-align: left;
        background-color: transparent !important; /* TRASPARENTE DI BASE (come i link) */
        border: none;
        cursor: pointer;
        
        /* FLEXBOX BLINDATO */
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        
        font-family: 'Instrument Sans', sans-serif;
        font-size: 16px; /* Font size simile ai link */
        font-weight: 400; /* Peso normale */
        color: #000; /* Colore testo standard */
        transition: background 0.2s;
    }
    
    /* HOVER COLOR PANNA */
    .biblio-btn:hover {
        background-color: #faf7f0 !important; /* PANNA SU HOVER */
    }

    /* TESTO */
    .biblio-text {
        flex: 1; 
        min-width: 0;
        white-space: normal; 
        text-align: left;
        line-height: 1.2;
        padding-right: 10px;
        word-break: break-word;
    }

    /* FRECCIA */
    .biblio-arrow {
        flex-shrink: 0; 
        width: 12px;
        text-align: center;
        font-size: 10px;
        color: #666;
        transition: transform 0.3s ease;
        transform-origin: center;
        display: block;
    }
    .biblio-arrow.rotate {
        transform: rotate(180deg);
    }

    /* LISTA OPZIONI */
    .biblio-list {
        display: none;
        background-color: #ffffff !important;
        border: 1px solid #f0f0f0;
        border-radius: 4px;
        width: 95%; /* Leggermente più stretto per effetto 'annidato' */
        margin: 0 auto;
        max-height: 200px;
        overflow-y: auto;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); /* Ombretta interna leggera */
    }
    .biblio-list.open {
        display: block !important;
    }

    /* ITEM LISTA */
    .biblio-item {
        display: block;
        width: 100%;
        padding: 10px 15px;
        text-align: left;
        border: none;
        border-bottom: 1px solid #f9f9f9;
        background-color: white;
        cursor: pointer;
        font-size: 14px;
        color: #555;
        font-family: 'Instrument Sans', sans-serif;
    }
    .biblio-item:hover {
        background-color: #faf7f0 !important;
        color: #3f5135;
    }
    .biblio-item.active {
        font-weight: 700;
        color: #3f5135;
        background-color: #f4f1ea !important;
    }
</style>