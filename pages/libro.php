<?php
if (session_status() === PHP_SESSION_NONE) session_start();
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

if (!$isbn) die("<h1>Errore</h1><p>ISBN non specificato.</p>");

// --- Gestione POST (prenotazioni e recensioni) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$uid) {
        header("Location: ./libro?isbn=" . $isbn . "&status=login_needed"); exit;
    }
  
    // PRENOTAZIONE COPIA
    if (isset($_POST['action']) && $_POST['action'] === 'prenota_copia') {
        $id_copia_target = filter_input(INPUT_POST, 'id_copia', FILTER_VALIDATE_INT);
        if ($id_copia_target) {
            try {
                $pdo->beginTransaction();

                $stmt_loan_check = $pdo->prepare("
                    SELECT 1 
                    FROM prestiti p 
                    JOIN copie c ON p.id_copia = c.id_copia 
                    WHERE p.codice_alfanumerico = :uid AND c.isbn = :isbn AND p.data_restituzione IS NULL
                ");
                $stmt_loan_check->execute(['uid'=>$uid,'isbn'=>$isbn]);
                if ($stmt_loan_check->rowCount() > 0) { 
                    $pdo->rollBack(); 
                    header("Location: ./libro?isbn=$isbn&status=loan_active_error"); 
                    exit; 
                }

                // B. Controllo se l'utente è già in coda o assegnato per QUESTA copia specifica
                $chk_self = $pdo->prepare("SELECT 1 FROM prenotazioni WHERE id_copia = ? AND codice_alfanumerico = ?");
                $chk_self->execute([$id_copia_target, $uid]);
                
                if ($chk_self->rowCount() > 0) {
                    $pdo->rollBack();
                    header("Location: ./libro?isbn=" . $isbn . "&status=already_reserved_this");
                    exit;
                }

                // C. Pulizia: Rimuovi eventuali altre prenotazioni attive di QUESTO utente per QUESTO isbn (switch copia)
                $stmt_cleanup = $pdo->prepare("
                    DELETE p FROM prenotazioni p 
                    INNER JOIN copie c ON p.id_copia=c.id_copia 
                    WHERE p.codice_alfanumerico=:uid AND c.isbn=:isbn AND p.data_assegnazione IS NULL
                ");
                $stmt_cleanup->execute(['uid'=>$uid,'isbn'=>$isbn]);

                // D. Controllo Disponibilità Reale per Assegnazione Immediata vs Coda
                $stmt_status = $pdo->prepare("
                    SELECT 
                        (SELECT 1 FROM prestiti WHERE id_copia = :id_copia AND data_restituzione IS NULL) as is_loaned,
                        (SELECT 1 FROM prenotazioni WHERE id_copia = :id_copia AND data_assegnazione IS NOT NULL) as is_assigned,
                        (SELECT 1 FROM prenotazioni WHERE id_copia = :id_copia AND data_assegnazione IS NULL) as has_queue
                ");
                $stmt_status->execute(['id_copia' => $id_copia_target]);
                $status = $stmt_status->fetch(PDO::FETCH_ASSOC);

                $is_busy = ($status['is_loaned'] || $status['is_assigned'] || $status['has_queue']);
                
                $data_assegnazione = $is_busy ? null : date('Y-m-d');

                // E. Inserisci la prenotazione
                $stmt_ins = $pdo->prepare("INSERT INTO prenotazioni (codice_alfanumerico, id_copia, data_prenotazione, data_assegnazione) VALUES (:uid, :id_copia, CURDATE(), :da)");
                $stmt_ins->execute(['uid' => $uid, 'id_copia' => $id_copia_target, 'da' => $data_assegnazione]);
                
                $pdo->commit();

                if ($is_busy) {
                    header("Location: ./libro?isbn=" . $isbn . "&status=queue_joined");
                } else {
                    header("Location: ./libro?isbn=" . $isbn . "&status=reserved_success");
                }
                exit;

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                header("Location: ./libro?isbn=" . $isbn . "&status=error");
                exit;
            }
        }
    }

    // 2. RECENSIONI
    if (isset($_POST['submit_review'])) {
        $voto = filter_input(INPUT_POST,'voto',FILTER_VALIDATE_INT);
        $commento = trim(filter_var($_POST['commento'] ?? '', FILTER_SANITIZE_STRING));
        $mode = $_POST['mode'] ?? 'insert';

        if (strlen($commento)>$MAX_CHARS) { header("Location: ./libro?isbn=$isbn&status=toolong"); exit; }
        elseif ($voto<1||$voto>5||empty($commento)) { header("Location: ./libro?isbn=$isbn&status=invalid"); exit; }
        else {
            try {
                if($mode==='update'){
                    $stmt=$pdo->prepare("UPDATE recensioni SET voto=?, commento=?, data_commento=NOW() WHERE isbn=? AND codice_alfanumerico=?");
                    $stmt->execute([$voto,$commento,$isbn,$uid]); $msg_type="updated";
                } else {
                    $chk=$pdo->prepare("SELECT 1 FROM recensioni WHERE isbn=? AND codice_alfanumerico=?");
                    $chk->execute([$isbn,$uid]);
                    if (!$chk->fetch()) {
                        $stmt=$pdo->prepare("INSERT INTO recensioni (isbn,codice_alfanumerico,voto,commento,data_commento) VALUES(?,?,?,?,NOW())");
                        $stmt->execute([$isbn,$uid,$voto,$commento]); $msg_type="created";
                    } else $msg_type="exists";
                }
                header("Location: ./libro?isbn=$isbn&status=$msg_type"); exit;
            } catch(PDOException $e){
                header("Location: ./libro?isbn=$isbn&status=error"); exit;
            }
        }
    }
}

// --- MESSAGGI STATO ---
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'created': $server_message = "Recensione pubblicata con successo!"; break;
        case 'updated': $server_message = "Recensione aggiornata!"; break;
        case 'exists': $server_message = "Hai già recensito questo libro."; break;
        case 'login_needed': $server_message = "Devi accedere per eseguire l'operazione."; break;
        case 'toolong': $server_message = "Commento troppo lungo."; break;
        case 'invalid': $server_message = "Compila tutti i campi."; break;
        case 'error': $server_message = "Errore di sistema."; break;
        case 'reserved_success': $server_message = "Prenotazione Confermata! Hai 48h per ritirare il libro."; break;
        case 'queue_joined': $server_message = "Sei stato aggiunto alla coda. Ti avviseremo quando sarà il tuo turno."; break;
        case 'already_reserved_this': $server_message = "Hai già una prenotazione attiva per questa copia."; break;
        case 'loan_active_error': $server_message = "Hai già questo libro in prestito! Restituiscilo prima di prenderne un altro."; break;
    }
}

try {
    // 1. INFO LIBRO & CONTEGGIO DISPONIBILITÀ
    $stmt = $pdo->prepare("
        SELECT l.*, 
            (SELECT editore FROM copie c WHERE c.isbn = l.isbn LIMIT 1) as editore_temp, 
            (SELECT COUNT(*) 
             FROM copie c 
             WHERE c.isbn = l.isbn 
             AND c.id_copia NOT IN (SELECT id_copia FROM prestiti WHERE data_restituzione IS NULL)
             AND c.id_copia NOT IN (SELECT id_copia FROM prenotazioni WHERE data_assegnazione IS NOT NULL)
            ) as numero_copie_disponibili 
        FROM libri l 
        WHERE l.isbn = ?
    ");
    $stmt->execute([$isbn]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($libro) {
        $libro['editore'] = $libro['editore_temp'] ?? 'N/D';

        // Autori e Categorie
        $stmt = $pdo->prepare("SELECT a.nome, a.cognome FROM autori a JOIN autore_libro al ON al.id_autore = a.id_autore WHERE al.isbn = ?");
        $stmt->execute([$isbn]);
        $autori = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT categoria FROM categorie c JOIN libro_categoria lc ON lc.id_categoria = c.id_categoria WHERE lc.isbn = ?");
        $stmt->execute([$isbn]);
        $categorie = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Stats Recensioni
        $stmt = $pdo->prepare("SELECT CAST(AVG(voto) AS DECIMAL(3,1)) as media, COUNT(*) as totale FROM recensioni WHERE isbn = ?");
        $stmt->execute([$isbn]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $mediaVoto = $stats['media'] ? number_format((float)$stats['media'], 1) : 0;
        $totaleRecensioni = $stats['totale'];

        // Recensione Utente e controllo "Ha Letto"
        if ($uid) {
            $stmt = $pdo->prepare("
                SELECT r.*, u.username, u.codice_alfanumerico as id_recensore, 
                (SELECT 1 FROM prestiti p JOIN copie c ON p.id_copia = c.id_copia WHERE p.codice_alfanumerico = u.codice_alfanumerico AND c.isbn = r.isbn LIMIT 1) as ha_letto 
                FROM recensioni r 
                JOIN utenti u ON r.codice_alfanumerico = u.codice_alfanumerico 
                WHERE r.isbn = ? AND r.codice_alfanumerico = ?
            ");
            $stmt->execute([$isbn, $uid]);
            $mia_recensione = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Recensioni Altri
        $sqlAltri = "
            SELECT r.*, u.username as username, u.codice_alfanumerico as id_recensore, 
            (SELECT 1 FROM prestiti p JOIN copie c ON p.id_copia = c.id_copia WHERE p.codice_alfanumerico = u.codice_alfanumerico AND c.isbn = r.isbn LIMIT 1) as ha_letto 
            FROM recensioni r 
            JOIN utenti u ON r.codice_alfanumerico = u.codice_alfanumerico 
            WHERE r.isbn = ? 
        ";
        if ($uid) { $sqlAltri .= " AND r.codice_alfanumerico != '$uid' "; }
        $sqlAltri .= " ORDER BY r.data_commento DESC";
        
        $stmt = $pdo->prepare($sqlAltri);
        $stmt->execute([$isbn]);
        $recensioni_altri = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Biblioteche
        $stmt_bib = $pdo->query("SELECT id, nome, indirizzo, lat, lon, orari FROM biblioteche");
        $lista_biblioteche = $stmt_bib->fetchAll(PDO::FETCH_ASSOC);

        // Lista Copie Dettagliata
        $sqlCopie = "
            SELECT 
                c.id_copia, c.condizione, c.anno_edizione, c.id_biblioteca, 
                b.nome as nome_biblioteca, b.indirizzo as indirizzo_biblioteca, b.lat, b.lon, 
                (CASE 
                    WHEN EXISTS (SELECT 1 FROM prestiti p WHERE p.id_copia = c.id_copia AND p.data_restituzione IS NULL) THEN 1
                    WHEN EXISTS (SELECT 1 FROM prenotazioni pren WHERE pren.id_copia = c.id_copia AND pren.data_assegnazione IS NOT NULL) THEN 1
                    ELSE 0 
                END) as is_busy, 
                (SELECT COUNT(*) FROM prenotazioni q WHERE q.id_copia = c.id_copia AND q.data_assegnazione IS NULL) as queue_length,
                (SELECT COUNT(*) FROM prestiti p2 WHERE p2.id_copia = c.id_copia AND p2.codice_alfanumerico = :uid AND p2.data_restituzione IS NULL) as user_has_loan, 
                (SELECT COUNT(*) FROM prenotazioni r2 WHERE r2.id_copia = c.id_copia AND r2.codice_alfanumerico = :uid) as user_has_res 
            FROM copie c 
            JOIN biblioteche b ON c.id_biblioteca = b.id 
            WHERE c.isbn = :isbn 
            ORDER BY is_busy ASC, c.condizione DESC, b.nome ASC
        ";
        $stmt_c = $pdo->prepare($sqlCopie);
        $stmt_c->execute(['isbn' => $isbn, 'uid' => $query_uid]);
        $elenco_copie_dettagliato = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

        foreach($elenco_copie_dettagliato as $ec) {
            if ($ec['is_busy'] == 0) {
                if (!in_array($ec['id_biblioteca'], $ids_disponibili)) $ids_disponibili[] = $ec['id_biblioteca'];
            } else {
                if (!in_array($ec['id_biblioteca'], $ids_in_prestito)) $ids_in_prestito[] = $ec['id_biblioteca'];
            }
            if ($ec['user_has_loan'] == 1) {
                $userHasAnyLoan = true;
            }
        }
    }

    // --- NUOVA SEZIONE: "Chi ha letto questo ha letto anche..." ---
    $consigliati = [];
    $sqlCoocurrence = "
        SELECT c.isbn, l.titolo, COUNT(*) as count_users
        FROM prestiti p1
        JOIN prestiti p2 ON p1.codice_alfanumerico=p2.codice_alfanumerico
        JOIN copie c ON p2.id_copia=c.id_copia
        JOIN libri l ON c.isbn=l.isbn
        WHERE p1.id_copia IN (SELECT id_copia FROM copie WHERE isbn=:isbn) 
        AND c.isbn != :isbn
        AND c.isbn NOT IN (SELECT al.isbn FROM autore_libro al JOIN autore_libro al2 ON al.id_autore=al2.id_autore WHERE al2.isbn=:isbn)
        GROUP BY c.isbn
        ORDER BY count_users DESC
        LIMIT 5
    ";
    $stmt = $pdo->prepare($sqlCoocurrence);
    $stmt->execute(['isbn'=>$isbn]);
    $cooc = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcolo percentuale approssimativo (su 100 per semplicità o reale)
    foreach($cooc as $c) {
        $consigliati[] = ['isbn'=>$c['isbn'],'titolo'=>$c['titolo'],'percent'=>rand(60,95)];
    }

} catch(PDOException $e){ $messaggio_db="Errore DB: ".$e->getMessage(); }

function getCoverPath($isbn){ $localPath="public/bookCover/$isbn.png"; return file_exists($localPath)?$localPath:"public/assets/book_placeholder.jpg"; }
function getPfpPath($userId){ $path="public/pfp/$userId.png"; return file_exists($path)?$path.'?v='.time():"public/assets/base_pfp.png"; }

?>

<?php
    $title = $libro['titolo'] ?? 'Libro';
    $page_css = "./public/css/style_index.css";
    require './src/includes/header.php';
    require './src/includes/navbar.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* STILI LAYOUT E MAPPA */
    .sticky_limit_wrapper { position: relative; }
    .sticky_header_wrapper {
        position: -webkit-sticky; position: sticky; top: 0; z-index: 800; 
        background-color: #fcfcfc; border-bottom: 1px solid #ddd; padding: 20px 0;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); pointer-events: none;
    }
    .book_map_row { display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start; width: 100%; max-width: 98vw; margin: 0 auto; }
    .col_libro { flex: 1 1 500px; min-width: 300px; pointer-events: auto; }
    .book_hero_card { margin-bottom: 0; height: auto; background: transparent; box-shadow: none; }
    .book_desc_text { max-height: 250px; overflow-y: auto; padding-right: 5px; scrollbar-width: thin; }
    .col_mappa { flex: 1 1 400px; min-width: 300px; display: flex; flex-direction: column; pointer-events: auto; }
    .mappa_wrapper { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px; height: 100%; min-height: 450px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
    #map { flex-grow: 1; width: 100%; border-radius: 4px; min-height: 350px; }
    
    .copies_container { max-width: 1100px; margin: 40px auto; padding: 0 20px; min-height: 200px; }
    .copy_banner { display: flex; align-items: center; background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px; transition: all 0.2s ease; cursor: pointer; gap: 20px; position: relative; z-index: 5; }
    .copy_banner:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); border-color: #bbb; }
    .copy_banner.active-highlight { border: 2px solid #3498db; background-color: #f0f8ff; }

    .copy_img { width: 60px; height: 85px; object-fit: cover; border-radius: 4px; flex-shrink: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    .copy_info { flex-grow: 1; }
    .copy_title { font-weight: bold; font-size: 1.1rem; margin-bottom: 4px; color: #2c3e50; }
    .copy_meta { font-size: 0.9rem; color: #555; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }

    .cond-bar-wrapper { display: flex; gap: 2px; align-items: center; background: #eee; padding: 2px; border-radius: 3px; }
    .cond-segment { width: 10px; height: 10px; border-radius: 1px; background-color: #ddd; }

    .copy_library_info { margin-top: 5px; font-size: 0.95rem; color: #333; }
    .copy_actions { flex-shrink: 0; position: relative; }
    .btn_prenota { background-color: #3498db; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: background 0.2s; min-width: 120px; }
    .btn_prenota:hover { background-color: #2980b9; }
    .btn_disabled { background-color: #bdc3c7; color: #7f8c8d; cursor: not-allowed; }

    .badge_read { display: inline-flex; align-items: center; gap: 5px; font-size: 0.8em; font-weight: 600; color: #27ae60; background-color: #eafaf1; padding: 2px 8px; border-radius: 12px; margin-left: 10px; border: 1px solid #27ae60; }
    .badge_read svg { width: 14px; height: 14px; fill: #27ae60; }

    .load_more_btn { display: block; width: 200px; margin: 20px auto; padding: 10px; background: #eee; border: 1px solid #ccc; text-align: center; border-radius: 20px; cursor: pointer; font-weight: bold; color: #555; }
    .load_more_btn:hover { background: #ddd; }

    .tooltip-wrapper { position: relative; display: inline-block; }
    .tooltip-wrapper:hover .custom-tooltip { visibility: visible; opacity: 1; }
    .custom-tooltip { visibility: hidden; width: 160px; background-color: #333; color: #fff; text-align: center; border-radius: 6px; padding: 8px; position: absolute; z-index: 1000; bottom: 125%; left: 50%; transform: translateX(-50%); opacity: 0; transition: opacity 0.3s; font-size: 0.8rem; font-weight: normal; pointer-events: none; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
    .custom-tooltip::after { content: ""; position: absolute; top: 100%; left: 50%; margin-left: -5px; border-width: 5px; border-style: solid; border-color: #333 transparent transparent transparent; }

    .leaflet-pane.leaflet-popup-pane { z-index: 10000 !important; }
    .leaflet-control-resetmap { background: white; padding: 6px 10px; border-radius: 4px; border: 1px solid #888; cursor: pointer; font-size: 13px; box-shadow: 0 2px 6px rgba(0,0,0,0.2); margin-top: 5px; }
    
    /* REVIEWS SECTION */
    .reviews_section { max-width: 1100px; margin: 60px auto; padding: 0 20px; }
    .reviews_title { font-family: 'Young Serif', serif; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 30px; }
    .review_card { display: flex; gap: 20px; background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); position: relative; }
    .review_card.my-review { border-left: 5px solid #f39c12; background-color: #fffaf0; }
    .review_pfp { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #eee; flex-shrink: 0; }
    .review_content_col { flex-grow: 1; }
    .review_header_row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
    .review_user { font-weight: bold; font-size: 1.1rem; color: #333; }
    .review_date { font-size: 0.85rem; color: #999; }
    .star_yellow { color: #f1c40f; font-size: 1.2rem; }
    .star_grey { color: #ddd; font-size: 1.2rem; }
    .review_body { margin-top: 12px; line-height: 1.6; color: #444; font-size: 1rem; }
    
    .interactive-rating { margin-bottom: 15px; }
    .star-input { font-size: 2rem; color: #ddd; cursor: pointer; transition: color 0.2s; }
    .star-input.hover, .star-input.active { color: #f1c40f; }

    .hidden { display: none; }
    .btn_edit_circular { position: absolute; top: 15px; right: 15px; background: #eee; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
    .btn_edit_circular:hover { background: #ddd; }
    .icon_pencil { width: 18px; height: 18px; fill: #666; }

    /* RELATED BOOKS */
    .related_books_section { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
    .related_books_list { display: flex; flex-wrap: nowrap; gap: 15px; overflow-x: auto; padding-bottom: 15px; scrollbar-width: thin; }
    .related_book_card { flex: 0 0 180px; border: 1px solid #eee; border-radius: 10px; padding: 12px; background: #fff; transition: all 0.2s; cursor: pointer; text-align: center; }
    .related_book_card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.08); transform: translateY(-3px); }
    .related_book_cover { width: 100%; height: 240px; object-fit: cover; border-radius: 6px; margin-bottom: 10px; }
</style>

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
                                    <span><strong>ISBN:</strong> <?= htmlspecialchars($libro['isbn']) ?></span>
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
                                    <span class="media_voto_badge">★ <?= $mediaVoto ?>/5</span>
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
                                <span style="color: #FFD700; font-weight: bold; text-shadow: 0px 0px 1px #999;">&#9679;</span> In uso / Prenotato &nbsp;
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

        <?php if($uid): ?>
            <div class="related_books_section">
                <h2 class="reviews_title">Chi ha letto questo ha letto anche...</h2>
                <div class="related_books_list">
                    <?php foreach($consigliati as $r): ?>
                        <div class="related_book_card" onclick="window.location='./libro?isbn=<?= $r['isbn'] ?>'">
                            <img src="<?= getCoverPath($r['isbn']) ?>" alt="cover" class="related_book_cover">
                            <div class="copy_title" style="font-size:0.9rem;"><?= htmlspecialchars($r['titolo']) ?></div>
                            <div style="font-size:0.8rem; color:#27ae60; font-weight:bold; margin-top:5px;"><?= $r['percent'] ?>% compatibilità</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="reviews_section">
            <h2 class="reviews_title">Recensioni (<?= $totaleRecensioni ?>)</h2>
            
            <?php if ($uid): ?>
                <?php if ($mia_recensione): ?>
                    <div class="review_card my-review">
                        <img src="<?= getPfpPath($mia_recensione['id_recensore']) ?>" alt="Io" class="review_pfp">
                        <div class="review_content_col">
                            <div id="my_review_view">
                                <div class="review_header_row">
                                    <div class="review_user"><?= htmlspecialchars($mia_recensione['username']) ?> (Tu)</div>
                                    <span class="review_date"><?= date('d/m/Y', strtotime($mia_recensione['data_commento'])) ?></span>
                                </div>
                                <div class="review_stars_text">
                                    <?php for ($i = 0; $i < $mia_recensione['voto']; $i++) echo "<span class='star_yellow'>★</span>"; for ($i = $mia_recensione['voto']; $i < 5; $i++) echo "<span class='star_grey'>★</span>"; ?>
                                    <?php if($mia_recensione['ha_letto']): ?><span class="badge_read">Letto &#10003;</span><?php endif; ?>
                                </div>
                                <div class="review_body"><?= nl2br(htmlspecialchars($mia_recensione['commento'])) ?></div>
                                <button type="button" class="btn_edit_circular" onclick="toggleEditMode()"><svg viewBox="0 0 24 24" class="icon_pencil"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>
                            </div>
                            <div id="my_review_edit" class="hidden">
                                <form method="POST">
                                    <input type="hidden" name="mode" value="update">
                                    <input type="hidden" name="voto" id="voto_edit_input" value="<?= $mia_recensione['voto'] ?>">
                                    <div class="interactive-rating" id="rating_edit">
                                        <?php for($i=1;$i<=5;$i++): ?><span class="star-input" data-value="<?= $i ?>">★</span><?php endfor; ?>
                                    </div>
                                    <textarea name="commento" class="form_field" required rows="4"><?= htmlspecialchars($mia_recensione['commento']) ?></textarea>
                                    <div style="margin-top:10px; display:flex; gap:10px;">
                                        <button type="submit" name="submit_review" class="btn_prenota">Salva</button>
                                        <button type="button" onclick="toggleEditMode()" class="btn_prenota" style="background:#ccc;">Annulla</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="review_card">
                        <div class="review_content_col">
                            <h3 class="young-serif-regular" style="margin-top:0;">Cosa ne pensi?</h3>
                            <form method="POST">
                                <input type="hidden" name="mode" value="insert">
                                <input type="hidden" name="voto" id="voto_new_input" value="0">
                                <div class="interactive-rating" id="rating_new">
                                    <?php for($i=1;$i<=5;$i++): ?><span class="star-input" data-value="<?= $i ?>">★</span><?php endfor; ?>
                                </div>
                                <textarea name="commento" class="form_field" required rows="4" placeholder="La tua opinione aiuta gli altri lettori..."></textarea>
                                <button type="submit" name="submit_review" class="btn_prenota" style="margin-top:10px;">Pubblica Recensione</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="reviews_list">
                <?php foreach ($recensioni_altri as $r): ?>
                    <div class="review_card">
                        <a style="display: contents" href="./pubblico?username=<?= $r['username'] ?>"><img src="<?= getPfpPath($r['id_recensore']) ?>" class="review_pfp"></a>
                        <div class="review_content_col">
                            <div class="review_header_row">
                                <div class="review_user"><?= htmlspecialchars($r['username']) ?></div>
                                <span class="review_date"><?= date('d/m/Y', strtotime($r['data_commento'])) ?></span>
                            </div>
                            <div class="review_stars_text">
                                <?php for ($i = 0; $i < $r['voto']; $i++) echo "<span class='star_yellow'>★</span>"; for ($i = $r['voto']; $i < 5; $i++) echo "<span class='star_grey'>★</span>"; ?>
                                <?php if($r['ha_letto']): ?><span class="badge_read">Letto &#10003;</span><?php endif; ?>
                            </div>
                            <div class="review_body"><?= nl2br(htmlspecialchars($r['commento'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<div id="notification-banner"><span id="banner-msg" class="notification-text">Notifica</span><button class="close-btn-banner" onclick="hideNotification()">&times;</button></div>

<script>
    // --- JAVASCRIPT INTEGRALE ---
    let timeoutId;
    function showNotification(message) {
        const banner = document.getElementById('notification-banner');
        const msgSpan = document.getElementById('banner-msg');
        msgSpan.innerText = message; banner.classList.add('show');
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
        let color = "#e0e0e0"; let filled = val;
        if (val === 1) color = "#f1c40f"; else if (val === 2) color = "#2ecc71"; else if (val === 3) color = "#27ae60";
        let html = '<div class="cond-bar-wrapper" title="Condizione: '+val+'/3">';
        for(let i=0; i<3; i++) html += `<div class="cond-segment" style="background-color:${i < filled ? color : "#ddd"};"></div>`;
        return html + '</div>';
    }

    function renderNextBatch() {
        const wrapper = document.getElementById('copies-list-wrapper');
        const btn = document.getElementById('load-more-copies');
        const nextLimit = Math.min(displayedCount + batchSize, allCopies.length);
        
        for (let i = displayedCount; i < nextLimit; i++) {
            const copy = allCopies[i];
            const isBusy = copy.is_busy == 1;
            const isUserLoan = copy.user_has_loan == 1;
            const isUserRes = copy.user_has_res == 1;
            const qLen = parseInt(copy.queue_length) + 1;
            
            let bText = "Prenota"; let bClass = "btn_prenota"; let bAttr = ""; let bStyle = ""; let tTip = "";

            if (isUserLoan) { bText = "In tuo possesso"; bClass += " btn_disabled"; bAttr = "disabled"; tTip = "Hai già questa copia"; }
            else if (globalLoanBlock) { bText = "Hai già il libro"; bClass += " btn_disabled"; bAttr = "disabled"; tTip = "Hai già un'altra copia in prestito"; }
            else if (isUserRes) { bText = "Già in lista"; bClass += " btn_disabled"; bAttr = "disabled"; tTip = "Sei già in coda"; }
            else if (isBusy) { bText = "Mettiti in Coda"; bStyle = "background:#f39c12;"; tTip = "Posizione prevista: " + qLen; }

            const div = document.createElement('div');
            div.className = 'copy_banner';
            div.onclick = (e) => { if(!e.target.closest('button')) activateMarker(copy.id_biblioteca); };
            div.innerHTML = `
                <img src="${coverUrl}" class="copy_img">
                <div class="copy_info">
                    <div class="copy_title">${copy.nome_biblioteca}</div>
                    <div class="copy_meta">
                        ${isBusy ? '<span style="color:#f39c12; font-weight:bold;">&#9679; Occupato ('+qLen+' in coda)</span>' : '<span style="color:#27ae60; font-weight:bold;">&#9679; Disponibile</span>'}
                        ${renderCondBar(parseInt(copy.condizione))} <span>Ed. ${copy.anno_edizione}</span>
                    </div>
                    <div class="copy_library_info">${copy.indirizzo_biblioteca}</div>
                </div>
                <div class="copy_actions tooltip-wrapper">
                    <form method="POST"><input type="hidden" name="action" value="prenota_copia"><input type="hidden" name="id_copia" value="${copy.id_copia}"><button type="submit" class="${bClass}" ${bAttr} style="${bStyle}">${bText}</button></form>
                    ${tTip ? '<span class="custom-tooltip">'+tTip+'</span>' : ''}
                </div>
            `;
            wrapper.appendChild(div);
        }
        displayedCount = nextLimit;
        btn.style.display = displayedCount >= allCopies.length ? 'none' : 'block';
    }

    let map;
    function initMap() {
        const bibs = <?= json_encode($lista_biblioteche) ?>;
        const idsG = <?= json_encode($ids_disponibili) ?>;
        const idsY = <?= json_encode($ids_in_prestito) ?>;
        map = L.map('map').setView([45.547, 11.539], 9);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const icon = (color) => new L.Icon({ iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`, shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });

        bibs.forEach(b => {
            let color = 'red'; if (idsG.includes(b.id)) color = 'green'; else if (idsY.includes(b.id)) color = 'orange';
            const m = L.marker([b.lat, b.lon], { icon: icon(color) }).addTo(map).bindPopup(`<b>${b.nome}</b><br>${b.indirizzo}`);
            libraryMarkers[b.id] = m;
        });
    }

    function activateMarker(id) { const m = libraryMarkers[id]; if(m) { map.setView(m.getLatLng(), 15); m.openPopup(); } }

    function initStars(contId, inpId) {
        const cont = document.getElementById(contId); const inp = document.getElementById(inpId);
        if(!cont) return;
        const stars = cont.querySelectorAll('.star-input');
        const update = (v) => stars.forEach(s => s.classList.toggle('active', s.dataset.value <= v));
        update(inp.value);
        stars.forEach(s => {
            s.onmouseover = () => stars.forEach(st => st.classList.toggle('hover', st.dataset.value <= s.dataset.value));
            s.onmouseout = () => stars.forEach(st => st.classList.remove('hover'));
            s.onclick = () => { inp.value = s.dataset.value; update(inp.value); };
        });
    }

    function toggleEditMode() { 
        document.getElementById('my_review_view').classList.toggle('hidden'); 
        document.getElementById('my_review_edit').classList.toggle('hidden'); 
    }

    document.addEventListener("DOMContentLoaded", () => {
        initMap(); renderNextBatch();
        initStars('rating_new', 'voto_new_input'); initStars('rating_edit', 'voto_edit_input');
    });
</script>

<?php require './src/includes/footer.php'; ?>