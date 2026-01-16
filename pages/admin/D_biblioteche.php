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

// Impostazioni per header e navbar
$title = "Dashboard Biblioteche";
$path = "../";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <!-- INIZIO DEL BODY -->
    <div class="page_contents">

        <?php if ($messaggio_db): ?>
            <div class="message <?= $class_messaggio ?>">
                <?= htmlspecialchars($messaggio_db) ?>
            </div>
        <?php endif; ?>

        <h2>Gestione Biblioteche</h2>

        <!-- Form inserimento nuova biblioteca -->
        <h3>Inserisci nuova biblioteca</h3>
        <form method="post">
            <table>
                <tr>
                    <th>Nome</th>
                    <th>Indirizzo</th>
                    <th>Latitudine</th>
                    <th>Longitudine</th>
                    <th>Orari</th>
                    <th>Azioni</th>
                </tr>
                <tr>
                    <td>
                        <input type="text" name="nome" required>
                    </td>
                    <td>
                        <input type="text"  name="indirizzo" required>
                    </td>
                    <td>
                        <input type="number"  name="lat" required>
                    </td>
                    <td>
                        <input type="number" name="lon" required>
                    </td>
                    <td>
                        <input type="text"   name="orari">
                    </td>
                    <td>
                        <input type="hidden" name="inserisci" value="1">
                        <button type="submit">Inserisci</button>
                    </td>
                </tr>
            </table>
        </form>

        <!-- Elenco biblioteche esistenti -->
        <h3>Biblioteche esistenti</h3>

        <?php if (empty($biblioteche)): ?>
            <p>Nessuna biblioteca presente nel database.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Indirizzo</th>
                    <th>Latitudine</th>
                    <th>Longitudine</th>
                    <th>Orari</th>
                    <th>Azioni</th>
                </tr>
                <?php foreach ($biblioteche as $b): ?>
                    <tr>
                        <form method="POST">
                            <td><?= htmlspecialchars($b['id']) ?></td>
                            <td>
                                <input type="text" name="nome" value="<?= htmlspecialchars($b['nome']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="indirizzo" value="<?= htmlspecialchars($b['indirizzo']) ?>" required>
                            </td>
                            <td>
                                <input type="number" name="lat" value="<?= htmlspecialchars($b['lat']) ?>" required>
                            </td>
                            <td>
                                <input type="number" name="lon" value="<?= htmlspecialchars($b['lon']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="orari" value="<?= htmlspecialchars($b['orari'] ?? '') ?>"  >
                            </td>
                            <td>
                                <input type="hidden" name="edit_id" value="<?= $b['id'] ?>">
                                <button type="submit">Salva</button>
                        </form>

                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?= $b['id'] ?>">
                            <button type="submit" onclick="return confirm('Eliminare <?= htmlspecialchars($b['nome']) ?>?')">
                                Elimina
                            </button>
                        </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

    </div>

<?php require_once './src/includes/footer.php'; ?>

<style>
    th, td {
        padding: 15px;
        border: solid 1px black;
    }
</style>3
