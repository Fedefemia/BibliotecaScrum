<?php
session_start();
require_once 'db_config.php';

// ======================
// INCLUSIONE LIBRERIA CF
// ======================
$cfIncluded = false;
$possible = [
    __DIR__ . '/src/includes/codicefiscalemethods.php',
    __DIR__ . '/src/includes/codiceFiscaleMethods.php',
    __DIR__ . '/../src/includes/codicefiscalemethods.php',
    __DIR__ . '/../src/includes/codiceFiscaleMethods.php',
];
foreach ($possible as $p) {
    if (file_exists($p)) {
        require_once $p;
        $cfIncluded = true;
        break;
    }
}

require_once './phpmailer.php';

// modalità manuale se ?mode=manuale
$registratiConCodice = isset($_GET['mode']) && $_GET['mode'] === 'manuale';
$tipologia = $registratiConCodice ? 'manuale' : 'automatico';

$error_msg = "";
$success_msg = "";

// Funzione ID casuale per la tua tabella
function genID($l=6) { 
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,$l); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ======================
    // RACCOLTA DATI INPUT
    // ======================
    $nome     = trim($_POST['nome'] ?? '');
    $cognome  = trim($_POST['cognome'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $data     = $_POST['data_nascita'] ?? '';
    $sesso    = $_POST['sesso'] ?? '';
    $codice_comune = trim($_POST['codice_comune'] ?? '');
    $cf_input = trim($_POST['codice_fiscale'] ?? '');

    if (!isset($pdo)) {
        $error_msg = "Errore connessione database.";
    } else {
        try {

            // ======================
            // VALIDAZIONI
            // ======================
            if (!$username || !$nome || !$cognome || !$email || !$password) {
                throw new Exception("Compila tutti i campi obbligatori.");
            }

            // controllo duplicate username/email
            $chk = $pdo->prepare("SELECT 1 FROM utenti WHERE username = ? OR email = ? LIMIT 1");
            $chk->execute([$username, $email]);
            if ($chk->fetch()) {
                throw new Exception("Username o email già in uso.");
            }

            // ======================
            // CODICE FISCALE
            // ======================
            if (!empty($cf_input)) {
                $cf_finale = strtoupper($cf_input);
            } else {

                if (!$cfIncluded) {
                    throw new Exception("Libreria Codice Fiscale non trovata sul server.");
                }

                // FIX codice catastale
                $codice_comune = strtoupper(trim($codice_comune));
                if (strlen($codice_comune) !== 4) {
                    throw new Exception("Il codice catastale deve avere 4 caratteri.");
                }

                if ($nome && $cognome && $data && $sesso && $codice_comune) {
                    $cf_finale = generateCodiceFiscale($nome, $cognome, $data, $sesso, $codice_comune);
                    if (!$cf_finale) {
                        throw new Exception("Errore nel calcolo del codice fiscale.");
                    }
                } else {
                    throw new Exception("Compila correttamente Data, Sesso e Codice Comune per calcolare il CF.");
                }
            }

            // ======================
            // CREAZIONE UTENTE
            // ======================
            $id = genID();
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO utenti 
                (codice_alfanumerico, username, nome, cognome, email, codice_fiscale, password_hash, email_confermata, data_creazione) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");

            $stmt->execute([
                $id, $username, $nome, $cognome, $email, $cf_finale, $hash
            ]);

            // ======================
            // TOKEN EMAIL
            // ======================
            // NOTA IMPORTANTE:
            // La tua tabella tokenemail NON ha il campo scade_il
            // Quindi la tua INSERT precedente falliva.
            // ======================

            $token = bin2hex(random_bytes(32));

            $ins = $pdo->prepare("INSERT INTO tokenemail (token, codice_alfanumerico) 
                                  VALUES (?, ?)");
            $ins->execute([$token, $id]);

            // ======================
            // INVIO EMAIL
            // ======================
            $baseUrl = 'https://unexploratory-franchesca-lipochromic.ngrok-free.dev/verifica';
            $verifyLink = $baseUrl . '?token=' . urlencode($token);

            $mail = getMailer();
            $mail->addAddress($email, $nome . ' ' . $cognome);
            $mail->isHTML(true);
            $mail->Subject = 'Conferma la tua email';
            $mail->Body    = "<p>Ciao " . htmlspecialchars($nome) . ",</p>
                              <p>Clicca questo link per confermare la tua email:</p>
                              <p><a href=\"" . htmlspecialchars($verifyLink) . "\">Conferma email</a></p>";

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

<?php if (!empty($error_msg)): ?>
    <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="success"><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>

<h2>Registrati <?php echo $tipologia ?></h2>
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

</body>
</html>
