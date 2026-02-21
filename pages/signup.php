<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';
require_once './src/includes/codiceFiscaleMethods.php';
require_once './phpmailer.php';

// --- CARICAMENTO COMUNI DAL FILE JSON ---
$pathComuni = './src/comuni.json';
$listaComuni = [];
if (file_exists($pathComuni)) {
    $jsonContent = file_get_contents($pathComuni);
    $listaComuni = json_decode($jsonContent, true);
    // Ordina alfabeticamente per nome per comodità nella datalist
    usort($listaComuni, function($a, $b) {
        return strcmp($a['nome'], $b['nome']);
    });
}
// ----------------------------------------

$registratiConCodice = isset($_GET['mode']) && $_GET['mode'] === 'manuale';
$tipologia = $registratiConCodice ? 'manuale' : 'automatico';

$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $data = $_POST['data_nascita'] ?? '';
    $sesso = $_POST['sesso'] ?? '';
    // Qui riceviamo il CODICE dal campo hidden, non il nome
    $codice_comune = strtoupper(trim($_POST['comune_nascita'] ?? '')); 
    $cf_input = strtoupper(trim($_POST['codice_fiscale'] ?? ''));

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
            if ($chk->fetch())
                throw new Exception("Username o email già in uso.");

            // CODICE FISCALE
            if (!empty($cf_input)) {
                $cf_finale = $cf_input;
            } else {
                if (strlen($codice_comune) !== 4)
                    throw new Exception("Seleziona un comune valido dalla lista per calcolare il codice catastale.");
                
                if ($nome && $cognome && $data && $sesso && $codice_comune) {
                    $cf_finale = generateCodiceFiscale($nome, $cognome, $data, $sesso, $codice_comune);
                    if (!$cf_finale)
                        throw new Exception("Errore nel calcolo del codice fiscale.");
                } else {
                    throw new Exception("Compila correttamente Data, Sesso e Comune per calcolare il CF.");
                }
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // CREAZIONE UTENTE TRAMITE PROCEDURA
            $stmt = $pdo->prepare("CALL sp_crea_utente_alfanumerico(?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $nome, $cognome, $cf_finale, $email, $password_hash]);

            // Recupero id generato
            $nuovo_id = null;
            do {
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($res && isset($res['nuovo_id']))
                    $nuovo_id = $res['nuovo_id'];
            } while ($stmt->nextRowset());
            $stmt->closeCursor();

            $stmtRuolo = $pdo->prepare(
                "INSERT INTO ruoli (codice_alfanumerico, studente) VALUES (?, 1)"
            );
            $stmtRuolo->execute([$nuovo_id]);

            if (!$nuovo_id)
                throw new Exception("Errore nella creazione dell'utente.");

            // Assign a random default profile picture
            $defaultPfps = glob('src/DefaultPfp/*.png');
            if (!empty($defaultPfps)) {
                $randomPfp = $defaultPfps[array_rand($defaultPfps)];
                $pfpDir = 'public/pfp';
                if (!file_exists($pfpDir)) {
                    mkdir($pfpDir, 0755, true);
                }
                $newPfpPath = $pfpDir . '/' . $nuovo_id . '.png';
                copy($randomPfp, $newPfpPath);
            }

            // TOKEN EMAIL
            $token = bin2hex(random_bytes(32));
            $ins = $pdo->prepare("INSERT INTO tokenemail (token, codice_alfanumerico) VALUES (?, ?)");
            $ins->execute([$token, $nuovo_id]);

            // INVIO EMAIL
            $baseUrl = 'https://overgenially-unappareled-ross.ngrok-free.dev/verifica';
            $verifyLink = $baseUrl . '?token=' . urlencode($token);

            $mail = getMailer();
            $mail->addAddress($email, $nome . ' ' . $cognome);
            $mail->isHTML(true);
            $mail->Subject = 'Conferma la tua email';
            $mail->Body = "<p>Ciao " . htmlspecialchars($nome) . ",</p>
                           <p>Clicca questo link per confermare la tua email:</p>
                           <p><a href=\"" . htmlspecialchars($verifyLink) . "\">Conferma email</a></p>
                           <br>
                           <p>Inviato da: Biblioteca Scrum Itis Rossi</p>
                           <p><a href='https://unexploratory-franchesca-lipochromic.ngrok-free.dev/'>Biblioteca Itis Rossi</a></p>";
            $mail->send();

            $success_msg = "Registrazione riuscita! Ti abbiamo inviato una mail di conferma.";

        } catch (Exception $e) {
            $error_msg = "Errore: " . $e->getMessage();
        } catch (PDOException $e) {
            $error_msg = "Errore Database: " . $e->getMessage();
        }
    }
}

$title = "Registrati";
$page_css = "./public/css/style_forms.css";
?>
<?php include './src/includes/header.php'; ?>

<div class="form_container_2">

    <h2 class="form_title_2">Registrati <?php echo $tipologia ?></h2>

    <?php if (!empty($error_msg)): ?>
        <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>
    <?php if (!empty($success_msg)): ?>
        <div class="success"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <form method="post">

        <label class="form_2_label" for="username">Username:</label>
        <input class="form_2_input_string" placeholder="Username" required type="text" id="username" name="username">

        <div class="form_row">
            <div>
                <label class="form_2_label" for="nome">Nome:</label>
                <input class="form_2_input_string" placeholder="Nome" required type="text" id="nome" name="nome">
            </div>
            <div>
                <label class="form_2_label" for="cognome">Cognome:</label>
                <input class="form_2_input_string" placeholder="Cognome" required type="text" id="cognome" name="cognome">
            </div>
        </div>

        <hr class="form_separator">

        <?php if ($registratiConCodice) { ?>
            <label class="form_2_label" for="codice_fiscale">Codice Fiscale:</label>
            <input class="form_2_input_string" placeholder="Codice Fiscale" required type="text" id="codice_fiscale" name="codice_fiscale">
            <a href="#" onclick='redirectConCodice(false)'>Non hai il codice fiscale?</a>
        <?php } else { ?>
            <div class="form_row">
                <div>
                    <label class="form_2_label" for="input_comune_visuale">Comune di Nascita:</label>
                    
                    <input class="form_2_input_string" 
                           list="lista_comuni_data" 
                           id="input_comune_visuale" 
                           placeholder="Cerca comune..." 
                           required 
                           autocomplete="off"
                           onchange="aggiornaCodiceComune(this)">
                    
                    <datalist id="lista_comuni_data">
                        <?php foreach ($listaComuni as $comune): ?>
                            <option value="<?php echo htmlspecialchars($comune['nome']); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>

                    <input type="hidden" id="comune_nascita" name="comune_nascita">
                </div>
                <div>
                    <label class="form_2_label" for="data_nascita">Data di Nascita:</label>
                    <input class="form_2_input_date" placeholder="Data di Nascita" required type="date" id="data_nascita" name="data_nascita">
                </div>
            </div>

            <label class="form_2_label" for="sesso">Sesso:</label>
            <select class="form_2_select" required name="sesso" id="sesso">
                <option value="">--Sesso--</option>
                <optgroup label="Preferenze">
                    <option value="M">Maschio</option>
                    <option value="F">Femmina</option>
                </optgroup>
            </select>
            <a href="#" onclick='redirectConCodice(true)'>Hai il codice fiscale?</a>
        <?php } ?>

        <hr class="form_separator">
        <label class="form_2_label" for="email">Email:</label>
        <input class="form_2_input_string" placeholder="Email" required type="email" id="email" name="email">

        <label class="form_2_label" for="password">Password:</label>
        <input class="form_2_input_string" required type="password" id="password" name="password">

        <input class="form_2_btn_submit" type="submit" value="Registrami">
    </form>

    <a href="./login">Hai già un account? Accedi</a>

</div>

</div>
</body>
</html>

<script>
// Passiamo l'array PHP a JavaScript
const comuniData = <?php echo json_encode($listaComuni); ?>;

function redirectConCodice(conCodice) {
    if (conCodice) {
        window.location.href = '?mode=manuale';
    } else {
        window.location.href = '?';
    }
}

function aggiornaCodiceComune(inputElement) {
    const nomeSelezionato = inputElement.value;
    const hiddenInput = document.getElementById('comune_nascita');
    
    // Cerca il comune nell'array
    const comuneTrovato = comuniData.find(c => c.nome.toLowerCase() === nomeSelezionato.toLowerCase());
    
    if (comuneTrovato) {
        // Se trovato, imposta il codice nel campo nascosto
        hiddenInput.value = comuneTrovato.codice;
        // Opzionale: feedback visivo o log
        // console.log("Codice impostato: " + comuneTrovato.codice);
    } else {
        // Se non trovato (utente ha scritto un nome non valido), resetta il codice
        hiddenInput.value = "";
    }
}
</script>