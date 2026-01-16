<?php
/**
 * D_libri.php - FIX BARCODE ORIGINALE + FIX NGROK
 */

// --- 1. LOGICA BARCODE ORIGINALE (ROBUSTA) ---
if (isset($_GET['generate_barcode'])) {
    while (ob_get_level()) ob_end_clean();
    require_once __DIR__ . '/../../vendor/autoload.php';

    $isbn = preg_replace('/[^0-9]/', '', $_GET['isbn'] ?? '');

    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    try {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        if (empty($isbn)) throw new Exception("Empty");

        // QUESTA È LA LOGICA ORIGINALE CHE VOLEVI
        // Tenta EAN-13, se il checksum è errato o fallisce, passa a Code-128
        if (strlen($isbn) === 13) {
            try {
                echo $generator->getBarcode($isbn, $generator::TYPE_EAN_13);
            } catch (Exception $e) {
                echo $generator->getBarcode($isbn, $generator::TYPE_CODE_128);
            }
        } else {
            echo $generator->getBarcode($isbn, $generator::TYPE_CODE_128);
        }
    } catch (Exception $e) {
        $img = imagecreate(150, 30);
        imagecolorallocate($img, 255, 255, 255);
        imagestring($img, 2, 5, 5, "ERR", imagecolorallocate($img, 255, 0, 0));
        imagepng($img);
        imagedestroy($img);
    }
    exit;
}

// --- 2. SETUP E SICUREZZA ---
require_once 'security.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!checkAccess('amministratore')) header('Location: ./');

// FIX CONNESSIONE NGROK
try {
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 120);
    $pdo->exec("SET SESSION wait_timeout = 600");
    $pdo->exec("SET SESSION max_allowed_packet = 67108864"); 
} catch (Exception $e) {}

// Percorsi
$systemCoverDir = __DIR__ . '/../../public/bookCover/';
$webCoverDir = '../../public/bookCover/';
$placeholder = '../../public/assets/book_placeholder.jpg';

if (!is_dir($systemCoverDir)) { mkdir($systemCoverDir, 0777, true); }

$msg = "";
$msg_type = "success"; 
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = $_GET['search'] ?? '';

// --- 3. GESTIONE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CREATE
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $isbn = preg_replace('/[^0-9]/', '', $_POST['isbn']);
        $titolo = trim($_POST['titolo']);
        $anno = $_POST['anno_pubblicazione'];

        if (strlen($isbn) < 10) {
            $msg = "ISBN non valido."; $msg_type = "error";
        } else {
            $check = $pdo->prepare("SELECT COUNT(*) FROM libri WHERE isbn = ?");
            $check->execute([$isbn]);
            if ($check->fetchColumn() > 0) {
                $msg = "ISBN già presente."; $msg_type = "error";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO libri (isbn, titolo, anno_pubblicazione) VALUES (?, ?, ?)");
                    $stmt->execute([$isbn, $titolo, $anno]);

                    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                            move_uploaded_file($_FILES['cover']['tmp_name'], $systemCoverDir . $isbn . '.' . $ext);
                        }
                    }
                    $msg = "Libro aggiunto!";
                } catch (Exception $e) {
                    $msg = "Errore: " . $e->getMessage(); $msg_type = "error";
                }
            }
        }
    }

    // DELETE
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $isbn_to_delete = $_POST['isbn'];
        try {
            $pdo->beginTransaction();
            $stmtCopie = $pdo->prepare("SELECT id_copia FROM copie WHERE isbn = ?");
            $stmtCopie->execute([$isbn_to_delete]);
            $copieids = $stmtCopie->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($copieids)) {
                $chunks = array_chunk($copieids, 50);
                foreach ($chunks as $chunk) {
                    $inQuery = implode(',', array_fill(0, count($chunk), '?'));
                    $pdo->prepare("DELETE FROM prestiti WHERE id_copia IN ($inQuery)")->execute($chunk);
                    $pdo->prepare("DELETE FROM prenotazioni WHERE id_copia IN ($inQuery)")->execute($chunk);
                    $pdo->prepare("DELETE FROM richieste_bibliotecario WHERE id_copia IN ($inQuery)")->execute($chunk);
                }
                $pdo->prepare("DELETE FROM copie WHERE isbn = ?")->execute([$isbn_to_delete]);
            }
            $pdo->prepare("DELETE FROM autore_libro WHERE isbn = ?")->execute([$isbn_to_delete]);
            $pdo->prepare("DELETE FROM libro_categoria WHERE isbn = ?")->execute([$isbn_to_delete]);
            $pdo->prepare("DELETE FROM recensioni WHERE isbn = ?")->execute([$isbn_to_delete]);
            $pdo->prepare("DELETE FROM libri WHERE isbn = ?")->execute([$isbn_to_delete]);
            
            @unlink($systemCoverDir . $isbn_to_delete . '.jpg');
            @unlink($systemCoverDir . $isbn_to_delete . '.png');

            $pdo->commit();
            $msg = "Libro eliminato.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $msg = "Errore: " . $e->getMessage(); $msg_type = "error";
        }
    }

    // UPDATE
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $old_isbn = $_POST['old_isbn'];
        $new_isbn = preg_replace('/[^0-9]/', '', $_POST['isbn']);
        $titolo = $_POST['titolo'];
        $anno = $_POST['anno_pubblicazione'];

        try {
            $pdo->beginTransaction();
            if ($old_isbn != $new_isbn) {
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                $pdo->prepare("UPDATE libri SET isbn = ?, titolo = ?, anno_pubblicazione = ? WHERE isbn = ?")->execute([$new_isbn, $titolo, $anno, $old_isbn]);
                foreach(['copie','autore_libro','libro_categoria','recensioni'] as $t) {
                    $pdo->prepare("UPDATE $t SET isbn = ? WHERE isbn = ?")->execute([$new_isbn, $old_isbn]);
                }
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

                if (empty($_FILES['cover']['name'])) {
                    if (file_exists($systemCoverDir . $old_isbn . '.png')) rename($systemCoverDir . $old_isbn . '.png', $systemCoverDir . $new_isbn . '.png');
                    elseif (file_exists($systemCoverDir . $old_isbn . '.jpg')) rename($systemCoverDir . $old_isbn . '.jpg', $systemCoverDir . $new_isbn . '.jpg');
                }
            } else {
                $pdo->prepare("UPDATE libri SET titolo = ?, anno_pubblicazione = ? WHERE isbn = ?")->execute([$titolo, $anno, $old_isbn]);
            }

            if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    @unlink($systemCoverDir . $new_isbn . '.jpg');
                    @unlink($systemCoverDir . $new_isbn . '.png');
                    move_uploaded_file($_FILES['cover']['tmp_name'], $systemCoverDir . $new_isbn . '.' . $ext);
                }
            }

            $pdo->commit();
            header("Location: dashboard-libri?page=" . $page . "&search=" . urlencode($search)); 
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            $msg = "Errore: " . $e->getMessage(); $msg_type = "error";
        }
    }
}

// --- 4. LETTURA DATI (10 per pagina per sicurezza Ngrok) ---
try {
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    $whereClause = ""; $params = [];
    if (!empty($search)) {
        $whereClause = "WHERE isbn LIKE ? OR titolo LIKE ?";
        $params[] = "%$search%"; $params[] = "%$search%";
    }
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM libri $whereClause");
    $countStmt->execute($params);
    $total_books = $countStmt->fetchColumn();
    $total_pages = ceil($total_books / $per_page);

    $sql = "SELECT isbn, titolo, anno_pubblicazione FROM libri $whereClause ORDER BY anno_pubblicazione DESC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $libri = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { die("Errore DB: " . $e->getMessage()); }

$edit_isbn = $_GET['edit'] ?? null;

// ---------------- HTML HEADER ----------------
$path = "../";
$title = "Catalogo Libri - Dashboard";
$page_css = "../public/css/style_dashboards.css";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <div id="loading_overlay">
        <div class="spinner"></div>
        <div class="loading_text">Elaborazione...</div>
    </div>

    <div id="cover_modal">
        <img class="modal_content" id="img_full">
        <div id="caption" class="modal_caption"></div>
    </div>

    <div class="dashboard_container">
        <div class="page_header">
            <h2 class="page_title">Gestione Catalogo</h2>
            <div class="header_actions">
                <a href="/cover-fetcher" class="btn_action btn_fetcher trigger_loader">Cover Fetcher</a>
                <button onclick="toggleAddForm()" class="btn_action btn_save">+ Nuovo Libro</button>
            </div>
        </div>

        <?php if(!empty($msg)): ?>
            <div class="alert_msg <?= ($msg_type == 'error') ? 'alert_error' : 'alert_success' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div id="add_book_section" class="add_book_section">
            <form method="POST" class="add_form_wrapper form_spam_protect" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="form_group">
                    <label class="form_label">ISBN (Solo numeri)</label>
                    <input type="text" name="isbn" class="edit_input" required placeholder="Es. 9788804719999">
                </div>
                <div class="form_group">
                    <label class="form_label">Titolo del Libro</label>
                    <input type="text" name="titolo" class="edit_input" required placeholder="Titolo completo">
                </div>
                <div class="form_group short">
                    <label class="form_label">Anno</label>
                    <input type="number" name="anno_pubblicazione" class="edit_input" required placeholder="2024">
                </div>
                <div class="form_group">
                    <label class="form_label">Copertina</label>
                    <input type="file" name="cover" accept=".jpg,.jpeg,.png" style="font-size:0.9rem;">
                </div>
                <button type="submit" class="btn_action btn_save" style="margin-bottom: 5px;">Salva Libro</button>
            </form>
        </div>

        <form method="GET" class="search_bar_container">
            <input type="text" name="search" class="search_input" placeholder="Cerca per ISBN o Titolo..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn_search trigger_loader">Cerca</button>
            <?php if(!empty($search)): ?>
                <a href="dashboard-libri" class="btn_reset trigger_loader">Resetta</a>
            <?php endif; ?>
        </form>

        <div class="table_card">
            <div class="table_responsive">
                <table class="admin_table">
                    <thead>
                    <tr>
                        <th style="width: 180px; text-align: center;">Barcode</th>
                        <th>Dettagli Libro</th>
                        <th style="width: 200px; text-align: center;">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($libri)): ?>
                        <tr><td colspan="3" style="text-align:center; padding: 30px;">Nessun libro trovato.</td></tr>
                    <?php else: ?>
                        <?php foreach ($libri as $b): ?>
                            <?php
                            $is_editing = ($edit_isbn == $b['isbn']);

                            $coverSrc = $placeholder;
                            if (file_exists($systemCoverDir . $b['isbn'] . '.png')) {
                                $coverSrc = $webCoverDir . $b['isbn'] . '.png';
                            } elseif (file_exists($systemCoverDir . $b['isbn'] . '.jpg')) {
                                $coverSrc = $webCoverDir . $b['isbn'] . '.jpg';
                            }
                            $coverSrc .= '?v=' . time();
                            ?>
                            <tr>
                                <td style="text-align: center;">
                                    <div class="barcode_wrapper">
                                        <img src="dashboard-libri?generate_barcode=1&isbn=<?= htmlspecialchars($b['isbn']) ?>"
                                             alt="Barcode" class="barcode_img">
                                        <div class="isbn_text"><?= htmlspecialchars($b['isbn']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($is_editing): ?>
                                        <form id="form_edit_<?= $b['isbn'] ?>" method="POST" action="dashboard-libri?page=<?= $page ?>&search=<?= urlencode($search) ?>" enctype="multipart/form-data" class="form_spam_protect">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="old_isbn" value="<?= htmlspecialchars($b['isbn']) ?>">
                                            <input type="text" name="titolo" class="edit_input" value="<?= htmlspecialchars($b['titolo']) ?>" required placeholder="Titolo">
                                            <div style="display:flex; gap:10px; margin-top:5px;">
                                                <input type="text" name="isbn" class="edit_input" value="<?= htmlspecialchars($b['isbn']) ?>" required placeholder="ISBN">
                                                <input type="number" name="anno_pubblicazione" class="edit_input" style="width:100px;" value="<?= htmlspecialchars($b['anno_pubblicazione']) ?>" placeholder="Anno">
                                            </div>
                                            <div class="file_input_wrapper">
                                                <label>Nuova Copertina (JPG/PNG):</label>
                                                <input type="file" name="cover" accept=".jpg,.jpeg,.png">
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div>
                                            <button type="button" class="btn_preview" onclick="openCover('<?= $coverSrc ?>', '<?= addslashes($b['titolo']) ?>')">
                                                Vedi Cover
                                            </button>

                                            <div style="font-weight: 600; font-size: 1.1rem; color: var(--color_text_black);">
                                                <?= htmlspecialchars($b['titolo']) ?>
                                            </div>
                                            <div style="font-size: 0.9rem; color: #888; margin-top: 4px;">
                                                <?= htmlspecialchars($b['anno_pubblicazione']) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($is_editing): ?>
                                        <div style="display:flex; flex-direction:column; gap:5px;">
                                            <button type="submit" form="form_edit_<?= $b['isbn'] ?>" class="btn_action btn_save trigger_loader">Salva</button>
                                            <a href="dashboard-libri?page=<?= $page ?>&search=<?= urlencode($search) ?>" class="btn_action btn_cancel trigger_loader">Annulla</a>
                                        </div>
                                    <?php else: ?>
                                        <div style="display: flex; justify-content: center; gap: 5px;">
                                            <a href="?page=<?= $page ?>&search=<?= urlencode($search) ?>&edit=<?= htmlspecialchars($b['isbn']) ?>" class="btn_action btn_edit trigger_loader">Modifica</a>
                                            <form method="POST" class="form_spam_protect" onsubmit="return confirm('Eliminare libro e storico?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="isbn" value="<?= htmlspecialchars($b['isbn']) ?>">
                                                <button type="submit" class="btn_action btn_delete trigger_loader">Elimina</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="page_link trigger_loader">&laquo;</a>
                <?php endif; ?>
                <span class="page_link active">Pag <?= $page ?>/<?= $total_pages ?></span>
                <?php if($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="page_link trigger_loader">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleAddForm() {
            var x = document.getElementById("add_book_section");
            x.style.display = (x.style.display === "block") ? "none" : "block";
        }

        // GESTIONE MODAL PREVIEW
        var modal = document.getElementById("cover_modal");
        var modalImg = document.getElementById("img_full");
        var captionText = document.getElementById("caption");

        function openCover(src, title) {
            modal.style.display = "block";
            modalImg.src = src;
            captionText.innerHTML = title;
        }

        modal.onclick = function() {
            modal.style.display = "none";
        }

        function hideLoader() {
            const loader = document.getElementById('loading_overlay');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }
        }

        document.addEventListener('DOMContentLoaded', hideLoader);
        window.addEventListener('load', hideLoader);
        setTimeout(hideLoader, 3000);
    </script>

<?php require_once './src/includes/footer.php'; ?>