<?php
require_once 'security.php';

if (!checkAccess('amministratore') && !checkAccess('bibliotecario')) {
    header('Location: ./');
    exit;
}

require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$messaggio_db = "";
$class_messaggio = "";

// Paginazione e filtri
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = $_GET['search'] ?? '';
$filterStato = $_GET['stato'] ?? '';

// --- GESTIONE AZIONI POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // EMISSIONE MULTA
    if (isset($_POST['add_multa'])) {
        try {
            $pdo->beginTransaction();

            $codice = strtoupper(trim($_POST['codice_alfanumerico']));
            $importo = $_POST['importo'];
            $causale = trim($_POST['causale']);

            // Verifica esistenza utente
            $stmtUser = $pdo->prepare("SELECT username FROM utenti WHERE codice_alfanumerico = ?");
            $stmtUser->execute([$codice]);
            $user = $stmtUser->fetch();

            if (!$user) {
                throw new Exception("Utente $codice non trovato nel sistema.");
            }

            // Inserimento Multa (id_prestito ora è NULL grazie all'ALTER TABLE)
            $stmt = $pdo->prepare("INSERT INTO multe (id_prestito, importo, causale, data_creata, pagata) VALUES (NULL, ?, ?, CURDATE(), 0)");
            $stmt->execute([$importo, "[$codice] " . $causale]);

            // Inserimento Notifica
            $path = "../";
            $link = $path . "profilo";
            $msgNotifica = "Ti è stata assegnata una sanzione di €$importo. Motivo: $causale";
            
            $stmtNotifica = $pdo->prepare("INSERT INTO notifiche (codice_alfanumerico, titolo, messaggio, link_riferimento, tipo, dataora_invio) VALUES (?, 'Sanzione Amministrativa', ?, ?, 'sanzione', NOW())");
            $stmtNotifica->execute([$codice, $msgNotifica, $link]);

            $pdo->commit();
            $_SESSION['messaggio'] = "Sanzione inviata con successo a " . $user['username'];
            $_SESSION['tipo_messaggio'] = "success";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['messaggio'] = "Errore: " . $e->getMessage();
            $_SESSION['tipo_messaggio'] = "error";
        }
        header("Location: dashboard-multe");
        exit;
    }

    // ELIMINA MULTA
    if (isset($_POST['delete_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM multe WHERE id_multa = ?");
            $stmt->execute([$_POST['delete_id']]);
            $_SESSION['messaggio'] = "Multa eliminata correttamente.";
            $_SESSION['tipo_messaggio'] = "success";
        } catch (PDOException $e) {
            $_SESSION['messaggio'] = "Errore eliminazione.";
            $_SESSION['tipo_messaggio'] = "error";
        }
        header("Location: dashboard-multe?page=$page");
        exit;
    }

    // SEGNA PAGATA
    if (isset($_POST['pay_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE multe SET pagata = 1 WHERE id_multa = ?");
            $stmt->execute([$_POST['pay_id']]);
            $_SESSION['messaggio'] = "Multa segnata come pagata.";
            $_SESSION['tipo_messaggio'] = "success";
        } catch (PDOException $e) {
            $_SESSION['messaggio'] = "Errore aggiornamento.";
            $_SESSION['tipo_messaggio'] = "error";
        }
        header("Location: dashboard-multe?page=$page");
        exit;
    }
}

// Recupero messaggi
if (isset($_SESSION['messaggio'])) {
    $messaggio_db = $_SESSION['messaggio'];
    $class_messaggio = $_SESSION['tipo_messaggio'];
    unset($_SESSION['messaggio'], $_SESSION['tipo_messaggio']);
}

// Statistiche
$stats = ['incasso' => 0, 'attive' => 0];
try {
    $stats['incasso'] = $pdo->query("SELECT SUM(importo) FROM multe WHERE pagata = 0")->fetchColumn() ?: 0;
    $stats['attive'] = $pdo->query("SELECT COUNT(*) FROM multe WHERE pagata = 0")->fetchColumn();
} catch (PDOException $e) {}

// Query Risultati
$where = [];
$params = [];
if (!empty($search)) {
    $where[] = "m.causale LIKE ?";
    $params[] = "%$search%";
}
if ($filterStato === 'pagata') $where[] = "m.pagata = 1";
if ($filterStato === 'non_pagata') $where[] = "m.pagata = 0";

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

try {
    $sql = "SELECT m.* FROM multe m $whereSQL ORDER BY m.data_creata DESC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $multe = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $multe = []; }

$title = "Dashboard Multe";
$path = "../";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

<style>
    .page-title { font-family: "Young Serif", serif; font-size: 2.5rem; color: #333; margin: 1em 0; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
    .stat-card { background: transparent; color: #2c2c2c; border: solid 3px #2c2c2c; padding: 20px; border-radius: 12px; text-align: center; }
    .stat-value { font-family: "Young Serif", serif; font-size: 1.5rem; font-weight: bold; }
    .stat-label { font-family: "Young Serif", serif; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }

    .box-action { background: #faf9f6; padding: 25px; border-radius: 12px; border: 1px solid #eae3d2; font-family: "Instrument Sans", sans-serif; margin-bottom: 25px; }
    
    .table-wrapper { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .reviews-table { width: 100%; border-collapse: collapse; }
    .reviews-table thead { background-color: #eae3d2; }
    .reviews-table th { padding: 15px; text-align: left; font-family: "Young Serif", serif; color: #2c2c2c; }
    .reviews-table td { padding: 15px; border-bottom: 1px solid #f5f5f5; font-family: "Instrument Sans", sans-serif; }

    .btn { padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: "Instrument Sans", sans-serif; text-decoration: none; display: inline-block; font-size: 0.85rem; }
    .btn-green { background: #3f5135; color: white; }
    .btn-pay { background: #27ae60; color: white; }
    .btn-red { background: #e74c3c; color: white; }

    input, select { padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: "Instrument Sans", sans-serif; }
    .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }
    .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-family: "Instrument Sans", sans-serif; font-weight: 600; }
</style>

<div class="page_contents">
    <h2 class="page-title">Gestione Sanzioni</h2>

    <?php if ($messaggio_db): ?>
        <div class="alert" style="background: <?= $class_messaggio === 'error' ? '#f8d7da' : '#d4edda' ?>; color: <?= $class_messaggio === 'error' ? '#721c24' : '#155724' ?>;">
            <?= htmlspecialchars($messaggio_db) ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Da Incassare</div>
            <div class="stat-value">€ <?= number_format($stats['incasso'], 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Multe Pendenti</div>
            <div class="stat-value"><?= $stats['attive'] ?></div>
        </div>
    </div>

    <div class="box-action">
        <h3 style="font-family: 'Young Serif', serif; margin-top:0; margin-bottom: 20px;">Emetti Multa Manuale</h3>
        <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: flex-end;">
            <div>
                <label style="display:block; font-size: 0.8rem; font-weight: bold; margin-bottom: 5px;">Codice Alfanumerico</label>
                <input type="text" name="codice_alfanumerico" required placeholder="Es. 000008" style="width:100%;">
            </div>
            <div>
                <label style="display:block; font-size: 0.8rem; font-weight: bold; margin-bottom: 5px;">Importo (€)</label>
                <input type="number" step="0.01" name="importo" required style="width:100%;">
            </div>
            <div style="grid-column: span 2;">
                <label style="display:block; font-size: 0.8rem; font-weight: bold; margin-bottom: 5px;">Causale Dettagliata</label>
                <input type="text" name="causale" required placeholder="Es. Libro restituito con pagine mancanti" style="width:100%;">
            </div>
            <button type="submit" name="add_multa" class="btn btn-green">Emetti Sanzione</button>
        </form>
    </div>

    <div class="box-action" style="padding: 15px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
            <div style="flex: 1;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cerca in causale..." style="width: 100%;">
            </div>
            <select name="stato">
                <option value="">Tutti gli stati</option>
                <option value="non_pagata" <?= $filterStato==='non_pagata'?'selected':'' ?>>Pendente</option>
                <option value="pagata" <?= $filterStato==='pagata'?'selected':'' ?>>Pagata</option>
            </select>
            <button type="submit" class="btn" style="background: #2c2c2c; color: white;">Filtra</button>
        </form>
    </div>

    <div class="table-wrapper">
        <table class="reviews-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Causale (Codice Utente)</th>
                    <th>Importo</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($multe)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 40px; color: #888;">Nessun record trovato.</td></tr>
                <?php else: ?>
                    <?php foreach ($multe as $m): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($m['data_creata'])) ?></td>
                        <td style="color: #444;"><?= htmlspecialchars($m['causale']) ?></td>
                        <td style="font-weight:bold;">€ <?= number_format($m['importo'], 2) ?></td>
                        <td>
                            <span class="badge" style="background: <?= $m['pagata']?'#d4edda':'#f8d7da' ?>; color: <?= $m['pagata']?'#155724':'#721c24' ?>;">
                                <?= $m['pagata'] ? 'PAGATA' : 'DA PAGARE' ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!$m['pagata']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="pay_id" value="<?= $m['id_multa'] ?>">
                                    <button type="submit" class="btn btn-pay">Salda</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminare questa sanzione?')">
                                <input type="hidden" name="delete_id" value="<?= $m['id_multa'] ?>">
                                <button type="submit" class="btn btn-red">Elimina</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once './src/includes/footer.php'; ?>