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
$page_css = "../public/css/style_dashboards.css";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <div class="page_contents">
        <div class="page_header">
            <h2 class="page_title">Gestione Sanzioni</h2>
        </div>

        <?php if ($messaggio_db): ?>
            <div class="alert_msg <?= $class_messaggio === 'error' ? 'alert_error' : 'alert_success' ?>">
                <?= htmlspecialchars($messaggio_db) ?>
            </div>
        <?php endif; ?>

        <div class="kpi_grid">
            <div class="kpi_card">
                <div class="kpi_header">
                    <div class="kpi_label">Da Incassare</div>
                </div>
                <div class="kpi_value">€ <?= number_format($stats['incasso'], 2) ?></div>
            </div>
            <div class="kpi_card">
                <div class="kpi_header">
                    <div class="kpi_label">Multe Non Pagate</div>
                </div>
                <div class="kpi_value"><?= $stats['attive'] ?></div>
            </div>
        </div>

        <div class="add_form_wrapper" id="emetti_multa_wrapper">
            <div class="w-100">
                <h3 class="chart_title" id="titolo_emetti_multa">Emetti Multa Manuale</h3>
                <form method="POST" class="form_grid_multe">
                    <div class="form_group">
                        <label class="form_label">Codice Alfanumerico</label>
                        <input type="text" name="codice_alfanumerico" required placeholder="Es. 000008" class="edit_input">
                    </div>
                    <div class="form_group">
                        <label class="form_label">Importo (€)</label>
                        <input type="number" step="0.01" name="importo" required class="edit_input">
                    </div>
                    <div class="form_group" id="causale_group">
                        <label class="form_label">Causale Dettagliata</label>
                        <input type="text" name="causale" required placeholder="Es. Libro restituito con pagine mancanti" class="edit_input">
                    </div>
                    <div class="form_group align_bottom">
                        <button type="submit" name="add_multa" class="btn_action btn_save w-100">Emetti Sanzione</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="search_bar_container">
            <form method="GET" class="search_form_multe">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cerca in causale..." class="search_input">
                <select name="stato" class="edit_input select_stato">
                    <option value="">Tutti gli stati</option>
                    <option value="non_pagata" <?= $filterStato==='non_pagata'?'selected':'' ?>>Pendente</option>
                    <option value="pagata" <?= $filterStato==='pagata'?'selected':'' ?>>Pagata</option>
                </select>
                <button type="submit" class="btn_action btn_search">Filtra</button>
            </form>
        </div>

        <div class="table_card">
            <div class="table_responsive">
                <table class="admin_table">
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
                        <tr><td colspan="5" class="text_center empty_td">Nessun record trovato.</td></tr>
                    <?php else: ?>
                        <?php foreach ($multe as $m): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($m['data_creata'])) ?></td>
                                <td class="text_muted"><?= htmlspecialchars($m['causale']) ?></td>
                                <td class="text_bold">€ <?= number_format($m['importo'], 2) ?></td>
                                <td>
                                <span class="badge_status <?= $m['pagata'] ? 'badge_paid' : 'badge_unpaid' ?>">
                                    <?= $m['pagata'] ? 'PAGATA' : 'DA PAGARE' ?>
                                </span>
                                </td>
                                <td>
                                    <div class="action_buttons_row">
                                        <?php if (!$m['pagata']): ?>
                                            <form method="POST" class="inline_form">
                                                <input type="hidden" name="pay_id" value="<?= $m['id_multa'] ?>">
                                                <button type="submit" class="btn_action btn_save btn_small">Salda</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="inline_form" onsubmit="return confirm('Eliminare questa sanzione?')">
                                            <input type="hidden" name="delete_id" value="<?= $m['id_multa'] ?>">
                                            <button type="submit" class="btn_action btn_delete btn_small">Elimina</button>
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

<?php require_once './src/includes/footer.php'; ?>