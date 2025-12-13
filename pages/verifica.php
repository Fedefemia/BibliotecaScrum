<?php
require_once 'db_config.php';

echo '<!DOCTYPE html><html><body>';

if (!isset($_GET['token'])) {
    echo "<h1>C'è stato un problema nel recupero del token</h1>";
    exit;
}

$token = $_GET['token'];

// usa created_at invece di scade_il
$stmt = $pdo->prepare("SELECT codice_alfanumerico, created_at FROM tokenemail WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "<h1>Token non valido o già usato</h1>";
    exit;
}

// controllo scadenza (24 ore di default)
$now = new DateTime("now", new DateTimeZone("UTC"));
$created = new DateTime($row['created_at'], new DateTimeZone("UTC"));
$created->modify('+24 hours');

if ($now > $created) {
    // token scaduto → lo elimino
    $del = $pdo->prepare("DELETE FROM tokenemail WHERE token = ?");
    $del->execute([$token]);

    echo "<h1>Il token è scaduto, richiedi una nuova verifica</h1>";
    exit;
}

// aggiorno utente
$stmt = $pdo->prepare("UPDATE utenti SET email_confermata = 1 WHERE codice_alfanumerico = ?");
$stmt->execute([$row['codice_alfanumerico']]);

// elimino token usato
$stmt = $pdo->prepare("DELETE FROM tokenemail WHERE token = ?");
$stmt->execute([$token]);

echo "<h1>Email confermata!</h1>";
echo "<p>Ora puoi accedere.</p>";
echo '<a href="/login">Login</a>';

echo "</body></html>";
