<?php
// --- CONFIGURAZIONE PERCORSI ---
$baseDir = dirname(__DIR__, 2); 
$path = '../'; 

// --- INCLUSIONI ---
require_once $baseDir . '/security.php';
require_once $baseDir . '/db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Accesso solo bibliotecari/admin
if (!checkAccess('bibliotecario') && !checkAccess('amministratore')) {
    header('Location: ../login');
    exit;
}

$isAdmin = checkAccess('amministratore');
$messaggio = "";
$prenotazioni_utente = [];
$utente_scansionato = null;

// --- 1. GESTIONE SELEZIONE BIBLIOTECA (SOLO PER BIBLIOTECARI) ---
// L'admin non è obbligato a selezionare, vede tutto.
// Il bibliotecario DEVE selezionare.
if (!$isAdmin && !isset($_SESSION['id_biblioteca_operativa'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_biblioteca'])) {
        $_SESSION['id_biblioteca_operativa'] = $_POST['id_biblioteca'];
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Recuperiamo l'ID se esiste (per i bibliotecari o se l'admin volesse filtrarlo in futuro)
$id_biblio_operativa = $_SESSION['id_biblioteca_operativa'] ?? null;

// Determina se mostrare lo scanner: SI se è admin OPPURE se la biblioteca è settata
$showScanner = $isAdmin || $id_biblio_operativa;


// --- 2. GESTIONE EMISSIONE PRESTITO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione']) && $_POST['azione'] === 'emetti_prestito') {
    $id_prenotazione = $_POST['id_prenotazione'];
    $codice_utente_prestito = $_POST['codice_utente'];
    $id_copia_prestito = $_POST['id_copia'];
    
    try {
        $pdo->beginTransaction();

        // Inserimento Prestito
        $sql_insert = "INSERT INTO prestiti (codice_alfanumerico, id_copia, data_prestito, data_scadenza, num_rinnovi) 
                       VALUES (:utente, :copia, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0)";
        $stmt = $pdo->prepare($sql_insert);
        $stmt->execute(['utente' => $codice_utente_prestito, 'copia' => $id_copia_prestito]);

        // Cancellazione Prenotazione
        $sql_delete = "DELETE FROM prenotazioni WHERE id_prenotazione = :id";
        $stmt = $pdo->prepare($sql_delete);
        $stmt->execute(['id' => $id_prenotazione]);

        $pdo->commit();
        $messaggio = "Prestito registrato con successo.";
        
        $_GET['scan_code'] = $codice_utente_prestito;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $messaggio = "Errore database: " . $e->getMessage();
    }
}

// --- 3. GESTIONE SCANSIONE ---
$scan_code = $_GET['scan_code'] ?? '';

if ($showScanner && !empty($scan_code)) {
    // Cerca utente per Codice Alfanumerico O Codice Fiscale
    $stmt = $pdo->prepare("SELECT * FROM utenti WHERE codice_alfanumerico = :scan OR codice_fiscale = :scan");
    $stmt->execute(['scan' => $scan_code]);
    $utente_scansionato = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($utente_scansionato) {
        // Query Prenotazioni dinamica
        $sql_prenotazioni = "
            SELECT p.id_prenotazione, p.data_prenotazione, p.id_copia, 
                   l.titolo, l.isbn, c.id_biblioteca, b.nome as nome_biblioteca
            FROM prenotazioni p
            JOIN copie c ON p.id_copia = c.id_copia
            JOIN libri l ON c.isbn = l.isbn
            JOIN biblioteche b ON c.id_biblioteca = b.id
            WHERE p.codice_alfanumerico = :codice 
        ";

        $params_pren = ['codice' => $utente_scansionato['codice_alfanumerico']];

        // SE NON È ADMIN, FILTRA PER LA BIBLIOTECA OPERATIVA
        if (!$isAdmin) {
            $sql_prenotazioni .= " AND c.id_biblioteca = :id_biblio";
            $params_pren['id_biblio'] = $id_biblio_operativa;
        }

        $sql_prenotazioni .= " ORDER BY p.data_prenotazione ASC";

        $stmt = $pdo->prepare($sql_prenotazioni);
        $stmt->execute($params_pren);
        $prenotazioni_utente = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $messaggio = "Nessun utente trovato con codice: " . htmlspecialchars($scan_code);
    }
}

// Recupero dati ausiliari per la UI
$biblioteche = [];
$nome_biblio_operativa = "Tutte le Biblioteche (Admin)";

if (!$isAdmin && !$id_biblio_operativa) {
    // Serve solo per la select iniziale del bibliotecario
    $biblioteche = $pdo->query("SELECT id, nome FROM biblioteche ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
} 
elseif ($id_biblio_operativa) {
    $stmt = $pdo->prepare("SELECT nome FROM biblioteche WHERE id = ?");
    $stmt->execute([$id_biblio_operativa]);
    $nome_biblio_operativa = $stmt->fetchColumn();
}

// --- INCLUDE LAYOUT ---
$title = "Scanner Prestiti";
$page_css = "../public/css/style_dashboards.css";
require_once $baseDir . '/src/includes/header.php';
require_once $baseDir . '/src/includes/navbar.php';
?>

<div class="scanner-wrapper">

    <?php if (!$showScanner): ?>
        <div class="config-box">
            <h2 style="color: #3f5135;">Configurazione Postazione</h2>
            <p style="color: #666;">Seleziona la biblioteca operativa per questa sessione.</p>
            <form method="POST">
                <select name="id_biblioteca" class="biblio-select">
                    <?php foreach ($biblioteche as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <br>
                <button type="submit" name="set_biblioteca" class="btn-main">Imposta Postazione</button>
            </form>
        </div>
    <?php else: ?>

        <div class="scan-section">
            <div class="scan-header">
                <h2 style="margin:0; font-size: 20px;">Banco Prestiti</h2>
                <div style="text-align:right;">
                    <span style="font-size:12px; color:#888; display:block;">Postazione Attiva</span>
                    <strong style="color: #3f5135;"><?= htmlspecialchars($nome_biblio_operativa) ?></strong>
                </div>
            </div>

            <?php if ($messaggio): ?>
                <div class="alert-box <?= strpos($messaggio, 'Errore') !== false ? 'alert-error' : 'alert-success' ?>">
                    <?= htmlspecialchars($messaggio) ?>
                </div>
            <?php endif; ?>

            <div class="scan-input-container">
                <label for="scanner" style="display:block; margin-bottom:10px; font-weight:600; color:#555;">SCANSIONA TESSERA O C.F.</label>
                <form method="GET" action="">
                    <input type="text" id="scanner" name="scan_code" class="scan-input" placeholder="XXXXXX" autofocus autocomplete="off" value="<?= htmlspecialchars($scan_code) ?>">
                </form>
                <p style="font-size:12px; color:#999; margin-top:10px;">Premi Invio dopo la scansione</p>
            </div>
        </div>

        <?php if ($utente_scansionato): ?>
            
            <div class="user-card">
                <h3>Utente Identificato</h3>
                <div class="user-detail"><strong>Nome:</strong> <?= htmlspecialchars($utente_scansionato['nome'] . ' ' . $utente_scansionato['cognome']) ?></div>
                <div class="user-detail"><strong>Codice:</strong> <?= htmlspecialchars($utente_scansionato['codice_alfanumerico']) ?></div>
                <div class="user-detail"><strong>CF:</strong> <?= htmlspecialchars($utente_scansionato['codice_fiscale']) ?></div>
                <div class="user-detail"><strong>Email:</strong> <?= htmlspecialchars($utente_scansionato['email']) ?></div>
            </div>

            <h3 style="margin-bottom: 15px; color:#333;">Prenotazioni in Attesa (<?= count($prenotazioni_utente) ?>)</h3>
            
            <?php if (count($prenotazioni_utente) > 0): ?>
                <div class="prenotazioni-container">
                    <?php foreach ($prenotazioni_utente as $p): ?>
                        <div class="p-item">
                            <div class="p-info">
                                <strong><?= htmlspecialchars($p['titolo']) ?></strong>
                                <div class="p-meta">
                                    ISBN: <?= $p['isbn'] ?> &bull; ID Copia: <?= $p['id_copia'] ?>
                                    <br>
                                    Richiesto il: <?= date('d/m/Y', strtotime($p['data_prenotazione'])) ?>
                                </div>
                                <?php if ($isAdmin): ?>
                                    <span class="p-badge-biblio">📍 <?= htmlspecialchars($p['nome_biblioteca']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="azione" value="emetti_prestito">
                                <input type="hidden" name="id_prenotazione" value="<?= $p['id_prenotazione'] ?>">
                                <input type="hidden" name="codice_utente" value="<?= $utente_scansionato['codice_alfanumerico'] ?>">
                                <input type="hidden" name="id_copia" value="<?= $p['id_copia'] ?>">
                                <button type="submit" class="btn-emit">EMETTI PRESTITO</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:30px; background:white; border-radius:8px; border:1px solid #ddd; color:#666;">
                    <?php if ($isAdmin): ?>
                        Nessuna prenotazione attiva per questo utente in nessuna biblioteca.
                    <?php else: ?>
                        Nessuna prenotazione attiva presso <strong><?= htmlspecialchars($nome_biblio_operativa) ?></strong> per questo utente.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    <?php endif; ?>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var scannerInput = document.getElementById('scanner');
        if(scannerInput) {
            scannerInput.focus();
            document.addEventListener('click', function(e) {
                if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'SELECT') {
                    scannerInput.focus();
                }
            });
        }
    });
</script>

<?php require_once $baseDir . '/src/includes/footer.php'; ?>