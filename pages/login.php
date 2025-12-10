<?php
session_start();
require_once 'db_config.php';

if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) {
    header("Location: ./");
    exit;
}

$error_msg = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = trim($_POST['username'] ?? '');
    $pass_input = trim($_POST['password'] ?? '');

    if (isset($pdo)) {
        try {
            $password_hash = hash('sha256', $pass_input); 

            $stmt = $pdo->prepare("CALL CheckLoginUser(?, ?, @risultato)");
            
            $stmt->bindParam(1, $user_input, PDO::PARAM_STR);
            $stmt->bindParam(2, $password_hash, PDO::PARAM_STR); 
            $stmt->execute();
            
            $stmt->closeCursor();

            $row = $pdo->query("SELECT @risultato as esito")->fetch(PDO::FETCH_ASSOC);
            $esito = $row['esito'];

            if ($esito === 'utente_non_trovato') {
                $error_msg = "Utente non trovato.";
            } 
            elseif ($esito === 'password_sbagliata') {
                $error_msg = "Password errata.";
            } 
            elseif ($esito === 'blocked:1') {
                $error_msg = "Il tuo account è stato bloccato da un amministratore.";
            } 
            elseif ($esito === 'blocked:2') {
                $error_msg = "Troppi tentativi falliti. Riprova tra 15 minuti.";
            } 
            else {
                session_regenerate_id(true); 
                
                $_SESSION['logged'] = true;
                $_SESSION['codice_utente'] = $esito; 
                $_SESSION['username'] = $user_input; 
                
                setcookie('auth', 'ok', time() + 604800, '/', '', false, true);
                
                header("Location: ./"); 
                exit;
            }

        } catch (PDOException $e) {
            $error_msg = "Errore di sistema: " . $e->getMessage();
        }
    } else {
        $error_msg = "Errore di connessione al Database.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <!--style>
        .error { color: red; background-color: #fdd; padding: 10px; border: 1px solid red; margin-bottom: 15px; }
        .container { padding: 20px; max-width: 400px; margin: auto; }
        input { display: block; width: 100%; margin-bottom: 10px; padding: 8px; }
        button { padding: 10px 20px; cursor: pointer; }
    </style-->
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