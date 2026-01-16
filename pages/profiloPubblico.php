<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', '/var/www/html/php_errors.log');

require_once __DIR__ . '/../vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';
require_once './phpmailer.php';

$uid = $_GET['username'] ?? '';

if (!isset($pdo)) { die('Errore connessione DB.'); }


/* ---- Recupero Dati Utente ---- */
$stm = $pdo->prepare("SELECT * FROM utenti WHERE username = ?");
$stm->execute([$uid]);
$utente = $stm->fetch(PDO::FETCH_ASSOC) ?? null;
$livello = $utente['livello_privato'] ?? -1;

$stm = $pdo->prepare("
    SELECT p.id_prestito, c.isbn, p.data_restituzione
    FROM prestiti p
    JOIN copie c ON p.id_copia = c.id_copia
    WHERE p.codice_alfanumerico = ? AND p.data_restituzione IS NOT NULL
    ORDER BY p.data_restituzione DESC
");
$stm->execute([$uid]);
$libri_letti = $stm->fetchAll(PDO::FETCH_ASSOC);

$badges = [];

if (isset($uid) && $uid) {
    try {
        $stm = $pdo->prepare("
            SELECT b.*, ub.livello
            FROM badge b
            JOIN utente_badge ub ON b.id_badge = ub.id_badge
            WHERE ub.codice_alfanumerico = ?
            ORDER BY ub.livello DESC, b.nome ASC
        ");
        $stm->execute([$uid]);
        $badges = $stm->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messaggio_db = "Errore caricamento badge: " . $e->getMessage();
    }
}
function badgeIconHtmlProfile(array $badge) {
    $icon = $badge['icona'] ?? '';
    // Primo tentativo: file in public/badges/
    $webPath = "./public/assets/badge/" . $icon . '.png';
    if ($icon && file_exists($webPath)) {
        return '<img src="' . htmlspecialchars($webPath) . '" alt="' . htmlspecialchars($badge['nome']) . '" style="width:72px;height:72px;object-fit:contain;border-radius:8px;">';
    }
    // Non uso SVG inline qui per sicurezza — fallback lettera
    $letter = strtoupper(substr($badge['nome'] ?? 'B', 0, 1));
    return '<div style="width:72px;height:72px;border-radius:10px;background:#f3f3f3;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:28px;color:#666;">' .
        htmlspecialchars($letter) . '</div>';
}
require './src/includes/header.php';
require './src/includes/navbar.php';

function getCoverPath(string $isbn): string {
    $localPath = "public/bookCover/$isbn.png";
    return file_exists($localPath) ? $localPath : "public/assets/book_placeholder.jpg";
}

function formatCounter($dateTarget) {
    if (!$dateTarget) return ["N/D", "grey"];
    $today = new DateTime(); $target = new DateTime($dateTarget); $target->setTime(23, 59, 59);
    $diff = $today->diff($target); $days = $diff->days; if ($diff->invert) { $days = -$days; }
    $dateString = $target->format('d/m/Y'); $text = "Scadenza: $dateString";
    if ($days < 0) { return ["$text (Scaduto da " . abs($days) . " gg)", "#c0392b"]; }
    elseif ($days <= 2) { return ["$text ($days giorni)", "#e67e22"]; }
    else { return ["$text ($days giorni)", "#27ae60"]; }
}
?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Libre+Barcode+39+Text&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .grid { display: flex; flex-wrap: wrap; gap: 25px; }
        .book-item { display: flex; flex-direction: column; width: 120px; align-items: center; gap: 5px; }
        .card.cover-only { width: 120px; display: block; text-decoration: none; color: #333; margin-bottom: 0; }
        .card.cover-only img { width: 120px; height: 180px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: transform 0.2s; }
        .card.cover-only:hover img { transform: translateY(-3px); }
        .book-meta { font-size: 0.75rem; text-align: center; line-height: 1.3; font-weight: 500; width: 100%; }

        .info_column { display: flex; flex-direction: column; width: auto; justify-content: flex-start; align-items: center; gap: 10px; }
        .info_line { display: flex; flex-direction: row; width: 100%; justify-content: space-between; align-items: flex-start; gap: 20px; padding-top: 20px; }
        .pfp-wrapper { position: relative; width: 240px; height: 240px; border-radius: 50%; border: 5px solid #3f5135; overflow: hidden; cursor: pointer; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .info_pfp { width: 100%; height: 100%; object-fit: cover; display: block; }
        .pfp-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); display: flex; flex-direction: column; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; color: #fff; }
        .pfp-wrapper:hover .pfp-overlay { opacity: 1; }
        .pfp-icon { font-size: 24px; margin-bottom: 5px; }
        .pfp-text { font-size: 14px; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; }
        .extend_all { width: 100%; height: 100%; justify-content: space-between; align-items: flex-start; }
        .section { width: 100%; height: auto; display: flex; flex-direction: column; margin-bottom: 30px; }
        .edit-container-wrapper { margin-top: 10px; width: 260px; display: flex; flex-direction: column; gap: 8px; }
        .edit-row { width: 100%; display: flex; align-items: center; }
        .edit-input { flex: 1; min-width: 0; padding: 8px; border: 1px solid #ccc; border-radius: 4px; color: #333; font-family: 'Poppins', sans-serif; font-size: 1em; transition: all 0.3s ease; }
        .edit-input:disabled { background: #eee; color: #666; border: 1px solid transparent; }
        .btn-slide { width: 0; padding: 0; opacity: 0; margin-left: 0; overflow: hidden; white-space: nowrap; background-color: #3f5135; color: white; border: none; border-radius: 4px; font-size: 0.9em; cursor: pointer; transition: all 0.4s ease; }
        .edit-row.changed .btn-slide { width: 80px; padding: 8px 0; opacity: 1; margin-left: 5px; }
        #notification-banner { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background-color: #222; color: white; padding: 14px 24px; border-radius: 6px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 20px; transition: bottom 0.5s; z-index: 9999; min-width: 250px; justify-content: space-between; }
        #notification-banner.show { bottom: 30px; }
        .notification-text { font-size: 15px; font-weight: 500; }
        .close-btn-banner { background: none; border: none; color: #bbb; font-size: 22px; cursor: pointer; padding: 0; line-height: 1; }
    </style>

    <div class="info_line">
       <?php if($livello != -1): ?>

           <div class="info_column">
               <?php
               $pfpPath = 'public/pfp/' . htmlspecialchars($uid) . '.png';
               if (!file_exists($pfpPath)) { $pfpPath = 'public/assets/base_pfp.png'; }
               ?>
               <div class="pfp-wrapper">
                   <img class="info_pfp" alt="Pfp" src="<?= $pfpPath . '?v=' . time() ?>">
               </div>
               <div class="edit-container-wrapper">
                   <h2 style="text-align: center"><?= htmlspecialchars($utente['username'] ?? '') ?></h2>
               </div>
           </div>

           <div class="info_column extend_all">

               <?php if($livello > 0): ?>

                   <div class="section">
                       <?php if($livello >= 1) : ?>
                           <h2>Badge</h2>
                           <?php if (!empty($badges)): ?>
                               <?php foreach ($badges as $b): ?>
                                   <div class="book-item">
                                       <a href="./badge?id=<?= intval($b['id_badge']) ?>" class="card cover-only">
                                           <?= badgeIconHtmlProfile($b) ?>
                                       </a>
                                       <div class="book-meta">
                                           <div class="book_main_title" style="font-size:1rem; margin:0;">
                                               <?= htmlspecialchars($b['nome']) ?>
                                           </div>

                                           <div class="book_authors" style="margin-top:6px; font-size:0.9rem;">
                                               Livello: <strong><?= intval($b['livello']) ?></strong>
                                               <?php if (!empty($b['target_numerico'])): ?>
                                                   &nbsp;•&nbsp; Target: <?= intval($b['target_numerico']) ?>
                                               <?php endif; ?>
                                           </div>

                                           <?php if (!empty($b['descrizione'])): ?>
                                               <div class="book_desc_text" style="margin-top:8px; font-size:0.9rem; max-height:48px; overflow:hidden;">
                                                   <?= nl2br(htmlspecialchars($b['descrizione'])) ?>
                                               </div>
                                           <?php endif; ?>
                                       </div>
                                   </div>
                               <?php endforeach; ?>
                           <?php else: ?>
                               <h4 style="color:#888;">Nessun badge acquisito</h4>
                           <?php endif; ?>
                       <?php endif; ?>
                   </div>

                   <div class="section">
                       <?php if($livello == 2) : ?>
                           <h2>Libri Letti</h2>
                           <div class="grid">
                               <?php if ($libri_letti): foreach ($libri_letti as $libro): ?>
                                   <div class="book-item">
                                       <a href="./libro?isbn=<?= htmlspecialchars($libro['isbn']) ?>" class="card cover-only"><img src="<?= getCoverPath($libro['isbn']) ?>" alt="Libro"></a>
                                   </div>
                               <?php endforeach; else: ?>
                                   <h4 style="color:#888;">Nessun libro ancora letto</h4>
                               <?php endif; ?>
                           </div>
                       <?php endif; ?>
                   </div>

               <?php else: ?>
                   <div class="section">
                       <div class="info_column">
                           <img style="width: 240px; height: 240px" src="<?= $path ?>public/assets/icone_categorie/Lucchetto.png">
                           <h2>Il profilo è privato</h2>
                       </div>
                   </div>
               <?php endif; ?>

           </div>

        <?php else: ?>

            <h2 style="font-family: 'Young Serif', serif">Il profilo non esiste</h2>

        <?php endif; ?>
    </div>

    <div id="notification-banner">
        <span id="banner-msg" class="notification-text">Notifica</span>
        <button class="close-btn-banner" onclick="hideNotification()">&times;</button>
    </div>

<?php require './src/includes/footer.php'; ?>