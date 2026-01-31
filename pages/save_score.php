<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusione DB
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} elseif (file_exists('../db_config.php')) {
    require_once '../db_config.php';
}

if (isset($_SESSION['logged']) && $_SESSION['logged'] === true && isset($_POST['tempo'])) {
    
    // Adatta 'codice_utente' in base a come lo salvi nel login ($codice nel tuo index)
    $codice = $_SESSION['codice_utente'] ?? $_SESSION['codice_alfanumerico'] ?? null;
    $tempo = intval($_POST['tempo']);

    if ($codice && isset($pdo)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO classifica (codice_alfanumerico, tempo, data) VALUES (:code, :time, NOW())");
            $stmt->bindParam(':code', $codice);
            $stmt->bindParam(':time', $tempo);
            $stmt->execute();
            echo "success";
        } catch (PDOException $e) {
            echo "error";
        }
    }
}
?>