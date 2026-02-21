<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

$messaggio_db = "";
$badges = [];
$total = 0;

// pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    // count total
    $stmCnt = $pdo->query("SELECT COUNT(*) AS c FROM badge");
    $total = (int) ($stmCnt->fetchColumn() ?: 0);

    // fetch page
    $stm = $pdo->prepare("SELECT * FROM badge ORDER BY root ASC LIMIT :lim OFFSET :off");
    $stm->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stm->bindValue(':off', $offset, PDO::PARAM_INT);
    $stm->execute();
    $badges = $stm->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $messaggio_db = "Errore caricamento badge: " . $e->getMessage();
}

// helper for icon (reuse project classes)
function badgeIconHtmlList(array $badge) {
    $icon = $badge['icona'] ?? '';
    $path = __DIR__ . "/../public/badges/" . $icon;
    $webPath = "./public/badges/" . $icon;
    if ($icon && file_exists($path)) {
        // use small cover class to fit list layout
        return '<img src="' . htmlspecialchars($webPath) . '" alt="' . htmlspecialchars($badge['nome']) . '" class="book_cover">';
    }
    // fallback letter in a div with similar size
    $letter = strtoupper(substr($badge['nome'] ?? 'B', 0, 1));
    return '<div class="book_cover" style="display:flex;align-items:center;justify-content:center;background:#f3f3f3;font-weight:700;font-size:22px;border-radius:6px;">' . htmlspecialchars($letter) . '</div>';
}

// header/navbar (pattern same as other pages)
$title = "Elenco Badge";
$path = "./";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <div class="page_contents">
        <?php if ($messaggio_db): ?>
            <div class="alert_box danger" style="margin-top:20px;">
                <h1>Ops!</h1>
                <p><?= htmlspecialchars($messaggio_db) ?></p>
            </div>
        <?php endif; ?>

        <div class="sticky_limit_wrapper" style="padding-top:6px;">
            <div class="section">
                <div class="section_header" style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <img src="<?= $path ?>public/assets/icone_categorie/Icon_LibriPopolari.png" class="section_icon" alt="icon" style="width:36px;height:36px;">
                        <h2 class="section_title">Badge</h2>
                    </div>
                    <div>
                        <a href="./" class="btn_send" style="text-decoration:none;">Torna alla Home</a>
                    </div>
                </div>

                <?php if (empty($badges)): ?>
                    <div style="margin-top:20px;">
                        <p style="color:#666;">Nessun badge disponibile.</p>
                    </div>
                <?php else: ?>
                    <div class="books_grid" style="margin-top:14px;">
                        <?php foreach ($badges as $b): ?>
                            <div class="book-item" style="width:220px;">
                                <a href="./badge?id=<?= intval($b['id_badge']) ?>" class="card cover-only">
                                    <?= badgeIconHtmlList($b) ?>
                                </a>
                                <div class="book-meta">
                                    <div class="book_main_title" style="font-size:1rem;margin:6px 0 4px 0;">
                                        <a href="./badge?id=<?= intval($b['id_badge']) ?>" style="color:inherit;text-decoration:none;">
                                            <?= htmlspecialchars($b['nome']) ?>
                                        </a>
                                    </div>
                                    <div class="book_authors" style="font-size:0.9rem;color:#666;">
                                        <?php if (!empty($b['tipo'])): ?>
                                            <?= htmlspecialchars($b['tipo']) ?>
                                        <?php else: ?>
                                            &nbsp;
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($b['descrizione'])): ?>
                                        <div class="book_desc_text" style="margin-top:8px;font-size:0.9rem;max-height:40px;overflow:hidden;">
                                            <?= nl2br(htmlspecialchars($b['descrizione'])) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div style="margin-top:8px;font-size:0.85rem;color:#444;">
                                        Target: <?= intval($b['target_numerico'] ?? 0) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- pagination -->
                    <?php
                    $totalPages = max(1, (int) ceil($total / $perPage));
                    ?>
                    <?php if ($totalPages > 1): ?>
                        <div style="margin-top:20px;text-align:center;">
                            <?php if ($page > 1): ?>
                                <a href="./badges?page=<?= $page - 1 ?>" class="btn_send" style="margin-right:8px;text-decoration:none;">&laquo; Precedente</a>
                            <?php endif; ?>
                            <span style="color:#666;">Pagina <?= $page ?> di <?= $totalPages ?></span>
                            <?php if ($page < $totalPages): ?>
                                <a href="./badges?page=<?= $page + 1 ?>" class="btn_send" style="margin-left:8px;text-decoration:none;">Successiva &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>

<?php require_once './src/includes/footer.php'; ?>