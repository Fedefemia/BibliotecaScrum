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

            <a href="bibliotecario/dashboard-richieste" class="dashboard_card dashboard_card_1">
                <img src="<?= $path ?>/public/assets/icone_categorie/Richieste.png" alt="Icon">
                <h1>Richieste</h1>
            </a>

            <a href="bibliotecario/dashboard-aggiuntaprestiti" class="dashboard_card dashboard_card_2">
                <img src="<?= $path ?>/public/assets/icone_categorie/AggiuntaLibro.png" alt="Icon">
                <h1>Aggiunta Prestiti</h1>
            </a>

            <a href="bibliotecario/dashboard-gestioneprestiti" class="dashboard_card dashboard_card_3">
                <img src="<?= $path ?>/public/assets/icone_categorie/PilaLibri.png" alt="Icon">
                <h1>Gestione Prestiti</h1>
            </a>

            <a href="#" class="dashboard_card dashboard_card_4">
                <img src="<?= $path ?>/public/assets/icone_categorie/Multe.png" alt="Icon">
                <h1>Multe</h1>
            </a>

            <a href="admin/dashboard-biblioteche" class="dashboard_card dashboard_card_5">
                <img src="<?= $path ?>/public/assets/icone_categorie/Classico.png" alt="Icon">
                <h1>Gestione Biblioteche</h1>
            </a>

            <a href="admin/dashboard-libri" class="dashboard_card dashboard_card_6">
                <img src="<?= $path ?>/public/assets/icone_categorie/Libro.png" alt="Icon">
                <h1>Catalogo Libri</h1>
            </a>

            <a href="admin/dashboard-utenti" class="dashboard_card dashboard_card_7">
                <img src="<?= $path ?>/public/assets/icone_categorie/Utenti.png" alt="Icon">
                <h1>Dashboard Utenti</h1>
            </a>

            <a href="admin/dashboard-recensioni" class="dashboard_card dashboard_card_8">
                <img src="<?= $path ?>/public/assets/icone_categorie/Recensione.png" alt="Icon">
                <h1>Dashboard Recensioni</h1>
            </a>

            <a href="admin/dashboard-report" class="dashboard_card dashboard_card_9">
                <img src="<?= $path ?>/public/assets/icone_categorie/Statistiche.png" alt="Icon">
                <h1>Statistiche</h1>
            </a>

            <a href="#" class="dashboard_card dashboard_card_10">
                <img src="<?= $path ?>/public/assets/icone_categorie/Raccomandazione.png" alt="Icon">
                <h1>Badge</h1>
            </a>
            
        </div>
    </div>

<?php } elseif (checkAccess('bibliotecario')) { ?>
    <div class="page_contents">

        <div class="dashboard_cards_con_2 instrument-sans-semibold text_color_dark">

            <a href="bibliotecario/dashboard-gestioneprestiti" class="dashboard_card dashboard_card_2_1">
                <img src="<?= $path ?>/public/assets/icone_categorie/Libro.png" alt="Icon">
                <h1>Gestione Prestiti</h1>
            </a>

            <a href="bibliotecario/dashboard-aggiuntaprestiti" class="dashboard_card dashboard_card_2_2">
                <img src="<?= $path ?>/public/assets/icone_categorie/AggiuntaLibro.png" alt="Icon">
                <h1>Aggiunta Prestiti</h1>
            </a>

        </div>
    </div>
<?php }else{header('Location: ./'); } ?>

<?php require_once './src/includes/footer.php'; ?>