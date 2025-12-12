<?php
session_start();
require_once 'db_config.php'; 
// Includo la TUA libreria (controlla che il percorso sia giusto)
require_once __DIR__ . '/../src/includes/codiceFiscaleMethods.php'; 

// --- 1. GESTIONE VARIABILI PER L'HTML (FIX WARNING) ---
// Logica: se nell'URL c'è ?con_codice=1 allora mostriamo il campo CF, altrimenti il calcolatore
$registratiConCodice = isset($_GET['con_codice']); 
$tipologia = $registratiConCodice ? 'manuale' : 'automatico'; // Serve al tuo HTML
$error_msg = "";
$success_msg = "";

// Funzione ID (necessaria per la tua tabella)
function genID($l=6) { return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,$l); }

// --- 2. GESTIONE REGISTRAZIONE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Dati comuni
    $nome     = $_POST['nome'] ?? '';
    $cognome  = $_POST['cognome'] ?? '';
    $username = $_POST['username'] ?? '';
    $email    = $_POST['email'] ?? '';
    $pass     = $_POST['password'] ?? '';
    
    // Dati per calcolo CF
    $dataNascita  = $_POST['data_nascita'] ?? '';
    $sesso        = $_POST['sesso'] ?? '';
    $codiceComune = $_POST['codice_comune'] ?? ''; // Verifica il name nel tuo HTML
    
    // CF inserito a mano (se presente)
    $cf_manuale   = $_POST['codice_fiscale'] ?? '';

    if (isset($pdo)) {
        try {
            $cf_finale = "";

            // LOGICA DI SCELTA: CALCOLATO O MANUALE?
            if (!empty($cf_manuale)) {
                // Se l'utente ha scritto il CF a mano, usiamo quello
                $cf_finale = strtoupper($cf_manuale);
            } else {
                // Altrimenti usiamo la TUA funzione
                // Assicurati che i campi data/sesso/comune non siano vuoti
                if(!empty($dataNascita) && !empty($sesso) && !empty($codiceComune)) {
                    $cf_finale = generateCodiceFiscale($nome, $cognome, $dataNascita, $sesso, $codiceComune);
                } else {
                    throw new Exception("Mancano i dati per calcolare il Codice Fiscale.");
                }
            }

            // Controllo lunghezza CF
            if (strlen($cf_finale) !== 16) {
                throw new Exception("Il Codice Fiscale generato o inserito non è valido (lunghezza errata).");
            }

            // Preparazione dati DB
            $id = genID();
            $pass_hash = hash('sha256', $pass); // SHA256 per compatibilità col tuo login

            $sql = "INSERT INTO utenti 
                    (codice_alfanumerico, username, nome, cognome, email, codice_fiscale, password_hash, email_confermata, data_creazione) 
                    VALUES (:id, :user, :nome, :cogn, :email, :cf, :pass, 0, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id'    => $id,
                ':user'  => $username,
                ':nome'  => $nome,
                ':cogn'  => $cognome,
                ':email' => $email,
                ':cf'    => $cf_finale,
                ':pass'  => $pass_hash
            ]);

            $success_msg = "Registrato! CF: " . $cf_finale;
            // header("Location: /login"); exit; 

        } catch (Exception $e) { // Cattura sia PDOException che Exception generiche
            $error_msg = "Errore: " . $e->getMessage();
        } catch (TypeError $e) {
            $error_msg = "Errore dati funzione CF: " . $e->getMessage();
        }
    } else {
        $error_msg = "Errore connessione DB.";
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

    <?php require_once './src/includes/header.php'; ?>
    <?php require_once './src/includes/navbar.php'; ?>

    <div class="container">

        <?php if (!empty($error_msg)): ?>
            <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <h2>Registrati<?php echo $tipologia ?></h2>
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
                <label for="comune_nascita">Comune di Nascita:</label>
                <input placeholder="Comune di Nascita" required type="text" id="comune_nascita" name="comune_nascita">
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
            <input placeholder="Password" type="submit" value="Registrami">
        </form>
        <?php if ($registratiConCodice) { ?>
            <a href="#" onclick='redirectConCodice(false)'>Non hai il codice fiscale?</a>
        <?php } else { ?>
            <a href="#" onclick='redirectConCodice(true)'>Hai il codice fiscale?</a>
        <?php } ?>

    </div>

    <?php require_once "./src/includes/footer.php" ?>

    <script>
        const redirectConCodice = (conCodice) => {
            const virtual_form = document.createElement("form");
            virtual_form.style.display = "none"
            virtual_form.method = "POST";
            virtual_form.action = "./signup"
            const decision = document.createElement("input");
            decision.name = "conCodiceFiscale";
            decision.type = "hidden";
            decision.value = conCodice;
            virtual_form.appendChild(decision)
            document.body.appendChild(virtual_form);
            virtual_form.submit();
        }
    </script>
</body>

</html>