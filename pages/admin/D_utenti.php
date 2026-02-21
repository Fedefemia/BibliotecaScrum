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

// ---------------- HTML HEADER ----------------
$title = "Dashboard Utenti";
$path = "../";
$page_css = "../public/css/style_dashboards.css";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

<style>
    /* INTEGRAZIONE STILE REFERENCE */
    :root {
        --color-bg-cream: #eae3d2;
        --color-bg-light: #faf9f6;
        --color-dark-green: #3f5135;
        --color-text-dark: #333;
        --color-border: #ccc;
    }

    body {
        font-family: "Instrument Sans", sans-serif;
        background-color: var(--color-bg-light);
        color: var(--color-text-dark);
    }

    .dashboard_container_larger {
        padding: 40px;
        max-width: 1600px;
        margin: 0 auto;
    }

    .page_title {
        font-family: "Young Serif", serif;
        font-size: 2.8rem;
        color: var(--color-dark-green);
        margin-bottom: 20px;
    }

    .add_book_section {
        background-color: var(--color-bg-light);
        border: 2px solid var(--color-bg-cream);
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 40px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }

    .form_label {
        font-family: "Instrument Sans", sans-serif;
        font-weight: 700;
        color: #333;
        margin-bottom: 6px;
        display: block;
    }

    .edit_input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-family: "Instrument Sans", sans-serif;
        background: #fff;
        box-sizing: border-box;
        font-size: 0.95rem;
        transition: border-color 0.2s;
    }

    .edit_input:focus {
        border-color: var(--color-dark-green);
        outline: none;
    }

    /* Buttons Style */
    .btn_action {
        font-family: "Instrument Sans", sans-serif;
        font-weight: 600;
        border: none;
        padding: 8px 15px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        width: 100%;
    }

    .btn_save {
        background-color: #333;
        color: #fff;
    }
    .btn_save:hover { background-color: #555; }

    .btn_delete {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .btn_delete:hover { background-color: #f1b0b7; }

    .btn_manage {
        background-color: var(--color-bg-cream);
        color: var(--color-text-dark);
        border: 1px solid #dcdcdc;
    }
    .btn_manage:hover { background-color: #dcd6c5; }

    /* Table Styling Adaptation */
    .table_card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 4px 6px rgba(63, 81, 53, 0.05);
        padding: 20px;
        border: 1px solid #eee;
    }

    .admin_table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .admin_table thead tr {
        background-color: var(--color-bg-cream);
    }

    .admin_table th {
        font-family: "Young Serif", serif;
        color: var(--color-text-dark);
        font-weight: normal;
        padding: 15px 10px;
        text-align: left;
        border-bottom: 2px solid #ddd;
        font-size: 1rem;
    }
    
    .admin_table th:first-child { border-top-left-radius: 12px; }
    .admin_table th:last-child { border-top-right-radius: 12px; }

    .admin_table td {
        padding: 12px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #eee;
        font-size: 0.95rem;
    }

    .roles_container {
        display: flex; 
        gap: 15px; 
        flex-wrap: wrap; 
        background: #fff; 
        padding: 15px; 
        border-radius: 8px; 
        border: 1px solid #ccc;
    }

    /* --- DRAWER (PANNELLO LATERALE) --- */
    .backdrop-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.3);
        z-index: 999;
        display: none;
        backdrop-filter: blur(2px);
    }

    .user-settings-drawer {
        position: fixed;
        top: 0;
        right: -500px;
        width: 400px;
        height: 100vh;
        background: #fff;
        z-index: 1000;
        box-shadow: -5px 0 20px rgba(0,0,0,0.15);
        transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        border-left: 4px solid var(--color-dark-green);
    }

    .user-settings-drawer.active {
        right: 0;
    }

    .drawer-header {
        padding: 20px;
        background: var(--color-bg-cream);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ddd;
    }

    .drawer-title {
        font-family: "Young Serif", serif;
        margin: 0;
        font-size: 1.4rem;
    }

    .drawer-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
    }

    .drawer-body {
        padding: 25px;
        overflow-y: auto;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .drawer-section {
        background: #faf9f6;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #eee;
    }

    .drawer-section h4 {
        margin-top: 0;
        margin-bottom: 10px;
        font-family: "Instrument Sans", sans-serif;
        color: var(--color-dark-green);
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }

    .check-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        padding: 5px 0;
    }
</style>

    <div class="dashboard_container_larger">

        <div class="page_header">
            <h2 class="page_title">Gestione Utenti</h2>
            <div class="header_actions">
                <button onclick="toggleAddForm()" class="btn_action btn_save" style="width: auto;">+ Nuovo Utente</button>
            </div>
        </div>

        <?php if (!empty($messaggio_db)): ?>
            <div class="alert_msg <?= $class_messaggio == 'error' ? 'alert_error' : 'alert_success' ?>">
                <?= htmlspecialchars($messaggio_db) ?>
            </div>
        <?php endif; ?>

        <div id="add_user_section" class="add_book_section" style="display:none;">
            <h3 style="font-family: 'Young Serif', serif; margin-top:0; margin-bottom:20px; color:#333;">Inserisci Nuovo Utente</h3>
            <form method="POST" class="add_form_wrapper form_spam_protect" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <input type="hidden" name="inserisci" value="1">

                <div class="form_group">
                    <label class="form_label">Username</label>
                    <input type="text" name="username" class="edit_input" placeholder="Es: User123" required>
                </div>
                <div class="form_group">
                    <label class="form_label">Nome</label>
                    <input type="text" name="nome" class="edit_input" required>
                </div>
                <div class="form_group">
                    <label class="form_label">Cognome</label>
                    <input type="text" name="cognome" class="edit_input" required>
                </div>
                <div class="form_group">
                    <label class="form_label">Codice Fiscale</label>
                    <input type="text" name="codice_fiscale" class="edit_input" maxlength="16" placeholder="16 caratteri" required>
                </div>
                <div class="form_group">
                    <label class="form_label">Email</label>
                    <input type="email" name="email" class="edit_input" required>
                </div>
                <div class="form_group">
                    <label class="form_label">Password</label>
                    <input type="password" name="password_hash" class="edit_input" required>
                </div>

                <div class="form_group" style="grid-column: 1 / -1;">
                    <label class="form_label">Assegna Ruoli</label>
                    <div class="roles_container">
                        <div>
                            <input type="checkbox" name="ruolo0" value="1" id="ins_ruolo0"> <label for="ins_ruolo0">Studente</label>
                        </div>
                        <div>
                            <input type="checkbox" name="ruolo1" value="1" id="ins_ruolo1"> <label for="ins_ruolo1">Docente</label>
                        </div>
                        <div>
                            <input type="checkbox" name="ruolo2" value="1" id="ins_ruolo2"> <label for="ins_ruolo2">Bibliotecario</label>
                        </div>
                        <div>
                            <input type="checkbox" name="ruolo3" value="1" id="ins_ruolo3"> <label for="ins_ruolo3">Admin</label>
                        </div>
                    </div>
                </div>

                <div style="grid-column: 1 / -1;">
                    <button type="submit" class="btn_action btn_save trigger_loader" style="width: auto; padding: 12px 30px;">Crea Utente</button>
                </div>
            </form>
        </div>

        <div class="table_card">
            <div class="table_responsive">
                <div id="drawerBackdrop" class="backdrop-overlay" onclick="closeAllDrawers()"></div>

                <table class="admin_table">
                    <thead>
                    <tr>
                        <th style="width: 80px;">Codice</th>
                        <th>Username</th>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Codice Fiscale</th>
                        <th>Email</th>
                        <th>Password (Hash)</th>
                        <th style="width: 130px; text-align: center;">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($utenti as $u): ?>
                    <tr>
                        <form method="POST" class="form_spam_protect_row" id="form_row_<?= htmlspecialchars($u['codice_alfanumerico']) ?>">
                            <td style="color: #888; font-family: monospace;">
                                <?= htmlspecialchars($u['codice_alfanumerico'] ?? '') ?>
                                <input type="hidden" name="codice_alfanumerico" value="<?= htmlspecialchars($u['codice_alfanumerico'] ?? '') ?>">
                            </td>

                            <td><input type="text" name="username" class="edit_input" value="<?= htmlspecialchars($u['username'] ?? '') ?>" required></td>
                            <td><input type="text" name="nome" class="edit_input" value="<?= htmlspecialchars($u['nome'] ?? '') ?>" required></td>
                            <td><input type="text" name="cognome" class="edit_input" value="<?= htmlspecialchars($u['cognome'] ?? '') ?>" required></td>
                            <td><input type="text" name="codice_fiscale" class="edit_input" value="<?= htmlspecialchars($u['codice_fiscale'] ?? '') ?>" maxlength="16" required></td>
                            <td><input type="email" name="email" class="edit_input" value="<?= htmlspecialchars($u['email'] ?? '') ?>" required></td>

                            <td><input type="password" name="password_hash" class="edit_input" placeholder="*****" value="*****"></td>

                            <td style="text-align: center;">
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    
                                    <button type="button" class="btn_action btn_manage" onclick="openDrawer('drawer_<?= $u['codice_alfanumerico'] ?>')">
                                        Opzioni
                                    </button>

                                    <div id="drawer_<?= $u['codice_alfanumerico'] ?>" class="user-settings-drawer">
                                        <div class="drawer-header">
                                            <h3 class="drawer-title">Dettagli & Ruoli</h3>
                                            <button type="button" class="drawer-close" onclick="closeAllDrawers()">&times;</button>
                                        </div>
                                        
                                        <div class="drawer-body" style="text-align: left;">
                                            <div class="drawer-section">
                                                <h4>Stato Account</h4>
                                                <div class="check-row"><label>Login Bloccato</label><input type="checkbox" name="login_bloccato_check" value="1" <?= !empty($u['login_bloccato']) ? 'checked' : '' ?>></div>
                                                <div class="check-row"><label>Account Bloccato</label><input type="checkbox" name="account_bloccato" value="1" <?= !empty($u['account_bloccato']) ? 'checked' : '' ?>></div>
                                                <div class="check-row"><label>Affidabile</label><input type="checkbox" name="affidabile" value="1" <?= !empty($u['affidabile']) ? 'checked' : '' ?>></div>
                                                <div class="check-row"><label>Email Confermata</label><input type="checkbox" name="email_confermata" value="1" <?= !empty($u['email_confermata']) ? 'checked' : '' ?>></div>
                                            </div>
                                            <div class="drawer-section">
                                                <h4>Ruoli</h4>
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                                    <label><input type="checkbox" name="ruolo0" value="1" <?= !empty($u['studente']) ? 'checked' : '' ?>> Studente</label>
                                                    <label><input type="checkbox" name="ruolo1" value="1" <?= !empty($u['docente']) ? 'checked' : '' ?>> Docente</label>
                                                    <label><input type="checkbox" name="ruolo2" value="1" <?= !empty($u['bibliotecario']) ? 'checked' : '' ?>> Bibliotecario</label>
                                                    <label><input type="checkbox" name="ruolo3" value="1" <?= !empty($u['amministratore']) ? 'checked' : '' ?>> Admin</label>
                                                </div>
                                            </div>
                                            <div class="drawer-section">
                                                <h4>Extra</h4>
                                                <div class="form_group"><label class="form_label" style="font-size:0.85rem;">Livello Privato</label><input type="number" name="livello_privato" class="edit_input" value="<?= htmlspecialchars($u['livello_privato'] ?? 0) ?>" min="0" max="10"></div>
                                                <div class="form_group" style="margin-top:10px;"><label class="form_label" style="font-size:0.85rem;">Data Creazione</label><input type="datetime-local" name="data_creazione" class="edit_input" style="font-size: 0.8rem;" value="<?= isset($u['data_creazione']) ? date('Y-m-d\TH:i', strtotime($u['data_creazione'])) : '' ?>" required></div>
                                            </div>
                                        </div>
                                    </div> 
                                    <input type="hidden" name="edit_id" value="<?= htmlspecialchars($u['codice_alfanumerico'] ?? '') ?>">
                                    <button type="submit" class="btn_action btn_save trigger_loader">Salva</button>

                        </form> <form method="POST" style="width:100%;" onsubmit="return confirm('ATTENZIONE: Verranno eliminate anche tutte le recensioni di questo utente.\n\nConfermi eliminazione?')">
                                        <input type="hidden" name="delete_id" value="<?= htmlspecialchars($u['codice_alfanumerico'] ?? '') ?>">
                                        <button type="submit" class="btn_action btn_delete trigger_loader">Elimina</button>
                                    </form>

                                </div>
                            </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleAddForm() {
            var x = document.getElementById("add_user_section");
            x.style.display = (x.style.display === "block") ? "none" : "block";
        }

        function openDrawer(id) {
            closeAllDrawers();
            document.getElementById('drawerBackdrop').style.display = 'block';
            setTimeout(() => { document.getElementById(id).classList.add('active'); }, 10);
        }

        function closeAllDrawers() {
            document.querySelectorAll('.user-settings-drawer').forEach(el => { el.classList.remove('active'); });
            document.getElementById('drawerBackdrop').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('loading_overlay');
            if(overlay) {
                document.querySelectorAll('.trigger_loader').forEach(btn => {
                    btn.addEventListener('click', () => overlay.style.display = 'flex');
                });
                document.querySelectorAll('.form_spam_protect').forEach(form => {
                    form.addEventListener('submit', () => overlay.style.display = 'flex');
                });
                document.querySelectorAll('.form_spam_protect_row').forEach(form => {
                    form.addEventListener('submit', () => overlay.style.display = 'flex');
                });
            }
        });
    </script>

<?php require_once './src/includes/footer.php'; ?>