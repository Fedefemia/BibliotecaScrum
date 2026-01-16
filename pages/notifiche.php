<?php
// --- CONFIGURAZIONE PERCORSI E DATABASE ---
$baseDir = dirname(__DIR__); // Root del progetto
require_once $baseDir . '/db_config.php';

// Se security non è incluso in header/navbar, lo includiamo qui
if (file_exists($baseDir . '/security.php')) {
    require_once $baseDir . '/security.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Controllo Login
if (!isset($_SESSION['codice_utente']) || !isset($pdo)) {
    header("Location: login");
    exit;
}

$codice_utente = $_SESSION['codice_utente'];
$messaggio_feedback = "";

// --- GESTIONE AZIONI POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = $_POST['ids'] ?? []; 
    $azione = $_POST['azione'] ?? '';

    if (!empty($ids) && is_array($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $params[] = $codice_utente;

        try {
            if ($azione === 'segna_lette') {
                $sql = "UPDATE notifiche SET visualizzato = 1 WHERE id_notifica IN ($placeholders) AND codice_alfanumerico = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                header("Location: notifiche");
                exit;
            } 
            elseif ($azione === 'segna_non_lette') {
                $sql = "UPDATE notifiche SET visualizzato = 0 WHERE id_notifica IN ($placeholders) AND codice_alfanumerico = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                header("Location: notifiche");
                exit;
            }
            elseif ($azione === 'elimina') {
                $sql = "DELETE FROM notifiche WHERE id_notifica IN ($placeholders) AND codice_alfanumerico = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                header("Location: notifiche");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Errore azione notifiche: " . $e->getMessage());
        }
    }
}

// --- GESTIONE FILTRI ---
$filtro_ricerca = $_GET['q'] ?? '';
$filtro_stato = $_GET['stato'] ?? 'tutte'; 
$filtro_ordine = $_GET['ordine'] ?? 'recenti'; 

$sql = "SELECT * FROM notifiche WHERE codice_alfanumerico = ?";
$params = [$codice_utente];

if (!empty($filtro_ricerca)) {
    $sql .= " AND (titolo LIKE ? OR messaggio LIKE ?)";
    $params[] = "%$filtro_ricerca%";
    $params[] = "%$filtro_ricerca%";
}

if ($filtro_stato === 'non_lette') {
    $sql .= " AND visualizzato = 0";
} elseif ($filtro_stato === 'lette') {
    $sql .= " AND visualizzato = 1";
}

if ($filtro_ordine === 'vecchie') {
    $sql .= " ORDER BY dataora_invio ASC";
} else {
    $sql .= " ORDER BY dataora_invio DESC"; 
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notifiche = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Errore caricamento: " . $e->getMessage());
}

// --- SETUP HEADER E NAVBAR (Come index.php) ---
$title = "Centro Notifiche";
$path = "./"; // Fondamentale per il router
// Se hai un css specifico puoi metterlo qui, altrimenti lascia stringa vuota o null
$page_css = ""; 

// Includiamo HEADER e NAVBAR dai percorsi corretti
require $baseDir . '/src/includes/header.php';
require $baseDir . '/src/includes/navbar.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    .wrapper-notifiche {
        font-family: 'Inter', sans-serif;
        background-color: #f9f9f9;
        min-height: 80vh; /* Assicura che il footer stia giù */
        padding-top: 40px;
        padding-bottom: 60px;
    }

    .container-notifiche {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    .page-title {
        font-size: 24px;
        font-weight: 700;
        color: #3f5135;
        margin: 0;
    }

    /* TOOLBAR */
    .notifica-toolbar {
        background-color: white;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .n-search-group {
        flex-grow: 1;
        position: relative;
    }
    .n-search-input {
        width: 100%;
        padding: 10px 10px 10px 35px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
        box-sizing: border-box;
    }
    .n-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        opacity: 0.5;
        object-fit: contain;
    }

    .n-filter-select {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        background-color: white;
        font-family: inherit;
        cursor: pointer;
    }

    .n-btn-filter {
        padding: 10px 20px;
        background-color: #3f5135;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-family: inherit;
    }
    .n-btn-filter:hover { background-color: #2e3c27; }

    /* BULK ACTIONS */
    .bulk-actions {
        display: none;
        background-color: #eae3d2;
        padding: 10px 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #d4cbb8;
    }
    .bulk-actions.active { display: flex; }

    .n-btn-action {
        padding: 8px 15px;
        border: 1px solid transparent;
        border-radius: 5px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        margin-left: 5px;
        font-family: inherit;
    }
    .n-btn-read { background-color: white; color: #3f5135; border-color: #3f5135; }
    .n-btn-delete { background-color: #dc3545; color: white; }
    .n-btn-delete:hover { background-color: #c82333; }

    /* LISTA */
    .notifiche-list {
        background-color: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .n-row {
        display: flex;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
        position: relative;
    }
    .n-row:last-child { border-bottom: none; }
    
    .n-row.unread {
        background-color: #fffbef;
        border-left: 4px solid #3f5135;
    }
    .n-row.read {
        background-color: #fff;
        opacity: 0.8;
    }
    .n-row:hover { background-color: #f5f5f5; opacity: 1; }

    .n-check-col { padding-right: 15px; }
    .n-check-input { width: 18px; height: 18px; cursor: pointer; }

    .n-content-col { flex-grow: 1; }
    
    .n-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }
    .n-title { font-weight: 600; font-size: 15px; color: #111; }
    .unread .n-title { font-weight: 700; color: #3f5135; }

    .n-time { font-size: 12px; color: #888; white-space: nowrap; margin-left: 10px; }
    .n-msg { font-size: 14px; color: #555; line-height: 1.4; }
    
    .n-link-ref {
        display: inline-block;
        margin-top: 5px;
        font-size: 12px;
        color: #3f5135;
        text-decoration: none;
        font-weight: 500;
    }
    .n-link-ref:hover { text-decoration: underline; }

    .select-all-container {
        display: flex;
        align-items: center;
        margin-right: 15px;
        font-size: 13px;
        color: #666;
    }
</style>

<div class="wrapper-notifiche">
    <div class="container-notifiche">
        
        <div class="page-header">
            <h1 class="page-title">Centro Notifiche</h1>
        </div>

        <?php if ($messaggio_feedback): ?>
            <div style="padding:10px; background:#d4edda; color:#155724; border-radius:6px; margin-bottom:20px; text-align:center;">
                <?= htmlspecialchars($messaggio_feedback) ?>
            </div>
        <?php endif; ?>

        <form id="mainForm" method="GET" action="">
            <div class="notifica-toolbar">
                <div class="n-search-group">
                    <img src="<?= $path ?>public/assets/icon_search_dark.png" class="n-search-icon" alt="Cerca">
                    <input type="text" name="q" class="n-search-input" placeholder="Cerca titolo o messaggio..." value="<?= htmlspecialchars($filtro_ricerca) ?>">
                </div>
                
                <select name="stato" class="n-filter-select">
                    <option value="tutte" <?= $filtro_stato == 'tutte' ? 'selected' : '' ?>>Tutte</option>
                    <option value="non_lette" <?= $filtro_stato == 'non_lette' ? 'selected' : '' ?>>Non lette</option>
                    <option value="lette" <?= $filtro_stato == 'lette' ? 'selected' : '' ?>>Già lette</option>
                </select>

                <select name="ordine" class="n-filter-select">
                    <option value="recenti" <?= $filtro_ordine == 'recenti' ? 'selected' : '' ?>>Più recenti</option>
                    <option value="vecchie" <?= $filtro_ordine == 'vecchie' ? 'selected' : '' ?>>Più vecchie</option>
                </select>

                <button type="submit" class="n-btn-filter">Filtra</button>
                
                <?php if (!empty($filtro_ricerca) || $filtro_stato !== 'tutte'): ?>
                    <a href="notifiche" style="font-size:13px; color:#dc3545; text-decoration:none; margin-left:5px;">Resetta</a>
                <?php endif; ?>
            </div>
        </form>

        <form id="bulkForm" method="POST" action="">
            
            <div class="bulk-actions" id="bulkActionsBar">
                <div class="select-all-container">
                    <input type="checkbox" id="selectAllTop" class="n-check-input" style="margin-right: 8px;">
                    <span id="selectedCount">0 selezionate</span>
                </div>
                <div>
                    <button type="submit" name="azione" value="segna_lette" class="n-btn-action n-btn-read">Segna lette</button>
                    <button type="submit" name="azione" value="segna_non_lette" class="n-btn-action n-btn-read">Segna da leggere</button>
                    <button type="submit" name="azione" value="elimina" class="n-btn-action n-btn-delete" onclick="return confirm('Eliminare le notifiche selezionate?');">Elimina</button>
                </div>
            </div>

            <div class="notifiche-list">
                <?php if (count($notifiche) > 0): ?>
                    <?php foreach ($notifiche as $n): ?>
                        <div class="n-row <?= ($n['visualizzato'] == 0) ? 'unread' : 'read' ?>">
                            <div class="n-check-col">
                                <input type="checkbox" name="ids[]" value="<?= $n['id_notifica'] ?>" class="n-check-input item-check">
                            </div>
                            <div class="n-content-col">
                                <div class="n-header">
                                    <span class="n-title"><?= htmlspecialchars($n['titolo']) ?></span>
                                    <span class="n-time"><?= date('d/m/Y H:i', strtotime($n['dataora_invio'])) ?></span>
                                </div>
                                <div class="n-msg">
                                    <?= nl2br(htmlspecialchars($n['messaggio'])) ?>
                                </div>
                                <?php if (!empty($n['link_riferimento'])): ?>
                                    <a href="<?php $path ?>profilo" class="n-link-ref">Vai al dettaglio &rarr;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #888;">
                        Nessuna notifica trovata.
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php 
// Includiamo il footer se esiste
if (file_exists($baseDir . '/src/includes/footer.php')) {
    require $baseDir . '/src/includes/footer.php';
}
?>

<script>
    const selectAllCheckbox = document.getElementById('selectAllTop');
    const itemCheckboxes = document.querySelectorAll('.item-check');
    const bulkBar = document.getElementById('bulkActionsBar');
    const countLabel = document.getElementById('selectedCount');

    function updateBulkUI() {
        let checkedCount = 0;
        itemCheckboxes.forEach(cb => {
            if (cb.checked) checkedCount++;
        });

        if (checkedCount > 0) {
            bulkBar.classList.add('active');
            countLabel.textContent = checkedCount + (checkedCount === 1 ? ' selezionata' : ' selezionate');
        } else {
            bulkBar.classList.remove('active');
        }
    }

    if(selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            itemCheckboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            updateBulkUI();
        });
    }

    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (!this.checked && selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            updateBulkUI();
        });
    });
</script>