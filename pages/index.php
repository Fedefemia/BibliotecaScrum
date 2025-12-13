<?php
session_start();
require_once 'db_config.php';

$messaggio_db = "";

/* ---------------- GET GOOGLE BOOK DATA ---------------- */
function getGoogleBookData($isbn) {
    static $cache = [];
    if (isset($cache[$isbn])) return $cache[$isbn];

    $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . urlencode($isbn);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $json = curl_exec($ch);
    curl_close($ch);

    if (!$json) return null;

    $data = json_decode($json, true);
    if (!isset($data['items'][0]['volumeInfo'])) return null;

    $info = $data['items'][0]['volumeInfo'];

    $description = $info['description'] ?? 'Descrizione non disponibile';
    $description = strip_tags($description);
    $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $description = mb_substr($description, 0, 1000);

    $cache[$isbn] = [
            'title' => $info['title'] ?? '',
            'authors' => $info['authors'] ?? [],
            'description' => $description,
            'publisher' => $info['publisher'] ?? '',
            'publishedDate' => $info['publishedDate'] ?? '',
            'categories' => $info['categories'] ?? [],
            'cover' => $info['imageLinks']['thumbnail'] ?? 'src/assets/placeholder.jpg'
    ];
    return $cache[$isbn];
}

/* ---------------- SYNC BOOK DATA ---------------- */
function syncBookData($pdo, $isbn) {
    $bookData = getGoogleBookData($isbn);
    if (!$bookData) return false;

    preg_match('/\d{4}/', $bookData['publishedDate'], $m);
    $year = $m[0] ?? null;

    try {
        $pdo->beginTransaction();

        /* COPIE */
        $stmt = $pdo->prepare("SELECT copertina FROM copie WHERE isbn = ?");
        $stmt->execute([$isbn]);
        $current_cover = $stmt->fetchColumn();
        $cover = $current_cover ?: $bookData['cover'];

        $stmt = $pdo->prepare("
            INSERT INTO copie (isbn, ean, condizione, disponibile, anno_pubblicazione, conferma_anno_pubblicazione, editore, copertina)
            VALUES (?, ?, 0, 1, ?, 1, ?, ?)
            ON DUPLICATE KEY UPDATE 
                anno_pubblicazione = VALUES(anno_pubblicazione),
                editore = VALUES(editore),
                copertina = VALUES(copertina)
        ");
        $stmt->execute([$isbn, substr($isbn,0,3), $year, $bookData['publisher'], $cover]);

        /* LIBRI */
        $stmt = $pdo->prepare("
            INSERT INTO libri (isbn, titolo, descrizione)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                titolo = VALUES(titolo),
                descrizione = VALUES(descrizione)
        ");
        $stmt->execute([$isbn, $bookData['title'], $bookData['description']]);

        /* AUTORI */
        foreach ($bookData['authors'] as $author) {
            $parts = explode(' ', trim($author));
            $cognome = array_pop($parts);
            $nome = implode(' ', $parts);

            $stmt = $pdo->prepare("SELECT id_autore FROM autori WHERE nome = ? AND cognome = ?");
            $stmt->execute([$nome, $cognome]);
            $id_autore = $stmt->fetchColumn();

            if (!$id_autore) {
                $stmt = $pdo->prepare("INSERT INTO autori (nome, cognome) VALUES (?, ?)");
                $stmt->execute([$nome, $cognome]);
                $id_autore = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("INSERT IGNORE INTO autore_libro (id_autore, isbn) VALUES (?, ?)");
            $stmt->execute([$id_autore, $isbn]);
        }

        /* CATEGORIE */
        foreach ($bookData['categories'] as $cat) {
            $stmt = $pdo->prepare("SELECT id_categoria FROM categorie WHERE categoria = ?");
            $stmt->execute([$cat]);
            $id_cat = $stmt->fetchColumn();

            if (!$id_cat) {
                $stmt = $pdo->prepare("INSERT INTO categorie (categoria) VALUES (?)");
                $stmt->execute([$cat]);
                $id_cat = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("INSERT IGNORE INTO libro_categoria (isbn, id_categoria) VALUES (?, ?)");
            $stmt->execute([$isbn, $id_cat]);
        }

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        $GLOBALS['messaggio_db'] .= "Errore PDO: " . $e->getMessage();
        return false;
    }
}

/* ---------------- RENDER COVER ---------------- */
function renderBookCover($book) {
    $cover = $book['copertina'] ?? 'src/assets/placeholder.jpg';
    $isbn = $book['isbn'] ?? '';
    ob_start(); ?>
    <div class="card cover-only">
        <a href="libro_info.php?isbn=<?= htmlspecialchars($isbn) ?>">
            <img src="<?= htmlspecialchars($cover) ?>" alt="<?= htmlspecialchars($book['titolo'] ?? 'Libro') ?>">
        </a>
    </div>
    <?php
    return ob_get_clean();
}


/* ---------------- SYNC ALL ISBN ---------------- */
$stmt = $pdo->query("SELECT isbn FROM libri");
$isbns = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($isbns as $isbn) { syncBookData($pdo, $isbn); }

/* ---------------- ULTIME USCITE ---------------- */
$stmt = $pdo->query("
    SELECT l.*, c.copertina 
    FROM libri l 
    JOIN copie c ON l.isbn = c.isbn
    ORDER BY l.isbn DESC
");
$ultime_uscite = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- PRESTITI ATTIVI ---------------- */
$prestiti_attivi = [];
$codice = null;

if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT codice_alfanumerico FROM utenti WHERE id_utente = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $codice = $stmt->fetchColumn();

    if ($codice) {
        $stmt = $pdo->prepare("
            SELECT l.*, c.copertina, p.data_prestito, p.data_scadenza
            FROM prestiti p
            JOIN copie c ON p.id_copia = c.id_copia
            JOIN libri l ON l.isbn = c.isbn
            WHERE p.codice_alfanumerico = ?
              AND p.data_restituzione IS NULL
        ");
        $stmt->execute([$codice]);
        $prestiti_attivi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* ---------------- LIBRI TOP VOTATI ---------------- */
$stmt = $pdo->query("
    SELECT l.*, c.copertina, AVG(r.voto) AS media_voto
    FROM libri l
    JOIN copie c ON l.isbn = c.isbn
    JOIN recensioni r ON r.isbn = l.isbn
    GROUP BY l.isbn
    ORDER BY media_voto DESC
    LIMIT 10
");
$top_votati = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- PRESTITI ATTIVI ---------------- */
$prestiti_attivi = [];
$codice = $_SESSION['codice_utente'] ?? null;   // <-- QUI USIAMO IL CODICE UTENTE

if ($codice) {
    $stmt = $pdo->prepare("
        SELECT l.*, c.copertina, p.data_prestito, p.data_scadenza
        FROM prestiti p
        JOIN copie c ON p.id_copia = c.id_copia
        JOIN libri l ON l.isbn = c.isbn
        WHERE p.codice_alfanumerico = ?
          AND p.data_restituzione IS NULL
    ");
    $stmt->execute([$codice]);
    $prestiti_attivi = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------------- CONSIGLIATI PER L’UTENTE ---------------- */
/* ---------------- CONSIGLIATI PER L’UTENTE ---------------- */
$consigliati = [];

if ($codice) {
    // Prendo le categorie dei libri che l'utente ha preso in prestito
    $stmt = $pdo->prepare("
        SELECT DISTINCT lc.id_categoria
        FROM prestiti p
        JOIN copie c ON p.id_copia = c.id_copia
        JOIN libro_categoria lc ON lc.isbn = c.isbn
        WHERE p.codice_alfanumerico = ?
    ");
    $stmt->execute([$codice]);
    $catIDs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($catIDs) {
        $in = implode(',', array_fill(0, count($catIDs), '?'));

        // Prendo tutti gli ISBN già presi in prestito dall'utente
        $stmt2 = $pdo->prepare("
            SELECT c.isbn
            FROM prestiti p
            JOIN copie c ON p.id_copia = c.id_copia
            WHERE p.codice_alfanumerico = ?
        ");
        $stmt2->execute([$codice]);
        $esclusi = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        $esclusi_placeholders = "";
        if ($esclusi) {
            $esclusi_placeholders = "AND l.isbn NOT IN (" . implode(',', array_fill(0, count($esclusi), '?')) . ")";
        }

        // Seleziono libri consigliati basati sulle categorie, escludendo quelli già presi
        $sql = "
            SELECT DISTINCT l.*, c.copertina
            FROM libri l
            JOIN copie c ON l.isbn = c.isbn
            JOIN libro_categoria lc ON lc.isbn = l.isbn
            WHERE lc.id_categoria IN ($in) $esclusi_placeholders
            ORDER BY RAND()
            LIMIT 10
        ";
        $stmt3 = $pdo->prepare($sql);

        // Parametri per la query
        $params = array_merge($catIDs, $esclusi ?: []);
        $stmt3->execute($params);
        $consigliati = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    }
}



?>

<style>
    .grid {
        display: grid;
        grid-template-columns: repeat(10, 1fr);
        gap: 12px;
        width: 100%;
        justify-items: center;
    }

    .card.cover-only img {
        width: 100%;
        height: auto;
        object-fit: cover;
    }
</style>


<?php require './src/includes/header.php'; ?>
<?php require './src/includes/navbar.php'; ?>

<div class="page_contents">

    <h1>Home</h1>

    <!-- MESSAGGI -->
    <?php if ($messaggio_db): ?>
        <pre class="message"><?= htmlspecialchars($messaggio_db) ?></pre>
    <?php endif; ?>

    <!-- PRESTITI ATTIVI -->
    <?php if ($prestiti_attivi): ?>
        <div class="section">
            <h2>I tuoi prestiti attivi</h2>
            <div class="grid">
                <?php foreach ($prestiti_attivi as $libro): ?>
                    <?= renderBookCover($libro) ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ULTIME USCITE -->
    <div class="section">
        <h2>Ultime uscite</h2>
        <div class="grid">
            <?php foreach ($ultime_uscite as $libro): ?>
                <?= renderBookCover($libro) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TOP VOTATI -->
    <div class="section">
        <h2>Top votati</h2>
        <div class="grid">
            <?php foreach ($top_votati as $libro): ?>
                <?= renderBookCover($libro) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CONSIGLIATI -->
    <?php if ($codice): ?>
        <div class="section">
            <h2>Consigliati per te</h2>
            <div class="grid">
                <?php foreach ($consigliati as $libro): ?>
                    <?= renderBookCover($libro) ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require './src/includes/footer.php'; ?>
