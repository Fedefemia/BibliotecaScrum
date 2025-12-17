<?php
require_once 'db_config.php';

echo '<!DOCTYPE html><html><body>';

if (isset($_GET['token']) === isset($_GET['pswreset'])) {
    echo "<h1>C'è stato un problema nel recupero del token</h1>";
    exit;
}

$token = empty($_GET["token"]) ? (empty($_GET["pswreset"]) ? '' : $_GET["pswreset"]) : $_GET["token"];

$stmt = $pdo->prepare("SELECT codice_alfanumerico, created_at FROM tokenemail WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "<h1>Link non valido o già usato</h1>";
    exit;
}

// controllo scadenza (24 ore di default)
$now = new DateTime("now", new DateTimeZone("UTC"));
$created = new DateTime($row['created_at'], new DateTimeZone("UTC"));
$created->modify('+24 hours');

if ($now > $created) {
    // token scaduto
    $del = $pdo->prepare("DELETE FROM tokenemail WHERE token = ?");
    $del->execute([$token]);

    echo "<h1>Il token è scaduto, richiedi un nuovo link</h1>";
    exit;
}


if (empty($_GET["pswreset"])) {
    // aggiorno utente
    $stmt = $pdo->prepare("UPDATE utenti SET email_confermata = 1 WHERE codice_alfanumerico = ?");
    $stmt->execute([$row['codice_alfanumerico']]);

    echo "<h1>Email confermata!</h1>";
    echo "<p>Ora puoi accedere.</p>";
    echo '<a href="/login">Login</a>';

    echo "</body></html>";

    // elimino token usato
    $stmt = $pdo->prepare("DELETE FROM tokenemail WHERE token = ?");
    $stmt->execute([$token]);
    exit;
} else {

    // SE ARRIVA IL FORM
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (empty($_POST['password'])) {
            echo "<h1>Password mancante</h1>";
            exit;
        }

        $newPassword = $_POST['password'];
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        // RESET PASSWORD
        $stmt = $pdo->prepare("
            UPDATE utenti 
            SET password_hash = ? 
            WHERE codice_alfanumerico = ?
        ");
        $stmt->execute([$hash, $row['codice_alfanumerico']]);

        // elimino token usato
        $stmt = $pdo->prepare("DELETE FROM tokenemail WHERE token = ?");
        $stmt->execute([$token]);

        echo "<h1>Password aggiornata</h1>";
        echo "<p>Ora puoi accedere.</p>";
        echo '<a href="/login">Login</a>';
        echo "</body></html>";
        exit;
    }

    // FORM PASSWORD RESET
    echo '<h1>Reimposta password</h1>';
    echo '
        <form method="POST">
            <input 
                type="password" 
                name="password" 
                placeholder="Nuova password" 
                required
            >
            <button type="submit">Reset password</button>
        </form>
    ';

    echo "</body></html>";
    exit;
}