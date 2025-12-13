<?php
session_start();
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
            $stmt = $pdo->prepare("SELECT password_hash, codice_alfanumerico, email_confermata, nome, cognome, email FROM utenti WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$user_input, $user_input]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $error_msg = "Utente non trovato.";
            } elseif (!password_verify($pass_input, $row['password_hash'])) {
                $error_msg = "Password errata.";
            } elseif ($row['email_confermata'] != 1) {
                // Email non confermata → invio nuovo token
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
                               <p><a href='https://unexploratory-franchesca-lipochromic.ngrok-free.dev/verifica'>Biblioteca Itis Rossi</a></p>";
                $mail->send();

                $error_msg = "Conferma l'email prima di accedere. Ti è stato inviato un nuovo codice!";
            } else {
                // Login riuscito
                session_regenerate_id(true);
                $_SESSION['logged'] = true;
                $_SESSION['codice_utente'] = $row['codice_alfanumerico'];
                $_SESSION['username'] = $user_input;

                setcookie('auth', 'ok', time() + 604800, '/', '', false, true);

                header("Location: ./");
                exit;
            }

        } catch (PDOException $e) {
            $error_msg = "Errore di sistema: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>

    <?php include './src/includes/header.php'; ?>
    <?php include './src/includes/navbar.php'; ?>

    <div class="container">
        <h2>Accedi</h2>

        <?php if (!empty($error_msg)): ?>
            <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Username, Email o Codice Fiscale</label>
            <input name="username" type="text" placeholder="Inserisci credenziali" required value="<?php echo htmlspecialchars($user_input ?? ''); ?>">
            
            <label>Password</label>
            <input name="password" type="password" placeholder="Password" required>
            
            <button type="submit">Login</button>
        </form>

        <br>
        <a href="./signup">Non hai un account? Registrati</a>
    </div>

    <?php include './src/includes/footer.php'; ?>

</body>
</html>
