<?php
require_once 'security.php';

// Controllo accessi
if (!checkAccess('amministratore') && !checkAccess('bibliotecario')) {
    header('Location: ../index.php');
    exit;
}

require_once 'db_config.php';

$id_prestito = filter_input(INPUT_GET, 'id_prestito', FILTER_VALIDATE_INT);
$messaggio = "";
$tipo_messaggio = ""; // success o danger
$prestitoInfo = null;
$multe = [];

if (!$id_prestito) {
    header("Location: dashboard-gestioneprestiti.php");
    exit;
}

try {
    // 1. Recupera Info Prestito
    $stmt = $pdo->prepare("
        SELECT p.id_prestito, u.nome, u.cognome, u.codice_alfanumerico, l.titolo, p.data_prestito, p.data_scadenza, p.data_restituzione
        FROM prestiti p
        JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
        JOIN copie c ON p.id_copia = c.id_copia
        JOIN libri l ON c.isbn = l.isbn
        WHERE p.id_prestito = ?
    ");
    $stmt->execute([$id_prestito]);
    $prestitoInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prestitoInfo) {
        die("Prestito non trovato.");
    }

    // 2. Gestione POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Aggiungi Multa
        if (isset($_POST['add_multa'])) {
            $importo = filter_input(INPUT_POST, 'importo', FILTER_VALIDATE_FLOAT);
            $causale = trim($_POST['causale']);
            
            if ($importo && $causale) {
                $ins = $pdo->prepare("INSERT INTO multe (id_prestito, importo, causale, pagata, data_creata) VALUES (?, ?, ?, 0, CURDATE())");
                $ins->execute([$id_prestito, $importo, $causale]);
                $messaggio = "Multa registrata con successo.";
                $tipo_messaggio = "success";
            } else {
                $messaggio = "Dati non validi. Controlla importo e causale.";
                $tipo_messaggio = "danger";
            }
        }

        // Segna come Pagata
        if (isset($_POST['paga_multa_id'])) {
            $id_multa = $_POST['paga_multa_id'];
            $upd = $pdo->prepare("UPDATE multe SET pagata = 1 WHERE id_multa = ? AND id_prestito = ?");
            $upd->execute([$id_multa, $id_prestito]);
            $messaggio = "Multa segnata come pagata.";
            $tipo_messaggio = "success";
        }
    }

    // 3. Recupera Lista Multe
    $stmtM = $pdo->prepare("SELECT * FROM multe WHERE id_prestito = ? ORDER BY data_creata DESC");
    $stmtM->execute([$id_prestito]);
    $multe = $stmtM->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $messaggio = "Errore DB: " . $e->getMessage();
    $tipo_messaggio = "danger";
}

$title = "Gestione Multe";
$path = "../";
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
            --bg-main: #faf7f0;
            --accent-primary: #3f5135;
        }
        body { background-color: var(--bg-main); font-family: 'Instrument Sans', sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .bg-light-custom { background-color: #ffffff; }
    </style>
</head>
<body>

<div class="container py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold" style="color: #2c2c2c;">
            <i class="bi bi-cash-coin me-2 text-warning"></i>Gestione Multe
        </h2>
        <a href="dashboard-gestioneprestiti.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>Torna ai Prestiti
        </a>
    </div>

    <?php if ($messaggio): ?>
        <div class="alert alert-<?= $tipo_messaggio ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
            <?= htmlspecialchars($messaggio) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4 bg-white">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6 border-end">
                    <h6 class="text-uppercase text-muted small fw-bold">Libro in Prestito</h6>
                    <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($prestitoInfo['titolo']) ?></h4>
                    <span class="badge bg-light text-dark border mt-2">ID Prestito: #<?= $id_prestito ?></span>
                </div>
                <div class="col-md-6 ps-md-4 mt-3 mt-md-0">
                    <h6 class="text-uppercase text-muted small fw-bold">Utente</h6>
                    <div class="d-flex align-items-center">
                        <div class="bg-light rounded-circle p-2 me-3 text-secondary">
                            <i class="bi bi-person-fill fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($prestitoInfo['nome'] . ' ' . $prestitoInfo['cognome']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($prestitoInfo['codice_alfanumerico']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100 border-0">
                <div class="card-header bg-danger text-white fw-bold py-3">
                    <i class="bi bi-plus-circle me-2"></i>Emetti Nuova Multa
                </div>
                <div class="card-body bg-white">
                    <form method="POST">
                        <input type="hidden" name="add_multa" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Importo (€)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">€</span>
                                <input type="number" step="0.01" name="importo" class="form-control border-start-0" required placeholder="0.00">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Motivazione</label>
                            <textarea name="causale" class="form-control" rows="4" required placeholder="Es: Ritardo restituzione oltre 10gg, Copertina strappata..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold py-2">
                            Conferma e Inserisci
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100 border-0">
                <div class="card-header bg-white fw-bold py-3 border-bottom">
                    Storico Sanzioni
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Data</th>
                                    <th>Causale</th>
                                    <th>Importo</th>
                                    <th>Stato</th>
                                    <th class="text-end pe-4">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($multe)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-check-circle fs-1 d-block mb-3 opacity-25 text-success"></i>
                                            Nessuna multa presente per questo prestito.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($multe as $m): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small"><?= date('d/m/Y', strtotime($m['data_creata'])) ?></td>
                                            <td><?= htmlspecialchars($m['causale']) ?></td>
                                            <td class="fw-bold text-danger">€ <?= number_format($m['importo'], 2) ?></td>
                                            <td>
                                                <?php if ($m['pagata']): ?>
                                                    <span class="badge bg-success rounded-pill px-3">Pagata</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger rounded-pill px-3">Da Pagare</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php if (!$m['pagata']): ?>
                                                    <form method="POST" onsubmit="return confirm('Confermi che l\'utente ha pagato questa multa?');">
                                                        <input type="hidden" name="paga_multa_id" value="<?= $m['id_multa'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill fw-bold">
                                                            <i class="bi bi-check-lg me-1"></i>Segna Pagata
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small"><i class="bi bi-lock"></i> Chiusa</span>
                                                <?php endif; ?>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once './src/includes/footer.php'; ?>
</body>
</html>