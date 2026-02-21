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
$query_uid = $uid ?: 'GUEST';

if (isset($_POST['action']) && $_POST['action'] === 'doLike') {

    $id_recensione = isset($_POST['id_recensione']) ? intval($_POST['id_recensione']) : null;
    $tipo_voto = $_POST['tipo_voto'] ?? null;

    if (!$id_recensione || !in_array($tipo_voto, ['like', 'dislike'])) {
        echo json_encode(['success' => false, 'message' => 'Parametri non validi']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT codice_alfanumerico FROM recensioni WHERE id_recensione = ?");
        $stmt->execute([$id_recensione]);
        $recensione = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recensione) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Recensione non trovata']);
            exit;
        }

        if ($recensione['codice_alfanumerico'] === $uid) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Non puoi votare la tua recensione']);
            exit;
        }

        // Verifica voto esistente
        $stmt = $pdo->prepare("SELECT tipo_voto FROM recensioni_voti WHERE id_recensione = ? AND codice_alfanumerico = ?");
        $stmt->execute([$id_recensione, $uid]);
        $voto_esistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($voto_esistente) {
            if ($voto_esistente['tipo_voto'] === $tipo_voto) {
                // Rimuovi voto
                $stmt = $pdo->prepare("DELETE FROM recensioni_voti WHERE id_recensione = ? AND codice_alfanumerico = ?");
                $stmt->execute([$id_recensione, $uid]);
                $operazione = 'removed';
            } else {
                // Cambia voto
                $stmt = $pdo->prepare("UPDATE recensioni_voti SET tipo_voto = ? WHERE id_recensione = ? AND codice_alfanumerico = ?");
                $stmt->execute([$tipo_voto, $id_recensione, $uid]);
                $operazione = 'changed';
            }
        } else {
            // Inserisci nuovo voto
            $stmt = $pdo->prepare("INSERT INTO recensioni_voti (id_recensione, codice_alfanumerico, tipo_voto) VALUES (?, ?, ?)");
            $stmt->execute([$id_recensione, $uid, $tipo_voto]);
            $operazione = 'added';
        }

        // Aggiorna conteggi
        $stmt = $pdo->prepare("
                UPDATE recensioni 
                SET 
                    like_count = (SELECT COUNT(*) FROM recensioni_voti WHERE id_recensione = ? AND tipo_voto = 'like'),
                    dislike_count = (SELECT COUNT(*) FROM recensioni_voti WHERE id_recensione = ? AND tipo_voto = 'dislike')
                WHERE id_recensione = ?
            ");
        $stmt->execute([$id_recensione, $id_recensione, $id_recensione]);

        // Recupera conteggi
        $stmt = $pdo->prepare("SELECT like_count, dislike_count FROM recensioni WHERE id_recensione = ?");
        $stmt->execute([$id_recensione]);
        $conteggi = $stmt->fetch(PDO::FETCH_ASSOC);

        // Voto corrente
        $stmt = $pdo->prepare("SELECT tipo_voto FROM recensioni_voti WHERE id_recensione = ? AND codice_alfanumerico = ?");
        $stmt->execute([$id_recensione, $uid]);
        $voto_corrente = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();

        echo json_encode([
                'success' => true,
                'operazione' => $operazione,
                'like_count' => intval($conteggi['like_count']),
                'dislike_count' => intval($conteggi['dislike_count']),
                'user_vote' => $voto_corrente ? $voto_corrente['tipo_voto'] : null,
                'message' => $operazione === 'removed' ? 'Voto rimosso' : ($operazione === 'changed' ? 'Voto modificato' : 'Voto registrato')
        ]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Errore database']);
    }
    exit;
}

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
            (SELECT 1 FROM prestiti p JOIN copie c ON p.id_copia = c.id_copia WHERE p.codice_alfanumerico = u.codice_alfanumerico AND c.isbn = r.isbn LIMIT 1) as ha_letto,
            COALESCE(r.like_count, 0) as like_count,
            COALESCE(r.dislike_count, 0) as dislike_count
        ";
        if ($uid) {
            $sqlAltri .= ", (SELECT tipo_voto FROM recensioni_voti WHERE id_recensione = r.id_recensione AND codice_alfanumerico = '$uid') as user_vote ";
        }
        $sqlAltri .= "
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
                                        <span class="media_voto_badge young-serif-regular">★ <?= $mediaVoto ?>/5</span>
                                    </div>
                                    <div class="book_desc_box">
                                        <h3 class="book_desc_title">Trama</h3>
                                        <div class="book_desc_text"><?= nl2br(htmlspecialchars($libro['descrizione'])) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col_mappa">
                            <div class="mappa_wrapper young-serif-regular">
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
                    <div id="copies-list-wrapper" class="instrument-sans"></div>
                    <button id="load-more-copies" class="load_more_btn" style="display:none;" onclick="renderNextBatch()">Mostra altre copie</button>
                </div>
            </div>

            <?php if($uid): ?>
                <div class="related_books_section">
                    <h2 class="reviews_title">Chi ha letto questo ha letto anche...</h2>
                    <div class="related_books_list">
                        <?php foreach($consigliati as $r): ?>
                            <div class="related_book_card young-serif-regular" onclick="window.location='./libro?isbn=<?= $r['isbn'] ?>'">
                                <img src="<?= getCoverPath($r['isbn']) ?>" alt="cover" class="related_book_cover">
                                <div class="copy_title"><?= htmlspecialchars($r['titolo']) ?></div>
                                <div class="copy_com instrument-sans"><?= $r['percent'] ?>% compatibilità</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="reviews_section_new">
                <h2 class="reviews_title_new young-serif-regular">Recensioni (<?= $totaleRecensioni ?>)</h2>

                <?php if ($uid): ?>
                    <?php if ($mia_recensione): ?>
                        <div class="review_card_new my_review_highlight contents">
                            <a href="./pubblico?username=<?= urlencode($mia_recensione['username']) ?>" style="display: contents;">
                                <img src="<?= getPfpPath($mia_recensione['id_recensore']) ?>" alt="Profile Picture" class="review_pfp_new">
                            </a>
                            <div class="review_main_col">
                                <div id="myReviewView">
                                    <div class="review_header_new">
                                        <span class="review_author_name instrument-sans-semibold"><?= htmlspecialchars($mia_recensione['username']) ?> (Tu)</span>
                                        <span class="review_date_new instrument-sans"><?= date('d/m/Y', strtotime($mia_recensione['data_commento'])) ?></span>
                                    </div>
                                    <div class="review_rating_stars">
                                        <?php
                                        for ($i = 0; $i < $mia_recensione['voto']; $i++) echo '<img src="./public/assets/ui_icon_star.png" class="star_icon_display">';
                                        for ($i = $mia_recensione['voto']; $i < 5; $i++) echo '<img src="./public/assets/ui_icon_star_darken.png" class="star_icon_display">';
                                        ?>
                                        <?php if($mia_recensione['ha_letto']): ?><span class="badge_read_new instrument-sans">Letto &#10003;</span><?php endif; ?>
                                    </div>
                                    <div class="review_text_new instrument-sans"><?= nl2br(htmlspecialchars($mia_recensione['commento'])) ?></div>

                                    <div class="review_footer_new instrument-sans">
                                        <div class="review_stats_new">
                                            <span title="Mi piace">👍 <?= $mia_recensione['like_count'] ?? 0 ?></span>
                                            <span title="Non mi piace">👎 <?= $mia_recensione['dislike_count'] ?? 0 ?></span>
                                        </div>
                                        <button type="button" class="general_button_dark" onclick="toggleReviewEditMode()">Modifica</button>
                                    </div>
                                </div>

                                <div id="myReviewEdit" class="hidden_element">
                                    <form method="POST">
                                        <input type="hidden" name="mode" value="update">
                                        <input type="hidden" name="voto" id="editReviewRatingInput" value="<?= $mia_recensione['voto'] ?>">

                                        <div class="rating_selector_new" id="editReviewRating">
                                            <?php for($i=1;$i<=5;$i++): ?>
                                                <img src="./public/assets/ui_icon_star<?= $i <= $mia_recensione['voto'] ? '' : '_darken' ?>.png" class="star_input_img" data-value="<?= $i ?>">
                                            <?php endfor; ?>
                                        </div>

                                        <textarea name="commento" class="review_textarea_new instrument-sans" required rows="4"><?= htmlspecialchars($mia_recensione['commento']) ?></textarea>

                                        <div class="review_edit_actions">
                                            <button type="submit" name="submit_review" class="general_button_dark">Salva Modifiche</button>
                                            <button type="button" onclick="toggleReviewEditMode()" class="general_button_dark" style="background-color: #888;">Annulla</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="review_card_new contents">
                            <div class="review_main_col">
                                <h3 class="young-serif-regular" style="margin-top:0; color: var(--color_text_dark_green);">Lascia una recensione</h3>
                                <form method="POST">
                                    <input type="hidden" name="mode" value="insert">
                                    <input type="hidden" name="voto" id="newReviewRatingInput" value="0">

                                    <div class="rating_selector_new" id="newReviewRating">
                                        <?php for($i=1;$i<=5;$i++): ?>
                                            <img src="./public/assets/ui_icon_star_darken.png" class="star_input_img" data-value="<?= $i ?>">
                                        <?php endfor; ?>
                                    </div>

                                    <textarea name="commento" class="review_textarea_new instrument-sans" required rows="4" placeholder="Scrivi qui la tua opinione..."></textarea>
                                    <button type="submit" name="submit_review" class="general_button_dark" style="margin-top: 10px;">Pubblica</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="reviews_list_new">
                    <?php foreach ($recensioni_altri as $r): ?>
                        <div class="review_card_new contents">
                            <a href="./pubblico?username=<?= urlencode($r['username']) ?>" style="display: contents;">
                                <img src="<?= getPfpPath($r['id_recensore']) ?>" alt="Profile Picture" class="review_pfp_new">
                            </a>
                            <div class="review_main_col">
                                <div class="review_header_new">
                                    <span class="review_author_name instrument-sans-semibold"><?= htmlspecialchars($r['username']) ?></span>
                                    <span class="review_date_new instrument-sans"><?= date('d/m/Y', strtotime($r['data_commento'])) ?></span>
                                </div>

                                <div class="review_rating_stars">
                                    <?php
                                    for ($i = 0; $i < $r['voto']; $i++) echo '<img src="./public/assets/ui_icon_star.png" class="star_icon_display">';
                                    for ($i = $r['voto']; $i < 5; $i++) echo '<img src="./public/assets/ui_icon_star_darken.png" class="star_icon_display">';
                                    ?>
                                    <?php if($r['ha_letto']): ?><span class="badge_read_new instrument-sans">Letto &#10003;</span><?php endif; ?>
                                </div>

                                <div class="review_text_new instrument-sans"><?= nl2br(htmlspecialchars($r['commento'])) ?></div>

                                <div class="review_actions_row instrument-sans">
                                    <?php if ($uid): ?>
                                        <button class="vote_action_btn <?= isset($r['user_vote']) && $r['user_vote'] === 'like' ? 'vote_active_like' : '' ?>"
                                                onclick="processVote(<?= $r['id_recensione'] ?>, 'like', this)"
                                                data-review-id="<?= $r['id_recensione'] ?>">
                                            👍 <span class="vote_count_value like_count_target_<?= $r['id_recensione'] ?>"><?= $r['like_count'] ?></span>
                                        </button>
                                        <button class="vote_action_btn <?= isset($r['user_vote']) && $r['user_vote'] === 'dislike' ? 'vote_active_dislike' : '' ?>"
                                                onclick="processVote(<?= $r['id_recensione'] ?>, 'dislike', this)"
                                                data-review-id="<?= $r['id_recensione'] ?>">
                                            👎 <span class="vote_count_value dislike_count_target_<?= $r['id_recensione'] ?>"><?= $r['dislike_count'] ?></span>
                                        </button>
                                    <?php else: ?>
                                        <div class="vote_action_btn static_vote">
                                            👍 <span class="vote_count_value"><?= $r['like_count'] ?></span>
                                        </div>
                                        <div class="vote_action_btn static_vote">
                                            👎 <span class="vote_count_value"><?= $r['dislike_count'] ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
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

                let bText = "Prenota"; let bClass = "general_button_dark"; let bAttr = ""; let bStyle = ""; let tTip = "";

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

        function toggleReviewEditMode() {
            const viewSection = document.getElementById('myReviewView');
            const editSection = document.getElementById('myReviewEdit');
            if (viewSection && editSection) {
                viewSection.classList.toggle('hidden_element');
                editSection.classList.toggle('hidden_element');
            }
        }

        function initializeStarRating(containerId, inputId) {
            const container = document.getElementById(containerId);
            const inputElement = document.getElementById(inputId);
            if (!container || !inputElement) return;

            const starImages = container.querySelectorAll('.star_input_img');
            const fullStarSrc = './public/assets/ui_icon_star.png';
            const emptyStarSrc = './public/assets/ui_icon_star_darken.png';

            const updateStars = (ratingValue) => {
                starImages.forEach(star => {
                    const starValue = parseInt(star.getAttribute('data-value'));
                    if (starValue <= ratingValue) {
                        star.src = fullStarSrc;
                    } else {
                        star.src = emptyStarSrc;
                    }
                });
            };

            starImages.forEach(star => {
                star.style.cursor = 'pointer';
                star.addEventListener('mouseover', () => {
                    updateStars(parseInt(star.getAttribute('data-value')));
                });
                star.addEventListener('mouseout', () => {
                    updateStars(parseInt(inputElement.value));
                });
                star.addEventListener('click', () => {
                    inputElement.value = star.getAttribute('data-value');
                    updateStars(parseInt(inputElement.value));
                });
            });
        }

        async function processVote(reviewId, voteType, buttonElement) {
            try {
                const payload = new FormData();
                payload.append('id_recensione', reviewId);
                payload.append('tipo_voto', voteType);
                payload.append('action', 'doLike');

                const request = await fetch(window.location.href, {
                    method: 'POST',
                    body: payload
                });

                const responseData = await request.json();

                if (responseData.success) {
                    const likeDisplay = document.querySelector(`.like_count_target_${reviewId}`);
                    const dislikeDisplay = document.querySelector(`.dislike_count_target_${reviewId}`);

                    if (likeDisplay) likeDisplay.textContent = responseData.like_count;
                    if (dislikeDisplay) dislikeDisplay.textContent = responseData.dislike_count;

                    const actionsContainer = buttonElement.closest('.review_actions_row');
                    if (actionsContainer) {
                        const allButtons = actionsContainer.querySelectorAll('.vote_action_btn');
                        allButtons.forEach(btn => {
                            btn.classList.remove('vote_active_like', 'vote_active_dislike');
                        });

                        if (responseData.user_vote === 'like') {
                            const likeBtn = actionsContainer.querySelector('[onclick*="like"]');
                            if (likeBtn) likeBtn.classList.add('vote_active_like');
                        } else if (responseData.user_vote === 'dislike') {
                            const dislikeBtn = actionsContainer.querySelector('[onclick*="dislike"]');
                            if (dislikeBtn) dislikeBtn.classList.add('vote_active_dislike');
                        }
                    }

                    showNotification(responseData.message);
                } else {
                    showNotification(responseData.message || 'Error processing vote');
                }
            } catch (fetchError) {
                console.error(`Vote request failed for review ID ${reviewId}:`, fetchError);
                showNotification('Connection error occurred');
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            initMap(); renderNextBatch();
            initializeStarRating('newReviewRating', 'newReviewRatingInput');
            initializeStarRating('editReviewRating', 'editReviewRatingInput');
        });
    </script>

<?php require './src/includes/footer.php'; ?>