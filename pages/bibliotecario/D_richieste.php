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
        // QUERY: Recupera le richieste unendo le tabelle necessarie.
        // Include subquery per calcolare il totale delle multe NON PAGATE dell'utente richiedente.
        $stmt = $pdo->prepare("
            SELECT 
                rb.id_richiesta,
                rb.id_prestito,
                rb.tipo_richiesta,
                rb.data_richiesta,
                rb.data_scadenza_richiesta, -- Data richiesta dall'utente (se presente) o NULL
                rb.stato,
                p.data_scadenza as scadenza_attuale_prestito,
                p.codice_alfanumerico,
                p.id_copia,
                u.nome,
                u.cognome,
                l.titolo,
                (
                    SELECT COUNT(*) 
                    FROM multe m 
                    JOIN prestiti p2 ON m.id_prestito = p2.id_prestito
                    WHERE p2.codice_alfanumerico = p.codice_alfanumerico 
                    AND m.pagata = 0
                ) as numero_multe,
                (
                    SELECT COALESCE(SUM(m.importo), 0) 
                    FROM multe m 
                    JOIN prestiti p2 ON m.id_prestito = p2.id_prestito
                    WHERE p2.codice_alfanumerico = p.codice_alfanumerico 
                    AND m.pagata = 0
                ) as totale_multe
            FROM richieste_bibliotecario rb
            JOIN prestiti p ON rb.id_prestito = p.id_prestito
            JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
            JOIN copie c ON p.id_copia = c.id_copia
            JOIN libri l ON c.isbn = l.isbn
            ORDER BY 
                CASE WHEN rb.stato = 'in_attesa' THEN 0 ELSE 1 END, -- Prima quelle in attesa
                rb.data_richiesta DESC
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

                // 1. Recuperiamo l'ID Prestito associato a questa richiesta
                $stmt_info = $pdo->prepare("SELECT id_prestito FROM richieste_bibliotecario WHERE id_richiesta = ?");
                $stmt_info->execute([$id_richiesta]);
                $req_data = $stmt_info->fetch(PDO::FETCH_ASSOC);

                if (!$req_data) {
                    throw new Exception("Richiesta non trovata.");
                }
                
                $id_prestito_target = $req_data['id_prestito'];

                switch ($action) {
                    case 'approva':
                        // A. Aggiorna stato richiesta
                        $stmt = $pdo->prepare("UPDATE richieste_bibliotecario SET stato = 'approvata' WHERE id_richiesta = ?");
                        $stmt->execute([$id_richiesta]);

                        // B. Estendi il prestito collegato (+7 giorni)
                        $stmt_upd = $pdo->prepare("
                            UPDATE prestiti 
                            SET data_scadenza = DATE_ADD(data_scadenza, INTERVAL 7 DAY),
                                num_rinnovi = num_rinnovi + 1
                            WHERE id_prestito = ?
                        ");
                        $stmt_upd->execute([$id_prestito_target]);
                        break;

                    case 'rifiuta':
                        // A. Aggiorna solo lo stato richiesta
                        $stmt = $pdo->prepare("UPDATE richieste_bibliotecario SET stato = 'rifiutata' WHERE id_richiesta = ?");
                        $stmt->execute([$id_richiesta]);
                        break;
                }

                $pdo->commit();
                // Ricarica la pagina per evitare resubmission
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
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

<div class="page_contents">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestione Richieste (Rinnovi)</h2>
    </div>
    
    <?php if ($messaggio): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($messaggio) ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
            <tr>
                <th>ID Req</th>
                <th>Utente</th>
                <th>Prestito (Libro)</th>
                <th>Data Richiesta</th>
                <th>Scadenza Attuale</th>
                <th>Stato</th>
                <th>Situazione Multe</th>
                <th class="text-end">Azioni</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($richieste)): ?>
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">Nessuna richiesta trovata</td>
                </tr>
            <?php else: ?>
                <?php foreach ($richieste as $req): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($req['id_richiesta']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($req['cognome'] . ' ' . $req['nome']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($req['codice_alfanumerico']) ?></small>
                        </td>
                        <td>
                            #<?= htmlspecialchars($req['id_prestito']) ?> - <em><?= htmlspecialchars($req['titolo']) ?></em>
                        </td>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($req['data_richiesta']))) ?></td>
                        <td>
                            <?= htmlspecialchars(date('d/m/Y', strtotime($req['scadenza_attuale_prestito']))) ?>
                        </td>
                        <td>
                            <?php 
                            $badgeClass = 'bg-secondary';
                            $statoLabel = ucfirst(str_replace('_', ' ', $req['stato']));
                            
                            if($req['stato'] == 'approvata') {
                                $badgeClass = 'bg-success';
                            } elseif($req['stato'] == 'rifiutata') {
                                $badgeClass = 'bg-danger';
                            } elseif($req['stato'] == 'in_attesa') {
                                $badgeClass = 'bg-warning text-dark';
                            }
                            ?>
                            <span class="badge rounded-pill <?= $badgeClass ?>">
                                <?= htmlspecialchars($statoLabel) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($req['numero_multe'] > 0): ?>
                                <span class="text-danger fw-bold" title="L'utente ha multe non pagate">
                                    <i class="bi bi-exclamation-triangle-fill"></i> 
                                    <?= $req['numero_multe'] ?> (€<?= number_format($req['totale_multe'], 2, ',', '.') ?>)
                                </span>
                            <?php else: ?>
                                <span class="text-success"><i class="bi bi-check-circle"></i> Regolare</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($req['stato'] === 'in_attesa'): ?>
                                <form method="post" style="display: inline-block;">
                                    <input type="hidden" name="id_richiesta" value="<?= htmlspecialchars($req['id_richiesta']) ?>">
                                    
                                    <button type="submit" name="action" value="approva" class="btn btn-sm btn-success" title="Approva Estensione (+7gg)">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    
                                    <button type="submit" name="action" value="rifiuta" class="btn btn-sm btn-danger" title="Rifiuta" onclick="return confirm('Sei sicuro di voler rifiutare questa richiesta?');">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">Chiusa</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


    <style>
        th, td {
            padding: 15px;
            border: solid 1px black;
        }
    </style>


<?php require_once './src/includes/footer.php'; ?>