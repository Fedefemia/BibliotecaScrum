<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAccess($value) {
    if (isset($_SESSION['ruoloMaggiore']) && $_SESSION['ruoloMaggiore'] === $value) {
        return true;
    }
    return false;
}
