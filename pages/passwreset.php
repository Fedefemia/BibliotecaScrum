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

                $baseUrl = 'https://overgenially-unappareled-ross.ngrok-free.dev/verifica';
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
    <style>
        /* ===============================
           --- STILI PASSWORD RESET ---
        =============================== */
        :root {
            --color-bg-cream: #eae3d2;
            --color-bg-light: #faf9f6;
            --color-dark-green: #3f5135;
            --color-text-dark: #333;
            --color-accent-gold: #f39c12;
            --font-serif: "Young Serif", serif;
            --font-sans: "Instrument Sans", sans-serif;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--color-bg-light);
            color: var(--color-text-dark);
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Wrapper centrale per centrare la card */
        .auth_wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .auth_card {
            background-color: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(63, 81, 53, 0.08);
            width: 100%;
            max-width: 450px;
            border: 1px solid #eee;
            text-align: center;
        }

        .auth_title {
            font-family: var(--font-serif);
            font-size: 2.2rem;
            color: var(--color-dark-green);
            margin-top: 0;
            margin-bottom: 25px;
        }

        .form_group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form_label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--color-dark-green);
            font-size: 0.95rem;
        }

        .form_input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: var(--font-sans);
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
            background-color: #fafafa;
        }

        .form_input:focus {
            outline: none;
            border-color: var(--color-dark-green);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(63, 81, 53, 0.1);
        }

        .btn_submit {
            background-color: #333;
            color: #fff;
            border: none;
            padding: 14px 25px;
            border-radius: 8px;
            font-family: var(--font-sans);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn_submit:hover {
            background-color: #555;
            transform: translateY(-1px);
        }

        .auth_links {
            margin-top: 25px;
            font-size: 0.95rem;
        }

        .auth_links a {
            color: var(--color-dark-green);
            text-decoration: none;
            font-weight: 600;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s;
        }

        .auth_links a:hover {
            border-bottom-color: var(--color-dark-green);
        }

        /* Messaggi di errore/info */
        .alert_box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            text-align: left;
            line-height: 1.5;
        }

        .alert_error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Se il messaggio non è un errore grave ma info (come "email inviata") */
        .alert_info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

    </style>
</head>

<body>

    <?php include './src/includes/header.php'; ?>
    <?php include './src/includes/navbar.php'; ?>

    <div class="auth_wrapper">
        <div class="auth_card">
            <h2 class="auth_title">Reset della password</h2>

            <?php if (!empty($error_msg)): ?>
                <div class="alert_box <?php echo (strpos($error_msg, 'riceverai') !== false) ? 'alert_info' : 'alert_error'; ?>">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form_group">
                    <label class="form_label">Username, Email o Codice Fiscale</label>
                    <input name="username" type="text" class="form_input" placeholder="Inserisci le tue credenziali" required
                        value="<?php echo htmlspecialchars($user_input ?? ''); ?>">
                </div>

                <button type="submit" class="btn_submit">Manda la richiesta</button>
            </form>

            <div class="auth_links">
                <a href="./login">Login</a>
            </div>
        </div>
    </div>

    <?php include './src/includes/footer.php'; ?>

</body>
</html>