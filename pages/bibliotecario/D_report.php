<?php
require_once 'security.php';
if (!checkAccess('amministratore') ) {
    header('Location: ../index.php');
    exit;
}
require_once 'db_config.php';

$sogliaScaduti = 10;
ciao
try {
    // 1. KPI Principali
    $stmtKpi = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM libri) as totale_titoli,
            (SELECT COUNT(*) FROM copie) as copie_fisiche,
            (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as prestiti_attivi,
            (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL AND data_scadenza < CURDATE()) as prestiti_scaduti,
            (SELECT COUNT(*) FROM multe WHERE pagata = 0) as multe_non_pagate,
            (SELECT COUNT(*) FROM utenti) as utenti_totali
    ");
    $kpi = $stmtKpi->fetch(PDO::FETCH_ASSOC);

    // 2. Trend Prestiti (Ultimi 12 mesi)
    $trendPrestiti = $pdo->query("
        SELECT DATE_FORMAT(data_prestito, '%m/%Y') as mese, COUNT(*) as totale
        FROM prestiti 
        WHERE data_prestito >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY LAST_DAY(data_prestito) 
        ORDER BY LAST_DAY(data_prestito) ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Distribuzione Ruoli Utenti (Barre Verticali)
    $ruoliUtenti = $pdo->query("
        SELECT 
            SUM(studente) as studenti, 
            SUM(docente) as docenti, 
            SUM(bibliotecario) as bibliotecari, 
            SUM(amministratore) as amministratori 
        FROM ruoli
    ")->fetch(PDO::FETCH_ASSOC);

    // 4. Top 10 Libri più letti
    $topLibri = $pdo->query("
        SELECT l.titolo, COUNT(p.id_prestito) as n_prestiti 
        FROM libri l 
        JOIN copie c ON l.isbn = c.isbn 
        JOIN prestiti p ON c.id_copia = p.id_copia
        GROUP BY l.isbn 
        ORDER BY n_prestiti DESC LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 5. Distribuzione Prestiti per Categoria (Grafico a Torta/Doughnut)
    $distribuzioneCat = $pdo->query("
        SELECT c.categoria, COUNT(p.id_prestito) as conteggio 
        FROM categorie c 
        JOIN libro_categoria lc ON c.id_categoria = lc.id_categoria
        JOIN copie cp ON lc.isbn = cp.isbn
        JOIN prestiti p ON cp.id_copia = p.id_copia
        GROUP BY c.id_categoria
        ORDER BY conteggio DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 6. Trend Nuovi Utenti (Ultimi 30 giorni)
    $trendUtenti = $pdo->query("
        SELECT DATE_FORMAT(data_creazione, '%d/%m') as giorno, COUNT(*) as nuovi 
        FROM utenti 
        WHERE data_creazione >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY data_creazione 
        ORDER BY data_creazione ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Errore database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analitica Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .card-kpi { border-left: 4px solid; transition: transform 0.2s; }
        .card-kpi:hover { transform: translateY(-3px); }
        .chart-container { position: relative; height: 280px; width: 100%; }
        h5 { font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 1.5rem; letter-spacing: 0.5px; }
        .bg-alert-soft { background-color: #fef2f2; border: 1px solid #fee2e2 !important; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div>
            <h2 class="fw-bold m-0 text-dark">📊 Report Gestionale Biblioteca</h2>
            <p class="text-muted small m-0">Report generato il: <?= date('d/m/Y H:i') ?></p>
        </div>
        <button class="btn btn-primary shadow-sm btn-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Esporta Report
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-lg-2">
            <div class="card card-kpi p-3 border-primary">
                <small class="text-muted fw-bold">TITOLI</small>
                <div class="h4 m-0 fw-bold"><?= number_format($kpi['totale_titoli'], 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card card-kpi p-3 border-secondary">
                <small class="text-muted fw-bold">COPIE FISICHE</small>
                <div class="h4 m-0 fw-bold"><?= number_format($kpi['copie_fisiche'], 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card card-kpi p-3 border-success">
                <small class="text-muted fw-bold">PRESTITI ATTIVI</small>
                <div class="h4 m-0 fw-bold"><?= $kpi['prestiti_attivi'] ?></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card card-kpi p-3 border-danger <?= ($kpi['prestiti_scaduti'] > $sogliaScaduti) ? 'bg-alert-soft' : '' ?>">
                <small class="text-muted fw-bold">IN RITARDO</small>
                <div class="h4 m-0 fw-bold <?= ($kpi['prestiti_scaduti'] > $sogliaScaduti) ? 'text-danger' : '' ?>"><?= $kpi['prestiti_scaduti'] ?></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card card-kpi p-3 border-warning">
                <small class="text-muted fw-bold">MULTE PENDENTI</small>
                <div class="h4 m-0 fw-bold"><?= $kpi['multe_non_pagate'] ?></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card card-kpi p-3 border-info">
                <small class="text-muted fw-bold">UTENTI TOTALI</small>
                <div class="h4 m-0 fw-bold"><?= number_format($kpi['utenti_totali'], 0, ',', '.') ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card p-4 h-100">
                <h5>Andamento Storico Prestiti (12 Mesi)</h5>
                <div class="chart-container"><canvas id="linePrestiti"></canvas></div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <h5>Distribuzione Ruoli Personale/Utenti</h5>
                <div class="chart-container"><canvas id="barRuoli"></canvas></div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card p-4 h-100">
                <h5>I 10 Libri più richiesti</h5>
                <div class="chart-container"><canvas id="barLibri"></canvas></div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card p-4 h-100">
                <h5>Prestiti per Categoria</h5>
                <div class="chart-container"><canvas id="pieCategorie"></canvas></div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <h5>Nuove Iscrizioni (Ultimi 30gg)</h5>
                <div class="chart-container"><canvas id="areaUtenti"></canvas></div>
            </div>
        </div>
    </div>
</div>

<script>
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';

    // 1. Grafico Ruoli (Barre Verticali)
    new Chart(document.getElementById('barRuoli'), {
        type: 'bar',
        data: {
            labels: ['Studenti', 'Docenti', 'Bibliotecari', 'Admin'],
            datasets: [{
                data: [
                    <?= (int)$ruoliUtenti['studenti'] ?>,
                    <?= (int)$ruoliUtenti['docenti'] ?>,
                    <?= (int)$ruoliUtenti['bibliotecari'] ?>,
                    <?= (int)$ruoliUtenti['amministratori'] ?>
                ],
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                borderRadius: 6
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // 2. Grafico Prestiti (Linea)
    new Chart(document.getElementById('linePrestiti'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($trendPrestiti, 'mese')) ?>,
            datasets: [{
                label: 'Prestiti',
                data: <?= json_encode(array_column($trendPrestiti, 'totale')) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.05)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // 3. Grafico Categorie (Torta/Doughnut)
    new Chart(document.getElementById('pieCategorie'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($distribuzioneCat, 'categoria')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($distribuzioneCat, 'conteggio')) ?>,
                backgroundColor: ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#64748b']
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10 } } }
        }
    });

    // 4. Grafico Top Libri (Barre Orizzontali)
    new Chart(document.getElementById('barLibri'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($topLibri, 'titolo')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($topLibri, 'n_prestiti')) ?>,
                backgroundColor: '#06b6d4',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // 5. Grafico Iscrizioni (Area)
    new Chart(document.getElementById('areaUtenti'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($trendUtenti, 'giorno')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($trendUtenti, 'nuovi')) ?>,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
</script>

</body>
</html>