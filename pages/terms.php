<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Includiamo la configurazione
require_once 'db_config.php';

// Inizializziamo il messaggio per evitare errori "Undefined variable"
$messaggio_db = "";

// --- 1. TEST SCRITTURA (INSERT) ---
// Eseguiamo l'INSERT solo se la connessione ($pdo) esiste
if (isset($pdo)) {
    try {
        // Se l'utente è loggato, usiamo il suo nome nel DB, altrimenti "Utente Web"
        $nome_visitatore = isset($_SESSION['username']) ? $_SESSION['username'] . ' (Logged)' : 'Utente Web';

        $stmt = $pdo->prepare("INSERT INTO visitatori (nome) VALUES (:nome)");
        $stmt->execute(['nome' => $nome_visitatore]);
        $messaggio_db = "Nuovo accesso registrato nel DB!";
        $class_messaggio = "success";
    } catch (PDOException $e) {
        $messaggio_db = "Errore Scrittura: " . $e->getMessage();
        $class_messaggio = "error";
    }
} else {
    $messaggio_db = "Connessione al Database non riuscita (controlla db_config.php).";
    $class_messaggio = "error";
}


?>

<?php
// ---------------- HTML HEADER ----------------
$title = "Contatti - Biblioteca Scrum";
$path = "./";
$page_css = "./public/css/style_index.css";
require './src/includes/header.php';
require './src/includes/navbar.php';
?>
<?php require_once './src/includes/footer.php'; ?>
