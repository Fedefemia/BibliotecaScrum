<?php
session_start();
require_once 'db_config.php';
require_once './src/includes/codiceFiscaleMethods.php';
require_once './phpmailer.php';

$registratiConCodice = isset($_GET['mode']) && $_GET['mode'] === 'manuale';
$tipologia = $registratiConCodice ? 'manuale' : 'automatico';

$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome          = trim($_POST['nome'] ?? '');
    $cognome       = trim($_POST['cognome'] ?? '');
    $username      = trim($_POST['username'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $password      = $_POST['password'] ?? '';
    $data          = $_POST['data_nascita'] ?? '';
    $sesso         = $_POST['sesso'] ?? '';
    $codice_comune = strtoupper(trim($_POST['codice_comune'] ?? ''));
    $cf_input      = strtoupper(trim($_POST['codice_fiscale'] ?? ''));

    if (!isset($pdo)) {
        $error_msg = "Errore connessione database.";
    } else {
        try {
            if (!$username || !$nome || !$cognome || !$email || !$password) {
                throw new Exception("Compila tutti i campi obbligatori.");
            }

            // Controllo duplicati
            $chk = $pdo->prepare("SELECT 1 FROM utenti WHERE username = ? OR email = ? LIMIT 1");
            $chk->execute([$username, $email]);
            if ($chk->fetch()) throw new Exception("Username o email già in uso.");

            // CODICE FISCALE
            if (!empty($cf_input)) {
                $cf_finale = $cf_input;
            } else {
                if (strlen($codice_comune) !== 4) throw new Exception("Il codice catastale deve avere 4 caratteri.");
                if ($nome && $cognome && $data && $sesso && $codice_comune) {
                    $cf_finale = generateCodiceFiscale($nome, $cognome, $data, $sesso, $codice_comune);
                    if (!$cf_finale) throw new Exception("Errore nel calcolo del codice fiscale.");
                } else {
                    throw new Exception("Compila correttamente Data, Sesso e Codice Comune per calcolare il CF.");
                }
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // ======================
            // CREAZIONE UTENTE TRAMITE PROCEDURA
            // ======================
            $stmt = $pdo->prepare("CALL sp_crea_utente_alfanumerico(?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $nome, $cognome, $cf_finale, $email, $password_hash]);

            // Recupero id generato
            $nuovo_id = null;
            do {
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($res && isset($res['nuovo_id'])) $nuovo_id = $res['nuovo_id'];
            } while ($stmt->nextRowset());
            $stmt->closeCursor();

            if (!$nuovo_id) throw new Exception("Errore nella creazione dell'utente.");

            // ======================
            // TOKEN EMAIL
            // ======================
            $token = bin2hex(random_bytes(32));
            $ins = $pdo->prepare("INSERT INTO tokenemail (token, codice_alfanumerico) VALUES (?, ?)");
            $ins->execute([$token, $nuovo_id]);

            // ======================
            // INVIO EMAIL
            // ======================
            $baseUrl = 'https://unexploratory-franchesca-lipochromic.ngrok-free.dev/verifica';
            $verifyLink = $baseUrl . '?token=' . urlencode($token);

            $mail = getMailer();
            $mail->addAddress($email, $nome . ' ' . $cognome);
            $mail->isHTML(true);
            $mail->Subject = 'Conferma la tua email';
            $mail->Body = $mail->Body = "<p>Ciao " . htmlspecialchars($row['nome']) . ",</p>
                               <p>Clicca questo link per confermare la tua email:</p>
                               <p><a href=\"" . htmlspecialchars($verifyLink) . "\">Conferma email</a></p>
                               <br>
                               <p>Inviato da: Biblioteca Scrum Itis Rossi</p>
                               <p><a href='https://unexploratory-franchesca-lipochromic.ngrok-free.dev/verifica'>Biblioteca Itis Rossi</a></p>";
            $mail->send();

            $success_msg = "Registrazione riuscita! Ti abbiamo inviato una mail di conferma.";

        } catch (Exception $e) {
            $error_msg = "Errore: " . $e->getMessage();
        } catch (PDOException $e) {
            $error_msg = "Errore Database: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrazione</title>
</head>
<body>

<?php include './src/includes/header.php'; ?>
<?php include './src/includes/navbar.php'; ?>

<div class="container">
    <h2>Registrati <?php echo $tipologia ?></h2>

    <?php if (!empty($error_msg)): ?>
        <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>
    <?php if (!empty($success_msg)): ?>
        <div class="success"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="username">Username:</label>
        <input placeholder="Username" required type="text" id="username" name="username">

        <label for="nome">Nome:</label>
        <input placeholder="Nome" required type="text" id="nome" name="nome">

        <label for="cognome">Cognome:</label>
        <input placeholder="Cognome" required type="text" id="cognome" name="cognome">

        <?php if ($registratiConCodice) { ?>
            <label for="codice_fiscale">Codice Fiscale:</label>
            <input placeholder="Codice Fiscale" required type="text" id="codice_fiscale" name="codice_fiscale">
        <?php } else { ?>
            <label for="codice_comune">Codice/Comune di Nascita (codice catastale):</label>
            <input placeholder="Codice Comune" required type="text" id="codice_comune" name="codice_comune">

            <label for="data_nascita">Data di Nascita:</label>
            <input placeholder="Data di Nascita" required type="date" id="data_nascita" name="data_nascita">

            <label for="sesso">Sesso:</label>
            <select required name="sesso" id="sesso">
                <option value="">--Sesso--</option>
                <optgroup label="Preferenze">
                    <option value="M">Maschio</option>
                    <option value="F">Femmina</option>
                </optgroup>
            </select>
        <?php } ?>

        <label for="email">Email:</label>
        <input placeholder="Email" required type="email" id="email" name="email">

        <label for="password">Password:</label>
        <input required type="password" id="password" name="password">

        <input type="submit" value="Registrami">
    </form>

    <?php if ($registratiConCodice) { ?>
        <a href="?">Non hai il codice fiscale?</a>
    <?php } else { ?>
        <a href="?mode=manuale">Hai il codice fiscale?</a>
    <?php } ?>
</div>

<?php include './src/includes/footer.php'; ?>
</body>
</html>
