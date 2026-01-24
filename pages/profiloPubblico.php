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

    /* -----------------------------------------------------------
   LOGICA BADGE
----------------------------------------------------------- */
    $badges_to_display = [];
    $unlocked_badges_ids = [];
    $user_stats = [];

    $uid = $uid_target;
    try {
        // 1. Recupero statistiche utente per calcolo progressi
        $user_stats['libri_letti'] = $totale_libri_letti;

        $stm = $pdo->prepare("SELECT COUNT(*) FROM prestiti WHERE codice_alfanumerico = ? AND data_restituzione IS NOT NULL AND data_restituzione <= data_scadenza");
        $stm->execute([$uid]);
        $user_stats['restituzioni_puntuali'] = $stm->fetchColumn();

        $stm = $pdo->prepare("SELECT COUNT(*) FROM multe m JOIN prestiti p ON m.id_prestito = p.id_prestito WHERE p.codice_alfanumerico = ?");
        $stm->execute([$uid]);
        $user_stats['numero_multe'] = $stm->fetchColumn();

        $stm = $pdo->prepare("SELECT COUNT(*) FROM recensioni WHERE codice_alfanumerico = ?");
        $stm->execute([$uid]);
        $user_stats['recensioni_scritte'] = $stm->fetchColumn();

        $stm = $pdo->prepare("SELECT COUNT(*) FROM prestiti WHERE codice_alfanumerico = ?");
        $stm->execute([$uid]);
        $user_stats['prestiti_effettuati'] = $stm->fetchColumn();

        // 2. Recupero ID dei badge già sbloccati dall'utente (dal DB)
        $stm = $pdo->prepare("SELECT id_badge FROM utente_badge WHERE codice_alfanumerico = ?");
        $stm->execute([$uid]);
        $unlocked_badges_ids = $stm->fetchAll(PDO::FETCH_COLUMN, 0);

        // 3. Recupero di TUTTI i badge disponibili
        $stm = $pdo->query("SELECT * FROM badge ORDER BY id_badge ASC");
        $all_badges = $stm->fetchAll(PDO::FETCH_ASSOC);

        // 4. Raggruppamento per tipo e selezione del badge da mostrare
        $badges_by_type = [];
        foreach ($all_badges as $b) {
            $type = $b['tipo'];
            if (!isset($badges_by_type[$type])) {
                $badges_by_type[$type] = [];
            }
            $badges_by_type[$type][] = $b;
        }

        foreach ($badges_by_type as $type => $badges_list) {
            // ORDINAMENTO
            // Per 'numero_multe' il migliore è quello con target più BASSO (0 è meglio di 5).
            // Per gli altri, il migliore è quello con target più ALTO (100 è meglio di 1).

            usort($badges_list, function($a, $b) use ($type) {
                if ($type === 'numero_multe') {
                    // Decrescente per multe (es. 5, 3, 1, 0)
                    // Così iterando troviamo prima i "facili" (5) e poi i "difficili" (0)
                    // E sovrascriviamo $highest_unlocked man mano che troviamo quelli soddisfatti.
                    return $b['target_numerico'] - $a['target_numerico'];
                } else {
                    // Crescente per altri (es. 1, 10, 50, 100)
                    // Check 1: OK. Highest = Bronzo.
                    // Check 100: OK. Highest = Platino.
                    return $a['target_numerico'] - $b['target_numerico'];
                }
            });

            $highest_unlocked = null;
            $next_badge = null;

            foreach ($badges_list as $b) {
                $is_unlocked_db = in_array($b['id_badge'], $unlocked_badges_ids);
                $is_unlocked_dynamic = false;

                // Calcolo dinamico
                if (isset($user_stats[$type])) {
                    $currentVal = $user_stats[$type];
                    $target = intval($b['target_numerico']);

                    if ($type === 'numero_multe') {
                        // Sbloccato se ho MENO o UGUALI multe del target
                        if ($currentVal <= $target) {
                            $is_unlocked_dynamic = true;
                        }
                    } else {
                        // Sbloccato se ho PIÙ o UGUALI azioni del target
                        if ($currentVal >= $target) {
                            $is_unlocked_dynamic = true;
                        }
                    }
                }

                if ($is_unlocked_db || $is_unlocked_dynamic) {
                    $highest_unlocked = $b;
                    $highest_unlocked['is_unlocked'] = true;
                } else {
                    // Il primo che trovo NON sbloccato è il mio prossimo obiettivo
                    if ($next_badge === null) {
                        $next_badge = $b;
                    }
                }
            }

            // Costruiamo l'oggetto da visualizzare
            $display_item = [];

            if ($highest_unlocked) {
                $display_item = $highest_unlocked;
                if ($next_badge) {
                    $display_item['next_badge'] = $next_badge;
                }
            } else {
                // Nessuno sbloccato. Mostriamo il primo della lista (il più facile)
                if (!empty($badges_list)) {
                    $first_badge = $badges_list[0];
                    $first_badge['is_unlocked'] = false;
                    $display_item = $first_badge;
                    $display_item['next_badge'] = $first_badge;
                }
            }

            if (!empty($display_item)) {
                $badges_to_display[] = $display_item;
            }
        }

    } catch (PDOException $e) {
        $messaggio_alert = "Errore caricamento badge: " . $e->getMessage();
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