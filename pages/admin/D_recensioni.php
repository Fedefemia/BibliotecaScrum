<?php
require_once 'security.php';

if (!checkAccess('amministratore') && !checkAccess('bibliotecario')) {
    header('Location: ./');
    exit;
}

require_once 'db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$messaggio_db = "";
$class_messaggio = "";

// Paginazione e filtri
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = $_GET['search'] ?? '';
$filterVoto = $_GET['voto'] ?? '';
$filterData = $_GET['data'] ?? '';
$orderBy = $_GET['order'] ?? 'recenti'; // recenti, vecchi, voto_alto, voto_basso

// --- GESTIONE AZIONI POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ELIMINA RECENSIONE
    if (isset($_POST['delete_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM recensioni WHERE id_recensione = ?");
            $stmt->execute([$_POST['delete_id']]);
            $_SESSION['messaggio'] = "Recensione eliminata con successo!";
            $_SESSION['tipo_messaggio'] = "success";
        } catch (PDOException $e) {
            $_SESSION['messaggio'] = "Errore eliminazione: " . $e->getMessage();
            $_SESSION['tipo_messaggio'] = "error";
        }
        header("Location: dashboard-recensioni?page=$page&search=" . urlencode($search) . "&voto=$filterVoto&data=$filterData&order=$orderBy");
        exit;
    }

    // MODIFICA RECENSIONE (censura commento)
    if (isset($_POST['edit_id'])) {
        try {
            $nuovoCommento = trim($_POST['commento']);
            $stmt = $pdo->prepare("UPDATE recensioni SET commento = ? WHERE id_recensione = ?");
            $stmt->execute([$nuovoCommento, $_POST['edit_id']]);
            $_SESSION['messaggio'] = "Recensione modificata con successo!";
            $_SESSION['tipo_messaggio'] = "success";
        } catch (PDOException $e) {
            $_SESSION['messaggio'] = "Errore modifica: " . $e->getMessage();
            $_SESSION['tipo_messaggio'] = "error";
        }
        header("Location: dashboard-recensioni?page=$page&search=" . urlencode($search) . "&voto=$filterVoto&data=$filterData&order=$orderBy");
        exit;
    }
}

// Recupera messaggi dalla sessione
if (isset($_SESSION['messaggio'])) {
    $messaggio_db = $_SESSION['messaggio'];
    $class_messaggio = $_SESSION['tipo_messaggio'];
    unset($_SESSION['messaggio']);
    unset($_SESSION['tipo_messaggio']);
}

// --- STATISTICHE RAPIDE ---
$stats = [
    'totale' => 0,
    'media_voti' => 0,
    'oggi' => 0,
    'settimana' => 0,
    'mese' => 0
];

try {
    // Totale recensioni e media voti
    $stmt = $pdo->query("
        SELECT COUNT(*) as totale, 
               CAST(AVG(voto) AS DECIMAL(3,1)) as media 
        FROM recensioni
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['totale'] = $result['totale'];
    $stats['media_voti'] = $result['media'] ?? 0;

    // Recensioni oggi
    $stmt = $pdo->query("
        SELECT COUNT(*) as oggi 
        FROM recensioni 
        WHERE DATE(data_commento) = CURDATE()
    ");
    $stats['oggi'] = $stmt->fetchColumn();

    // Recensioni ultima settimana
    $stmt = $pdo->query("
        SELECT COUNT(*) as settimana 
        FROM recensioni 
        WHERE data_commento >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stats['settimana'] = $stmt->fetchColumn();

    // Recensioni ultimo mese
    $stmt = $pdo->query("
        SELECT COUNT(*) as mese 
        FROM recensioni 
        WHERE data_commento >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats['mese'] = $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Errore statistiche: " . $e->getMessage());
}

// --- QUERY RECENSIONI CON FILTRI ---
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(l.titolo LIKE ? OR u.username LIKE ? OR u.nome LIKE ? OR u.cognome LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($filterVoto)) {
    $where[] = "r.voto = ?";
    $params[] = $filterVoto;
}

if (!empty($filterData)) {
    if ($filterData === 'oggi') {
        $where[] = "DATE(r.data_commento) = CURDATE()";
    } elseif ($filterData === 'settimana') {
        $where[] = "r.data_commento >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($filterData === 'mese') {
        $where[] = "r.data_commento >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// Ordinamento
$orderSQL = "ORDER BY r.data_commento DESC"; // default
switch ($orderBy) {
    case 'vecchi':
        $orderSQL = "ORDER BY r.data_commento ASC";
        break;
    case 'voto_alto':
        $orderSQL = "ORDER BY r.voto DESC, r.data_commento DESC";
        break;
    case 'voto_basso':
        $orderSQL = "ORDER BY r.voto ASC, r.data_commento DESC";
        break;
    case 'like':
        $orderSQL = "ORDER BY r.like_count DESC, r.data_commento DESC";
        break;
}

try {
    // Count totale per paginazione
    $countSQL = "
        SELECT COUNT(*) 
        FROM recensioni r
        JOIN libri l ON r.isbn = l.isbn
        JOIN utenti u ON r.codice_alfanumerico = u.codice_alfanumerico
        $whereSQL
    ";
    $stmtCount = $pdo->prepare($countSQL);
    $stmtCount->execute($params);
    $totalRecensioni = $stmtCount->fetchColumn();
    $totalPages = ceil($totalRecensioni / $perPage);

    // Query principale
    $sql = "
        SELECT r.id_recensione, r.voto, r.commento, r.data_commento, 
               r.like_count, r.dislike_count, r.isbn,
               l.titolo as libro_titolo,
               u.username, u.nome, u.cognome, u.codice_alfanumerico,
               (SELECT COUNT(*) 
                FROM prestiti p 
                JOIN copie c ON p.id_copia = c.id_copia 
                WHERE p.codice_alfanumerico = r.codice_alfanumerico 
                AND c.isbn = r.isbn 
                LIMIT 1) as ha_letto
        FROM recensioni r
        JOIN libri l ON r.isbn = l.isbn
        JOIN utenti u ON r.codice_alfanumerico = u.codice_alfanumerico
        $whereSQL
        $orderSQL
        LIMIT $perPage OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recensioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $messaggio_db = "Errore caricamento recensioni: " . $e->getMessage();
    $class_messaggio = "error";
    $recensioni = [];
    $totalRecensioni = 0;
    $totalPages = 1;
}

// Variabili per edit mode
$edit_id = $_GET['edit'] ?? null;

$title = "Dashboard Recensioni";
$path = "../";
$page_css = "../public/css/style_dashboards.css";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <style>
        /* Statistiche Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: transparent;
            color: #2c2c2c;
            border: solid 3px #2c2c2c;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-value { font-family: "Young Serif", serif; font-size: 1.5rem; font-weight: bold; margin: 5px 0; }
        .stat-label { font-family: "Young Serif", serif; font-size: 0.9rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }

        /* Filtri */
        .filters-wrapper {
            background: #faf9f6;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #eae3d2;
        }

        .filters-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
            font-size: 0.9rem;
        }

        .filter-input, .filter-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: "Instrument Sans", sans-serif;
            font-size: 0.95rem;
        }

        .btn-filter {
            background-color: #3f5135;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-filter:hover { background-color: #2c3a24; }

        .btn-reset {
            background-color: #ccc;
            color: #333;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        /* Tabella */
        .table-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .page-title { font-family: "Young Serif", serif; font-size: 2.5rem; color: #333; margin: 1em 0; }

        .reviews-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reviews-table thead {
            background-color: #eae3d2;
        }

        .reviews-table th {
            padding: 15px;
            text-align: left;
            font-family: "Young Serif", serif;
            font-size: 1rem;
            color: #2c2c2c;
            font-weight: normal;
        }

        .reviews-table td {
            padding: 15px;
            border-bottom: 1px solid #f5f5f5;
            vertical-align: top;
            font-family: "Instrument Sans", sans-serif;
        }

        .reviews-table tr:hover {
            background-color: #fafafa;
        }

        .book-title {
            font-weight: 600;
            color: #333;
            display: block;
            margin-bottom: 3px;
        }

        .book-link {
            color: #3498db;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .book-link:hover {
            text-decoration: underline;
        }

        .user-info {
            font-weight: 600;
            color: #333;
        }

        .stars {
            color: #f39c12;
            font-size: 1.2rem;
            letter-spacing: 2px;
        }

        .comment-text {
            max-width: 300px;
            line-height: 1.4;
            color: #555;
            font-size: 0.9rem;
        }

        .comment-text.full {
            max-width: none;
        }

        .badge-verified {
            display: inline-block;
            background-color: #d4edda;
            color: #155724;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 5px;
        }

        .likes-info {
            display: flex;
            gap: 10px;
            font-size: 0.9rem;
        }

        .like { color: #27ae60; }
        .dislike { color: #e74c3c; }

        .actions-cell {
            white-space: nowrap;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            margin: 2px;
            font-family: "Instrument Sans", sans-serif;
        }

        .btn-edit {
            background-color: #3498db;
            color: white;
        }

        .btn-edit:hover {
            background-color: #2980b9;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }

        .btn-save {
            background-color: #27ae60;
            color: white;
        }

        .btn-cancel {
            background-color: #95a5a6;
            color: white;
        }

        .edit-textarea {
            width: 100%;
            min-height: 80px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: "Instrument Sans", sans-serif;
            resize: vertical;
        }

        /* Paginazione */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 25px;
            font-family: "Instrument Sans", sans-serif;
        }

        .page-link {
            padding: 10px 15px;
            background: #fff;
            border: 1px solid #eae3d2;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-weight: 600;
        }

        .page-link.active {
            background: #3f5135;
            color: #fff;
            border-color: #3f5135;
        }

        .page-link:hover:not(.active) {
            background: #f0f0f0;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .filters-row {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-wrapper {
                overflow-x: auto;
            }

            .reviews-table {
                min-width: 900px;
            }
        }
    </style>

    <div class="page_contents">

        <h2 class="page-title">
            Dashboard Recensioni
        </h2>

        <?php if (!empty($messaggio_db)): ?>
            <div class="alert <?= $class_messaggio === 'error' ? 'alert-error' : 'alert-success' ?>">
                <?= htmlspecialchars($messaggio_db) ?>
            </div>
        <?php endif; ?>

        <!-- Statistiche -->
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-label">Totale Recensioni</div>
                <div class="stat-value"><?= number_format($stats['totale']) ?></div>
            </div>
            <div class="stat-card green">
                <div class="stat-label">Media Voti</div>
                <div class="stat-value"><?= number_format($stats['media_voti'], 1) ?> ★</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-label">Oggi</div>
                <div class="stat-value"><?= $stats['oggi'] ?></div>
            </div>
            <div class="stat-card orange">
                <div class="stat-label">Ultimo Mese</div>
                <div class="stat-value"><?= $stats['mese'] ?></div>
            </div>
        </div>

        <!-- Filtri -->
        <div class="filters-wrapper">
            <form method="GET" action="dashboard-recensioni">
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Cerca (Libro o Utente)</label>
                        <input type="text" name="search" class="filter-input"
                               placeholder="Titolo libro o username..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="filter-group" style="flex: 0 0 120px;">
                        <label class="filter-label">Voto</label>
                        <select name="voto" class="filter-select">
                            <option value="">Tutti</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= $filterVoto == $i ? 'selected' : '' ?>>
                                    <?= $i ?> ★
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group" style="flex: 0 0 150px;">
                        <label class="filter-label">Periodo</label>
                        <select name="data" class="filter-select">
                            <option value="">Tutti</option>
                            <option value="oggi" <?= $filterData === 'oggi' ? 'selected' : '' ?>>Oggi</option>
                            <option value="settimana" <?= $filterData === 'settimana' ? 'selected' : '' ?>>Ultima settimana</option>
                            <option value="mese" <?= $filterData === 'mese' ? 'selected' : '' ?>>Ultimo mese</option>
                        </select>
                    </div>

                    <div class="filter-group" style="flex: 0 0 150px;">
                        <label class="filter-label">Ordina per</label>
                        <select name="order" class="filter-select">
                            <option value="recenti" <?= $orderBy === 'recenti' ? 'selected' : '' ?>>Più recenti</option>
                            <option value="vecchi" <?= $orderBy === 'vecchi' ? 'selected' : '' ?>>Più vecchi</option>
                            <option value="voto_alto" <?= $orderBy === 'voto_alto' ? 'selected' : '' ?>>Voto alto</option>
                            <option value="voto_basso" <?= $orderBy === 'voto_basso' ? 'selected' : '' ?>>Voto basso</option>
                            <option value="like" <?= $orderBy === 'like' ? 'selected' : '' ?>>Più apprezzati</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: flex-end;">
                        <button type="submit" class="btn-filter">Filtra</button>
                        <a href="dashboard-recensioni" class="btn-reset">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Risultati -->
        <p style="margin-bottom: 15px; color: #666; font-family: 'Instrument Sans', sans-serif;">
            Trovate <strong><?= number_format($totalRecensioni) ?></strong> recensioni
        </p>

        <?php if (empty($recensioni)): ?>
            <div class="alert" style="background: #e9ecef; color: #333; text-align: center; padding: 40px;">
                <p style="margin: 0; font-size: 1.1rem;">📝 Nessuna recensione trovata</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="reviews-table">
                    <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Libro</th>
                        <th>Utente</th>
                        <th style="width: 80px;">Voto</th>
                        <th>Commento</th>
                        <th style="width: 100px;">Data</th>
                        <th style="width: 100px;">Like/Dislike</th>
                        <th style="width: 150px;">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recensioni as $r):
                        $is_editing = ($edit_id == $r['id_recensione']);
                        ?>
                        <tr>
                            <td><?= $r['id_recensione'] ?></td>

                            <td>
                                <span class="book-title"><?= htmlspecialchars($r['libro_titolo']) ?></span>
                                <a href="../libro?isbn=<?= $r['isbn'] ?>" class="book-link" target="_blank">
                                    Vedi libro →
                                </a>
                            </td>

                            <td>
                                <span class="user-info"><?= htmlspecialchars($r['username']) ?></span>
                                <br>
                                <small style="color: #888;">
                                    <?= htmlspecialchars($r['nome'] . ' ' . $r['cognome']) ?>
                                </small>
                                <?php if ($r['ha_letto']): ?>
                                    <span class="badge-verified">✓ Letto</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="stars">
                                    <?= str_repeat('★', $r['voto']) ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($is_editing): ?>
                                    <form method="POST" action="dashboard-recensioni?page=<?= $page ?>&search=<?= urlencode($search) ?>&voto=<?= $filterVoto ?>&data=<?= $filterData ?>&order=<?= $orderBy ?>">
                                        <input type="hidden" name="edit_id" value="<?= $r['id_recensione'] ?>">
                                        <textarea name="commento" class="edit-textarea" required><?= htmlspecialchars($r['commento']) ?></textarea>
                                        <div style="margin-top: 8px; display: flex; gap: 5px;">
                                            <button type="submit" class="btn-action btn-save">Salva</button>
                                            <a href="dashboard-recensioni?page=<?= $page ?>&search=<?= urlencode($search) ?>&voto=<?= $filterVoto ?>&data=<?= $filterData ?>&order=<?= $orderBy ?>"
                                               class="btn-action btn-cancel" style="text-decoration: none; display: inline-block;">
                                                Annulla
                                            </a>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="comment-text">
                                        <?= nl2br(htmlspecialchars($r['commento'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= date('d/m/Y', strtotime($r['data_commento'])) ?>
                                <br>
                                <small style="color: #888;">
                                    <?= date('H:i', strtotime($r['data_commento'])) ?>
                                </small>
                            </td>

                            <td>
                                <div class="likes-info">
                                    <span class="like">👍 <?= $r['like_count'] ?></span>
                                    <span class="dislike">👎 <?= $r['dislike_count'] ?></span>
                                </div>
                            </td>

                            <td class="actions-cell">
                                <?php if ($is_editing): ?>
                                    <em style="color: #888; font-size: 0.85rem;">Modifica in corso...</em>
                                <?php else: ?>
                                    <a href="?page=<?= $page ?>&search=<?= urlencode($search) ?>&voto=<?= $filterVoto ?>&data=<?= $filterData ?>&order=<?= $orderBy ?>&edit=<?= $r['id_recensione'] ?>"
                                       class="btn-action btn-edit">
                                        Modifica
                                    </a>
                                    <form method="POST" style="display: inline;"
                                          onsubmit="return confirm('Eliminare questa recensione?\n\nUtente: <?= addslashes($r['username']) ?>\nLibro: <?= addslashes($r['libro_titolo']) ?>')">
                                        <input type="hidden" name="delete_id" value="<?= $r['id_recensione'] ?>">
                                        <button type="submit" class="btn-action btn-delete">Elimina</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginazione -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&voto=<?= $filterVoto ?>&data=<?= $filterData ?>&order=<?= $orderBy ?>"
                           class="page-link">&laquo; Precedente</a>
                    <?php endif; ?>

                    <span class="page-link active">Pagina <?= $page ?> di <?= $totalPages ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&voto=<?= $filterVoto ?>&data=<?= $filterData ?>&order=<?= $orderBy ?>"
                           class="page-link">Successiva &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

<?php require_once './src/includes/footer.php'; ?>