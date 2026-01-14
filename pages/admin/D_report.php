<?php
require_once 'security.php';
if (!checkAccess('amministratore') ) {
    header('Location: ../index.php');
    exit;
}
require_once 'db_config.php';

// Inizializzazione dati
$kpi = ['totale_titoli' => 0, 'copie_fisiche' => 0, 'prestiti_attivi' => 0, 'prestiti_scaduti' => 0, 'scadenza_oggi' => 0, 'multe_totali' => 0, 'utenti_totali' => 0];
$trendPrestiti = []; $topLibri = []; $distribuzioneCat = []; $ruoliLabels = []; $ruoliValori = []; $statoCopie = ['Disponibili' => 0, 'In_Prestito' => 0]; $catStoricoPrestiti = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // 1. KPI Generali: Divisione Titoli/Copie
        $stmtKpi = $pdo->query("SELECT 
            (SELECT COUNT(*) FROM libri) as totale_titoli, 
            (SELECT COUNT(*) FROM copie) as copie_fisiche, 
            (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as prestiti_attivi, 
            (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL AND data_scadenza < CURDATE()) as prestiti_scaduti, 
            (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL AND data_scadenza = CURDATE()) as scadenza_oggi, 
            (SELECT COUNT(*) FROM multe WHERE pagata = 0) as multe_totali, 
            (SELECT COUNT(*) FROM utenti) as utenti_totali");
        $kpi = $stmtKpi->fetch(PDO::FETCH_ASSOC);

        // 2. Categorie più prestate (Storico per grafico torta)
        $catStoricoPrestiti = $pdo->query("SELECT c.categoria, COUNT(p.id_prestito) as conteggio 
            FROM categorie c 
            JOIN libro_categoria lc ON c.id_categoria = lc.id_categoria 
            JOIN copie cp ON lc.isbn = cp.isbn 
            JOIN prestiti p ON cp.id_copia = p.id_copia 
            GROUP BY c.id_categoria 
            ORDER BY conteggio DESC 
            LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

        // 3. Distribuzione Catalogo (Cosa abbiamo in totale)
        $distribuzioneCat = $pdo->query("SELECT c.categoria, COUNT(lc.isbn) as conteggio FROM categorie c JOIN libro_categoria lc ON c.id_categoria = lc.id_categoria GROUP BY c.id_categoria")->fetchAll(PDO::FETCH_ASSOC);

        // 4. Ruoli Utenti
        $distRuoli = $pdo->query("SELECT SUM(studente) as Studenti, SUM(docente) as Docenti, SUM(bibliotecario) as Bibliotecari, SUM(amministratore) as Admin FROM ruoli")->fetch(PDO::FETCH_ASSOC);
        $ruoliLabels = array_keys($distRuoli); $ruoliValori = array_values($distRuoli);

        // 5. Scadenze Imminenti
        $scadenzeProssime = $pdo->query("SELECT p.data_scadenza, l.titolo, u.email FROM prestiti p JOIN copie c ON p.id_copia = c.id_copia JOIN libri l ON c.isbn = l.isbn JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico WHERE p.data_restituzione IS NULL AND (p.data_scadenza = CURDATE() OR p.data_scadenza = DATE_ADD(CURDATE(), INTERVAL 1 DAY)) ORDER BY p.data_scadenza ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

        // 6. Trend e Classifiche
        $topUtenti = $pdo->query("SELECT u.nome, u.cognome, COUNT(p.id_prestito) as tot FROM utenti u JOIN prestiti p ON u.codice_alfanumerico = p.codice_alfanumerico GROUP BY u.codice_alfanumerico ORDER BY tot DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        $topLibri = $pdo->query("SELECT l.titolo, COUNT(p.id_prestito) as n_prestiti FROM libri l JOIN copie c ON l.isbn = c.isbn JOIN prestiti p ON c.id_copia = p.id_copia GROUP BY l.isbn ORDER BY n_prestiti DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        $statoCopie = $pdo->query("SELECT (SELECT COUNT(*) FROM copie) - (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as Disponibili, (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as In_Prestito")->fetch(PDO::FETCH_ASSOC);
        $trendPrestiti = $pdo->query("SELECT DATE_FORMAT(data_prestito, '%m/%Y') as mese, COUNT(*) as totale FROM prestiti WHERE data_prestito >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY LAST_DAY(data_prestito) ORDER BY LAST_DAY(data_prestito) ASC")->fetchAll(PDO::FETCH_ASSOC);
        $trendUtenti = $pdo->query("SELECT DATE_FORMAT(data_creazione, '%d/%m') as giorno, COUNT(*) as nuovi FROM utenti WHERE data_creazione >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY data_creazione ORDER BY data_creazione ASC")->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) { error_log($e->getMessage()); }
}

$title = "Dashboard Analitica";
$path = "../";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.04); }
        .kpi-card { border-left: 4px solid; transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-5px); }
        .nav-pills .nav-link { color: #555; font-weight: 500; border-radius: 8px; margin-right: 8px; }
        .nav-pills .nav-link.active { background-color: #4e73df; color: white; }
        .chart-container { position: relative; height: 260px; width: 100%; }
        .text-xs { font-size: 0.7rem; font-weight: 700; text-uppercase: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div>
            <h2 class="h3 fw-bold text-dark m-0">Dashboard Amministrativa</h2>
            <p class="text-muted small m-0">Statistiche e monitoraggio del patrimonio librario</p>
        </div>
        <button class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>Stampa Report
        </button>
    </div>

    <div class="row g-3 mb-4">
        <?php
        $metrics = [
                ['label' => 'Titoli (Opere)', 'val' => 'totale_titoli', 'color' => '#4e73df', 'icon' => 'bi-journal-text'],
                ['label' => 'Copie (Fisiche)', 'val' => 'copie_fisiche', 'color' => '#6610f2', 'icon' => 'bi-layers'],
                ['label' => 'Prestiti Attivi', 'val' => 'prestiti_attivi', 'color' => '#1cc88a', 'icon' => 'bi-arrow-repeat'],
                ['label' => 'Scadenze Oggi', 'val' => 'scadenza_oggi', 'color' => '#f6c23e', 'icon' => 'bi-bell'],
                ['label' => 'Ritardi', 'val' => 'prestiti_scaduti', 'color' => '#e74a3b', 'icon' => 'bi-exclamation-triangle'],
                ['label' => 'Utenti', 'val' => 'utenti_totali', 'color' => '#36b9cc', 'icon' => 'bi-people'],
                ['label' => 'Multe', 'val' => 'multe_totali', 'color' => '#5a5c69', 'icon' => 'bi-cash']
        ];
        foreach($metrics as $m): ?>
            <div class="col-6 col-md-4 col-xl">
                <div class="card kpi-card h-100" style="border-color: <?= $m['color'] ?>;">
                    <div class="card-body py-3">
                        <div class="text-xs mb-1" style="color: <?= $m['color'] ?>;"><?= $m['label'] ?></div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="h4 mb-0 fw-bold"><?= $kpi[$m['val']] ?></span>
                            <i class="bi <?= $m['icon'] ?> opacity-25 fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <ul class="nav nav-pills bg-white p-2 rounded shadow-sm mb-4" id="dashTabs">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-attivita">Attività Prestiti</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-utenza">Utenza</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-patrimonio">Patrimonio</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-attivita">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card p-4 h-100">
                        <h6 class="fw-bold mb-4">Volume Prestiti (Ultimi 12 mesi)</h6>
                        <div class="chart-container"><canvas id="linePrestiti"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card p-4 h-100">
                        <h6 class="fw-bold mb-4 text-center">Categorie più prestate</h6>
                        <div class="chart-container"><canvas id="piePrestitiCat"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card p-4 h-100 border-top border-warning border-4">
                        <h6 class="fw-bold text-warning mb-3 small text-uppercase">Scadenze Imminenti</h6>
                        <div class="list-group list-group-flush small">
                            <?php foreach($scadenzeProssime as $s): ?>
                                <div class="list-group-item px-0 border-light d-flex justify-content-between align-items-center">
                                    <div class="text-truncate" style="max-width: 70%;">
                                        <div class="fw-bold text-dark text-truncate"><?= htmlspecialchars($s['titolo']) ?></div>
                                        <div class="text-muted x-small"><?= htmlspecialchars($s['email']) ?></div>
                                    </div>
                                    <span class="badge bg-light text-dark border"><?= date('d/m', strtotime($s['data_scadenza'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-utenza">
            <div class="row g-4">
                <div class="col-md-4"><div class="card p-4"><h6>Ruoli Sistema</h6><div class="chart-container"><canvas id="barRuoli"></canvas></div></div></div>
                <div class="col-md-4"><div class="card p-4"><h6>Nuovi Iscritti (30gg)</h6><div class="chart-container"><canvas id="areaUtenti"></canvas></div></div></div>
                <div class="col-md-4">
                    <div class="card p-4"><h6>Top Lettori</h6>
                        <table class="table table-sm small">
                            <tbody><?php foreach($topUtenti as $u): ?><tr><td><?= $u['nome'].' '.$u['cognome'] ?></td><td class="text-end fw-bold"><?= $u['tot'] ?></td></tr><?php endforeach; ?></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-patrimonio">
            <div class="row g-4">
                <div class="col-md-4"><div class="card p-4"><h6>Composizione Catalogo</h6><div class="chart-container"><canvas id="pieCat"></canvas></div></div></div>
                <div class="col-md-4"><div class="card p-4"><h6>Stato Fisico Copie</h6><div class="chart-container"><canvas id="pieDispo"></canvas></div></div></div>
                <div class="col-md-4"><div class="card p-4"><h6>I 10 Libri più richiesti</h6><div class="chart-container"><canvas id="barLibri"></canvas></div></div></div>
            </div>
        </div>
    </div>
</div>

<script>
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#858796';

    // Refresh grafici nei tab
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', () => window.dispatchEvent(new Event('resize')));
    });

    // 1. Linea Prestiti
    new Chart(document.getElementById('linePrestiti'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($trendPrestiti, 'mese')) ?>,
            datasets: [{ label: 'Prestiti', data: <?= json_encode(array_column($trendPrestiti, 'totale')) ?>, borderColor: '#4e73df', backgroundColor: 'rgba(78,115,223,0.05)', fill: true, tension: 0.3 }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // 2. NUOVO: Categorie più prestate (Doughnut)
    new Chart(document.getElementById('piePrestitiCat'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($catStoricoPrestiti, 'categoria')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($catStoricoPrestiti, 'conteggio')) ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796']
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
        }
    });

    // 3. Ruoli
    new Chart(document.getElementById('barRuoli'), {
        type: 'bar',
        data: { labels: <?= json_encode($ruoliLabels) ?>, datasets: [{ data: <?= json_encode($ruoliValori) ?>, backgroundColor: '#4e73df' }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // 4. Categorie Catalogo
    new Chart(document.getElementById('pieCat'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($distribuzioneCat, 'categoria')) ?>,
            datasets: [{ data: <?= json_encode(array_column($distribuzioneCat, 'conteggio')) ?>, backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'] }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // 5. Disponibilità
    new Chart(document.getElementById('pieDispo'), {
        type: 'doughnut',
        data: {
            labels: ['Disponibili', 'In Prestito'],
            datasets: [{ data: [<?= $statoCopie['Disponibili'] ?>, <?= $statoCopie['In_Prestito'] ?>], backgroundColor: ['#1cc88a', '#f6c23e'] }]
        },
        options: { maintainAspectRatio: false }
    });

    // 6. Nuovi Utenti
    new Chart(document.getElementById('areaUtenti'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($trendUtenti, 'giorno')) ?>,
            datasets: [{ data: <?= json_encode(array_column($trendUtenti, 'nuovi')) ?>, borderColor: '#6f42c1', tension: 0.4 }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // 7. Top Libri
    new Chart(document.getElementById('barLibri'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(fn($l) => strlen($l['titolo']) > 15 ? substr($l['titolo'], 0, 15).'...' : $l['titolo'], $topLibri)) ?>,
            datasets: [{ data: <?= json_encode(array_column($topLibri, 'n_prestiti')) ?>, backgroundColor: '#36b9cc' }]
        },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>