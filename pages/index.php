<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/php_errors.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

$messaggio_db = "";

// Recupera codice utente se loggato
$codice = $_SESSION['codice_utente'] ?? null;

// ---------------- POPOLARI (Con Media Voto) ----------------
$stmt = $pdo->query("
    SELECT l.isbn, CAST(AVG(r.voto) AS DECIMAL(3,1)) as media_voto
    FROM libri l
    JOIN recensioni r ON r.isbn = l.isbn
    GROUP BY l.isbn
    ORDER BY media_voto DESC
    LIMIT 10
");
$popolari = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------- CATEGORIE POPOLARI (Con Media Voto) ----------------
$catPopolari = [];
$stmt = $pdo->query("SELECT id_categoria, categoria FROM categorie LIMIT 5");
$catPopolari = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoriePopolari = [];
foreach ($catPopolari as $cat) {
    // Qui usiamo LEFT JOIN su recensioni per prendere il voto anche nelle categorie
    $stmt = $pdo->prepare("
        SELECT l.isbn, CAST(AVG(r.voto) AS DECIMAL(3,1)) as media_voto
        FROM libri l
        JOIN libro_categoria lc ON lc.isbn = l.isbn
        LEFT JOIN recensioni r ON r.isbn = l.isbn
        WHERE lc.id_categoria = ?
        GROUP BY l.isbn
        LIMIT 10
    ");
    $stmt->execute([$cat['id_categoria']]);
    $categoriePopolari[$cat['categoria']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------- PRESTITI ATTIVI --------------------
$prestiti_attivi = [];
if ($codice) {
    // Anche qui calcoliamo la media globale del libro prestato
    $stmt = $pdo->prepare("
        SELECT c.isbn, 
               (SELECT CAST(AVG(voto) AS DECIMAL(3,1)) FROM recensioni WHERE isbn = c.isbn) as media_voto
        FROM prestiti p
        JOIN copie c ON p.id_copia = c.id_copia
        WHERE p.codice_alfanumerico = ? AND p.data_restituzione IS NULL
    ");
    $stmt->execute([$codice]);
    $prestiti_attivi = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------- HTML HEADER ----------------
require './src/includes/header.php';
require './src/includes/navbar.php';

function getCoverPath(string $isbn): string {
    $localPath = "public/bookCover/$isbn.png";
    $publicPath = "public/bookCover/$isbn.png";

    if (file_exists($localPath)) {
        return $publicPath;
    }

    return "public/assets/book_placeholder.jpg";
}

// Funzione helper per stampare il voto
function renderVoto($media) {
    if (!$media) return '<span class="voto-box" style="color:#ccc">N/V</span>';
    // number_format forza la visualizzazione di 1 decimale (es. 3.0 o 3.4)
    $votoFormattato = number_format((float)$media, 1);
    return '<span class="voto-box"><span style="color: #f39c12;">★</span> ' . $votoFormattato . '</span>';
}
?>

<style>
.grid {
    display: flex;
    flex-wrap: wrap;
    gap: 20px; /* Aumentato leggermente lo spazio */
}

.card.cover-only {
    flex: 0 0 auto;
    width: 120px;
    /* Altezza rimossa (auto) per contenere anche il voto */
    height: auto; 
    display: flex;
    flex-direction: column;
    text-decoration: none; /* Toglie la sottolineatura dal link */
    color: #333;
    margin-bottom: 10px;
}

.card.cover-only img {
    width: 120px;
    height: 180px; /* L'immagine mantiene l'altezza fissa */
    object-fit: cover;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.voto-box {
    margin-top: 5px;
    font-size: 0.9rem;
    text-align: center;
    font-weight: bold;
    display: block;
}
</style>

<div class="page_contents">
    <?php if ($messaggio_db): ?>
        <pre class="message"><?= $messaggio_db ?></pre>
    <?php endif; ?>

    <?php if ($prestiti_attivi): ?>
        <div class="section">
            <h2>I tuoi prestiti attivi</h2>
            <div class="grid">
                <?php foreach ($prestiti_attivi as $libro): ?>
                    <a href="./libro?isbn=<?= $libro['isbn'] ?>" class="card cover-only">
                        <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Libro">
                        <?= renderVoto($libro['media_voto']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="section">
        <h2>Libri Popolari</h2>
        <div class="grid">
            <?php foreach ($popolari as $libro): ?>
                <a href="./libro?isbn=<?= $libro['isbn'] ?>" class="card cover-only">
                    <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Libro">
                    <?= renderVoto($libro['media_voto']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php foreach ($categoriePopolari as $catName => $libriCat): ?>
        <div class="section">
            <h2><?= htmlspecialchars($catName) ?></h2>
            <div class="grid">
                <?php if ($libriCat): ?>
                    <?php foreach ($libriCat as $libro): ?>
                        <a href="./libro?isbn=<?= $libro['isbn'] ?>" class="card cover-only">
                            <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Libro">
                            <?= renderVoto($libro['media_voto']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>Nessun libro in questa categoria</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require './src/includes/footer.php'; ?>