<?php
session_start();
require_once 'db_config.php';

$messaggio_db = "";
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

        // 4. Calcola Media Voti e Totale (MODIFICATO PER FLOAT)
        // Usiamo CAST per ottenere un decimale preciso dal DB
        $stmt = $pdo->prepare("
            SELECT CAST(AVG(voto) AS DECIMAL(3,1)) as media, COUNT(*) as totale 
            FROM recensioni 
            WHERE isbn = ?
        ");
        $stmt->execute([$isbn]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Usiamo number_format per forzare la visualizzazione del decimale (es. 4.0 o 3.4)
        $mediaVoto = $stats['media'] ? number_format((float)$stats['media'], 1) : 0;
        $totaleRecensioni = $stats['totale'];

        // 5. Recupera SOLO 2 Recensioni (LIMIT 2)
        $stmt = $pdo->prepare("
            SELECT r.*, u.username
            FROM recensioni r
            JOIN utenti u ON r.codice_alfanumerico = u.codice_alfanumerico
            WHERE r.isbn = ?
            ORDER BY r.data_commento DESC
            LIMIT 2
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
    return "public/assets/book_placeholder.jpg";
}
?>

<?php require './src/includes/header.php'; ?>
<?php require './src/includes/navbar.php'; ?>

<div class="page_contents">

    <?php if ($messaggio_db || !$libro): ?>
        <div class="error-container">
            <h1>Ops!</h1>
            <p><?= htmlspecialchars($messaggio_db ?: "Impossibile trovare il libro richiesto.") ?></p>
            <a href="index.php">Torna alla Home</a>
        </div>
    <?php else: ?>

        <h1><?= htmlspecialchars($libro['titolo']) ?></h1>

        <div class="book_info">
            <img id="book-cover-image" src="<?= getCoverPath($libro['isbn']) ?>" alt="Copertina" class="book_cover"
                style="max-width: 200px;">

            <p><strong>Autori:</strong>
                <?= htmlspecialchars(implode(', ', array_map(fn($a) => $a['nome'] . ' ' . $a['cognome'], $autori))) ?>
            </p>

            <p><strong>Editore:</strong> <?= htmlspecialchars($libro['editore']) ?></p>
            <p><strong>Anno pubblicazione:</strong> <?= htmlspecialchars($libro['anno_pubblicazione'] ?? 'N/D') ?></p>

            <p><strong>Media Voto:</strong>
                <?php if ($totaleRecensioni > 0): ?>
                    <span>★</span>
                    <strong><?= $mediaVoto ?></strong> / 5.0
                    <small>(su <?= $totaleRecensioni ?> recensioni totali)</small>
                <?php else: ?>
                    <span>Nessuna valutazione</span>
                <?php endif; ?>
            </p>

            <p><strong>Disponibilità:</strong>
                <?php if ($libro['numero_copie_disponibili'] > 0): ?>
                    <span>Disponibile (<?= $libro['numero_copie_disponibili'] ?> copie)</span>
                <?php else: ?>
                    <span>Non disponibile al momento</span>
                <?php endif; ?>
            </p>

            <p class="descrizione"><strong>Descrizione:</strong><?= nl2br(htmlspecialchars($libro['descrizione'])) ?></p>

            <p><strong>Categorie:</strong> <?= htmlspecialchars(implode(', ', $categorie)) ?></p>
        </div>

        <hr>

        <h2>Ultime Recensioni</h2>

        <?php if ($recensioni): ?>
            <div class="reviews">
                <?php foreach ($recensioni as $r): ?>
                    <div class="review_card">
                        <div>
                            <strong><?= htmlspecialchars($r['username']) ?></strong>
                            <small><?= htmlspecialchars($r['data_commento']) ?></small>
                        </div>
                        <div>
                            <?php for ($i = 0; $i < $r['voto']; $i++)
                                echo "★"; ?>
                            <span><?php for ($i = $r['voto']; $i < 5; $i++)
                                echo "★"; ?></span>
                        </div>
                        <p><em>"<?= nl2br(htmlspecialchars($r['commento'])) ?>"</em></p>
                    </div>
                <?php endforeach; ?>

                <?php if ($totaleRecensioni > 2): ?>
                    <p><em>...e altre <?= $totaleRecensioni - 2 ?> recensioni.</em></p>
                <?php endif; ?>

            </div>
        <?php else: ?>
            <p>Nessuna recensione disponibile per questo libro.</p>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php require './src/includes/footer.php'; ?>