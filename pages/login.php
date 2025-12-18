<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';
require_once './phpmailer.php';

if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) {
    header("Location: ./");
    exit;
}

$error_msg = ""; 
$user_input = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = trim($_POST['username'] ?? '');
    $pass_input = trim($_POST['password'] ?? '');

    if (!$user_input || !$pass_input) {
        $error_msg = "Compila tutti i campi.";
    } elseif (!isset($pdo)) {
        $error_msg = "Errore di connessione al database.";
    } else {
        try {
            // Recupero utente dal DB
            $stmt = $pdo->prepare("
            SELECT u.password_hash, u.codice_alfanumerico, u.email_confermata, u.nome, u.cognome, u.email, r.studente, r.docente, r.bibliotecario, r.amministratore
            FROM utenti u
            JOIN ruoli r on r.codice_alfanumerico = u.codice_alfanumerico
            WHERE  u.username = ? OR u.email = ? OR u.codice_fiscale = ? 
            LIMIT 1
            ");
            $stmt->execute([$user_input, $user_input, $user_input]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $error_msg = "Utente non trovato.";
            } elseif (!password_verify($pass_input, $row['password_hash'])) {
                $error_msg = "Password errata.";
            } elseif ($row['email_confermata'] != 1) {
                // Email non confermata
                $token = bin2hex(random_bytes(32));
                $ins = $pdo->prepare("INSERT INTO tokenemail (token, codice_alfanumerico) VALUES (?, ?)");
                $ins->execute([$token, $row['codice_alfanumerico']]);

                $baseUrl = 'https://unexploratory-franchesca-lipochromic.ngrok-free.dev/verifica';
                $verifyLink = $baseUrl . '?token=' . urlencode($token);

                $mail = getMailer();
                $mail->addAddress($row['email'], $row['nome'] . ' ' . $row['cognome']);
                $mail->isHTML(true);
                $mail->Subject = 'Conferma la tua email';
                $mail->Body = "<p>Ciao " . htmlspecialchars($row['nome']) . ",</p>
                               <p>Devi confermare la tua email prima di accedere. Clicca questo link per confermare:</p>
                               <p><a href=\"" . htmlspecialchars($verifyLink) . "\">Conferma email</a></p>
                               <br>
                               <p>Inviato da: Biblioteca Scrum Itis Rossi</p>
                               <p><a href='https://unexploratory-franchesca-lipochromic.ngrok-free.dev/'>Biblioteca Itis Rossi</a></p>";
                $mail->send();

                $error_msg = "Conferma l'email prima di accedere. Ti è stato inviato un nuovo codice!";
            } else {
                // Login riuscito
                session_regenerate_id(true);
                $_SESSION['logged'] = true;
                $_SESSION['codice_utente'] = $row['codice_alfanumerico'];
                $_SESSION['username'] = $user_input;
                $_SESSION['ruoloMaggiore'] = $row['amministratore'] ? 'amministratore' : ($row['bibliotecario'] ? 'bibliotecario' : ($row['docente'] ? 'docente' : 'studente'));

                setcookie('auth', 'ok', time() + 604800, '/', '', false, true);

                header("Location: ./");
                exit;
            }

        } catch (PDOException $e) {
            $error_msg = "Errore di sistema: " . $e->getMessage();
        }
    }
}

$title = "Accedi";
$page_css = "./public/css/style_forms.css";
?>
    <?php include './src/includes/header.php'; ?>

    <div class="form_container_1">
        <h2 class="form_title_1">Accedi</h2>

        <?php if (!empty($error_msg)): ?>
            <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form method="post">
            <label class="form_1_label">Username, Email o Codice Fiscale</label>
            <input class="form_1_input_sring" name="username" type="text" placeholder="Inserisci credenziali" required value="<?php echo htmlspecialchars($user_input ?? ''); ?>">
            
            <label class="form_1_label">Password</label>
            <input class="form_1_input_sring" name="password" type="password" placeholder="Password" required>
            <a id="form_1_sublabel" href="./password-reset">Password dimenticata?</a>
            
            <button class="form_1_btn_submit" type="submit">Login</button>
        </form>

        <br>
        <a href="./signup">Registrati</a>
    </div>

</div>
</body>
</html>
