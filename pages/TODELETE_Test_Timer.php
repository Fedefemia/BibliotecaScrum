<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_config.php';

// ---------------- HTML HEADER ----------------
$title = "Book Game";
$path = "./";
$page_css = "./public/css/style_game.css";
require './src/includes/header.php';
require './src/includes/navbar.php';
?>

<div class="timer_con young-serif-regular">
    <div class="timer_pill">
        <h3><span id="seconds">00</span>:<span id="tens">00</span></h3>
    </div>
</div>

<script src="./public/scripts/game_timer.js"></script>