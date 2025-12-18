<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'security.php';
require_once 'db_config.php';
$messaggio_db = "";


require_once './src/includes/header.php';
require_once './src/includes/navbar.php';

if (checkAccess('amministratore')) { ?>
    <div class="page_contents">
        <div>
            <a href="admin/dashboard-biblioteche">Dashboard biblioteche</a>
        </div>
        <div>
            <a href="admin/dashboard-libri">Dashboard libri</a>
        </div>
        <div>
            <a href="admin/dashboard-utenti">Dashboard utenti</a>
        </div>

    </div>

<?php } elseif (checkAccess('bibliotecario')) { ?>
    <div class="page_contents">
        Ciao Bibliotecario!
    </div>
<?php }else{header('Location: ./'); } ?>

<?php require_once './src/includes/footer.php'; ?>