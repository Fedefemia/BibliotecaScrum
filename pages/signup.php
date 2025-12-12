<?php
session_start();
require_once "./src/includes/codiceFiscaleMethods.php";
require_once 'db_config.php';
require __DIR__ . '/phpmailer.php';

// Redirect se già loggato
if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) {
    header("Location: /");
    exit();
}

$registratiConCodice = isset($_POST['conCodiceFiscale']) && $_POST['conCodiceFiscale'] == "true";
$tipologia = $registratiConCodice ? " con Codice Fiscale" : "";
$error_msg = '';

// LOGICA DI SIGNUP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($pdo)) {
            $email = $_POST['email'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $nome = $_POST['nome'] ?? '';
            $cognome = $_POST['cognome'] ?? '';
            $daCodiceFiscale = isset($_POST['daCodiceFiscale']) ? boolval($_POST['daCodiceFiscale']) : false;
            $follow_along = false;

            if ($nome != '' && $cognome != '' && $email != '' && $username != '' && $password != '') {
                if (!$daCodiceFiscale) {
                    $data_nascita = $_POST['data_nascita'] ?? '';
                    $comune_nascita = $_POST['comune_nascita'] ?? '';
                    $sesso = $_POST['sesso'] ?? '';
                    if ($data_nascita == '' || $comune_nascita == '') {
                        $error_msg = "Dati inseriti non validi";
                    }
                    $codice_fiscale = generateCodiceFiscale($nome, $cognome, $data_nascita, $comune_nascita, $sesso);
                } else {
                    $codice_fiscale = $_POST['codice_fiscale'] ?? '';
                    if (empty($datiDaCodice)) {
                        $error_msg = "Codice Fiscale non valido";
                    }
                }
                $follow_along = true;
            }
            // Inserimento Utente
            if ($error_msg == '' && $follow_along) {
                $insert_string = "CALL sp_crea_utente_alfanumerico(:username, :nome, :cognome, :codice_fiscale, :email, :password)";
                $stmt = $pdo->prepare($insert_string);
                $password_hash = hash("sha256", $password);
                $stmt->bindParam(":nome", $nome);
                $stmt->bindParam(":cognome", $cognome);
                $stmt->bindParam(":username", $username);
                $stmt->bindParam(":codice_fiscale", $codice_fiscale);
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":password", $password_hash);
                $resu = $stmt->execute();
                if ($resu) {

                    //logica token email
                    $token = '';
                    while ($token == '') {
                        $temp = hash('sha256', uniqid(mt_rand(), true));

                        $stmt = $pdo->prepare("SELECT id FROM tokenemail WHERE token = ? LIMIT 1");
                        $stmt->execute([$temp]);

                        if ($stmt->fetch() === false) {
                            $token = $temp;
                        }
                    }
                    $codice = $pdo->query("SELECT codice_alfanumerico FROM utente WHERE email = " . $pdo->quote($email) . " LIMIT 1")->fetchColumn();

                    $stmt = $pdo->prepare("INSERT INTO tokenemail (codice_alfanumerico, token) VALUES (?, ?)");
                    $stmt->execute([$codice, $token]);

                    $verifyUrl = "https://unexploratory-franchesca-lipochromic.ngrok-free.dev/verifica?token=" . urlencode($token);

                    $subject = "Conferma la tua email";
                    $message = "
                        <html>
                            <body>
                                <p>Ciao $nome,</p>
                                <p>Clicca qui per confermare la tua email:</p>
                                <p><a href='$verifyUrl'>$verifyUrl</a></p>
                            </body>
                        </html>
                    ";

                    $mail = getMailer();

                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $message;

                    $mail->send();

                    header("Location: /login");
                    exit();
                } else {
                    $status = "Errore nell'inserimento dell'utente";
                }
            }
        } else {
            $error_msg = "Errore di connessione al Database.";
        }
    } catch (PDOException $e) {
        $error_msg = "Errore di sistema: " . $e->getMessage();
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