<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'security.php';
require_once 'db_config.php';

// ---------------- HTML HEADER ----------------
$path = "./";
$title = "Dashboard";
$page_css = "./public/css/style_dashboards.css";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';

$messaggio_db = "";
?>

<?php if (checkAccess('amministratore')) { ?>

    <div class="page_contents">
        <div class="dashboard_cards_con instrument-sans-semibold text_color_dark">
            
            <a href="admin/dashboard-biblioteche" class="dashboard_card dashboard_card_1">
                <img src="<?= $path ?>/public/assets/icon.png" alt="Icon">
                <h1>Dashboard biblioteche</h1>
            </a>

            <a href="admin/dashboard-libri" class="dashboard_card dashboard_card_2">
                <img src="<?= $path ?>/public/assets/icon.png" alt="Icon">
                <h1>Dashboard libri</h1>
            </a>

            <a href="admin/dashboard-utenti" class="dashboard_card dashboard_card_3">
                <img src="<?= $path ?>/public/assets/icon.png" alt="Icon">
                <h1>Dashboard utenti</h1>
            </a>

            <a href="bibliotecario/dashboard-gestioneprestiti" class="dashboard_card dashboard_card_4">
                <img src="<?= $path ?>/public/assets/icon.png" alt="Icon">
                <h1>Gestione Prestiti</h1>
            </a>

            <a href="bibliotecario/dashboard-aggiuntaprestiti" class="dashboard_card dashboard_card_5">
                <img src="<?= $path ?>/public/assets/icon.png" alt="Icon">
                <h1>Aggiunta Prestiti</h1>
            </a>

            <a href="admin/dashboard-report" class="dashboard_card dashboard_card_4">
                <img src="<?= $path ?>/public/assets/icon.png" alt="Icon">
                <h1>Statistiche</h1>
            </a>
            
        </div>
    </div>

<?php } elseif (checkAccess('bibliotecario')) { ?>
    <div class="page_contents">
        Ciao Bibliotecario!
        <div class="page_contents">
            <div>
                <a href="bibliotecario/dashboard-gestioneprestiti">Gestione Prestiti</a>
            </div>
            <div>
                <a href="bibliotecario/dashboard-aggiuntaprestiti">Aggiunta Prestiti</a>
            </div>
        </div>
    </div>
<?php }else{header('Location: ./'); } ?>

<?php require_once './src/includes/footer.php'; ?>