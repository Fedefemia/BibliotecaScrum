<?php

require_once 'security.php';
if (!checkAccess('amministratore')) header('Location: ./');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';
require_once './src/includes/codiceFiscaleMethods.php';

$messaggio_db = "";
$class_messaggio = "";
$utenti = [];

if (!isset($pdo)) {
    die("Connessione DB non riuscita");
}

try {

    //ELIMINA
    if (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare(
                "DELETE FROM utenti WHERE codice_alfanumerico = :codice"
        );
        $stmt->execute([
                'codice' => $_POST['delete_id']
        ]);

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

        $ruolo0 = isset($_POST['ruolo0']) ? 1 : 0;
        $ruolo1 = isset($_POST['ruolo1']) ? 1 : 0;
        $ruolo2 = isset($_POST['ruolo2']) ? 1 : 0;
        $ruolo3 = isset($_POST['ruolo3']) ? 1 : 0;

        $stmt = $pdo->prepare("
            UPDATE utenti SET
                nome = :nome,
                cognome = :cognome,
                codice_fiscale = :codice_fiscale,
                email = :email,
                password_hash = :password_hash,
                login_bloccato = :login_bloccato,
                account_bloccato = :account_bloccato,
                data_creazione = :data_creazione,
                affidabile = :affidabile,
                livello_privato = :livello_privato,
                email_confermata = :email_confermata,
            WHERE codice_alfanumerico = :codice
        ");

        $stmt->execute([
                'nome' => $_POST['nome'],
                'cognome' => $_POST['cognome'],
                'codice_fiscale' => $_POST['codice_fiscale'],
                'email' => $_POST['email'],
                'password_hash' => $password,
                'login_bloccato' => $_POST['login_bloccato'],
                'account_bloccato' => $account_bloccato,
                'data_creazione' => $_POST['data_creazione'],
                'affidabile' => $affidabile,
                'livello_privato' => $_POST['livello_privato'],
                'email_confermata' => $_POST['email_confermata'],
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

        header("Location: dashboard-utenti");
        exit;
    }

    //AGGIUNGI
    if (isset($_POST['inserisci'])) {
        $stmt = $pdo->prepare("
            INSERT INTO utenti (
                codice_alfanumerico, nome, cognome, codice_fiscale,email,
                password_hash, login_bloccato, account_bloccato,
                data_creazione, affidabile
            ) VALUES (
                :codice, :nome, :cognome, :data_nascita,
                :sesso, :comune, :cf, :email,
                :password, 0, 0,
                NOW(), 0
            )
        ");

        $stmt->execute([
                'codice' => $_POST['codice_alfanumerico'],
                'nome' => $_POST['nome'],
                'cognome' => $_POST['cognome'],
                'data_nascita' => $_POST['data_nascita'],
                'sesso' => $_POST['sesso'],
                'comune' => $_POST['comune_nascita'],
                'cf' => $_POST['codice_fiscale'],
                'email' => $_POST['email'],
                'password' => password_hash($_POST['password_hash'], PASSWORD_DEFAULT)
        ]);

        $ruolo0 = isset($_POST['ruolo0']) ? 1 : 0;
        $ruolo1 = isset($_POST['ruolo1']) ? 1 : 0;
        $ruolo2 = isset($_POST['ruolo2']) ? 1 : 0;
        $ruolo3 = isset($_POST['ruolo3']) ? 1 : 0;

        $stmt = $pdo->prepare("
                                INSERT INTO ruoli(
                                codice_alfanumerico,studente,docente,
                                bibliotecario,amministratore)
                                values(
                                :codice_alfanumerico,:studente,:docente,
                                :bibliotecario,:amministratore                             
                                )
                                ");
        $stmt->execute(['codice_alfanumerico' => $_POST['codice_alfanumerico'],
                'studente' => $ruolo0,
                'docente' => $ruolo1,
                'bibliotecario' => $ruolo2,
                'amministratore' => $ruolo3
        ]);

        header("Location: dashboard-utenti");
        exit;
    }

    $stmt = $pdo->query("SELECT * FROM utenti 
                                JOIN ruoli ON utenti.codice_alfanumerico = ruoli.codice_alfanumerico
                                ORDER BY data_creazione DESC");
    $utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

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


        <h2>Inserisci nuovo utente</h2>

        <table style="margin-bottom: 40px">
            <tr>
                <th>Codice</th>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Data Nascita</th>
                <th>Sesso</th>
                <th>Comune</th>
                <th>Codice Fiscale</th>
                <th>Email</th>
                <th>Password</th>
                <th>Ruolo</th>
                <th>Azioni</th>
            </tr>

            <tr>
                <form method="POST">
                    <td><input type="text" name="codice_alfanumerico" required></td>
                    <td><input type="text" name="nome" required></td>
                    <td><input type="text" name="cognome" required></td>
                    <td><input type="date" name="data_nascita" required></td>

                    <td>
                        <select name="sesso" required>
                            <option value="M">M</option>
                            <option value="F">F</option>
                        </select>
                    </td>

                    <td><input type="text" name="comune_nascita" required></td>
                    <td><input type="text" name="codice_fiscale" maxlength="16" required></td>
                    <td><input type="email" name="email" required></td>
                    <td><input type="password" name="password_hash" required></td>
                    <td>
                        <input type="checkbox" name="ruolo0" value="1">
                        <label for="ruolo0">Studente </label><br>
                        <input type="checkbox" name="ruolo1" value="1">
                        <label for="ruolo1">Docente </label><br>
                        <input type="checkbox" name="ruolo2" value="1">
                        <label for="ruolo2">Bibliotecario</label><br>
                        <input type="checkbox" name="ruolo3" value="1">
                        <label for="ruolo3">Amministratore </label><br>
                    </td>
                    <td>
                        <input type="hidden" name="inserisci" value="1">
                        <button type="submit">Inserisci</button>
                    </td>
                </form>
            </tr>
        </table>


        <table>
        <tr>
            <th>Codice Alfanumerico</th>
            <th>Nome</th>
            <th>Cognome</th>
            <th>Data Nascita</th>
            <th>Sesso</th>
            <th>Comune Nascita</th>
            <th>Codice Fiscale</th>
            <th>Email</th>
            <th>Password</th>
            <th>Tentativi Login</th>
            <th>Account Bloccato</th>
            <th>Data Creazione</th>
            <th>Affidabile</th>
            <th>Ruolo</th>
            <th>Azioni</th>
        </tr>

        <?php foreach ($utenti as $u): ?>
            <tr>
                <form method="POST">
                    <td>
                        <?= htmlspecialchars($u['codice_alfanumerico']) ?>
                        <input type="hidden" name="codice_alfanumerico"
                               value="<?= htmlspecialchars($u['codice_alfanumerico']) ?>">
                    </td>

                    <td>
                        <input type="text" name="nome"
                               value="<?= htmlspecialchars($u['nome']) ?>" required>
                    </td>

                    <td>
                        <input type="text" name="cognome"
                               value="<?= htmlspecialchars($u['cognome']) ?>" required>
                    </td>

                    <td>
                        <input type="date" name="data_nascita"
                               value="<?= htmlspecialchars($u['data_nascita']) ?>" required>
                    </td>

                    <td>
                        <select name="sesso" required>
                            <option value="M" <?= $u['sesso'] == 'M' ? 'selected' : '' ?>>M</option>
                            <option value="F" <?= $u['sesso'] == 'F' ? 'selected' : '' ?>>F</option>
                        </select>
                    </td>

                    <td>
                        <input type="text" name="comune_nascita"
                               value="<?= htmlspecialchars($u['comune_nascita']) ?>" required>
                    </td>

                    <td>
                        <input type="text" name="codice_fiscale"
                               value="<?= htmlspecialchars($u['codice_fiscale']) ?>"
                               maxlength="16" required>
                    </td>

                    <td>
                        <input type="email" name="email"
                               value="<?= htmlspecialchars($u['email']) ?>" required>
                    </td>

                    <td>
                        <input type="password" name="password_hash"
                               value="*****"
                    </td>

                    <td>
                        <input type="number" name="login_bloccato"
                               value="<?= htmlspecialchars($u['login_bloccato']) ?>"
                               min="0">
                    </td>

                    <td>
                        <input type="checkbox" name="account_bloccato"
                               value="1" <?= $u['account_bloccato'] ? 'checked' : '' ?>>
                    </td>

                    <td>
                        <input type="datetime" name="data_creazione"
                               value="<?= htmlspecialchars($u['data_creazione']) ?>" required>

                    </td>

                    <td>
                        <input type="checkbox" name="affidabile"
                               value="1" <?= $u['affidabile'] ? 'checked' : '' ?>>
                    </td>

                    <td>
                        <input type="checkbox" name="ruolo0" value="1" <?= $u['studente'] ? 'checked' : '' ?>>
                        <label for="ruolo0">Studente </label><br>
                        <input type="checkbox" name="ruolo1" value="1" <?= $u['docente'] ? 'checked' : '' ?>>
                        <label for="ruolo1">Docente </label><br>
                        <input type="checkbox" name="ruolo2" value="1" <?= $u['bibliotecario'] ? 'checked' : '' ?>>
                        <label for="ruolo2">Bibliotecario</label><br>
                        <input type="checkbox" name="ruolo3" value="1" <?= $u['amministratore'] ? 'checked' : '' ?>>
                        <label for="ruolo3">Admin </label><br>
                    </td>

                    <td>
                        <!-- SALVA -->
                        <input type="hidden" name="edit_id" value="<?= $u['codice_alfanumerico'] ?>">
                        <button type="submit">Salva</button>
                </form>

                <!-- ELIMINA -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $u['codice_alfanumerico'] ?>">
                    <button type="submit"
                            onclick="return confirm('Eliminare questo utente?')">
                        Elimina
                    </button>
                </form>
                </td>
            </tr>
        <?php endforeach; ?>

    </table>

</div>

<?php require_once './src/includes/footer.php'; ?>

<style>
    th, td {
        padding: 10px;
        border: solid 1px black;
        text-align: center;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="date"],
    input[type="number"],
    select {
        width: 100%;
        padding: 5px;
        box-sizing: border-box;
    }

    table {
        border-collapse: collapse;
    }

    button {
        padding: 5px 10px;
        margin: 2px;
        cursor: pointer;
    }
</style>


<?php require_once './src/includes/footer.php'; ?>
<style>
    th, td {
        padding: 15px;
        border: solid 1px black;
    }
</style>