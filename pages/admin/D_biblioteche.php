<?php
require_once 'security.php';
require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica accesso amministratore
if (!checkAccess('amministratore')) {
    header('Location: /');
    exit;
}

$messaggio_db = "";
$class_messaggio = "";
$biblioteche = [];

// Verifica connessione database
if (!isset($pdo)) {
    die("Connessione al Database non riuscita");
}

// --- OPERAZIONI CRUD ---
try {
    // ELIMINA
    if (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM biblioteche WHERE id = :id");
        $stmt->execute(['id' => $_POST['delete_id']]);
        header("Location: "."dashboard-biblioteche");
        exit;
    }

    // MODIFICA
    if (isset($_POST['edit_id']) && !isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("
            UPDATE biblioteche 
            SET nome = :nome, indirizzo = :indirizzo, lat = :lat, lon = :lon, orari = :orari
            WHERE id = :id
        ");
        $stmt->execute([
                'nome' => trim($_POST['nome']),
                'indirizzo' => trim($_POST['indirizzo']),
                'lat' => trim($_POST['lat']),
                'lon' => trim($_POST['lon']),
                'orari' => !empty(trim($_POST['orari'])) ? trim($_POST['orari']) : null,
                'id' => $_POST['edit_id']
        ]);
        header("Location: "."dashboard-biblioteche");
        exit;
    }

    // INSERISCI
    if (isset($_POST['inserisci'])) {
        $stmt = $pdo->prepare("
            INSERT INTO biblioteche (nome, indirizzo, lat, lon, orari)
            VALUES (:nome, :indirizzo, :lat, :lon, :orari)
        ");
        $stmt->execute([
                'nome' => trim($_POST['nome']),
                'indirizzo' => trim($_POST['indirizzo']),
                'lat' => trim($_POST['lat']),
                'lon' => trim($_POST['lon']),
                'orari' => !empty(trim($_POST['orari'])) ? trim($_POST['orari']) : null
        ]);
        header("Location: "."dashboard-biblioteche");
        exit;
    }

    // Recupera tutte le biblioteche
    $stmt = $pdo->prepare("SELECT * FROM biblioteche ORDER BY nome");
    $stmt->execute();
    $biblioteche = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $messaggio_db = "Errore database: " . $e->getMessage();
    $class_messaggio = "error";
}

// ---------------- HTML HEADER ----------------
$path = "../";
$title = "Biblioteche - Dashboard";
$page_css = "../public/css/style_dashboards.css";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

<div class="dashboard_container">

    <div class="page_header">
        <h2 class="page_title">Gestione Biblioteche</h2>
        <div class="header_actions">
            <button onclick="toggleAddForm()" class="btn_action btn_save">+ Nuova Biblioteca</button>
        </div>
    </div>

    <?php if ($messaggio_db): ?>
        <div class="alert_msg <?= $class_messaggio == 'error' ? 'alert_error' : 'alert_success' ?>">
            <?= htmlspecialchars($messaggio_db) ?>
        </div>
    <?php endif; ?>

    <div id="add_library_section" class="add_book_section">
        <form method="post" class="add_form_wrapper form_spam_protect">
            <input type="hidden" name="inserisci" value="1">

            <div class="form_group">
                <label class="form_label">Nome Biblioteca</label>
                <input type="text" name="nome" class="edit_input" required placeholder="Es. Biblioteca Centrale">
            </div>

            <div class="form_group">
                <label class="form_label">Indirizzo</label>
                <input type="text" name="indirizzo" class="edit_input" required placeholder="Via Roma 1">
            </div>

            <div class="form_group short">
                <label class="form_label">Latitudine</label>
                <input type="number" step="any" name="lat" class="edit_input" required placeholder="45.123">
            </div>

            <div class="form_group short">
                <label class="form_label">Longitudine</label>
                <input type="number" step="any" name="lon" class="edit_input" required placeholder="11.456">
            </div>

            <div class="form_group">
                <label class="form_label">Orari</label>
                <input type="text" name="orari" class="edit_input" placeholder="Lun-Ven 9-18">
            </div>

            <button type="submit" class="btn_action btn_save trigger_loader" style="margin-bottom: 5px;">Inserisci</button>
        </form>
    </div>

    <div class="table_card">
        <div class="table_responsive">
            <table class="admin_table" style="min-width: 900px;">
                <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Nome</th>
                    <th>Indirizzo</th>
                    <th style="width: 100px;">Lat</th>
                    <th style="width: 100px;">Lon</th>
                    <th>Orari</th>
                    <th style="width: 180px; text-align: center;">Azioni</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($biblioteche)): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 30px;">Nessuna biblioteca presente.</td></tr>
                <?php else: ?>
                <?php foreach ($biblioteche as $b): ?>
                <tr>
                    <form method="POST" class="form_spam_protect_row">
                        <td style="color: #888;"><?= htmlspecialchars($b['id']) ?></td>

                        <td>
                            <input type="text" name="nome" value="<?= htmlspecialchars($b['nome']) ?>" required class="edit_input">
                        </td>
                        <td>
                            <input type="text" name="indirizzo" value="<?= htmlspecialchars($b['indirizzo']) ?>" required class="edit_input">
                        </td>
                        <td>
                            <input type="number" step="any" name="lat" value="<?= htmlspecialchars($b['lat']) ?>" required class="edit_input">
                        </td>
                        <td>
                            <input type="number" step="any" name="lon" value="<?= htmlspecialchars($b['lon']) ?>" required class="edit_input">
                        </td>
                        <td>
                            <input type="text" name="orari" value="<?= htmlspecialchars($b['orari'] ?? '') ?>" class="edit_input">
                        </td>

                        <td style="text-align: center;">
                            <div style="display: flex; gap: 5px; justify-content: center;">
                                <input type="hidden" name="edit_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="btn_action btn_save trigger_loader" title="Salva Modifiche">Salva</button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare <?= addslashes($b['nome']) ?>?');">
                        <input type="hidden" name="delete_id" value="<?= $b['id'] ?>">
                        <button type="submit" class="btn_action btn_delete trigger_loader" title="Elimina">Elimina</button>
                    </form>
        </div>
        </td>

        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        </table>
    </div>
</div>

</div>

<script>
    // Funzione per mostrare/nascondere il form di inserimento
    function toggleAddForm() {
        var x = document.getElementById("add_library_section");
        x.style.display = (x.style.display === "block") ? "none" : "block";
    }

    // Gestione Loader
    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.getElementById('loading_overlay');

        // Listener sui bottoni con classe trigger_loader
        document.querySelectorAll('.trigger_loader').forEach(btn => {
            btn.addEventListener('click', () => overlay.style.display = 'flex');
        });

        // Listener sui form principali
        document.querySelectorAll('.form_spam_protect').forEach(form => {
            form.addEventListener('submit', () => overlay.style.display = 'flex');
        });

        // Listener sui form delle righe (per il salva)
        document.querySelectorAll('.form_spam_protect_row').forEach(form => {
            form.addEventListener('submit', () => overlay.style.display = 'flex');
        });
    });
</script>

<?php require_once './src/includes/footer.php'; ?>
