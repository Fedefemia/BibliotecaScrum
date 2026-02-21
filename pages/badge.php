<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';

$messaggio_db = "";
$server_message = "";
$badge = null;
$possessori = [];
$mia_assegnazione = null;

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$uid = $_SESSION['codice_utente'] ?? null;
$query_uid = $uid ? $uid : 'GUEST';

if (!$id) {
    $messaggio_db = "Badge non specificato";
}

// Gestione azioni POST (solo se è presente un id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $action = $_POST['action'] ?? null;

    // Logica se appena acquisito (claim)
    if ($action === 'claim') {
        if (!$uid) {
            header("Location: ./badge?id={$id}&msg=login_needed");
            exit;
        }
        try {
            // Verifica se esiste già
            $chk = $pdo->prepare("SELECT livello FROM utente_badge WHERE id_badge = ? AND codice_alfanumerico = ?");
            $chk->execute([$id, $uid]);
            $exists = $chk->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                header("Location: ./badge?id={$id}&msg=already_has");
                exit;
            } else {
                $ins = $pdo->prepare("INSERT INTO utente_badge (id_badge, codice_alfanumerico, livello) VALUES (?, ?, ?)");
                $ins->execute([$id, $uid, 1]);
                header("Location: ./badge?id={$id}&msg=claimed");
                exit;
            }
        } catch (PDOException $e) {
            $messaggio_db = "Errore DB: " . $e->getMessage();
        }
    }
}

// Messaggi server via GET (coerente con libro.php)
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'login_needed': $server_message = "Devi accedere per eseguire l'operazione."; break;
        case 'claimed': $server_message = "Segnalazione acquisizione inviata!"; break;
        case 'already_has': $server_message = "Hai già questo badge."; break;
        case 'error': $server_message = "Errore di sistema."; break;
    }
}

if (!$messaggio_db) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM badge WHERE id_badge = ?");
        $stmt->execute([$id]);
        $badge = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$badge) {
            $messaggio_db = "Badge non trovato.";
        } else {
            // Possessori (mostra fino a 50)
            $stmt2 = $pdo->prepare("
                SELECT ub.livello, u.nome, u.cognome, u.codice_alfanumerico
                FROM utente_badge ub
                JOIN utenti u ON ub.codice_alfanumerico = u.codice_alfanumerico
                WHERE ub.id_badge = ?
                ORDER BY ub.livello DESC, u.cognome ASC
                LIMIT 50
            ");
            $stmt2->execute([$id]);
            $possessori = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            if ($uid) {
                $stm3 = $pdo->prepare("SELECT livello FROM utente_badge WHERE id_badge = ? AND codice_alfanumerico = ?");
                $stm3->execute([$id, $uid]);
                $mia_assegnazione = $stm3->fetch(PDO::FETCH_ASSOC);
            }
        }

    } catch (PDOException $e) {
        $messaggio_db = "Errore database: " . $e->getMessage();
    }
}

function badgeIconHtml($badge) {
    $icon = $badge['icona'] ?? null;
    $path = __DIR__ . "/../public/badge/";
    $webPath = "./public/badge/";
    if ($icon) {
        // uso la classe già presente per le cover dei libri così erediti gli stili esistenti
        return '<img src="' . htmlspecialchars($webPath) . '" alt="' . htmlspecialchars($badge['nome']) . '" class="book_hero_cover">';
    }
    // fallback lettera (usando classe cover-like per mantenere dimensione)
    $letter = strtoupper(substr($badge['nome'] ?? 'B', 0, 1));
    return '<div class="book_cover" style="display:flex;align-items:center;justify-content:center;background:#f3f3f3;font-weight:700;font-size:28px;border-radius:8px;width:120px;height:120px;">' . htmlspecialchars($letter) . '</div>';
}

function progressBarHtml($level, $target) {
    if (!$target || $target <= 0) return '';
    $percent = min(100, round(($level / $target) * 100));
    return '<div class="cond-bar-wrapper" style="margin-top:10px;width:100%;max-width:420px;">'
        . '<div style="flex:1;background:#eee;border-radius:8px;height:12px;overflow:hidden;"><div style="height:12px;background:linear-gradient(90deg,#6dd3a8,#27ae60);width:' . $percent . '%;"></div></div>'
        . '<div style="margin-left:8px;font-size:0.85rem;color:#444;">' . $percent . '%</div>'
        . '</div>';
}

// Header e navbar
$title = "Badge - " . ($badge['nome'] ?? 'Non trovato');
$path = "./";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <div class="page_contents">

        <?php if ($messaggio_db || !$badge): ?>
            <div class="alert_box danger">
                <h1>Ops!</h1>
                <p><?= htmlspecialchars($messaggio_db ?: "Badge non trovato.") ?></p>
                <div style="margin-top:12px;">
                    <a href="./badges" class="btn_send">Torna all'elenco badge</a>
                    <a href="./" class="btn_send" style="margin-left:8px;">Torna alla Home</a>
                </div>
            </div>

        <?php else: ?>

            <?php if ($server_message): ?>
                <div class="alert_box info">
                    <p><?= htmlspecialchars($server_message) ?></p>
                </div>
            <?php endif; ?>

            <div class="sticky_limit_wrapper">
                <div class="sticky_header_wrapper">
                    <div class="book_map_row">
                        <div class="col_libro">
                            <div class="book_hero_card">
                                <div class="book_hero_left">
                                    <?= badgeIconHtml($badge) ?>
                                </div>

                                <div class="book_hero_right">
                                    <h1 class="book_main_title"><?= htmlspecialchars($badge['nome']) ?></h1>

                                    <div class="book_authors">
                                        <span style="font-weight:600;color:#666;">Tipo:</span>
                                        <span style="margin-left:8px; color:#444;"><?= htmlspecialchars($badge['tipo'] ?? '—') ?></span>

                                        <?php if (!empty($badge['data_fine'])): ?>
                                            <span class="badge_avail badge_ko" style="margin-left:12px;">Scadenza: <?= htmlspecialchars($badge['data_fine']) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="meta_info_grid">
                                        <div>
                                            <strong>Target numerico:</strong>
                                            <div><?= intval($badge['target_numerico'] ?: 0) ?></div>
                                        </div>
                                        <div>
                                            <strong>Root:</strong>
                                            <div><?= intval($badge['root'] ?? 0) ?></div>
                                        </div>
                                        <div>
                                            <strong>ID:</strong>
                                            <div><?= intval($badge['id_badge']) ?></div>
                                        </div>
                                    </div>

                                    <div class="book_tags">
                                        <div class="book_desc_box">
                                            <h3 class="book_desc_title">Descrizione</h3>
                                            <div class="book_desc_text"><?= nl2br(htmlspecialchars($badge['descrizione'] ?? 'Nessuna descrizione.')) ?></div>
                                        </div>
                                    </div>

                                    <div style="margin-top:18px;">
                                        <?php if ($uid): ?>
                                            <?php if ($mia_assegnazione): ?>
                                                <div style="font-weight:700;color:#27ae60;">Hai già questo badge — Livello <?= intval($mia_assegnazione['livello']) ?></div>
                                                <?= ($badge['target_numerico'] > 0) ? progressBarHtml(intval($mia_assegnazione['livello']), intval($badge['target_numerico'])) : '' ?>
                                            <?php else: ?>
                                                <form method="POST" style="margin-top:8px;">
                                                    <input type="hidden" name="action" value="claim">
                                                    <button type="submit" class="btn_prenota">Segnala acquisizione</button>
                                                </form>
                                                <div style="color:#666;margin-top:8px;">La segnalazione sarà valutata dagli amministratori.</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="./login" class="btn_prenota">Accedi per segnalare</a>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="info_column extend_all">
                            <div class="section">
                                <h2>Utenti con questo badge</h2>
                                <div class="grid">
                                    <?php if ($possessori): ?>
                                        <?php foreach ($possessori as $p): ?>
                                            <div class="book-item">
                                                <div style="font-weight:700;"><?= htmlspecialchars($p['nome'] . ' ' . $p['cognome']) ?></div>
                                                <div style="color:#888;font-size:0.9rem;">Lv <?= intval($p['livello']) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($possessori) >= 50): ?>
                                            <div style="color:#666;margin-top:8px;">Elenco limitato a 50 risultati.</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <h4 style="color:#888;">Nessun utente ha ancora questo badge</h4>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="section">
                                <h2>Informazioni</h2>
                                <p style="color:#666;">ID badge: <?= intval($badge['id_badge']) ?></p>
                                <p style="color:#666;">Tipo: <?= htmlspecialchars($badge['tipo'] ?? '—') ?></p>
                                <p style="color:#666;">Target numerico: <?= intval($badge['target_numerico'] ?? 0) ?></p>
                            </div>

                            <div class="section">
                                <h2>Biblioteche</h2>
                                <div>
                                    <?php if ($lista_biblioteche): ?>
                                        <ul>
                                            <?php foreach ($lista_biblioteche as $b): ?>
                                                <li><?= htmlspecialchars($b['nome']) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div style="color:#888;">Nessuna biblioteca registrata</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="page_actions">
                <a href="./badges" class="btn_send">Torna all'elenco badge</a>
            </div>

        <?php endif; ?>

    </div>

<?php require_once './src/includes/footer.php'; ?>