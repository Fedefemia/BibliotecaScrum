<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

$messaggio_db = "";
$messaggio_form = ""; // Nuovo: per messaggi relativi all'invio del commento
$libro = null;
$autori = [];
$categorie = [];
$recensioni = [];
$mediaVoto = 0;
$totaleRecensioni = 0;

$isbn = $_GET['isbn'] ?? null;

if (!$isbn) {
    die("<h1>Errore</h1><p>ISBN non specificato.</p>");
}

// --- GESTIONE NUOVO COMMENTO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    // IMPORTANTE: Sostituisci 'codice_alfanumerico' con la tua variabile di sessione utente reale se diversa
    if (!isset($_SESSION['codice_alfanumerico'])) {
        $messaggio_form = "<div class='alert alert-warning'>Devi effettuare il login per lasciare una recensione.</div>";
    } else {
        $voto = filter_input(INPUT_POST, 'voto', FILTER_VALIDATE_INT);
        $commento = trim(filter_input(INPUT_POST, 'commento', FILTER_SANITIZE_STRING));
        $utente_id = $_SESSION['codice_alfanumerico'];

        if ($voto < 1 || $voto > 5 || empty($commento)) {
            $messaggio_form = "<div class='alert alert-danger'>Per favore, inserisci un voto valido (1-5) e un commento.</div>";
        } else {
            try {
                // Query di inserimento (assicurati che i nomi delle colonne siano corretti nel tuo DB)
                $stmtInsert = $pdo->prepare("
                    INSERT INTO recensioni (isbn, codice_alfanumerico, voto, commento, data_commento) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmtInsert->execute([$isbn, $utente_id, $voto, $commento]);
                
                // Refresh della pagina per mostrare il nuovo commento ed evitare doppio invio
                header("Location: " . $_SERVER['PHP_SELF'] . "?isbn=" . $isbn . "&succ=1");
                exit;
            } catch (PDOException $e) {
                $messaggio_form = "<div class='alert alert-danger'>Errore durante il salvataggio: " . $e->getMessage() . "</div>";
            }
        }
    }
}

if (isset($_GET['succ'])) {
    $messaggio_form = "<div class='alert alert-success'>Recensione aggiunta con successo!</div>";
}
// --- FINE GESTIONE COMMENTO ---


try {
    // 1. Recupera info libro
    $stmt = $pdo->prepare("
        SELECT 
            l.*,
            (SELECT editore FROM copie c WHERE c.isbn = l.isbn LIMIT 1) as editore_temp,
            (SELECT COUNT(*) FROM copie c WHERE c.isbn = l.isbn AND c.disponibile = 1) as numero_copie_disponibili
        FROM libri l
        WHERE l.isbn = ?
    ");
    $stmt->execute([$isbn]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($libro) {
        $libro['editore'] = $libro['editore_temp'] ?? 'N/D';

        // 2. Recupera Autori
        $stmt = $pdo->prepare("
            SELECT a.nome, a.cognome
            FROM autori a
            JOIN autore_libro al ON al.id_autore = a.id_autore
            WHERE al.isbn = ?
        ");
        $stmt->execute([$isbn]);
        $autori = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Recupera Categorie
        $stmt = $pdo->prepare("
            SELECT categoria
            FROM categorie c
            JOIN libro_categoria lc ON lc.id_categoria = c.id_categoria
            WHERE lc.isbn = ?
        ");
        $stmt->execute([$isbn]);
        $categorie = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 4. Calcola Media Voti e Totale
        $stmt = $pdo->prepare("
            SELECT CAST(AVG(voto) AS DECIMAL(3,1)) as media, COUNT(*) as totale 
            FROM recensioni 
            WHERE isbn = ?
        ");
        $stmt->execute([$isbn]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $mediaVoto = $stats['media'] ? number_format((float)$stats['media'], 1) : 0;
        $totaleRecensioni = $stats['totale'];

        // 5. Recupera SOLO le ultime Recensioni (Ho aumentato a 3 per estetica)
        $stmt = $pdo->prepare("
            SELECT r.*, u.username
            FROM recensioni r
            JOIN utenti u ON r.codice_alfanumerico = u.codice_alfanumerico
            WHERE r.isbn = ?
            ORDER BY r.data_commento DESC
            LIMIT 3 
        ");
        $stmt->execute([$isbn]);
        $recensioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $messaggio_db = "Libro non trovato nel database.";
    }

} catch (PDOException $e) {
    $messaggio_db = "Errore nel recupero dati: " . $e->getMessage();
}

function getCoverPath($isbn)
{
    $localPath = __DIR__ . "/../public/bookCover/$isbn.png";
    $publicPath = "public/bookCover/$isbn.png";
    if (file_exists($localPath)) {
        return $publicPath;
    }
    // Placeholder se l'immagine non esiste (assicurati di averne uno o togli questa riga)
    return "https://via.placeholder.com/200x300?text=Nessuna+Copertina"; 
}
?>


<?php
// Impostiamo il CSS specifico se vuoi caricarlo dinamicamente dall'header
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

            <div class="book_details_wrapper">

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
                            <div class="book_desc_text">
                                <?= nl2br(htmlspecialchars($libro['descrizione'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reviews_section">
                    <h2 class="reviews_title">Recensioni (<?= $totaleRecensioni ?>)</h2>

                    <?= $messaggio_form ?>

                    <?php if (isset($_SESSION['codice_alfanumerico'])): ?>
                        <div class="review_form_box">
                            <h3 class="young-serif-regular" style="margin-top:0;">Lascia un pensiero</h3>
                            <form method="POST" action="">
                                <div style="margin-bottom:15px;">
                                    <label for="voto" class="form_label">Voto</label>
                                    <select name="voto" id="voto" required class="form_field">
                                        <option value="" disabled selected>Quante stelle?</option>
                                        <option value="5">★★★★★ (5 - Adoro)</option>
                                        <option value="4">★★★★☆ (4 - Bello)</option>
                                        <option value="3">★★★☆☆ (3 - Carino)</option>
                                        <option value="2">★★☆☆☆ (2 - Insomma)</option>
                                        <option value="1">★☆☆☆☆ (1 - No)</option>
                                    </select>
                                </div>
                                <div style="margin-bottom:15px;">
                                    <label for="commento" class="form_label">Commento</label>
                                    <textarea name="commento" id="commento" rows="4" required class="form_field" placeholder="Scrivi qui..."></textarea>
                                </div>
                                <button type="submit" name="submit_review" class="btn_send">Pubblica Recensione</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert_box login">
                            <p>Vuoi lasciare una recensione? <a href="./login" style="color:#333; font-weight:bold;">Accedi</a> per dirci la tua!</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($recensioni): ?>
                        <div class="reviews_list">
                            <?php foreach ($recensioni as $r): ?>
                                <div class="review_card">
                                    <div class="review_header_row">
                                        <div class="review_user">
                                            <?= htmlspecialchars($r['username']) ?>
                                        </div>
                                        <span class="review_date"><?= date('d/m/Y', strtotime($r['data_commento'])) ?></span>
                                    </div>

                                    <div class="review_stars_text" style="margin-bottom:8px;">
                                        <?php
                                        // Uso il tuo loop originale per le stelle
                                        for ($i = 0; $i < $r['voto']; $i++) echo "★";
                                        for ($i = $r['voto']; $i < 5; $i++) echo "<span style='color:#ccc'>★</span>";
                                        ?>
                                    </div>

                                    <div class="review_body">
                                        <?= nl2br(htmlspecialchars($r['commento'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align:center; color:#888; margin-top:30px;">Ancora nessuna recensione</p>
                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>
    </div>

<?php require './src/includes/footer.php'; ?>