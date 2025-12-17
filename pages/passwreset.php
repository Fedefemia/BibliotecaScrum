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

    if (!$user_input) {
        $error_msg = "Compila tutti i campi.";
    } elseif (!isset($pdo)) {
        $error_msg = "Errore di connessione al database.";
    } else {
        try {
            // Recupero utente dal DB
            $stmt = $pdo->prepare("
    SELECT 
        email,
        nome,
        cognome,
        codice_alfanumerico
    FROM utenti
    WHERE username = ?
       OR email = ?
       OR codice_fiscale = ?
    LIMIT 1
");

            $stmt->execute([$user_input, $user_input, $user_input]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $error_msg = "Utente non trovato.";
            } else {
                $token = bin2hex(random_bytes(32));
                $ins = $pdo->prepare("INSERT INTO tokenemail (token, codice_alfanumerico) VALUES (?, ?)");
                $ins->execute([$token, $row['codice_alfanumerico']]);

                $baseUrl = 'https://unexploratory-franchesca-lipochromic.ngrok-free.dev/verifica';
                $verifyLink = $baseUrl . '?pswreset=' . urlencode($token);

                $mail = getMailer();
                $mail->addAddress($row['email'], $row['nome'] . ' ' . $row['cognome']);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset';
                $mail->Body = "<p>Ciao " . htmlspecialchars($row['nome']) . ",</p>
                               <p>Qualcuno ha provato a resettare la password del tuo account</p>
                               <p>Se sei stato tu <a href=\"" . htmlspecialchars($verifyLink) . "\">clicca qui</a></p>
                               <p>Sennó puoi ignorare questa email</p>
                               <br>
                               <p>Inviato da: Biblioteca Scrum Itis Rossi</p>
                               <p><a href='https://unexploratory-franchesca-lipochromic.ngrok-free.dev/'>Biblioteca Itis Rossi</a></p>";
                $mail->send();

                $error_msg = "Se l’account esiste, riceverai un’email per reimpostare la password.";
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
    <title>Password Reset</title>
</head>

<body>

    <?php include './src/includes/header.php'; ?>
    <?php include './src/includes/navbar.php'; ?>

    <div class="container">
        <h2>Reset della password</h2>

        <?php if (!empty($error_msg)): ?>
            <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Inserisci Username, Email o Codice Fiscale</label>
            <input name="username" type="text" placeholder="Inserisci credenziali" required
                value="<?php echo htmlspecialchars($user_input ?? ''); ?>">

            <button type="submit">Manda la richiesta</button>
        </form>

        <br>
        <a href="./login">Login</a>
    </div>

    <?php include './src/includes/footer.php'; ?>

</body>

</html>