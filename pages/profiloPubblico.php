<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', '/var/www/html/php_errors.log');

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';
require_once './phpmailer.php';

$username_target = $_GET['username'] ?? '';

if (!isset($pdo)) {
    die('Errore connessione DB.');
}

/* ---- Recupero Dati Utente Target ---- */
$stm = $pdo->prepare("SELECT * FROM utenti WHERE username = ?");
$stm->execute([$username_target]);
$utente = $stm->fetch(PDO::FETCH_ASSOC) ?? null;

$user_exists = ($utente !== null);
$uid_target = $utente['codice_alfanumerico'] ?? null;
$livello = $utente['livello_privato'] ?? -1; // 0: Privato, 1: Badge, 2: Full

/* ---- Logica Recupero Dati ---- */
$badges_to_display = [];
$libri_letti = [];

if ($user_exists && $livello > 0) {

    // --- RECUPERO LIBRI LETTI (Solo se Livello 2) ---
    if ($livello == 2) {
        $stm = $pdo->prepare("
            SELECT p.id_prestito, c.isbn, p.data_restituzione
            FROM prestiti p
            JOIN copie c ON p.id_copia = c.id_copia
            WHERE p.codice_alfanumerico = ? AND p.data_restituzione IS NOT NULL
            ORDER BY p.data_restituzione DESC
        ");
        $stm->execute([$uid_target]);
        $libri_letti = $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CALCOLO STATISTICHE E BADGE (Livello 1 e 2) ---
    try {
        // 1. Calcolo Statistiche dell'utente target
        $user_stats = [];

        // Libri Letti (Totale)
        $stm = $pdo->prepare("SELECT COUNT(*) FROM prestiti WHERE codice_alfanumerico = ? AND data_restituzione IS NOT NULL");
        $stm->execute([$uid_target]);
        $user_stats['libri_letti'] = $stm->fetchColumn();

        // Restituzioni Puntuali
        $stm = $pdo->prepare("SELECT COUNT(*) FROM prestiti WHERE codice_alfanumerico = ? AND data_restituzione IS NOT NULL AND data_restituzione <= data_scadenza");
        $stm->execute([$uid_target]);
        $user_stats['restituzioni_puntuali'] = $stm->fetchColumn();

        // Numero Multe (connesse ai prestiti dell'utente)
        $stm = $pdo->prepare("SELECT COUNT(*) FROM multe m JOIN prestiti p ON m.id_prestito = p.id_prestito WHERE p.codice_alfanumerico = ?");
        $stm->execute([$uid_target]);
        $user_stats['numero_multe'] = $stm->fetchColumn();

        // Recensioni Scritte
        $stm = $pdo->prepare("SELECT COUNT(*) FROM recensioni WHERE codice_alfanumerico = ?");
        $stm->execute([$uid_target]);
        $user_stats['recensioni_scritte'] = $stm->fetchColumn();

        // Prestiti Effettuati
        $stm = $pdo->prepare("SELECT COUNT(*) FROM prestiti WHERE codice_alfanumerico = ?");
        $stm->execute([$uid_target]);
        $user_stats['prestiti_effettuati'] = $stm->fetchColumn();

        // Recupero ID Badge già assegnati DB
        $stm = $pdo->prepare("SELECT id_badge FROM utente_badge WHERE codice_alfanumerico = ?");
        $stm->execute([$uid_target]);
        $unlocked_badges_ids = $stm->fetchAll(PDO::FETCH_COLUMN, 0);

        // Recupero badge
        $stm = $pdo->query("SELECT * FROM badge ORDER BY id_badge ASC");
        $all_badges = $stm->fetchAll(PDO::FETCH_ASSOC);

        $badges_by_type = [];
        foreach ($all_badges as $b) {
            $badges_by_type[$b['tipo']][] = $b;
        }

        // Elaborazione Logic Badge
        foreach ($badges_by_type as $type => $badges_list) {
            usort($badges_list, function ($a, $b) use ($type) {
                if ($type === 'numero_multe') {
                    return $b['target_numerico'] - $a['target_numerico'];
                } else {
                    return $a['target_numerico'] - $b['target_numerico'];
                }
            });

            $highest_unlocked = null;

            foreach ($badges_list as $b) {
                $is_unlocked = in_array($b['id_badge'], $unlocked_badges_ids);

                if (!$is_unlocked && isset($user_stats[$type])) {
                    $currentVal = $user_stats[$type];
                    $target = intval($b['target_numerico']);
                    if ($type === 'numero_multe') {
                        if ($currentVal <= $target) $is_unlocked = true;
                    } else {
                        if ($currentVal >= $target) $is_unlocked = true;
                    }
                }

                if ($is_unlocked) {
                    $highest_unlocked = $b;
                }
            }

            if ($highest_unlocked) {
                $badges_to_display[] = $highest_unlocked;
            }
        }

    } catch (PDOException $e) {
        error_log("Errore badge profilo pubblico: " . $e->getMessage());
    }
}

function getCoverPath(string $isbn): string
{
    $localPath = "public/bookCover/$isbn.png";
    return file_exists($localPath) ? $localPath : "public/assets/book_placeholder.jpg";
}

// Setup Pagina
$title = "Profilo di " . htmlspecialchars($username_target);
$path = "./";
$page_css = "./public/css/style_profilo.css";

require './src/includes/header.php';
require './src/includes/navbar.php';
?>

    <div class="info_line">

        <?php if ($user_exists): ?>

            <div class="info_column">
                <?php
                $pfpPath = 'public/pfp/' . htmlspecialchars($uid_target) . '.png';
                if (!file_exists($pfpPath)) {
                    $pfpPath = 'public/assets/base_pfp.png';
                }
                ?>
                <div class="pfp_wrapper">
                    <img class="info_pfp" alt="Foto Profilo" src="<?= $pfpPath . '?v=' . time() ?>">
                </div>

                <h2 class="young-serif-regular h3_title">
                    <?= htmlspecialchars($utente['username']) ?>
                </h2>
            </div>

            <div class="info_column extend_all">

                <?php if ($livello == 0): ?>

                    <div class="section young-serif-regular">
                        <div class="info_column private_section"
                             style="justify-content: center; align-items: center; opacity: 0.7; width: 100%;">
                            <img src="public/assets/icone_categorie/Lucchetto.png" alt="Lucchetto"
                                 style="width: 150px; height: 150px; margin-bottom: 20px;">
                            <h2>Questo profilo è privato</h2>
                        </div>
                    </div>

                <?php else: ?>

                    <div class="section">
                        <h2>Badge Sbloccati</h2>

                        <?php if (!empty($badges_to_display)): ?>
                            <div class="badges_grid">
                                <?php foreach ($badges_to_display as $b):
                                    $idBadge = intval($b['id_badge']);
                                    $imgPath = $path . 'public/assets/badge/' . $idBadge . '.png';
                                    ?>
                                    <div class="badge_card" style="cursor: default;">
                                        <div class="badge_status_pill unlocked">Sbloccato</div>

                                        <div class="badge_image_wrapper">
                                            <img src="<?= htmlspecialchars($imgPath) ?>"
                                                 alt="<?= htmlspecialchars($b['nome']) ?>" class="badge_image">
                                        </div>

                                        <div class="badge_content">
                                            <div class="badge_info_title"><?= htmlspecialchars($b['nome']) ?></div>
                                            <div class="badge_info_desc"><?= htmlspecialchars($b['descrizione']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #888; font-style: italic;">Nessun badge sbloccato... per ora! ✨</p>
                        <?php endif; ?>
                    </div>

                    <?php if ($livello == 2): ?>
                        <div class="section">
                            <h2>Storico letture</h2>
                            <div class="grid">
                                <?php if ($libri_letti): foreach ($libri_letti as $libro): ?>
                                    <div class="book_item">
                                        <a href="./libro?isbn=<?= htmlspecialchars($libro['isbn']) ?>"
                                           class="card cover-only">
                                            <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Copertina">
                                        </a>
                                    </div>
                                <?php endforeach; else: ?>
                                    <p style="color: #888; font-style: italic;">Nessun libro letto di recente.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

            </div>
        <?php endif; ?>
    </div>

<?php require './src/includes/footer.php'; ?>