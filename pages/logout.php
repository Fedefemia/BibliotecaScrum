<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$era_loggato = isset($_SESSION['logged']) && $_SESSION['logged'] === true;
$_SESSION = [];
if (isset($_COOKIE['auth'])) {
    setcookie('auth', '', time() - 3600, '/', '', false, true);
}
if ($era_loggato) {
    $_SESSION['status'] = 'Logout riuscito';
}
session_write_close();
header("Location: ./login");
exit;
?>