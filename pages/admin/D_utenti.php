<?php

require_once 'security.php';
if (!checkAccess('amministratore')) header('Location: ./');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

$messaggio_db = "";
$class_messaggio = "";
$utenti = [];

if (!isset($pdo)) {
    die("Connessione DB non riuscita");
}

try {

    //ELIMINA
    if (isset($_POST['delete_id'])) {
        // Prima controlla se ci sono recensioni collegate
        $stmt = $pdo->prepare("SELECT COUNT(*) as num FROM recensioni WHERE codice_alfanumerico = :codice");
        $stmt->execute(['codice' => $_POST['delete_id']]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->beginTransaction();

        try {
            // Prima elimina le recensioni
            if ($count['num'] > 0) {
                $stmt = $pdo->prepare("DELETE FROM recensioni WHERE codice_alfanumerico = :codice");
                $stmt->execute(['codice' => $_POST['delete_id']]);
            }

            // Elimina dai ruoli
            $stmt = $pdo->prepare("DELETE FROM ruoli WHERE codice_alfanumerico = :codice");
            $stmt->execute(['codice' => $_POST['delete_id']]);

            // Elimina l'utente
            $stmt = $pdo->prepare("DELETE FROM utenti WHERE codice_alfanumerico = :codice");
            $stmt->execute(['codice' => $_POST['delete_id']]);

            $pdo->commit();

            if ($count['num'] > 0) {
                $_SESSION['messaggio'] = "Utente eliminato insieme a " . $count['num'] . " recensione/i!";
            } else {
                $_SESSION['messaggio'] = "Utente eliminato con successo!";
            }
            $_SESSION['tipo_messaggio'] = "success";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['messaggio'] = "ERRORE: " . $e->getMessage();
            $_SESSION['tipo_messaggio'] = "error";
        }

        header("Location: dashboard-utenti");
        exit;
    }

    //MODIFICA
    if (isset($_POST['edit_id'])) {

        // Password
        if ($_POST['password_hash'] !== '*****' && !empty($_POST['password_hash'])) {
            $password = password_hash($_POST['password_hash'], PASSWORD_DEFAULT);
        } else {
            $stmt_old = $pdo->prepare(
                    "SELECT password_hash FROM utenti WHERE codice_alfanumerico = :codice"
            );
            $stmt_old->execute(['codice' => $_POST['codice_alfanumerico']]);
            $password = $stmt_old->fetchColumn();
        }

        $account_bloccato = isset($_POST['account_bloccato']) ? 1 : 0;
        $affidabile = isset($_POST['affidabile']) ? 1 : 0;
        $email_confermata = isset($_POST['email_confermata']) ? 1 : 0;
        $login_bloccato_check = isset($_POST['login_bloccato_check']) ? 1 : 0;

        $ruolo0 = isset($_POST['ruolo0']) ? 1 : 0;
        $ruolo1 = isset($_POST['ruolo1']) ? 1 : 0;
        $ruolo2 = isset($_POST['ruolo2']) ? 1 : 0;
        $ruolo3 = isset($_POST['ruolo3']) ? 1 : 0;

        $stmt = $pdo->prepare("
            UPDATE utenti SET
                username = :username,
                nome = :nome,
                cognome = :cognome,
                codice_fiscale = :codice_fiscale,
                email = :email,
                password_hash = :password_hash,
                login_bloccato = :login_bloccato,
                account_bloccato = :account_bloccato,
                affidabile = :affidabile,
                email_confermata = :email_confermata,
                livello_privato = :livello_privato,
                data_creazione = :data_creazione
            WHERE codice_alfanumerico = :codice
        ");

        $stmt->execute([
                'username' => $_POST['username'],
                'nome' => $_POST['nome'],
                'cognome' => $_POST['cognome'],
                'codice_fiscale' => $_POST['codice_fiscale'],
                'email' => $_POST['email'],
                'password_hash' => $password,
                'login_bloccato' => $login_bloccato_check,
                'account_bloccato' => $account_bloccato,
                'affidabile' => $affidabile,
                'email_confermata' => $email_confermata,
                'livello_privato' => $_POST['livello_privato'],
                'data_creazione' => $_POST['data_creazione'],
                'codice' => $_POST['codice_alfanumerico']
        ]);

        $stmt = $pdo->prepare("
            UPDATE ruoli SET
                studente = :studente,
                docente = :docente,
                bibliotecario = :bibliotecario,
                amministratore = :amministratore
            WHERE codice_alfanumerico = :codice
        ");
        $stmt->execute([
                "studente" => $ruolo0,
                "docente" => $ruolo1,
                "bibliotecario" => $ruolo2,
                "amministratore" => $ruolo3,
                "codice" => $_POST['codice_alfanumerico']
        ]);

        $_SESSION['messaggio'] = "Utente modificato con successo!";
        $_SESSION['tipo_messaggio'] = "success";

        header("Location: dashboard-utenti");
        exit;
    }

    //AGGIUNGI - USA STORED PROCEDURE
    if (isset($_POST['inserisci'])) {
        try {
            // Chiama la stored procedure
            $stmt = $pdo->prepare("CALL sp_crea_utente_alfanumerico(
                :username, 
                :nome, 
                :cognome, 
                :codice_fiscale, 
                :email, 
                :password_hash
            )");

            $password_hash = password_hash($_POST['password_hash'], PASSWORD_DEFAULT);

            $stmt->execute([
                    'username' => $_POST['username'],
                    'nome' => $_POST['nome'],
                    'cognome' => $_POST['cognome'],
                    'codice_fiscale' => $_POST['codice_fiscale'],
                    'email' => $_POST['email'],
                    'password_hash' => $password_hash
            ]);

            // Recupera il nuovo_id generato
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nuovo_id = $result['nuovo_id'];

            // Chiudi il cursore per permettere altre query
            $stmt->closeCursor();

            // Inserisci i ruoli
            $ruolo0 = isset($_POST['ruolo0']) ? 1 : 0;
            $ruolo1 = isset($_POST['ruolo1']) ? 1 : 0;
            $ruolo2 = isset($_POST['ruolo2']) ? 1 : 0;
            $ruolo3 = isset($_POST['ruolo3']) ? 1 : 0;

            $stmt = $pdo->prepare("
                INSERT INTO ruoli(
                    codice_alfanumerico, studente, docente,
                    bibliotecario, amministratore
                ) VALUES (
                    :codice_alfanumerico, :studente, :docente,
                    :bibliotecario, :amministratore                             
                )
            ");

            $stmt->execute([
                    'codice_alfanumerico' => $nuovo_id,
                    'studente' => $ruolo0,
                    'docente' => $ruolo1,
                    'bibliotecario' => $ruolo2,
                    'amministratore' => $ruolo3
            ]);

            $_SESSION['messaggio'] = "Utente creato con successo! Codice alfanumerico: " . $nuovo_id;
            $_SESSION['tipo_messaggio'] = "success";

        } catch (PDOException $e) {
            $_SESSION['messaggio'] = "ERRORE durante la creazione: " . $e->getMessage();
            $_SESSION['tipo_messaggio'] = "error";
        }

        header("Location: dashboard-utenti");
        exit;
    }

    $stmt = $pdo->query("SELECT * FROM utenti 
                        JOIN ruoli ON utenti.codice_alfanumerico = ruoli.codice_alfanumerico
                        ORDER BY data_creazione DESC");
    $utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recupera messaggi dalla sessione
    if (isset($_SESSION['messaggio'])) {
        $messaggio_db = $_SESSION['messaggio'];
        $class_messaggio = $_SESSION['tipo_messaggio'];
        unset($_SESSION['messaggio']);
        unset($_SESSION['tipo_messaggio']);
    }

} catch (PDOException $e) {
    die("Errore DB: " . $e->getMessage());
}
?>

<?php
$title = "Dashboard Utenti";
$path = "../";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <!-- INIZIO DEL BODY -->

    <div class="page_contents">

        <?php if (!empty($messaggio_db)): ?>
            <div style="padding: 10px; background: <?= $class_messaggio == 'error' ? '#f8d7da' : '#d4edda' ?>; border: 1px solid <?= $class_messaggio == 'error' ? '#f5c6cb' : '#c3e6cb' ?>; margin: 10px 0; color: <?= $class_messaggio == 'error' ? '#721c24' : '#155724' ?>;">
                <?= htmlspecialchars($messaggio_db) ?>
            </div>
        <?php endif; ?>

        <h2>Inserisci nuovo utente</h2>

        <table style="margin-bottom: 40px">
            <tr>
                <th>Username</th>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Codice Fiscale</th>
                <th>Email</th>
                <th>Password</th>
                <th>Ruolo</th>
                <th>Azioni</th>
            </tr>

            <tr>
                <form method="POST">
                    <td><input type="text" name="username" placeholder="Es: TestUsername1" required></td>
                    <td><input type="text" name="nome" required></td>
                    <td><input type="text" name="cognome" required></td>
                    <td><input type="text" name="codice_fiscale" maxlength="16" placeholder="16 caratteri" required></td>
                    <td><input type="email" name="email" required></td>
                    <td><input type="password" name="password_hash" required></td>
                    <td>
                        <input type="checkbox" name="ruolo0" value="1" id="ins_ruolo0">
                        <label for="ins_ruolo0">Studente</label><br>
                        <input type="checkbox" name="ruolo1" value="1" id="ins_ruolo1">
                        <label for="ins_ruolo1">Docente</label><br>
                        <input type="checkbox" name="ruolo2" value="1" id="ins_ruolo2">
                        <label for="ins_ruolo2">Bibliotecario</label><br>
                        <input type="checkbox" name="ruolo3" value="1" id="ins_ruolo3">
                        <label for="ins_ruolo3">Amministratore</label><br>
                    </td>
                    <td>
                        <input type="hidden" name="inserisci" value="1">
                        <button type="submit">Inserisci</button>
                    </td>
                </form>
            </tr>
        </table>

        <h2>Utenti registrati</h2>

        <table>
            <tr>
                <th>Codice</th>
                <th>Username</th>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Codice Fiscale</th>
                <th>Email</th>
                <th>Password</th>
                <th>Login Bloccato</th>
                <th>Account Bloccato</th>
                <th>Livello Privato</th>
                <th>Data Creazione</th>
                <th>Affidabile</th>
                <th>Email Confermata</th>
                <th>Ruolo</th>
                <th>Azioni</th>
            </tr>

            <?php foreach ($utenti as $u): ?>
                <tr>
                    <form method="POST">
                        <td>
                            <?= htmlspecialchars($u['codice_alfanumerico'] ?? '') ?>
                            <input type="hidden" name="codice_alfanumerico"
                                   value="<?= htmlspecialchars($u['codice_alfanumerico'] ?? '') ?>">
                        </td>
                        <td>
                            <input type="text" name="username"
                                   value="<?= htmlspecialchars($u['username'] ?? '') ?>" required>
                        </td>
                        <td>
                            <input type="text" name="nome"
                                   value="<?= htmlspecialchars($u['nome'] ?? '') ?>" required>
                        </td>

                        <td>
                            <input type="text" name="cognome"
                                   value="<?= htmlspecialchars($u['cognome'] ?? '') ?>" required>
                        </td>

                        <td>
                            <input type="text" name="codice_fiscale"
                                   value="<?= htmlspecialchars($u['codice_fiscale'] ?? '') ?>"
                                   maxlength="16" required>
                        </td>

                        <td>
                            <input type="email" name="email"
                                   value="<?= htmlspecialchars($u['email'] ?? '') ?>" required>
                        </td>

                        <td>
                            <input type="password" name="password_hash" placeholder="Lascia ***** per non modificare" value="*****">
                        </td>

                        <td>
                            <input type="checkbox" name="login_bloccato_check"
                                   value="1" <?= !empty($u['login_bloccato']) ? 'checked' : '' ?>>
                        </td>

                        <td>
                            <input type="checkbox" name="account_bloccato"
                                   value="1" <?= !empty($u['account_bloccato']) ? 'checked' : '' ?>>
                        </td>

                        <td>
                            <input type="number" name="livello_privato"
                                   value="<?= htmlspecialchars($u['livello_privato'] ?? 0) ?>"
                                   min="0" max="10">
                        </td>

                        <td>
                            <input type="datetime-local" name="data_creazione"
                                   value="<?= isset($u['data_creazione']) ? date('Y-m-d\TH:i', strtotime($u['data_creazione'])) : '' ?>" required>
                        </td>

                        <td>
                            <input type="checkbox" name="affidabile"
                                   value="1" <?= !empty($u['affidabile']) ? 'checked' : '' ?>>
                        </td>

                        <td>
                            <input type="checkbox" name="email_confermata"
                                   value="1" <?= !empty($u['email_confermata']) ? 'checked' : '' ?>>
                        </td>

                        <td>
                            <input type="checkbox" name="ruolo0" value="1" <?= !empty($u['studente']) ? 'checked' : '' ?>>
                            <label>Studente</label><br>
                            <input type="checkbox" name="ruolo1" value="1" <?= !empty($u['docente']) ? 'checked' : '' ?>>
                            <label>Docente</label><br>
                            <input type="checkbox" name="ruolo2" value="1" <?= !empty($u['bibliotecario']) ? 'checked' : '' ?>>
                            <label>Bibliotecario</label><br>
                            <input type="checkbox" name="ruolo3" value="1" <?= !empty($u['amministratore']) ? 'checked' : '' ?>>
                            <label>Admin</label><br>
                        </td>

                        <td>
                            <input type="hidden" name="edit_id" value="<?= htmlspecialchars($u['codice_alfanumerico'] ?? '') ?>">
                            <button type="submit">Salva</button>
                    </form>

                    <form method="POST" style="display:inline;" onsubmit="return confirm('ATTENZIONE: Verranno eliminate anche tutte le recensioni di questo utente.\n\nConfermi eliminazione?')">
                        <input type="hidden" name="delete_id" value="<?= htmlspecialchars($u['codice_alfanumerico'] ?? '') ?>">
                        <button type="submit">Elimina</button>
                    </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

    </div>

<?php require_once './src/includes/footer.php'; ?>
<style>
    th, td {
        padding: 15px;
        border: solid 1px black;
    }
</style>
