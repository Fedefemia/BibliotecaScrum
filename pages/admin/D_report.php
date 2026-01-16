<?php
require_once 'security.php';

// Controllo accesso: solo amministratori
if (!checkAccess('amministratore')) {
    header('Location: ../index.php');
    exit;
}

require_once 'db_config.php';

// Inizializzazione KPI e dati
$kpi = [
    'totale_titoli' => 0,
    'copie_fisiche' => 0,
    'prestiti_attivi' => 0,
    'prestiti_scaduti' => 0,
    'scadenza_oggi' => 0,
    'multe_totali' => 0,
    'utenti_totali' => 0
];

$trendPrestiti = [];
$topLibri = [];
$distribuzioneCat = [];
$ruoliLabels = [];
$ruoliValori = [];
$statoCopie = ['Disponibili' => 0, 'In_Prestito' => 0];
$catStoricoPrestiti = [];
$scadenzeProssime = [];
$topUtenti = [];
$ultimiPrestiti = [];
$prestitiScaduti = [];
$multeAttive = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // KPI Generali
        $stmtKpi = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM libri) as totale_titoli, 
                (SELECT COUNT(*) FROM copie) as copie_fisiche, 
                (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as prestiti_attivi, 
                (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL AND data_scadenza < CURDATE()) as prestiti_scaduti, 
                (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL AND data_scadenza = CURDATE()) as scadenza_oggi, 
                (SELECT COUNT(*) FROM multe WHERE pagata = 0) as multe_totali, 
                (SELECT COUNT(*) FROM utenti) as utenti_totali
        ");
        $kpi = $stmtKpi->fetch(PDO::FETCH_ASSOC);

        // Categorie più prestate (storico)
        // GROUP BY aggiornato per compatibilità SQL standard
        $catStoricoPrestiti = $pdo->query("
            SELECT c.categoria, COUNT(p.id_prestito) as conteggio
            FROM categorie c
            JOIN libro_categoria lc ON c.id_categoria = lc.id_categoria
            JOIN copie cp ON lc.isbn = cp.isbn
            JOIN prestiti p ON cp.id_copia = p.id_copia
            GROUP BY c.id_categoria, c.categoria
            ORDER BY conteggio DESC
            LIMIT 6
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Distribuzione catalogo
        $distribuzioneCat = $pdo->query("
            SELECT c.categoria, COUNT(lc.isbn) as conteggio 
            FROM categorie c 
            JOIN libro_categoria lc ON c.id_categoria = lc.id_categoria 
            GROUP BY c.id_categoria, c.categoria
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Distribuzione ruoli utenti
        $distRuoli = $pdo->query("
            SELECT SUM(studente) as Studenti, SUM(docente) as Docenti, SUM(bibliotecario) as Bibliotecari, SUM(amministratore) as Admin
            FROM ruoli
        ")->fetch(PDO::FETCH_ASSOC);
        
        $ruoliLabels = $distRuoli ? array_keys($distRuoli) : [];
        $ruoliValori = $distRuoli ? array_values($distRuoli) : [];

        // Scadenze imminenti (lista breve)
        $scadenzeProssime = $pdo->query("
            SELECT p.data_scadenza, l.titolo, u.email 
            FROM prestiti p
            JOIN copie c ON p.id_copia = c.id_copia
            JOIN libri l ON c.isbn = l.isbn
            JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
            WHERE p.data_restituzione IS NULL 
              AND (p.data_scadenza = CURDATE() OR p.data_scadenza = DATE_ADD(CURDATE(), INTERVAL 1 DAY))
            ORDER BY p.data_scadenza ASC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Top utenti per numero di prestiti
        // GROUP BY aggiornato per compatibilità SQL standard
        $topUtenti = $pdo->query("
            SELECT u.nome, u.cognome, COUNT(p.id_prestito) as tot
            FROM utenti u
            JOIN prestiti p ON u.codice_alfanumerico = p.codice_alfanumerico
            GROUP BY u.codice_alfanumerico, u.nome, u.cognome
            ORDER BY tot DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Top libri per numero di prestiti
        // GROUP BY aggiornato per compatibilità SQL standard
        $topLibri = $pdo->query("
            SELECT l.titolo, COUNT(p.id_prestito) as n_prestiti
            FROM libri l
            JOIN copie c ON l.isbn = c.isbn
            JOIN prestiti p ON c.id_copia = p.id_copia
            GROUP BY l.isbn, l.titolo
            ORDER BY n_prestiti DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Stato copie fisiche
        $statoCopie = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM copie) - (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as Disponibili,
                (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as In_Prestito
        ")->fetch(PDO::FETCH_ASSOC);

        // Trend prestiti ultimi 12 mesi
        $trendPrestiti = $pdo->query("
            SELECT DATE_FORMAT(data_prestito, '%m/%Y') as mese, COUNT(*) as totale
            FROM prestiti
            WHERE data_prestito >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY LAST_DAY(data_prestito)
            ORDER BY LAST_DAY(data_prestito) ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Nuovi utenti ultimi 30 giorni
        $trendUtenti = $pdo->query("
            SELECT DATE_FORMAT(data_creazione, '%d/%m') as giorno, COUNT(*) as nuovi
            FROM utenti
            WHERE data_creazione >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY data_creazione
            ORDER BY data_creazione ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Ultimi 10 prestiti
        $ultimiPrestiti = $pdo->query("
            SELECT p.data_prestito, p.data_scadenza, l.titolo, u.nome, u.cognome
            FROM prestiti p
            JOIN copie c ON p.id_copia = c.id_copia
            JOIN libri l ON c.isbn = l.isbn
            JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
            ORDER BY p.data_prestito DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Prestiti scaduti
        $prestitiScaduti = $pdo->query("
            SELECT l.titolo, u.nome, u.cognome, DATEDIFF(CURDATE(), p.data_scadenza) AS ritardo
            FROM prestiti p
            JOIN copie c ON p.id_copia = c.id_copia
            JOIN libri l ON c.isbn = l.isbn
            JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
            WHERE p.data_restituzione IS NULL AND p.data_scadenza < CURDATE()
            ORDER BY ritardo DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Multe attive (AGGIORNATA per usare id_prestito)
        $multeAttive = $pdo->query("
            SELECT u.nome, u.cognome, l.titolo, m.importo
            FROM multe m
            JOIN prestiti p ON m.id_prestito = p.id_prestito
            JOIN copie c ON p.id_copia = c.id_copia
            JOIN libri l ON c.isbn = l.isbn
            JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
            WHERE m.pagata = 0
            ORDER BY m.importo DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// ---------------- HTML HEADER ----------------
$path = "../";
$title = "Reportistica - Dashboard";
$page_css = "../public/css/style_dashboards.css";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <div class="dashboard_container">

        <div class="page_header">
            <h1 class="page_title">Dashboard Analitica</h1>
            <div class="header_actions">
                <button class="general_button_dark" onclick="window.print()">
                    Stampa Report
                </button>
            </div>
        </div>

        <div class="report_grid_kpi">
            <div class="kpi_card">
                <div class="kpi_header">
                    <span class="kpi_label">Titoli in Catalogo</span>
                    <span class="kpi_icon">📚</span>
                </div>
                <div class="kpi_value"><?= $kpi['totale_titoli'] ?></div>
            </div>

            <div class="kpi_card">
                <div class="kpi_header">
                    <span class="kpi_label">Copie Fisiche</span>
                    <span class="kpi_icon">📦</span>
                </div>
                <div class="kpi_value"><?= $kpi['copie_fisiche'] ?></div>
            </div>

            <div class="kpi_card kpi_border_accent">
                <div class="kpi_header">
                    <span class="kpi_label">Prestiti in Corso</span>
                    <span class="kpi_icon">📖</span>
                </div>
                <div class="kpi_value"><?= $kpi['prestiti_attivi'] ?></div>
            </div>

            <div class="kpi_card kpi_border_warning">
                <div class="kpi_header">
                    <span class="kpi_label">Scadono Oggi</span>
                    <span class="kpi_icon">⚠️</span>
                </div>
                <div class="kpi_value"><?= $kpi['scadenza_oggi'] ?></div>
            </div>
        </div>

        <div class="report_grid_charts">
            <div class="chart_card">
                <div class="chart_header_row">
                    <h3 class="chart_title">Stato Copie</h3>
                </div>
                <div class="chart_box">
                    <canvas id="pieDispo"></canvas>
                </div>
            </div>

            <div class="chart_card">
                <div class="chart_header_row">
                    <h3 class="chart_title">Andamento Prestiti</h3>
                </div>
                <div class="chart_box">
                    <canvas id="lineTrend"></canvas>
                </div>
            </div>
        </div>

        <div class="report_grid_charts">
            <div class="chart_card">
                <div class="chart_header_row">
                    <h3 class="chart_title">Categorie Top</h3>
                </div>
                <div class="chart_box">
                    <canvas id="barCategorie"></canvas>
                </div>
            </div>

            <div class="chart_card">
                <div class="chart_header_row">
                    <h3 class="chart_title">Utenza</h3>
                </div>
                <div class="chart_box">
                    <canvas id="doughnutRuoli"></canvas>
                </div>
            </div>
        </div>

        <div class="chart_card mt-large">
            <div class="chart_header_row">
                <h3 class="chart_title">Top 10 Libri più richiesti</h3>
            </div>
            <div class="chart_box_large">
                <canvas id="barLibri"></canvas>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Configurazione Globale Font (preso da style_global)
        Chart.defaults.font.family = "'Instrument Sans', sans-serif";
        Chart.defaults.color = '#333';

        // 1. Stato copie fisiche
        new Chart(document.getElementById('pieDispo'), {
            type: 'doughnut',
            data: {
                labels: ['Disponibili','In Prestito'],
                datasets: [{
                    data: [<?= $statoCopie['Disponibili'] ?>, <?= $statoCopie['In_Prestito'] ?>],
                    backgroundColor: ['#aac99a', '#3f5135'],
                    borderWidth: 0
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // 2. Trend Prestiti
        new Chart(document.getElementById('lineTrend'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($trendPrestiti, 'mese')) ?>,
                datasets: [{
                    label: 'Prestiti',
                    data: <?= json_encode(array_column($trendPrestiti, 'totale')) ?>,
                    borderColor: '#3f5135',
                    backgroundColor: 'rgba(63, 81, 53, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: { maintainAspectRatio: false }
        });

        // 3. Categorie
        new Chart(document.getElementById('barCategorie'), {
            type: 'bar',
            indexAxis: 'y',
            data: {
                labels: <?= json_encode(array_column($catStoricoPrestiti, 'categoria')) ?>,
                datasets: [{
                    label: 'Prestiti',
                    data: <?= json_encode(array_column($catStoricoPrestiti, 'conteggio')) ?>,
                    backgroundColor: '#c5e0b7',
                    borderRadius: 4
                }]
            },
            options: { maintainAspectRatio: false }
        });

        // 4. Ruoli
        new Chart(document.getElementById('doughnutRuoli'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($ruoliLabels) ?>,
                datasets: [{
                    data: <?= json_encode($ruoliValori) ?>,
                    backgroundColor: ['#3f5135', '#819e71', '#aac99a', '#f8f4e9']
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });

        // 5. Top Libri
        new Chart(document.getElementById('barLibri'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(fn($l)=>strlen($l['titolo'])>20?substr($l['titolo'],0,20).'...':$l['titolo'], $topLibri)) ?>,
                datasets: [{
                    label: 'N. Prestiti',
                    data: <?= json_encode(array_column($topLibri, 'n_prestiti')) ?>,
                    backgroundColor: '#3f5135',
                    borderRadius: 4
                }]
            },
            options: { maintainAspectRatio: false }
        });
    </script>

<?php require_once './src/includes/footer.php'; ?>