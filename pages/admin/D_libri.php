<?php

// --- 1. LOGICA BARCODE ---
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

        // Tenta EAN-13, fallback su Code-128
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
        // --- BLOCCO ERRORE CHE VOLEVI MANTENERE ---
        $img = imagecreate(150, 30);
        imagecolorallocate($img, 255, 255, 255); // Sfondo bianco
        imagestring($img, 2, 5, 5, "ERR", imagecolorallocate($img, 255, 0, 0)); // Scritta rossa
        imagepng($img);
        imagedestroy($img);
        // ------------------------------------------
    }
    exit;
}

// --- 2. SETUP E SICUREZZA ---
require_once 'security.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!checkAccess('amministratore')) header('Location: ./');

// FIX CONNESSIONE NGROK / TIMEOUT
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

// Recupero liste complete (con GROUP BY per evitare duplicati visivi)
try {
    $allAutori = $pdo->query("
        SELECT MIN(id_autore) as id_autore, nome, cognome 
        FROM autori 
        GROUP BY nome, cognome 
        ORDER BY cognome, nome
    ")->fetchAll(PDO::FETCH_ASSOC);

    $allCategorie = $pdo->query("
        SELECT MIN(id_categoria) as id_categoria, categoria 
        FROM categorie 
        GROUP BY categoria 
        ORDER BY categoria
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allAutori = [];
    $allCategorie = [];
}

// --- 3. GESTIONE POST (Create, Update, Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CREATE
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $isbn = preg_replace('/[^0-9]/', '', $_POST['isbn']);
        $titolo = trim($_POST['titolo']);
        $descrizione = trim($_POST['descrizione'] ?? '');
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
                    $stmt = $pdo->prepare("INSERT INTO libri (isbn, titolo, descrizione, anno_pubblicazione) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$isbn, $titolo, $descrizione, $anno]);

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
            // Elimina dipendenze copie
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
            // Elimina associazioni dirette
            $pdo->prepare("DELETE FROM autore_libro WHERE isbn = ?")->execute([$isbn_to_delete]);
            $pdo->prepare("DELETE FROM libro_categoria WHERE isbn = ?")->execute([$isbn_to_delete]);
            $pdo->prepare("DELETE FROM recensioni WHERE isbn = ?")->execute([$isbn_to_delete]);
            // Elimina libro
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
        $descrizione = $_POST['descrizione'];
        $anno = $_POST['anno_pubblicazione'];
        
        $selected_autori = $_POST['autori'] ?? [];
        $selected_categorie = $_POST['categorie'] ?? [];

        try {
            $pdo->beginTransaction();
            
            // 1. Aggiorna Tabella Libri
            if ($old_isbn != $new_isbn) {
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                // Controlla se il campo si chiama 'descrizione' o 'description' nel tuo DB. Qui uso 'descrizione'.
                $pdo->prepare("UPDATE libri SET isbn = ?, titolo = ?, descrizione = ?, anno_pubblicazione = ? WHERE isbn = ?")
                    ->execute([$new_isbn, $titolo, $descrizione, $anno, $old_isbn]);
                
                foreach(['copie','autore_libro','libro_categoria','recensioni'] as $t) {
                    $pdo->prepare("UPDATE $t SET isbn = ? WHERE isbn = ?")->execute([$new_isbn, $old_isbn]);
                }
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

                if (empty($_FILES['cover']['name'])) {
                    if (file_exists($systemCoverDir . $old_isbn . '.png')) rename($systemCoverDir . $old_isbn . '.png', $systemCoverDir . $new_isbn . '.png');
                    elseif (file_exists($systemCoverDir . $old_isbn . '.jpg')) rename($systemCoverDir . $old_isbn . '.jpg', $systemCoverDir . $new_isbn . '.jpg');
                }
            } else {
                $pdo->prepare("UPDATE libri SET titolo = ?, descrizione = ?, anno_pubblicazione = ? WHERE isbn = ?")
                    ->execute([$titolo, $descrizione, $anno, $old_isbn]);
            }

            // 2. Aggiorna Autori (Delete & Insert)
            $pdo->prepare("DELETE FROM autore_libro WHERE isbn = ?")->execute([$new_isbn]);
            if (!empty($selected_autori)) {
                $stmtInsAut = $pdo->prepare("INSERT INTO autore_libro (isbn, id_autore) VALUES (?, ?)");
                foreach ($selected_autori as $id_autore) {
                    $stmtInsAut->execute([$new_isbn, $id_autore]);
                }
            }

            // 3. Aggiorna Categorie (Delete & Insert)
            $pdo->prepare("DELETE FROM libro_categoria WHERE isbn = ?")->execute([$new_isbn]);
            if (!empty($selected_categorie)) {
                $stmtInsCat = $pdo->prepare("INSERT INTO libro_categoria (isbn, id_categoria) VALUES (?, ?)");
                foreach ($selected_categorie as $id_categoria) {
                    $stmtInsCat->execute([$new_isbn, $id_categoria]);
                }
            }

            // 4. Copertina
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
            $msg = "Errore Aggiornamento: " . $e->getMessage(); $msg_type = "error";
        }
    }
}

// --- 4. LETTURA DATI ---
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

    $sql = "SELECT isbn, titolo, descrizione, anno_pubblicazione FROM libri $whereClause ORDER BY anno_pubblicazione DESC LIMIT $per_page OFFSET $offset";
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

    <style>
        /* CSS EXTRA PER EDIT FORM E LISTA AUTORI */
        .edit_container { display: flex; flex-wrap: wrap; gap: 10px; }
        .edit_col { flex: 1; min-width: 200px; }
        .edit_textarea { width: 100%; min-height: 80px; resize: vertical; margin-bottom: 10px; font-family: inherit; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .label_small { font-size: 0.85rem; font-weight: bold; color: #555; display: block; margin-bottom: 3px; }
        
        /* Lista checkbox scrollabile */
        .scrollable_list { height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 5px; background: #fff; border-radius: 4px; }
        .author_item, .cat_item { padding: 3px 0; border-bottom: 1px solid #f0f0f0; }
        .author_item:hover, .cat_item:hover { background-color: #f9f9f9; }
    </style>

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
                    <label class="form_label">ISBN</label>
                    <input type="text" name="isbn" class="edit_input" required placeholder="Es. 9788804719999">
                </div>
                <div class="form_group">
                    <label class="form_label">Titolo</label>
                    <input type="text" name="titolo" class="edit_input" required placeholder="Titolo completo">
                </div>
                <div class="form_group short">
                    <label class="form_label">Anno</label>
                    <input type="number" name="anno_pubblicazione" class="edit_input" required placeholder="2024">
                </div>
                <div class="form_group" style="flex: 1 1 100%;">
                    <label class="form_label">Descrizione</label>
                    <textarea name="descrizione" class="edit_input" style="height: 60px;" placeholder="Trama..."></textarea>
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

                            // Logica Cover
                            $coverSrc = $placeholder;
                            if (file_exists($systemCoverDir . $b['isbn'] . '.png')) {
                                $coverSrc = $webCoverDir . $b['isbn'] . '.png';
                            } elseif (file_exists($systemCoverDir . $b['isbn'] . '.jpg')) {
                                $coverSrc = $webCoverDir . $b['isbn'] . '.jpg';
                            }
                            $coverSrc .= '?v=' . time(); // Cache buster

                            // Se in modifica, recupera relazioni
                            $book_autori_ids = [];
                            $book_categorie_ids = [];
                            if ($is_editing) {
                                $stmtA = $pdo->prepare("SELECT id_autore FROM autore_libro WHERE isbn = ?");
                                $stmtA->execute([$b['isbn']]);
                                $book_autori_ids = $stmtA->fetchAll(PDO::FETCH_COLUMN);

                                $stmtC = $pdo->prepare("SELECT id_categoria FROM libro_categoria WHERE isbn = ?");
                                $stmtC->execute([$b['isbn']]);
                                $book_categorie_ids = $stmtC->fetchAll(PDO::FETCH_COLUMN);
                            }
                            ?>
                            <tr>
                                <td style="text-align: center; vertical-align: top;">
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
                                            
                                            <div class="edit_container">
                                                <div class="edit_col">
                                                    <span class="label_small">Titolo</span>
                                                    <input type="text" name="titolo" class="edit_input" value="<?= htmlspecialchars($b['titolo']) ?>" required>
                                                </div>
                                                <div class="edit_col">
                                                     <span class="label_small">ISBN</span>
                                                    <input type="text" name="isbn" class="edit_input" value="<?= htmlspecialchars($b['isbn']) ?>" required>
                                                </div>
                                                <div class="edit_col" style="flex: 0 0 100px;">
                                                     <span class="label_small">Anno</span>
                                                    <input type="number" name="anno_pubblicazione" class="edit_input" value="<?= htmlspecialchars($b['anno_pubblicazione']) ?>">
                                                </div>
                                            </div>

                                            <div style="margin-top: 10px;">
                                                <span class="label_small">Descrizione</span>
                                                <textarea name="descrizione" class="edit_textarea"><?= htmlspecialchars($b['descrizione'] ?? '') ?></textarea>
                                            </div>

                                            <div class="edit_container">
                                                <div class="edit_col">
                                                    <span class="label_small">Autori</span>
                                                    <div style="display:flex; gap:5px; margin-bottom:5px;">
                                                        <input type="text" id="search_autore_input" class="edit_input" placeholder="Cerca autore..." onkeyup="filterAuthors()" style="width:100%;">
                                                        <button type="button" id="btn_add_autore" class="btn_action btn_edit" style="display:none; white-space:nowrap; padding: 2px 8px;" onclick="addAuthor()">+ Aggiungi</button>
                                                    </div>
                                                    
                                                    <div id="autori_list_container" class="scrollable_list">
                                                        <?php foreach($allAutori as $aut): ?>
                                                            <?php 
                                                                $isChecked = in_array($aut['id_autore'], $book_autori_ids) ? 'checked' : ''; 
                                                                $fullName = htmlspecialchars($aut['cognome'] . ' ' . $aut['nome']);
                                                            ?>
                                                            <div class="author_item">
                                                                <label style="display:flex; align-items:center; cursor:pointer; font-size:0.9rem;">
                                                                    <input type="checkbox" name="autori[]" value="<?= $aut['id_autore'] ?>" <?= $isChecked ?> style="margin-right:8px;">
                                                                    <span class="auth_name"><?= $fullName ?></span>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <div class="edit_col">
                                                    <span class="label_small">Categorie</span>
                                                    <div class="scrollable_list" style="margin-top: 31px;"> <?php foreach($allCategorie as $cat): ?>
                                                            <?php $isChecked = in_array($cat['id_categoria'], $book_categorie_ids) ? 'checked' : ''; ?>
                                                            <div class="cat_item">
                                                                <label style="display:flex; align-items:center; cursor:pointer; font-size:0.9rem;">
                                                                    <input type="checkbox" name="categorie[]" value="<?= $cat['id_categoria'] ?>" <?= $isChecked ?> style="margin-right:8px;">
                                                                    <?= htmlspecialchars($cat['categoria']) ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="file_input_wrapper" style="margin-top: 10px;">
                                                <label>Nuova Copertina:</label>
                                                <input type="file" name="cover" accept=".jpg,.jpeg,.png">
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div>
                                            <div style="font-weight: 600; font-size: 1.1rem; color: var(--color_text_black);">
                                                <?= htmlspecialchars($b['titolo']) ?>
                                            </div>
                                            <div style="font-size: 0.9rem; color: #888; margin-top: 4px;">
                                                <?= htmlspecialchars($b['anno_pubblicazione']) ?>
                                            </div>
                                            <?php if(!empty($b['descrizione'])): ?>
                                                <div style="font-size: 0.85rem; color: #666; margin-top: 5px; max-height: 40px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <?= htmlspecialchars(substr($b['descrizione'], 0, 100)) ?>...
                                                </div>
                                            <?php endif; ?>
                                            <button type="button" class="btn_preview" style="margin-top: 5px;" onclick="openCover('<?= $coverSrc ?>', '<?= addslashes($b['titolo']) ?>')">
                                                Vedi Cover
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; vertical-align: top;">
                                    <?php if ($is_editing): ?>
                                        <div style="display:flex; flex-direction:column; gap:5px; margin-top: 20px;">
                                            <button type="submit" form="form_edit_<?= $b['isbn'] ?>" class="btn_action btn_save trigger_loader">Salva Modifiche</button>
                                            <a href="dashboard-libri?page=<?= $page ?>&search=<?= urlencode($search) ?>" class="btn_action btn_cancel trigger_loader">Annulla</a>
                                        </div>
                                    <?php else: ?>
                                        <div style="display: flex; justify-content: center; gap: 5px; margin-top: 10px;">
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

        // --- GESTIONE MODAL PREVIEW ---
        var modal = document.getElementById("cover_modal");
        var modalImg = document.getElementById("img_full");
        var captionText = document.getElementById("caption");

        function openCover(src, title) {
            modal.style.display = "block";
            modalImg.src = src;
            captionText.innerHTML = title;
        }

        modal.onclick = function() { modal.style.display = "none"; }

        // --- GESTIONE LOADER ---
        function hideLoader() {
            const loader = document.getElementById('loading_overlay');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => { loader.style.display = 'none'; }, 500);
            }
        }
        document.addEventListener('DOMContentLoaded', hideLoader);
        window.addEventListener('load', hideLoader);
        setTimeout(hideLoader, 3000);

        // --- GESTIONE AUTORI VANILLA JS ---
        function filterAuthors() {
            let input = document.getElementById('search_autore_input');
            let filter = input.value.toLowerCase();
            let container = document.getElementById('autori_list_container');
            let items = container.getElementsByClassName('author_item');
            let btnAdd = document.getElementById('btn_add_autore');
            let visibleCount = 0;

            for (let i = 0; i < items.length; i++) {
                let span = items[i].getElementsByClassName('auth_name')[0];
                let txtValue = span.textContent || span.innerText;
                
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    items[i].style.display = "";
                    visibleCount++;
                } else {
                    items[i].style.display = "none";
                }
            }

            if (visibleCount === 0 && filter.length > 0) {
                btnAdd.style.display = "block";
                btnAdd.innerText = "+ Aggiungi \"" + input.value + "\"";
            } else {
                btnAdd.style.display = "none";
            }
        }

        function addAuthor() {
            let input = document.getElementById('search_autore_input');
            let nameToAdd = input.value;
            let btn = document.getElementById('btn_add_autore');

            if(!nameToAdd) return;

            btn.disabled = true;
            btn.innerText = "Salvataggio...";

            let formData = new FormData();
            formData.append('ajax_action', 'create_author_vanilla');
            formData.append('name', nameToAdd);

            fetch('D_libri.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                if (data.status === 'success' || data.status === 'exists') {
                    let container = document.getElementById('autori_list_container');
                    let newDiv = document.createElement('div');
                    newDiv.className = 'author_item';
                    newDiv.innerHTML = `
                        <label style="display:flex; align-items:center; cursor:pointer; font-size:0.9rem; background-color: #e6fffa;">
                            <input type="checkbox" name="autori[]" value="${data.id}" checked style="margin-right:8px;">
                            <span class="auth_name">${data.name}</span> <b style="margin-left:5px; color:green;">(Nuovo)</b>
                        </label>
                    `;
                    container.insertBefore(newDiv, container.firstChild);
                    input.value = '';
                    filterAuthors(); 
                    container.scrollTop = 0;
                } else {
                    alert("Errore: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.disabled = false;
                alert("Errore di connessione.");
            });
        }
    </script>

<?php require_once './src/includes/footer.php'; ?>