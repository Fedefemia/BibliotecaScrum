<?php
require_once 'security.php';

// Controllo accessi
if (!checkAccess('amministratore') && !checkAccess('bibliotecario')) {
    header('Location: /login');
    exit;
}

require_once 'db_config.php';

// Gestione Ricerca
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';

$prestitiAttivi = [];
if (isset($pdo)) {
    try {
        // Logica per processare la restituzione
        if (isset($_POST['restituisci_id'])) {
            $data_scelta = !empty($_POST['data_fine']) ? $_POST['data_fine'] : date('Y-m-d');
            $stmt = $pdo->prepare("UPDATE prestiti SET data_restituzione = :data WHERE id_prestito = :id");
            $stmt->execute([
                    'data' => $data_scelta,
                    'id' => $_POST['restituisci_id']
            ]);

            // ROUTING AGGIORNATO (Whitelist)
            header("Location: /bibliotecario/dashboard-gestioneprestiti?success=1");
            exit;
        }

        // Costruzione Query con Ricerca
        $query = "SELECT 
                    p.id_prestito, 
                    u.nome, 
                    u.cognome, 
                    u.codice_alfanumerico,
                    l.titolo, 
                    p.id_copia, 
                    p.data_prestito, 
                    p.data_scadenza, 
                    p.num_rinnovi,
                    DATEDIFF(p.data_scadenza, CURDATE()) as giorni_rimanenti,
                    (SELECT COUNT(*) FROM multe m WHERE m.id_prestito = p.id_prestito AND m.pagata = 0) as multe_pendenti
                  FROM prestiti p
                  JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
                  JOIN copie c ON p.id_copia = c.id_copia
                  JOIN libri l ON c.isbn = l.isbn
                  WHERE p.data_restituzione IS NULL";

        $params = [];
        if (!empty($searchTerm)) {
            $query .= " AND (u.nome LIKE :q OR u.cognome LIKE :q OR u.codice_alfanumerico LIKE :q OR l.titolo LIKE :q)";
            $params['q'] = "%$searchTerm%";
        }

        $query .= " ORDER BY p.data_scadenza ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $prestitiAttivi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errorMsg = "Errore: " . $e->getMessage();
    }
}

$title = "Gestione Prestiti";
$path = "../../"; 
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --accent-primary: #3f5135;
            --bg-main: #faf7f0;
        }
        body { background-color: var(--bg-main); font-family: 'Instrument Sans', sans-serif; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .table-custom th { background-color: var(--accent-primary); color: white; font-weight: 500; }
        .badge-status { font-size: 0.8rem; padding: 6px 10px; border-radius: 8px; }
        .bg-warning-soft { background-color: #fff3cd; color: #856404; }
        .bg-danger-soft { background-color: #f8d7da; color: #721c24; }
        .bg-success-soft { background-color: #d4edda; color: #155724; }
    </style>
</head>
<body>

<div class="container-fluid py-4 px-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1" style="color: #2c2c2c;">Gestione Prestiti Attivi</h2>
            <p class="text-muted mb-0">Gestisci restituzioni e sanzioni.</p>
        </div>
        
        <div class="d-flex gap-2">
            <form method="GET" action="/bibliotecario/dashboard-gestioneprestiti" class="d-flex">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Cerca utente o libro..." value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="btn btn-outline-secondary" type="submit">Cerca</button>
                </div>
                <?php if(!empty($searchTerm)): ?>
                    <a href="/bibliotecario/dashboard-gestioneprestiti" class="btn btn-light ms-2" title="Reset"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </form>

            <a href="/bibliotecario/dashboard-aggiuntaprestiti" class="btn btn-primary shadow-sm" style="background-color: var(--accent-primary); border:none;">
                <i class="bi bi-plus-lg me-2"></i>Nuovo Prestito
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Operazione completata con successo!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th class="ps-4">Libro / Copia</th>
                            <th>Utente</th>
                            <th>Date</th>
                            <th>Stato</th>
                            <th class="text-end pe-4">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($prestitiAttivi)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-3 opacity-25"></i>
                                Nessun prestito trovato.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($prestitiAttivi as $p): ?>
                            <?php 
                                $giorni = $p['giorni_rimanenti'];
                                $statusClass = 'bg-success-soft';
                                $statusText = 'Regolare';
                                
                                if ($giorni < 0) {
                                    $statusClass = 'bg-danger-soft';
                                    $statusText = 'Scaduto (' . abs($giorni) . 'gg)';
                                } elseif ($giorni <= 3) {
                                    $statusClass = 'bg-warning-soft';
                                    $statusText = 'In scadenza';
                                }
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['titolo']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-upc-scan"></i> ID: <?= $p['id_copia'] ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($p['cognome'] . ' ' . $p['nome']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($p['codice_alfanumerico']) ?></div>
                                </td>
                                <td>
                                    <div class="small text-muted">Dal: <?= date('d/m/Y', strtotime($p['data_prestito'])) ?></div>
                                    <div class="fw-bold text-dark">Al: <?= date('d/m/Y', strtotime($p['data_scadenza'])) ?></div>
                                </td>
                                <td>
                                    <span class="badge-status <?= $statusClass ?> d-inline-block mb-1">
                                        <?= $statusText ?>
                                    </span>
                                    <?php if ($p['multe_pendenti'] > 0): ?>
                                        <br><span class="badge bg-danger text-white rounded-pill" style="font-size:0.7rem;">
                                            <i class="bi bi-exclamation-circle"></i> Multe attive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2 align-items-center">
                                        
                                        <a href="/bibliotecario/gestione-multe?id_prestito=<?= $p['id_prestito'] ?>" class="btn btn-sm btn-outline-warning text-dark fw-bold" title="Gestisci Multe">
                                            <i class="bi bi-cash-coin me-1"></i> Multe
                                        </a>

                                        <form method="POST" class="d-flex align-items-center bg-light rounded p-1 border">
                                            <input type="date" name="data_fine" value="<?= date('Y-m-d') ?>" class="form-control form-control-sm border-0 bg-transparent" style="width: 110px;" title="Data restituzione">
                                            <input type="hidden" name="restituisci_id" value="<?= $p['id_prestito'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success text-white fw-bold ms-1" onclick="return confirm('Confermi la restituzione?')">
                                                <i class="bi bi-box-arrow-in-down-left"></i> Restituisci
                                            </button>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once './src/includes/footer.php'; ?>
</body>
</html>