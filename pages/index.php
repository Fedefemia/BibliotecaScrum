<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

$messaggio_db = "";
$codice = $_SESSION['codice_utente'] ?? null;

// ---------------- 1. LOGICA CONSIGLIATI (SOLO SE LOGGATO) ----------------
$consigliati = [];
if ($codice) {
    // Questa query cerca libri basati su generi e autori dei prestiti passati CBR
    // Esclude i libri già letti e ne prende 6 casuali
    $queryConsigliati = "
        SELECT DISTINCT l.isbn, 
               (SELECT CAST(AVG(voto) AS DECIMAL(3,1)) FROM recensioni WHERE isbn = l.isbn) as media_voto,
               -- Determiniamo la motivazione (Priorità al Genere, poi Autore)
               CASE 
                  WHEN lc.id_categoria IN (
                      SELECT lc2.id_categoria FROM prestiti p2 
                      JOIN copie c2 ON p2.id_copia = c2.id_copia 
                      JOIN libro_categoria lc2 ON c2.isbn = lc2.isbn 
                      WHERE p2.codice_alfanumerico = :cod 
                  ) THEN 'Perché ami questo genere'
                  WHEN al.id_autore IN (
                      SELECT al2.id_autore FROM prestiti p3 
                      JOIN copie c3 ON p3.id_copia = c3.id_copia 
                      JOIN autore_libro al2 ON c3.isbn = al2.isbn 
                      WHERE p3.codice_alfanumerico = :cod
                  ) THEN 'Perché ti piace questo autore'
                  ELSE 'Consigliato per te'
               END as motivazione
        FROM libri l
        LEFT JOIN libro_categoria lc ON l.isbn = lc.isbn
        LEFT JOIN autore_libro al ON l.isbn = al.isbn
        WHERE (
            lc.id_categoria IN (SELECT lc3.id_categoria FROM prestiti p4 JOIN copie c4 ON p4.id_copia = c4.id_copia JOIN libro_categoria lc3 ON c4.isbn = lc3.isbn WHERE p4.codice_alfanumerico = :cod)
            OR 
            al.id_autore IN (SELECT al3.id_autore FROM prestiti p5 JOIN copie c5 ON p5.id_copia = c5.id_copia JOIN autore_libro al3 ON c5.isbn = al3.isbn WHERE p5.codice_alfanumerico = :cod)
        )
        AND l.isbn NOT IN (
            SELECT c6.isbn FROM prestiti p6 
            JOIN copie c6 ON p6.id_copia = c6.id_copia 
            WHERE p6.codice_alfanumerico = :cod
        )
        ORDER BY RAND()
        LIMIT 6
    ";

    $stmtCons = $pdo->prepare($queryConsigliati);
    $stmtCons->execute(['cod' => $codice]);
    $consigliati = $stmtCons->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------- 2. POPOLARI (Con Media Voto) ----------------
$stmt = $pdo->query("
    SELECT l.isbn, CAST(AVG(r.voto) AS DECIMAL(3,1)) as media_voto
    FROM libri l
    JOIN recensioni r ON r.isbn = l.isbn
    GROUP BY l.isbn
    ORDER BY media_voto DESC
    LIMIT 10
");
$popolari = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------- 3. CATEGORIE POPOLARI ----------------
$catPopolari = [];
$stmt = $pdo->query("SELECT id_categoria, categoria FROM categorie LIMIT 5");
$catPopolari = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoriePopolari = [];
foreach ($catPopolari as $cat) {
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

// ---------------- 4. PRESTITI ATTIVI --------------------
$prestiti_attivi = [];
if ($codice) {
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

function getCoverPath(string $isbn): string {
    $localPath = "public/bookCover/$isbn.png";
    if (file_exists($localPath)) {
        return $localPath;
    }
    return "public/assets/book_placeholder.jpg";
}
?>

<?php
$title = "Biblioteca Scrum";
$path = "./";
$page_css = "./public/css/style_index.css";
require './src/includes/header.php';
require './src/includes/navbar.php';
?>

    <style>
        /* Stili specifici per il widget consigliati */
        .book_wrapper { display: flex; flex-direction: column; align-items: center; width: 100%; }
        .spiegazione_trasparente {
            font-size: 0.75rem;
            color: #777;
            text-align: center;
            margin-top: 10px;
            font-style: italic;
            max-width: 140px;
            line-height: 1.2;
        }
    </style>

    <div class="index_wrapper">
        <header class="index_hero">
            <img src="./public/assets/icon.png" alt="Logo" class="hero_icon">
            <h1 class="hero_title young-serif-regular">Scrum Library</h1>
        </header>

        <div class="page_contents">

            <?php if ($prestiti_attivi): ?>
                <section class="index_section">
                    <div class="section_header">
                        <img src="./public/assets/logo_ligth.png" class="section_icon" alt="icon">
                        <h2 class="section_title young-serif-regular">I tuoi prestiti</h2>
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

            <?php if ($codice && !empty($consigliati)): ?>
                <section class="index_section">
                    <div class="section_header">
                        <img src="./public/assets/icone_categorie/Raccomandazione.png" class="section_icon" alt="icon">
                        <h2 class="section_title young-serif-regular">Consigliati per te</h2>
                    </div>
                    <div class="books_grid">
                        <?php foreach ($consigliati as $libro): ?>
                            <div class="book_wrapper">
                                <a href="./libro?isbn=<?= $libro['isbn'] ?>" class="book_item">
                                    <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Cover" class="book_cover">
                                    <div class="voto-box instrument-sans-semibold">
                                        <img src="./public/assets/ui_icon_star.png" class="star_icon" alt="star">
                                        <?= $libro['media_voto'] ? number_format((float)$libro['media_voto'], 1) : 'N/V' ?>
                                    </div>
                                </a>
                                <p class="spiegazione_trasparente"><?= htmlspecialchars($libro['motivazione']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <hr class="section_divider">
                </section>
            <?php endif; ?>

            <section class="index_section">
                <div class="section_header">
                    <img src="<?= $path ?>public/assets/icone_categorie/Icon_LibriPopolari.png" class="section_icon" alt="icon">
                    <h2 class="section_title young-serif-regular">Libri Popolari</h2>
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
                            <img src="<?= $path ?>public/assets/icone_categorie/<?=$catName ?>.png" class="section_icon" alt="icon" onerror="this.src='<?= $path ?>public/assets/logo_ligth.png'">
                            <h2 class="section_title young-serif-regular"><?= htmlspecialchars($catName) ?></h2>
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