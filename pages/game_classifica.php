<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_config.php';

// ---------------- HTML HEADER ----------------
$title = "Classifica";
$path = "./";
$isGame = true;
$page_css = "./public/css/style_game.css";
require './src/includes/header.php';
require './src/includes/navbar.php';
?>

<?php require './src/includes/footer.php'; ?>