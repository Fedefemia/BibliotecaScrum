<?php
// --- CONFIGURAZIONE PERCORSI ---
$baseDir = dirname(__DIR__, 2);
$path = '../'; 

require_once $baseDir . '/security.php';
require_once $baseDir . '/db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Controllo Accessi
if (!checkAccess('amministratore') && !checkAccess('bibliotecario')) {
    header('Location: ../login');
    exit;
}

$isAdmin = checkAccess('amministratore');
$isBibliotecario = checkAccess('bibliotecario') && !$isAdmin; 
$messaggio = "";

// --- 1. GESTIONE SELEZIONE BIBLIOTECA (SOLO BIBLIOTECARI) ---
if (!$isAdmin && !isset($_SESSION['id_biblioteca_operativa'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_biblioteca'])) {
        $_SESSION['id_biblioteca_operativa'] = $_POST['id_biblioteca'];
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}
$id_biblio_operativa = $_SESSION['id_biblioteca_operativa'] ?? null;
$showDashboard = $isAdmin || $id_biblio_operativa;

// --- 2. GESTIONE AZIONI POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. RESTITUZIONE PRESTITO (Standard)
    if (isset($_POST['restituisci_id'])) {
        try {
            $data_scelta = !empty($_POST['data_fine']) ? $_POST['data_fine'] : date('Y-m-d');
            $stmt = $pdo->prepare("UPDATE prestiti SET data_restituzione = :data WHERE id_prestito = :id");
            $stmt->execute([
                'data' => $data_scelta,
                'id' => $_POST['restituisci_id']
            ]);
            header("Location: dashboard-gestioneprestiti?success=restituzione");
            exit;
        } catch (PDOException $e) {
            $messaggio = "Errore restituzione: " . $e->getMessage();
        }
    }

    // B. MULTA DANNI + CHIUSURA PRESTITO (SOLO BIBLIOTECARIO)
    if (isset($_POST['azione']) && $_POST['azione'] === 'multa_danni' && $isBibliotecario) {
        $id_prestito_multa = $_POST['id_prestito'];
        $id_copia_multa = $_POST['id_copia'];
        $importo_danni = 15.00; // Importo fisso

        try {
            $pdo->beginTransaction();

            // 1. Inserisci Multa
            $stmtM = $pdo->prepare("INSERT INTO multe (id_prestito, importo, causale, data_creata, pagata) VALUES (?, ?, 'Danni al materiale (Copertina/Pagine)', CURDATE(), 0)");
            $stmtM->execute([$id_prestito_multa, $importo_danni]);

            // 2. Decrementa Condizione Copia
            $stmtCheck = $pdo->prepare("SELECT condizione FROM copie WHERE id_copia = ?");
            $stmtCheck->execute([$id_copia_multa]);
            $condAttuale = $stmtCheck->fetchColumn();

            if ($condAttuale > 0) {
                $stmtUpdate = $pdo->prepare("UPDATE copie SET condizione = condizione - 1 WHERE id_copia = ?");
                $stmtUpdate->execute([$id_copia_multa]);
            }

            // 3. CHIUDI IL PRESTITO (Restituzione automatica a oggi)
            $stmtClose = $pdo->prepare("UPDATE prestiti SET data_restituzione = CURDATE() WHERE id_prestito = ?");
            $stmtClose->execute([$id_prestito_multa]);

            $pdo->commit();
            header("Location: dashboard-gestioneprestiti?success=multa_e_chiuso");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $messaggio = "Errore applicazione danni: " . $e->getMessage();
        }
    }
}

// --- 3. QUERY PRESTITI ATTIVI ---
$prestitiAttivi = [];
$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';

// Carichiamo biblioteche per la select iniziale
$biblioteche = [];
if (!$showDashboard) {
    $biblioteche = $pdo->query("SELECT id, nome FROM biblioteche ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
}

if ($showDashboard && isset($pdo)) {
    try {
        $query = "SELECT 
                    p.id_prestito, 
                    u.nome, 
                    u.cognome, 
                    u.codice_alfanumerico,
                    u.codice_fiscale,
                    l.titolo, 
                    p.id_copia, 
                    c.condizione, 
                    p.data_prestito, 
                    p.data_scadenza, 
                    p.num_rinnovi,
                    b.nome as nome_biblioteca,
                    DATEDIFF(p.data_scadenza, CURDATE()) as giorni_rimanenti,
                    (SELECT COUNT(*) FROM multe m WHERE m.id_prestito = p.id_prestito AND m.pagata = 0) as multe_pendenti
                  FROM prestiti p
                  JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
                  JOIN copie c ON p.id_copia = c.id_copia
                  JOIN libri l ON c.isbn = l.isbn
                  JOIN biblioteche b ON c.id_biblioteca = b.id
                  WHERE p.data_restituzione IS NULL";

        $params = [];

        if (!$isAdmin) {
            $query .= " AND c.id_biblioteca = :id_biblio";
            $params['id_biblio'] = $id_biblio_operativa;
        }

        if (!empty($searchTerm)) {
            $query .= " AND (u.nome LIKE :q OR u.cognome LIKE :q OR u.codice_alfanumerico LIKE :q OR u.codice_fiscale LIKE :q OR l.titolo LIKE :q)";
            $params['q'] = "%$searchTerm%";
        }

        $query .= " ORDER BY p.data_scadenza ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $prestitiAttivi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $messaggio = "Errore caricamento dati: " . $e->getMessage();
    }
}

// ---------------- HTML HEADER ----------------
$path = "../";
$title = "Gestione Prestiti";
$page_css = "../public/css/style_dashboards.css";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>
    <div class="dashboard_container">

        <div class="page_header">
            <h2 class="page_title">Gestione Prestiti Attivi</h2>
            <div class="header_actions">
                <?php if ($messaggio): ?>
                    <div class="alert alert-info py-1 px-3 m-0 d-flex align-items-center">
                        <?= htmlspecialchars($messaggio) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success py-1 px-3 m-0 d-flex align-items-center">
                        <i class="bi bi-check-circle me-2"></i> Operazione completata!
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$showDashboard): ?>
            <div class="dashboard_card_1" style="max-width: 600px; margin: 40px auto;">
                <div class="contents p-5 text-center">
                    <h3 class="young-serif-regular mb-4" style="font-size: 1.8rem;">Seleziona Biblioteca Operativa</h3>
                    <form method="POST">
                        <select name="id_biblioteca" class="search_input w-100 mb-3" required>
                            <option value="">-- Scegli biblioteca --</option>
                            <?php foreach ($biblioteche as $biblio): ?>
                                <option value="<?= $biblio['id'] ?>"><?= htmlspecialchars($biblio['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="set_biblioteca" class="general_button_dark w-100">
                            Conferma e Accedi
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>

            <div class="search_bar_container">
                <i class="bi bi-search" style="color: var(--color_accent_medium);"></i>
                <form action="" method="GET" class="w-100 d-flex">
                    <input type="text" name="q" class="search_input border-0 p-0"
                           placeholder="Cerca per utente, codice o titolo libro..."
                           value="<?= htmlspecialchars($searchTerm) ?>">
                </form>
            </div>

            <div class="table_card instrument-sans">
                <div class="table_responsive">
                    <table class="admin_table">
                        <thead>
                        <tr>
                            <th>Utente</th>
                            <th>Libro (Copia)</th>
                            <th>Bibliot.</th>
                            <th>Date</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($prestitiAttivi)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    Nessun prestito attivo trovato.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($prestitiAttivi as $p): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($p['nome'] . ' ' . $p['cognome']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($p['codice_alfanumerico']) ?></small>
                                    </td>

                                    <td>
                                        <div class="fw-bold text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($p['titolo']) ?>">
                                            <?= htmlspecialchars($p['titolo']) ?>
                                        </div>
                                        <small class="text-muted">
                                            Copia: <?= $p['id_copia'] ?> | Cond: <?= $p['condizione'] ?>/5
                                        </small>
                                    </td>

                                    <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($p['nome_biblioteca']) ?>
                                    </span>
                                    </td>

                                    <td>
                                        <small class="d-block text-muted">Inizio: <?= $p['data_prestito'] ?></small>
                                        <div class="fw-bold <?= $p['giorni_rimanenti'] < 0 ? 'text-danger' : 'text-success' ?>">
                                            Scadenza: <?= $p['data_scadenza'] ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($p['giorni_rimanenti'] < 0): ?>
                                            <span class="status_badge status_late">
                                            Scaduto da <?= abs($p['giorni_rimanenti']) ?> gg
                                        </span>
                                        <?php else: ?>
                                            <span class="status_badge status_regular">
                                            Regolare (<?= $p['giorni_rimanenti'] ?> gg)
                                        </span>
                                        <?php endif; ?>

                                        <?php if ($p['multe_pendenti'] > 0): ?>
                                            <div class="mt-1 badge bg-danger">
                                                <i class="bi bi-exclamation-triangle"></i> Multe
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-column gap-2">

                                            <form method="POST" class="loan_action_form">
                                                <input type="date" name="data_fine" value="<?= date('Y-m-d') ?>"
                                                       class="input_date_small" title="Data restituzione">
                                                <input type="hidden" name="restituisci_id" value="<?= $p['id_prestito'] ?>">
                                                <button type="submit" class="btn_return"
                                                        onclick="return confirm('Confermi la restituzione corretta?')">
                                                    <i class="bi bi-box-arrow-in-down-left"></i> Restituisci
                                                </button>
                                            </form>

                                            <?php if ($isBibliotecario && $p['condizione'] > 0): ?>
                                                <form method="POST" class="mt-1">
                                                    <input type="hidden" name="azione" value="multa_danni">
                                                    <input type="hidden" name="id_prestito" value="<?= $p['id_prestito'] ?>">
                                                    <input type="hidden" name="id_copia" value="<?= $p['id_copia'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100 border-0 text-start ps-0"
                                                            style="font-size: 0.85rem;"
                                                            onclick="return confirm('ATTENZIONE: Verrà applicata una multa di 15€ e ridotta la condizione della copia. Confermi?')">
                                                        <i class="bi bi-bandaid"></i> Segnala Danni & Chiudi
                                                    </button>
                                                </form>
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
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once $baseDir . '/src/includes/footer.php'; ?>