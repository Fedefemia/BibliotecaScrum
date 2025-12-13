<?php
session_start();
require_once 'db_config.php';

$messaggio_db = "";
$isbn = $_GET['isbn'] ?? null;

if (!$isbn) {
    die("ISBN non specificato.");
}

// Recupera info libro
try {
    $stmt = $pdo->prepare("
        SELECT l.*, c.copertina, c.anno_pubblicazione, c.editore, c.disponibile
        FROM libri l
        JOIN copie c ON l.isbn = c.isbn
        WHERE l.isbn = ?
    ");
    $stmt->execute([$isbn]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$libro) die("Libro non trovato.");

    // Autori
    $stmt = $pdo->prepare("
        SELECT a.nome, a.cognome
        FROM autori a
        JOIN autore_libro al ON al.id_autore = a.id_autore
        WHERE al.isbn = ?
    ");
    $stmt->execute([$isbn]);
    $autori = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Categorie
    $stmt = $pdo->prepare("
        SELECT categoria
        FROM categorie c
        JOIN libro_categoria lc ON lc.id_categoria = c.id_categoria
        WHERE lc.isbn = ?
    ");
    $stmt->execute([$isbn]);
    $categorie = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Recensioni
    $stmt = $pdo->prepare("
        SELECT r.*, u.username
        FROM recensioni r
        JOIN utenti u ON r.codice_alfanumerico = u.codice_alfanumerico
        WHERE r.isbn = ?
        ORDER BY r.data_commento DESC
    ");
    $stmt->execute([$isbn]);
    $recensioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $messaggio_db .= "Errore DB: " . $e->getMessage();
}
?>

<?php require './src/includes/header.php'; ?>
<?php require './src/includes/navbar.php'; ?>

<div class="page_contents">
    <h1><?= htmlspecialchars($libro['titolo']) ?></h1>

    <?php if ($messaggio_db): ?>
        <pre class="message"><?= htmlspecialchars($messaggio_db) ?></pre>
    <?php endif; ?>

    <div class="book_info">
        <img src="<?= htmlspecialchars($libro['copertina']) ?>" alt="Copertina" class="book_cover">
        <p><strong>Autori:</strong>
            <?= htmlspecialchars(implode(', ', array_map(fn($a)=>$a['nome'].' '.$a['cognome'], $autori))) ?>
        </p>
        <p><strong>Editore:</strong> <?= htmlspecialchars($libro['editore']) ?></p>
        <p><strong>Anno pubblicazione:</strong> <?= htmlspecialchars($libro['anno_pubblicazione']) ?></p>
        <p><strong>Disponibile:</strong> <?= $libro['disponibile'] ? 'Sì' : 'No' ?></p>
        <p><strong>Descrizione:</strong></p>
        <p><?= nl2br(htmlspecialchars($libro['descrizione'])) ?></p>

        <p><strong>Categorie:</strong> <?= htmlspecialchars(implode(', ', $categorie)) ?></p>
    </div>

    <h2>Recensioni</h2>
    <?php if ($recensioni): ?>
        <div class="reviews">
            <?php foreach ($recensioni as $r): ?>
                <div class="review_card">
                    <p><strong><?= htmlspecialchars($r['username']) ?></strong> - <?= htmlspecialchars($r['data_commento']) ?></p>
                    <p>Voto: <?= htmlspecialchars($r['voto']) ?>/5</p>
                    <p><?= nl2br(htmlspecialchars($r['commento'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Nessuna recensione disponibile.</p>
    <?php endif; ?>
</div>

<?php require './src/includes/footer.php'; ?>
