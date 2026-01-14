<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

$MAX_CHARS = 1000;

$messaggio_db = "";
$server_message = ""; 
$libro = null;
$autori = [];
$categorie = [];
$recensioni_altri = [];
$mia_recensione = null;
$mediaVoto = 0;
$totaleRecensioni = 0;

$lista_biblioteche = [];
$ids_disponibili = []; 
$ids_in_prestito = [];
$elenco_copie_dettagliato = []; 

$userHasAnyLoan = false; 

$isbn = $_GET['isbn'] ?? null;
$uid = $_SESSION['codice_utente'] ?? null;
$query_uid = $uid ? $uid : 'GUEST';

if (!$isbn) {
    die("<h1>Errore</h1><p>ISBN non specificato.</p>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!$uid) {
         header("Location: ./libro?isbn=" . $isbn . "&status=login_needed");
         exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'prenota_copia') {
        $id_copia_target = filter_input(INPUT_POST, 'id_copia', FILTER_VALIDATE_INT);

        if ($id_copia_target) {
            try {
                $pdo->beginTransaction();

                $stmt_loan_check = $pdo->prepare("SELECT 1 FROM prestiti p JOIN copie c ON p.id_copia = c.id_copia WHERE p.codice_alfanumerico = :uid AND c.isbn = :isbn AND p.data_restituzione IS NULL");
                $stmt_loan_check->execute(['uid' => $uid, 'isbn' => $isbn]);

                if ($stmt_loan_check->rowCount() > 0) {
                    $pdo->rollBack();
                    header("Location: ./libro?isbn=" . $isbn . "&status=loan_active_error");
                    exit;
                }

                $stmt_res_check = $pdo->prepare("SELECT 1 FROM prenotazioni WHERE codice_alfanumerico = :uid AND id_copia = :id_copia AND data_assegnazione IS NULL");
                $stmt_res_check->execute(['uid' => $uid, 'id_copia' => $id_copia_target]);

                if ($stmt_res_check->rowCount() > 0) {
                    $pdo->rollBack();
                    header("Location: ./libro?isbn=" . $isbn . "&status=already_reserved_this");
                    exit;
                }

                $stmt_cleanup = $pdo->prepare("DELETE p FROM prenotazioni p INNER JOIN copie c ON p.id_copia = c.id_copia WHERE p.codice_alfanumerico = :uid AND c.isbn = :isbn AND p.data_assegnazione IS NULL");
                $stmt_cleanup->execute(['uid' => $uid, 'isbn' => $isbn]);

                $stmt_ins = $pdo->prepare("INSERT INTO prenotazioni (codice_alfanumerico, id_copia, data_prenotazione) VALUES (:uid, :id_copia, CURDATE())");
                $stmt_ins->execute(['uid' => $uid, 'id_copia' => $id_copia_target]);
                
                $pdo->commit();
                header("Location: ./libro?isbn=" . $isbn . "&status=reserved_success");
                exit;

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                header("Location: ./libro?isbn=" . $isbn . "&status=error");
                exit;
            }
        }
    }

    if (isset($_POST['submit_review'])) {
        $voto = filter_input(INPUT_POST, 'voto', FILTER_VALIDATE_INT);
        $raw_commento = $_POST['commento'] ?? '';
        $commento = trim(filter_var($raw_commento, FILTER_SANITIZE_STRING));
        $mode = $_POST['mode'] ?? 'insert';

        if (strlen($commento) > $MAX_CHARS) {
             header("Location: ./libro?isbn=" . $isbn . "&status=toolong");
             exit;
        } elseif ($voto < 1 || $voto > 5 || empty($commento)) {
             header("Location: ./libro?isbn=" . $isbn . "&status=invalid");
             exit;
        } else {
            try {
                if ($mode === 'update') {
                    $stmt = $pdo->prepare("UPDATE recensioni SET voto = ?, commento = ?, data_commento = NOW() WHERE isbn = ? AND codice_alfanumerico = ?");
                    $stmt->execute([$voto, $commento, $isbn, $uid]);
                    $msg_type = "updated";
                } else {
                    $chk = $pdo->prepare("SELECT 1 FROM recensioni WHERE isbn = ? AND codice_alfanumerico = ?");
                    $chk->execute([$isbn, $uid]);
                    if (!$chk->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO recensioni (isbn, codice_alfanumerico, voto, commento, data_commento) VALUES (?, ?, ?, ?, NOW())");
                        $stmt->execute([$isbn, $uid, $voto, $commento]);
                        $msg_type = "created";
                    } else {
                        $msg_type = "exists";
                    }
                }
                header("Location: ./libro?isbn=" . $isbn . "&status=" . $msg_type);
                exit;
            } catch (PDOException $e) {
                header("Location: ./libro?isbn=" . $isbn . "&status=error");
                exit;
            }
        }
    }
}

if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'created': $server_message = "Recensione pubblicata con successo!"; break;
        case 'updated': $server_message = "Recensione aggiornata!"; break;
        case 'exists': $server_message = "Hai già recensito questo libro."; break;
        case 'login_needed': $server_message = "Devi accedere per eseguire l'operazione."; break;
        case 'toolong': $server_message = "Commento troppo lungo."; break;
        case 'invalid': $server_message = "Compila tutti i campi."; break;
        case 'error': $server_message = "Errore di sistema."; break;
        case 'reserved_success': $server_message = "Prenotazione effettuata! Eventuali altre prenotazioni per questo libro sono state cancellate."; break;
        case 'already_reserved_this': $server_message = "Sei già prenotato per questa specifica copia."; break;
        case 'loan_active_error': $server_message = "Hai già questo libro in prestito! Restituiscilo prima di prenotarne un altro."; break;
    }
}

try {
    $stmt = $pdo->prepare("SELECT l.*, (SELECT editore FROM copie c WHERE c.isbn = l.isbn LIMIT 1) as editore_temp, (SELECT COUNT(*) FROM copie c LEFT JOIN prestiti p ON c.id_copia = p.id_copia AND p.data_restituzione IS NULL WHERE c.isbn = l.isbn AND p.id_prestito IS NULL) as numero_copie_disponibili FROM libri l WHERE l.isbn = ?");
    $stmt->execute([$isbn]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($libro) {
        $libro['editore'] = $libro['editore_temp'] ?? 'N/D';

        $stmt = $pdo->prepare("SELECT a.nome, a.cognome FROM autori a JOIN autore_libro al ON al.id_autore = a.id_autore WHERE al.isbn = ?");
        $stmt->execute([$isbn]);
        $autori = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT categoria FROM categorie c JOIN libro_categoria lc ON lc.id_categoria = c.id_categoria WHERE lc.isbn = ?");
        $stmt->execute([$isbn]);
        $categorie = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT CAST(AVG(voto) AS DECIMAL(3,1)) as media, COUNT(*) as totale FROM recensioni WHERE isbn = ?");
        $stmt->execute([$isbn]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $mediaVoto = $stats['media'] ? number_format((float)$stats['media'], 1) : 0;
        $totaleRecensioni = $stats['totale'];

        if ($uid) {
            $stmt = $pdo->prepare("SELECT r.*, u.username, u.codice_alfanumerico as id_recensore, (SELECT 1 FROM prestiti p JOIN copie c ON p.id_copia = c.id_copia WHERE p.codice_alfanumerico = u.codice_alfanumerico AND c.isbn = r.isbn LIMIT 1) as ha_letto FROM recensioni r JOIN utenti u ON r.codice_alfanumerico = u.codice_alfanumerico WHERE r.isbn = ? AND r.codice_alfanumerico = ?");
            $stmt->execute([$isbn, $uid]);
            $mia_recensione = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $sqlAltri = "SELECT r.*, u.username, u.codice_alfanumerico as id_recensore, (SELECT 1 FROM prestiti p JOIN copie c ON p.id_copia = c.id_copia WHERE p.codice_alfanumerico = u.codice_alfanumerico AND c.isbn = r.isbn LIMIT 1) as ha_letto FROM recensioni r JOIN utenti u ON r.codice_alfanumerico = u.codice_alfanumerico WHERE r.isbn = ? ";
        if ($uid) { $sqlAltri .= " AND r.codice_alfanumerico != ? "; }
        $sqlAltri .= " ORDER BY r.data_commento DESC";
        
        $stmt = $pdo->prepare($sqlAltri);
        if ($uid) $stmt->execute([$isbn, $uid]);
        else $stmt->execute([$isbn]);
        $recensioni_altri = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt_bib = $pdo->query("SELECT id, nome, indirizzo, lat, lon, orari FROM biblioteche");
        $lista_biblioteche = $stmt_bib->fetchAll(PDO::FETCH_ASSOC);

        $sqlCopie = "SELECT c.id_copia, c.condizione, c.anno_edizione, c.id_biblioteca, b.nome as nome_biblioteca, b.indirizzo as indirizzo_biblioteca, b.lat, b.lon, (CASE WHEN p.id_prestito IS NOT NULL THEN 1 ELSE 0 END) as in_prestito, (SELECT COUNT(*) FROM prestiti p2 WHERE p2.id_copia = c.id_copia AND p2.codice_alfanumerico = :uid AND p2.data_restituzione IS NULL) as user_has_loan, (SELECT COUNT(*) FROM prenotazioni r2 WHERE r2.id_copia = c.id_copia AND r2.codice_alfanumerico = :uid AND r2.data_assegnazione IS NULL) as user_has_res FROM copie c JOIN biblioteche b ON c.id_biblioteca = b.id LEFT JOIN prestiti p ON c.id_copia = p.id_copia AND p.data_restituzione IS NULL WHERE c.isbn = :isbn ORDER BY in_prestito ASC, c.condizione DESC, b.nome ASC";
        $stmt_c = $pdo->prepare($sqlCopie);
        $stmt_c->execute(['isbn' => $isbn, 'uid' => $query_uid]);
        $elenco_copie_dettagliato = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

        foreach($elenco_copie_dettagliato as $ec) {
            if ($ec['in_prestito'] == 0) {
                if (!in_array($ec['id_biblioteca'], $ids_disponibili)) $ids_disponibili[] = $ec['id_biblioteca'];
            } else {
                if (!in_array($ec['id_biblioteca'], $ids_in_prestito)) $ids_in_prestito[] = $ec['id_biblioteca'];
            }
            if ($ec['user_has_loan'] == 1) {
                $userHasAnyLoan = true;
            }
        }

    } else {
        $messaggio_db = "Libro non trovato.";
    }

} catch (PDOException $e) {
    $messaggio_db = "Errore DB: " . $e->getMessage();
}

function getCoverPath($isbn) {
    $localPath = "public/bookCover/$isbn.png";
    return file_exists($localPath) ? $localPath : "public/assets/book_placeholder.jpg";
}

function getPfpPath($userId) {
    $path = "public/pfp/$userId.png";
    return file_exists($path) ? $path . '?v=' . time() : "public/assets/base_pfp.png";
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* ==========================================================================
       SEZIONE: LAYOUT GENERALE E STICKY HEADER
       ========================================================================== */
    
    /* [sticky_limit_wrapper]
       Contenitore padre che avvolge tutto il contenuto (libro, mappa e lista copie)
       Serve a definire fin dove l'elemento sticky può scorrere.
    */
    .sticky_limit_wrapper { position: relative; }

    /* [sticky_header_wrapper]
       La barra superiore che contiene il libro e la mappa.
       'position: sticky' la fa rimanere incollata in alto durante lo scroll.
       'pointer-events: none' serve a far passare i click attraverso gli spazi vuoti.
    */
    .sticky_header_wrapper {
        position: -webkit-sticky; position: sticky; top: 0; z-index: 800; 
        background-color: #fcfcfc; border-bottom: 1px solid #ddd; padding: 20px 0;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); pointer-events: none;
    }

    /* [book_map_row]
       Contenitore Flexbox che affianca la colonna del libro (sinistra) e la mappa (destra).
    */
    .book_map_row { display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start; width: 100%; max-width: 98vw; margin: 0 auto; }

    /* ==========================================================================
       SEZIONE: DETTAGLI LIBRO (Colonna Sinistra)
       ========================================================================== */

    /* [col_libro]
       Colonna di sinistra contenente l'immagine e i dettagli del libro.
       'pointer-events: auto' riabilita i click disabilitati dal wrapper sticky.
    */
    .col_libro { flex: 1 1 500px; min-width: 300px; pointer-events: auto; }

    /* [book_hero_card] Card principale del libro */
    .book_hero_card { margin-bottom: 0; height: auto; background: transparent; box-shadow: none; }

    /* [book_desc_text] Testo della trama. Ha una max-height e overflow per gestire trame lunghe */
    .book_desc_text { max-height: 250px; overflow-y: auto; padding-right: 5px; scrollbar-width: thin; }

    /* ==========================================================================
       SEZIONE: MAPPA (Colonna Destra)
       ========================================================================== */

    /* [col_mappa]
       Colonna di destra contenente il box della mappa Leaflet.
    */
    .col_mappa { flex: 1 1 400px; min-width: 300px; display: flex; flex-direction: column; pointer-events: auto; }

    /* [mappa_wrapper] Box bianco che racchiude il div #map */
    .mappa_wrapper { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px; height: 100%; min-height: 450px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; flex-direction: column; }

    /* [map] L'elemento HTML dove Leaflet renderizza la mappa */
    #map { flex-grow: 1; width: 100%; border-radius: 4px; min-height: 350px; }
    
    /* ==========================================================================
       SEZIONE: LISTA COPIE (Sotto Mappa e Libro)
       ========================================================================== */

    /* [copies_container] Contenitore generale della lista copie */
    .copies_container { max-width: 1100px; margin: 40px auto; padding: 0 20px; min-height: 200px; }

    /* [copy_banner]
       La singola riga/banner che rappresenta una copia fisica del libro.
       Contiene immagine, titolo, condizione e pulsante prenota.
    */
    .copy_banner { display: flex; align-items: center; background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px; transition: all 0.2s ease; cursor: pointer; gap: 20px; position: relative; z-index: 5; }
    .copy_banner:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); border-color: #bbb; }
    
    /* [active-highlight] Classe aggiunta via JS quando si clicca un banner */
    .copy_banner.active-highlight { border: 2px solid #3498db; background-color: #f0f8ff; }

    .copy_img { width: 60px; height: 85px; object-fit: cover; border-radius: 4px; flex-shrink: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    .copy_info { flex-grow: 1; }
    .copy_title { font-weight: bold; font-size: 1.1rem; margin-bottom: 4px; color: #2c3e50; }
    .copy_meta { font-size: 0.9rem; color: #555; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }

    /* [cond-bar-wrapper] La barra grafica a segmenti per la condizione (0-3) */
    .cond-bar-wrapper { display: flex; gap: 2px; align-items: center; background: #eee; padding: 2px; border-radius: 3px; }
    .cond-segment { width: 10px; height: 10px; border-radius: 1px; background-color: #ddd; }

    .copy_library_info { margin-top: 5px; font-size: 0.95rem; color: #333; }
    .copy_actions { flex-shrink: 0; position: relative; }

    /* [btn_prenota] Pulsante per prenotare la copia */
    .btn_prenota { background-color: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: background 0.2s; min-width: 120px; }
    .btn_prenota:hover { background-color: #2980b9; }
    
    /* [btn_disabled] Stile per pulsante disabilitato (es. già prenotato) */
    .btn_disabled { background-color: #bdc3c7; color: #7f8c8d; cursor: not-allowed; }
    .btn_disabled:hover { background-color: #bdc3c7; }

    /* [badge_read] Il badge verde "Letto" nelle recensioni */
    .badge_read { display: inline-flex; align-items: center; gap: 5px; font-size: 0.8em; font-weight: 600; color: #27ae60; background-color: #eafaf1; padding: 2px 8px; border-radius: 12px; margin-left: 10px; border: 1px solid #27ae60; }
    .badge_read svg { width: 14px; height: 14px; fill: #27ae60; }

    /* [load_more_btn] Pulsante "Mostra altre copie" */
    .load_more_btn { display: block; width: 200px; margin: 20px auto; padding: 10px; background: #eee; border: 1px solid #ccc; text-align: center; border-radius: 20px; cursor: pointer; font-weight: bold; color: #555; }
    .load_more_btn:hover { background: #ddd; }

    /* ==========================================================================
       SEZIONE: TOOLTIP E POPUP
       ========================================================================== */

    /* Tooltip personalizzato che appare sui pulsanti disabilitati */
    .tooltip-wrapper { position: relative; display: inline-block; }
    .tooltip-wrapper:hover .custom-tooltip { visibility: visible; opacity: 1; }
    .custom-tooltip { visibility: hidden; width: 160px; background-color: #333; color: #fff; text-align: center; border-radius: 6px; padding: 8px; position: absolute; z-index: 100; bottom: 125%; left: 50%; transform: translateX(-50%); opacity: 0; transition: opacity 0.3s; font-size: 0.8rem; font-weight: normal; pointer-events: none; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
    .custom-tooltip::after { content: ""; position: absolute; top: 100%; left: 50%; margin-left: -5px; border-width: 5px; border-style: solid; border-color: #333 transparent transparent transparent; }

    /* Override Z-Index per popup Leaflet e controlli mappa */
    .leaflet-pane.leaflet-popup-pane { z-index: 10000 !important; }
    .leaflet-control-resetmap { background: white; padding: 6px 10px; border-radius: 4px; border: 1px solid #888; cursor: pointer; font-size: 13px; box-shadow: 0 2px 6px rgba(0,0,0,0.2); margin-top: 5px; }
    .leaflet-control-resetmap:hover { background: #f0f0f0; }
</style>

<?php
$title = $libro['titolo'] ?? 'Libro';
    $page_css = "./public/css/style_index.css";
    require './src/includes/header.php';
    require './src/includes/navbar.php';
?>

    <div class="page_contents">

        <?php if ($messaggio_db || !$libro): ?>
            <div class="alert_box danger" style="margin-top:40px;">
                <h1>Ops!</h1>
                <p><?= htmlspecialchars($messaggio_db ?: "Libro non trovato.") ?></p>
                <a href="./" class="btn_send" style="text-decoration:none;">Torna alla Home</a>
            </div>
        <?php else: ?>

            <div class="sticky_limit_wrapper">
                <div class="sticky_header_wrapper">
                    <div class="book_map_row">
                        <div class="col_libro">
                            <div class="book_hero_card">
                                <div class="book_hero_left">
                                    <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Cover" class="book_hero_cover">
                                </div>
                                <div class="book_hero_right">
                                    <h1 class="book_main_title"><?= htmlspecialchars($libro['titolo']) ?></h1>
                                    <div class="book_authors">
                                        di <?= htmlspecialchars(implode(', ', array_map(fn($a) => $a['nome'] . ' ' . $a['cognome'], $autori))) ?>
                                    </div>
                                    <div class="meta_info_grid">
                                        <span><strong>Editore:</strong> <?= htmlspecialchars($libro['editore']) ?></span>
                                        <span><strong>Anno:</strong> <?= htmlspecialchars($libro['anno_pubblicazione'] ?? 'N/D') ?></span>
                                    </div>
                                    <?php if ($categorie): ?>
                                        <div class="book_tags">
                                            <?php foreach($categorie as $cat): ?>
                                                <span class="tag_pill"><?= htmlspecialchars($cat) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div style="margin-bottom: 20px;">
                                        <?php if ($libro['numero_copie_disponibili'] > 0): ?>
                                            <span class="badge_avail badge_ok">Disponibile (<?= $libro['numero_copie_disponibili'] ?>)</span>
                                        <?php else: ?>
                                            <span class="badge_avail badge_ko">Non disponibile</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="book_desc_box">
                                        <h3 class="book_desc_title">Trama</h3>
                                        <div class="book_desc_text"><?= nl2br(htmlspecialchars($libro['descrizione'])) ?></div>
                                    </div>
                                </div>
                            </div> 
                        </div> 
                        <div class="col_mappa">
                            <div class="mappa_wrapper">
                                <h3 style="margin-top:0; margin-bottom:10px; font-size:1.1rem; color:#333;">Disponibilità in zona</h3>
                                <p style="font-size: 0.85em; margin-bottom: 10px; color:#666;">
                                    <span style="color: green; font-weight: bold;">&#9679;</span> Disponibile &nbsp;
                                    <span style="color: #FFD700; font-weight: bold; text-shadow: 0px 0px 1px #999;">&#9679;</span> In prestito &nbsp;
                                    <span style="color: red; font-weight: bold;">&#9679;</span> Non disp.
                                </p>
                                <div id="map"></div>
                            </div>
                        </div> 
                    </div> 
                </div>
                
                <div class="copies_container">
                    <h2 style="font-family: 'Young Serif', serif; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">
                        Copie Disponibili (<?= count($elenco_copie_dettagliato) ?>)
                    </h2>
                    <div id="copies-list-wrapper"></div>
                    <button id="load-more-copies" class="load_more_btn" style="display:none;" onclick="renderNextBatch()">Mostra altre copie</button>
                </div>
            </div>

            <div class="reviews_section">
                <h2 class="reviews_title">Recensioni (<?= $totaleRecensioni ?>)</h2>
                <?php if ($uid): ?>
                    <?php if ($mia_recensione): ?>
                        <div class="review_card my-review">
                            <div class="review_avatar_col"><img src="<?= getPfpPath($mia_recensione['id_recensore']) ?>" alt="Io" class="review_pfp"></div>
                            <div class="review_content_col">
                                <div id="my_review_view">
                                    <div class="review_header_row">
                                        <div class="review_user"><?= htmlspecialchars($mia_recensione['username']) ?></div>
                                        <span class="review_date"><?= date('d/m/Y', strtotime($mia_recensione['data_commento'])) ?></span>
                                    </div>
                                    <div class="review_stars_text" style="display:flex; align-items:center;">
                                        <?php for ($i = 0; $i < $mia_recensione['voto']; $i++) echo "<span class='star_yellow'>★</span>"; for ($i = $mia_recensione['voto']; $i < 5; $i++) echo "<span class='star_grey'>★</span>"; ?>
                                        <?php if(isset($mia_recensione['ha_letto']) && $mia_recensione['ha_letto']): ?>
                                            <span class="badge_read" title="Utente ha preso in prestito questo libro">
                                                <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                                Letto &#10003;
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="review_body"><?= nl2br(htmlspecialchars($mia_recensione['commento'])) ?></div>
                                    <button type="button" class="btn_edit_circular" onclick="toggleEditMode()" title="Modifica"><svg viewBox="0 0 24 24" class="icon_pencil"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                                </div>
                                <div id="my_review_edit" class="hidden">
                                    <h3 class="young-serif-regular" style="margin-top:0; color:#f39c12; margin-bottom: 20px;">Modifica</h3>
                                    <form method="POST" action="./libro?isbn=<?= $isbn ?>">
                                        <input type="hidden" name="mode" value="update"><input type="hidden" name="voto" id="voto_edit_input" value="<?= $mia_recensione['voto'] ?>">
                                        <div class="interactive-rating" id="rating_edit"><span class="star-input" data-value="1">★</span><span class="star-input" data-value="2">★</span><span class="star-input" data-value="3">★</span><span class="star-input" data-value="4">★</span><span class="star-input" data-value="5">★</span></div>
                                        <div style="margin-bottom:10px;"><label class="form_label">Commento</label><textarea name="commento" id="commento_edit" rows="4" required class="form_field" maxlength="<?= $MAX_CHARS ?>" oninput="updateCharCount(this)"><?= htmlspecialchars($mia_recensione['commento']) ?></textarea><div class="char_counter">0 / <?= $MAX_CHARS ?></div></div>
                                        <div style="display:flex; gap:10px; justify-content: flex-end;"><button type="button" onclick="toggleEditMode()" class="btn_send" style="background:#ccc;">Annulla</button><button type="submit" name="submit_review" class="btn_send">Salva</button></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="review_form_box">
                            <h3 class="young-serif-regular" style="margin-top:0; margin-bottom: 20px;">Lascia un pensiero</h3>
                            <form method="POST" action="./libro?isbn=<?= $isbn ?>">
                                <input type="hidden" name="mode" value="insert"><input type="hidden" name="voto" id="voto_new_input" value="0">
                                <div class="interactive-rating" id="rating_new"><span class="star-input" data-value="1">★</span><span class="star-input" data-value="2">★</span><span class="star-input" data-value="3">★</span><span class="star-input" data-value="4">★</span><span class="star-input" data-value="5">★</span></div>
                                <div style="margin-bottom:15px;"><label for="commento" class="form_label">Commento</label><textarea name="commento" id="commento_new" rows="5" required class="form_field" maxlength="<?= $MAX_CHARS ?>" oninput="updateCharCount(this)" placeholder="Scrivi qui la tua recensione dettagliata..."></textarea><div class="char_counter">0 / <?= $MAX_CHARS ?></div></div>
                                <button type="submit" name="submit_review" class="btn_send">Pubblica Recensione</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert_box login"><p>Vuoi lasciare una recensione? <a href="./login" style="color:#333; font-weight:bold;">Accedi</a> per dirci la tua!</p></div>
                <?php endif; ?>
                <?php if ($recensioni_altri): ?>
                    <div class="reviews_list">
                        <?php foreach ($recensioni_altri as $r): ?>
                            <div class="review_card">
                                <div class="review_avatar_col"><img src="<?= getPfpPath($r['id_recensore']) ?>" alt="Utente" class="review_pfp"></div>
                                <div class="review_content_col">
                                    <div class="review_header_row">
                                        <div class="review_user"><?= htmlspecialchars($r['username']) ?></div>
                                        <span class="review_date"><?= date('d/m/Y', strtotime($r['data_commento'])) ?></span>
                                    </div>
                                    <div class="review_stars_text" style="display:flex; align-items:center;">
                                        <?php for ($i = 0; $i < $r['voto']; $i++) echo "<span class='star_yellow'>★</span>"; for ($i = $r['voto']; $i < 5; $i++) echo "<span class='star_grey'>★</span>"; ?>
                                        <?php if(isset($r['ha_letto']) && $r['ha_letto']): ?>
                                            <span class="badge_read" title="Utente ha preso in prestito questo libro">
                                                <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                                Letto &#10003;
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="review_body"><?= nl2br(htmlspecialchars($r['commento'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <?php if (!$mia_recensione): ?><p style="text-align:center; color:#888; margin-top:30px; font-size:1.1em;">Ancora nessuna recensione. Sii il primo a scriverne una!</p><?php endif; ?>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>

    <div id="notification-banner"><span id="banner-msg" class="notification-text">Notifica</span><button class="close-btn-banner" onclick="hideNotification()">&times;</button></div>

<script>
    let timeoutId;
    function showNotification(message) {
        const banner = document.getElementById('notification-banner');
        const msgSpan = document.getElementById('banner-msg');
        msgSpan.innerText = message;
        banner.classList.add('show');
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(() => { hideNotification(); }, 5000);
    }
    function hideNotification() { document.getElementById('notification-banner').classList.remove('show'); }
    const serverMessage = "<?= addslashes($server_message) ?>";
    if (serverMessage.length > 0) { setTimeout(() => { showNotification(serverMessage); }, 500); }

    const allCopies = <?php echo json_encode($elenco_copie_dettagliato, JSON_UNESCAPED_UNICODE); ?>;
    const coverUrl = "<?= getCoverPath($libro['isbn']) ?>";
    let displayedCount = 0;
    const batchSize = 5;
    let libraryMarkers = {};
    const globalLoanBlock = <?= $userHasAnyLoan ? 'true' : 'false' ?>; 

    function renderCondBar(val) {
        let color = "#e0e0e0"; let filledCount = 0;
        if (val === 1) { color = "#f1c40f"; filledCount = 1; }
        else if (val === 2) { color = "#2ecc71"; filledCount = 2; }
        else if (val === 3) { color = "#27ae60"; filledCount = 3; }
        else if (val === 0) { color = "#c0392b"; filledCount = 0; } 
        let html = '<div class="cond-bar-wrapper" title="Condizione: '+val+'/3">';
        for(let i=0; i<3; i++) { let bgColor = (i < filledCount) ? color : "#ddd"; html += `<div class="cond-segment" style="background-color:${bgColor};"></div>`; }
        html += '</div>'; return html;
    }

    function renderNextBatch() {
        const wrapper = document.getElementById('copies-list-wrapper');
        const btn = document.getElementById('load-more-copies');
        const total = allCopies.length;
        const nextLimit = Math.min(displayedCount + batchSize, total);
        
        for (let i = displayedCount; i < nextLimit; i++) {
            const copy = allCopies[i];
            const isLoaned = copy.in_prestito == 1;
            const isUserLoan = copy.user_has_loan == 1;
            const isUserRes = copy.user_has_res == 1;
            
            let btnText = "Prenota";
            let btnClass = "btn_prenota";
            let btnDisabledAttr = "";
            let tooltipText = "";

            if (isUserLoan) {
                btnText = "In tuo possesso";
                btnClass += " btn_disabled";
                btnDisabledAttr = "disabled";
                tooltipText = "Hai già questa copia in prestito";
            } 
            else if (globalLoanBlock) {
                btnText = "Hai già il libro";
                btnClass += " btn_disabled";
                btnDisabledAttr = "disabled";
                tooltipText = "Hai già una copia di questo libro in prestito.";
            }
            else if (isUserRes) {
                btnText = "Prenotato";
                btnClass += " btn_disabled";
                btnDisabledAttr = "disabled";
                tooltipText = "Hai già una prenotazione attiva per questa copia";
            } 
            else if (isLoaned) {
                btnText = "Prenota (Coda)";
            }

            const statusBadge = isLoaned 
                ? '<span style="color:#f39c12; font-weight:bold; margin-right:10px;">&#9679; In Prestito</span>'
                : '<span style="color:#27ae60; font-weight:bold; margin-right:10px;">&#9679; Disponibile</span>';

            const condBar = renderCondBar(parseInt(copy.condizione));
            const div = document.createElement('div');
            div.className = 'copy_banner';
            div.setAttribute('data-bib-id', copy.id_biblioteca);
            
            div.onmouseenter = () => highlightMarker(copy.id_biblioteca);
            div.onmouseleave = () => resetMarker(copy.id_biblioteca);
            div.onclick = (e) => {
                if(e.target.tagName !== 'BUTTON' && !e.target.closest('button')) {
                    activateMarker(copy.id_biblioteca);
                    document.querySelectorAll('.copy_banner').forEach(b => b.classList.remove('active-highlight'));
                    div.classList.add('active-highlight');
                }
            };

            const tooltipHtml = tooltipText ? `<span class="custom-tooltip">${tooltipText}</span>` : '';

            div.innerHTML = `
                <img src="${coverUrl}" class="copy_img" alt="book">
                <div class="copy_info">
                    <div class="copy_title"><?= addslashes($libro['titolo']) ?></div>
                    <div class="copy_meta">
                        ${statusBadge}
                        <div style="display:flex; align-items:center; gap:5px;">
                            <span style="font-size:0.85em; color:#666;">Condizione:</span>
                            ${condBar}
                        </div>
                        <span>Ed. ${copy.anno_edizione}</span>
                    </div>
                    <div class="copy_library_info">
                        <strong>${copy.nome_biblioteca}</strong> - ${copy.indirizzo_biblioteca}
                    </div>
                </div>
                <div class="copy_actions tooltip-wrapper">
                    <form method="POST" action="./libro?isbn=<?= $isbn ?>">
                        <input type="hidden" name="action" value="prenota_copia">
                        <input type="hidden" name="id_copia" value="${copy.id_copia}">
                        <button type="submit" class="${btnClass}" ${btnDisabledAttr}>${btnText}</button>
                    </form>
                    ${tooltipHtml}
                </div>
            `;
            wrapper.appendChild(div);
        }
        displayedCount = nextLimit;
        if (displayedCount >= total) { btn.style.display = 'none'; } else { btn.style.display = 'block'; }
    }

    function highlightMarker(bibId) { const marker = libraryMarkers[bibId]; if(!marker) return; marker.setZIndexOffset(2000); if(marker._icon) { marker._icon.style.transition = "transform 0.2s"; marker._icon.style.transform += " scale(1.2)"; } }
    function resetMarker(bibId) { const marker = libraryMarkers[bibId]; if(!marker) return; if(marker._icon) { marker._icon.style.transform = marker._icon.style.transform.replace(" scale(1.2)", ""); } marker.setZIndexOffset(marker.options.zIndexOffset || 0); }
    function activateMarker(bibId) { const marker = libraryMarkers[bibId]; if(!marker) return; map.setView(marker.getLatLng(), 14); marker.openPopup(); if(marker._icon) { marker._icon.style.transform += " scale(1.3)"; } }

    function initStarRating(containerId, inputId) {
        const container = document.getElementById(containerId); const input = document.getElementById(inputId); if (!container || !input) return;
        const stars = container.querySelectorAll('.star-input');
        const paintStars = (value, className) => { stars.forEach(star => { const sVal = parseInt(star.getAttribute('data-value')); if (sVal <= value) star.classList.add(className); else star.classList.remove(className); }); };
        paintStars(parseInt(input.value), 'active');
        stars.forEach(star => {
            star.addEventListener('mouseover', function() { paintStars(parseInt(this.getAttribute('data-value')), 'hover'); });
            star.addEventListener('mouseout', function() { stars.forEach(s => s.classList.remove('hover')); paintStars(parseInt(input.value), 'active'); });
            star.addEventListener('click', function() { input.value = parseInt(this.getAttribute('data-value')); paintStars(input.value, 'active'); });
        });
    }
    function toggleEditMode() { const viewDiv = document.getElementById('my_review_view'); const editDiv = document.getElementById('my_review_edit'); if (viewDiv.classList.contains('hidden')) { viewDiv.classList.remove('hidden'); editDiv.classList.add('hidden'); } else { viewDiv.classList.add('hidden'); editDiv.classList.remove('hidden'); updateCharCount(document.getElementById('commento_edit')); } }
    function updateCharCount(textarea) { const max = <?= $MAX_CHARS ?>; const current = textarea.value.length; const counterDiv = textarea.nextElementSibling; counterDiv.innerText = current + " / " + max; if (current >= max) { counterDiv.classList.add('limit-reached'); counterDiv.classList.remove('limit-near'); } else if (current >= max * 0.9) { counterDiv.classList.add('limit-near'); counterDiv.classList.remove('limit-reached'); } else { counterDiv.classList.remove('limit-reached', 'limit-near'); } }

    let map; 
    function initMap() {
        let biblioteche = <?php echo json_encode($lista_biblioteche, JSON_UNESCAPED_UNICODE); ?>;
        const idsGreen = <?php echo json_encode($ids_disponibili); ?>;
        const idsLoaned = <?php echo json_encode($ids_in_prestito); ?>;
        if (!document.getElementById('map')) return;
        const boundsVeneto = L.latLngBounds([44.7, 10.5], [46.8, 13.2]);
        const centerDefault = [45.5470, 11.5396];
        map = L.map('map', { center: centerDefault, zoom: 9, minZoom: 8, maxZoom: 18, maxBounds: boundsVeneto, maxBoundsViscosity: 1.0 });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OSM' }).addTo(map);
        const greenIcon = new L.Icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });
        const yellowIcon = new L.Icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-yellow.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });
        const redIcon = new L.Icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });
        const userIcon = new L.Icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });
        const orariStandard = "Lun: 14:00-19:00<br>Mar-Ven: 9-13, 14-19<br>Sab: 9-13";

        biblioteche.forEach(bib => {
            let icona = redIcon;
            let statoTesto = '<span style="color:red; font-weight:bold;">Non disponibile</span>';
            let zIndex = 0; 
            if (idsGreen.some(id => id == bib.id)) { icona = greenIcon; statoTesto = '<span style="color:green; font-weight:bold;">Disponibile qui</span>'; zIndex = 1000; } 
            else if (idsLoaned.some(id => id == bib.id)) { icona = yellowIcon; statoTesto = '<span style="color:#FFD700; font-weight:bold;">In prestito (Prenotabile)</span>'; zIndex = 500; }
            const marker = L.marker([bib.lat, bib.lon], { icon: icona, zIndexOffset: zIndex }).addTo(map);
            libraryMarkers[bib.id] = marker;
            const popupContent = `<div style="font-family: sans-serif; min-width: 200px;"><strong style="font-size:14px;">${bib.nome}</strong><br><small>${bib.indirizzo}</small><br><div style="margin: 8px 0;">${statoTesto}</div><hr style="margin:5px 0; border:0; border-top:1px solid #eee;"><div style="font-size:12px; line-height:1.4;">${bib.orari ? bib.orari : orariStandard}</div></div>`;
            marker.bindPopup(popupContent);
        });

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
                const lat = position.coords.latitude; const lon = position.coords.longitude; const uLatLng = new L.LatLng(lat, lon);
                const uMarker = L.marker(uLatLng, {icon: userIcon, zIndexOffset: 2000}).addTo(map); uMarker.bindPopup("<b>Tu sei qui</b>");
                if(boundsVeneto.contains(uLatLng)) { map.setView(uLatLng, 12); }
            }, () => { console.warn("Geo error"); }, { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 });
        }
        const ResetControl = L.Control.extend({ options: { position: 'topleft' }, onAdd: function () { const container = L.DomUtil.create('div', 'leaflet-control-resetmap'); container.innerHTML = "Centra"; container.onclick = () => map.setView(centerDefault, 9); L.DomEvent.disableClickPropagation(container); return container; } });
        map.addControl(new ResetControl());
        setTimeout(() => { map.invalidateSize(); }, 200);
    }

    document.addEventListener("DOMContentLoaded", function() {
        initStarRating('rating_new', 'voto_new_input');
        initStarRating('rating_edit', 'voto_edit_input');
        const editTxt = document.getElementById('commento_edit');
        if(editTxt) updateCharCount(editTxt);
        initMap();
        renderNextBatch();
    });
</script>

<?php require './src/includes/footer.php'; ?>