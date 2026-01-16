<?php
require_once 'security.php';

// Controllo accesso: solo amministratori e bibliotecari
if (!checkAccess('amministratore') && !checkAccess('bibliotecario')) {
    header('Location: ./');
    exit;
}

require_once 'db_config.php';

$messaggio = "";
$richieste = [];

if (isset($pdo)) {
    try {
        // QUERY: Recupera le richieste (INVARIATA)
        $stmt = $pdo->prepare("
            SELECT 
                rb.id_richiesta,
                rb.id_prestito,
                rb.tipo_richiesta,
                rb.data_richiesta,
                rb.data_scadenza_richiesta, 
                rb.stato,
                p.data_scadenza as scadenza_attuale_prestito,
                p.codice_alfanumerico,
                p.id_copia,
                u.nome,
                u.cognome,
                l.titolo,
                (SELECT COUNT(*) FROM multe m JOIN prestiti p2 ON m.id_prestito = p2.id_prestito WHERE p2.codice_alfanumerico = p.codice_alfanumerico AND m.pagata = 0) as numero_multe,
                (SELECT COALESCE(SUM(m.importo), 0) FROM multe m JOIN prestiti p2 ON m.id_prestito = p2.id_prestito WHERE p2.codice_alfanumerico = p.codice_alfanumerico AND m.pagata = 0) as totale_multe
            FROM richieste_bibliotecario rb
            JOIN prestiti p ON rb.id_prestito = p.id_prestito
            JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
            JOIN copie c ON p.id_copia = c.id_copia
            JOIN libri l ON c.isbn = l.isbn
            ORDER BY CASE WHEN rb.stato = 'in_attesa' THEN 0 ELSE 1 END, rb.data_richiesta DESC
        ");
        $stmt->execute();
        $richieste = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messaggio = "Errore caricamento dati: " . $e->getMessage();
    }
}

// GESTIONE AZIONI (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id_richiesta'])) {
        $action = $_POST['action'];
        $id_richiesta = filter_input(INPUT_POST, 'id_richiesta', FILTER_VALIDATE_INT);

        if ($id_richiesta) {
            try {
                $pdo->beginTransaction();
                $stmt_info = $pdo->prepare("SELECT id_prestito FROM richieste_bibliotecario WHERE id_richiesta = ?");
                $stmt_info->execute([$id_richiesta]);
                $req_data = $stmt_info->fetch(PDO::FETCH_ASSOC);

                if (!$req_data) throw new Exception("Richiesta non trovata.");
                $id_prestito_target = $req_data['id_prestito'];

                switch ($action) {
                    case 'approva':
                        $stmt = $pdo->prepare("UPDATE richieste_bibliotecario SET stato = 'approvata' WHERE id_richiesta = ?");
                        $stmt->execute([$id_richiesta]);
                        $stmt_upd = $pdo->prepare("UPDATE prestiti SET data_scadenza = DATE_ADD(data_scadenza, INTERVAL 7 DAY), num_rinnovi = num_rinnovi + 1 WHERE id_prestito = ?");
                        $stmt_upd->execute([$id_prestito_target]);
                        break;
                    case 'rifiuta':
                        $stmt = $pdo->prepare("UPDATE richieste_bibliotecario SET stato = 'rifiutata' WHERE id_richiesta = ?");
                        $stmt->execute([$id_richiesta]);
                        break;
                }
                $pdo->commit();
                header("Location: dashboard-richieste");
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $messaggio = "Errore durante l'operazione: " . $e->getMessage();
            }
        }
    }
}
?>

<?php
$title = "Gestione Richieste";
$path = "../";
$page_css = "../public/css/style_dashboards.css";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <div class="dashboard_container_larger">

        <div class="page_header">
            <h1 class="page_title">Gestione Richieste</h1>
            <div class="header_actions">
            </div>
        </div>

        <?php if ($messaggio): ?>
            <div style="padding: 15px; background: #f8d7da; color: #842029; border-radius: 12px; margin-bottom: 20px;">
                <?= htmlspecialchars($messaggio) ?>
            </div>
        <?php endif; ?>

        <div class="table_card">
            <div class="table_responsive">
                <table class="admin_table">
                    <thead>
                    <tr>
                        <th>Utente</th>
                        <th>Libro</th>
                        <th>Tipo Richiesta</th>
                        <th>Data Richiesta</th>
                        <th>Scadenza Attuale</th>
                        <th>Affidabilità</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($richieste)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #6c757d;">
                                <i class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                Nessuna richiesta presente al momento ✨
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($richieste as $req): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <div class="user_avatar_placeholder">
                                            <?= strtoupper(substr($req['nome'], 0, 1) . substr($req['cognome'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--color_text_black);"><?= htmlspecialchars($req['nome'] . ' ' . $req['cognome']) ?></div>
                                            <div style="font-size: 0.85rem; color: #6c757d;">ID: <?= htmlspecialchars($req['codice_alfanumerico']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-weight: 500; color: var(--color_accent_dark);">
                                    <?= htmlspecialchars($req['titolo']) ?>
                                    <div style="font-size: 0.8rem; color: #888;">Copia ID: <?= $req['id_copia'] ?></div>
                                </td>
                                <td>
                                    <span style="font-family: 'Instrument Sans', sans-serif; font-weight: 600;">
                                        <?= ucfirst($req['tipo_richiesta']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($req['data_richiesta'])) ?></td>
                                <td>
                                    <?php
                                    $scad = strtotime($req['scadenza_attuale_prestito']);
                                    $oggi = time();
                                    $color = ($scad < $oggi) ? '#dc3545' : 'inherit';
                                    $icon = ($scad < $oggi) ? '<i class="bi bi-exclamation-circle-fill"></i>' : '';
                                    ?>
                                    <span style="color: <?= $color ?>;">
                                        <?= date('d/m/Y', $scad) ?> <?= $icon ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($req['numero_multe'] > 0): ?>
                                        <span class="status_badge status_rejected" style="font-size: 0.75rem;">
                                            <?= $req['numero_multe'] ?> Multe (€<?= number_format($req['totale_multe'], 2) ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="status_badge status_approved" style="font-size: 0.75rem;">
                                            Affidabile
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = 'status_waiting';
                                    $statusLabel = 'In Attesa';

                                    if ($req['stato'] === 'approvata') {
                                        $statusClass = 'status_approved';
                                        $statusLabel = 'Approvata';
                                    } elseif ($req['stato'] === 'rifiutata') {
                                        $statusClass = 'status_rejected';
                                        $statusLabel = 'Rifiutata';
                                    }
                                    ?>
                                    <span class="status_badge <?= $statusClass ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <?php if ($req['stato'] === 'in_attesa'): ?>
                                            <form method="post" class="m-0 d-flex gap-2" style="display:flex; gap:10px;">
                                                <input type="hidden" name="id_richiesta" value="<?= $req['id_richiesta'] ?>">
                                                <button type="submit" name="action" value="approva" class="btn_action btn_accept">
                                                    <i class="bi bi-check-lg"></i> Accetta
                                                </button>
                                                <button type="submit" name="action" value="rifiuta" class="btn_action btn_reject" onclick="return confirm('Rifiutare questa richiesta?');">
                                                    <i class="bi bi-x-lg"></i> Rifiuta
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #adb5bd; font-size: 0.9rem;">--</span>
                                        <?php endif; ?>
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
        // JS per il Loading Screen
        window.addEventListener('load', function() {
            const loader = document.getElementById('loading_overlay');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }
        });
    </script>

<?php require_once './src/includes/footer.php'; ?>