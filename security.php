<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {

    $_SESSION['status'] = "Utente non loggato per questa pagina";
    
    header("Location: ./login");
    exit;
}
?>