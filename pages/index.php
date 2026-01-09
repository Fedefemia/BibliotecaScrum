<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
//ini_set('error_log', '/var/www/html/php_errors.log');

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
$path = "./";
$page_css = "./public/css/style_index.css";
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

    <div class="index_wrapper">
        <header class="index_hero">
            <img src="./public/assets/icon.png" alt="Logo" class="hero_icon">
            <h1 class="hero_title">Scrum Library</h1>
        </header>

        <div class="page_contents">
            <?php if ($messaggio_db): ?>
                <pre class="message"><?= $messaggio_db ?></pre>
            <?php endif; ?>

            <?php if ($prestiti_attivi): ?>
                <section class="index_section">
                    <div class="section_header">
                        <img src="./public/assets/logo_ligth.png" class="section_icon" alt="icon">
                        <h2 class="section_title">I tuoi prestiti</h2>
                    </div>
                    <div class="books_grid">
                        <?php foreach ($prestiti_attivi as $libro): ?>
                            <a href="./libro?isbn=<?= $libro['isbn'] ?>" class="book_item">
                                <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Cover" class="book_cover">
                                <div class="voto-box instrument-sans-semibold">
                                    <img src="./public/assets/ui_icon_star.png" class="star_icon" alt="star">
                                    <?= $libro['media_voto'] ? number_format((float)$libro['media_voto'], 1) : 'N/V' ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <hr class="section_divider">
                </section>
            <?php endif; ?>

            <section class="index_section">
                <div class="section_header">
                    <img src="<?= $path ?>public/assets/icone_categorie/Icon_LibriPopolari.png" class="section_icon" alt="icon">
                    <h2 class="section_title">Libri Popolari</h2>
                </div>
                <div class="books_grid">
                    <?php foreach ($popolari as $libro): ?>
                        <a href="./libro?isbn=<?= $libro['isbn'] ?>" class="book_item">
                            <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Cover" class="book_cover">
                            <div class="voto-box instrument-sans-semibold">
                                <img src="./public/assets/ui_icon_star.png" class="star_icon" alt="star">
                                <?= $libro['media_voto'] ? number_format((float)$libro['media_voto'], 1) : 'N/V' ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <hr class="section_divider">
            </section>

            <?php foreach ($categoriePopolari as $catName => $libriCat): ?>
                <?php if ($libriCat): ?>
                    <section class="index_section">
                        <div class="section_header">
                            <img src="<?= $path ?>public/assets/icone_categorie/<?=$catName ?>.png" class="section_icon" alt="icon">
                            <h2 class="section_title"><?= htmlspecialchars($catName) ?></h2>
                        </div>
                        <div class="books_grid">
                            <?php foreach ($libriCat as $libro): ?>
                                <a href="./libro?isbn=<?= $libro['isbn'] ?>" class="book_item">
                                    <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Cover" class="book_cover">
                                    <div class="voto-box instrument-sans-semibold">
                                        <img src="./public/assets/ui_icon_star.png" class="star_icon" alt="star">
                                        <?= $libro['media_voto'] ? number_format((float)$libro['media_voto'], 1) : 'N/V' ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <hr class="section_divider">
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

<?php require './src/includes/footer.php'; ?>