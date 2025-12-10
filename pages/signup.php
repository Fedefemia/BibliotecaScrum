<?php
session_start();
require_once "utility/codiceFiscaleMethods.php";
require_once 'db_config.php';

// Redirect se già loggato
if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) {
    header("Location: /");
    exit;
}

$registratiConCodice = $_GET['conCodiceFIscale'] == true ?? false;
$tipologia = $registratiConCodice ? " con Codice Fiscale" : "";
$status = '';

// LOGICA DI SIGNUP

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $daCodiceFiscale = boolval($_POST['daCodiceFiscale']) ?? false;

    if(!$daCodiceFiscale) {
        $nome = $_POST['nome'] ?? '';
        $cognome = $_POST['cognome'] ?? '';
        $data_nascita = $_POST['data_nascita'] ?? '';
        $comune_nascita = $_POST['data_nascita'] ?? '';
        $sesso = $_POST['sesso'] ?? '';
        if($nome.$cognome.$data_nascita.$comune_nascita === '') $status = "Dati inseriti non validi";
    }
    else {
        $datiDaCodice = extractFromCodiceFiscale($_POST['sesso']);
        if(empty($datiDaCodice)) {
            $status = "Codice Fiscale non definito";
        } else {
            $nome = $_POST['nome'] ?? '';
            $cognome = $_POST['cognome'] ?? '';
            $data_nascita = $daCodiceFiscale['data_nascita'] ?? '';
            $comune_nascita = $daCodiceFiscale['comune_nascita'] ?? '';
            $sesso = $daCodiceFiscale['sesso'] ?? '';
            $codice_fiscale = $datiDaCodice['codice_fiscale'] ?? '';
        }
    }

    //Inserimento nel database (query da modificare se necessario)
    if($status != '') {
        $insert_string = "INSERT INTO users (nome, cognome, comune_nascita, data_nascita, sesso, codice_fiscale,email, password) VALUES (:nome,:cognome,:comune,:data,:sesso,:codice_fiscale,:email, :password)";
        $stmt = $pdo->prepare($insert_string);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":conome", $cognome);
        $stmt->bindParam(":data", $data_nascita);
        $stmt->bindParam(":comune", $comune_nascita);
        $stmt->bindParam(":sesso", $sesso);
        $stmt->bindParam(":codice_fiscale", $codice_fiscale);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);
        $result = $stmt->execute();
        if($result) header("Location: /login");
        else $status = "Errore nell'inserimento dell'utente";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>
</head>
<body>

<?php include 'navbar.php'; ?>

<div style="padding: 20px;">
    <?php if ($status) : echo $status; ?>
    <h2>Registrati<?= $tipologia ?></h2>
    <form method="post">
        <label for="nome">Nome:</label>
        <input required type="text" id="nome" name="nome">
        <label for="cognome">Cognome:</label>
        <input required type="text" id="cognome" name="cognome">
        <?php if($registratiConCodice) { ?>
            <label for="codice_fiscale">Codice Fiscale:</label>
            <input required type="text" id="codice_fiscale" name="codice_fiscale">
            <label for="email">Email:</label>
            <input required type="email" id="email" name="email">
            <label for="password">Password:</label>
            <input required type="password" id="password" name="password">
        <?php } else { ?>
            <label for="comune_nascita">Comune di Nascita:</label>
            <input required type="text" id="comune_nascita" name="comune_nascita">
            <label for="data_nascita">Data di Nascita:</label>
            <input required type="date" id="data_nascita" name="data_nascita">
            <label for="sesso">Sesso:</label>
            <select required name="sesso" id="sesso">
                <option value="">--Seleziona--</option>
                <optgroup label="Preferenze">
                    <option value="M">Maschio</option>
                    <option value="N">Feminna</option>
                    <option value="PND">Preferisco non dirlo</option>
                </optgroup>
            </select>
            <label for="email">Email:</label>
            <input required type="email" id="email" name="email">
            <label for="password">Password:</label>
            <input required type="password" id="password" name="password">
        <?php } ?>
        <input required type="submit" value="Registrami">
    </form>

    <?php if($registratiConCodice) { ?>
        <a href='/signup/?conCodiceFiscale=true'>Non hai il codice fiscale?</a>
    <?php } else {?>
        <a href='/signup/?conCodiceFiscale=false'>Hai il codice fiscale?</a>
    <?php } ?>
</div>

</body>
</html>