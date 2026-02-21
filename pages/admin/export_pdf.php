<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../../security.php';

if (!checkAccess('amministratore')) {
    header('Location: ../../index.php');
    exit;
}

try {

    // KPI Generali
    $kpi = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM libri) as totale_titoli,
        (SELECT COUNT(*) FROM copie) as copie_fisiche,
        (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as prestiti_attivi,
        (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL AND data_scadenza < CURDATE()) as prestiti_scaduti,
        (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL AND data_scadenza = CURDATE()) as scadenza_oggi,
        (SELECT COUNT(*) FROM multe WHERE pagata = 0) as multe_totali,
        (SELECT COUNT(*) FROM utenti) as utenti_totali
    ")->fetch(PDO::FETCH_ASSOC);

    //  Stato Copie
    $statoCopie = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM copie) - (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as Disponibili,
            (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as In_Prestito
    ")->fetch(PDO::FETCH_ASSOC);

    //  Ultimi 10 Prestiti
    $ultimiPrestiti = $pdo->query("
        SELECT p.data_prestito, p.data_scadenza, l.titolo, u.nome, u.cognome
        FROM prestiti p
        JOIN copie c ON p.id_copia = c.id_copia
        JOIN libri l ON c.isbn = l.isbn
        JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
        ORDER BY p.data_prestito DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Prestiti Scaduti
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

    //  Multe Attive
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

    //  Top 10 Libri
    $topLibri = $pdo->query("
        SELECT l.titolo, COUNT(p.id_prestito) as n_prestiti
        FROM libri l
        JOIN copie c ON l.isbn = c.isbn
        JOIN prestiti p ON c.id_copia = p.id_copia
        GROUP BY l.isbn
        ORDER BY n_prestiti DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Top 10 Utenti
    $topUtenti = $pdo->query("
        SELECT u.nome, u.cognome, COUNT(p.id_prestito) as tot
        FROM utenti u
        JOIN prestiti p ON u.codice_alfanumerico = p.codice_alfanumerico
        GROUP BY u.codice_alfanumerico
        ORDER BY tot DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Categorie più Prestate
    $catStoricoPrestiti = $pdo->query("
        SELECT c.categoria, COUNT(p.id_prestito) as conteggio
        FROM categorie c
        JOIN libro_categoria lc ON c.id_categoria = lc.id_categoria
        JOIN copie cp ON lc.isbn = cp.isbn
        JOIN prestiti p ON cp.id_copia = p.id_copia
        GROUP BY c.id_categoria
        ORDER BY conteggio DESC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);

    //  Prestiti in Scadenza
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

} catch (PDOException $e) {
    die("Errore DB: " . $e->getMessage());
}

// Creazione PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Sistema Biblioteca');
$pdf->SetAuthor('Dashboard Amministrativa');
$pdf->SetTitle('Report Mensile Biblioteca');
$pdf->SetMargins(15, 20, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

$html = "<h1>Report Mensile Biblioteca</h1>";
$html .= "<p>Generato il " . date('d/m/Y H:i') . "</p>";

//  KPI
$html .= "<h2>KPI Generali</h2><ul>";
foreach ($kpi as $key => $val) {
    $html .= "<li><b>$key:</b> $val</li>";
}
$html .= "</ul>";

// Stato copie
$html .= "<h2>Stato Copie</h2>
<ul>
<li>Disponibili: {$statoCopie['Disponibili']}</li>
<li>In Prestito: {$statoCopie['In_Prestito']}</li>
</ul>";

// Ultimi prestiti
$html .= "<h2>Ultimi 10 Prestiti</h2>
<table border='1' cellpadding='4'>
<tr><th>Titolo</th><th>Utente</th><th>Data Prestito</th><th>Data Scadenza</th></tr>";
foreach ($ultimiPrestiti as $p) {
    $html .= "<tr>
        <td>{$p['titolo']}</td>
        <td>{$p['nome']} {$p['cognome']}</td>
        <td>{$p['data_prestito']}</td>
        <td>{$p['data_scadenza']}</td>
    </tr>";
}
$html .= "</table>";

// Prestiti scaduti
$html .= "<h2>Prestiti Scaduti</h2>
<table border='1' cellpadding='4'>
<tr><th>Titolo</th><th>Utente</th><th>Ritardo (gg)</th></tr>";
foreach ($prestitiScaduti as $ps) {
    $html .= "<tr>
        <td>{$ps['titolo']}</td>
        <td>{$ps['nome']} {$ps['cognome']}</td>
        <td>{$ps['ritardo']}</td>
    </tr>";
}
$html .= "</table>";

// Multe attive
$html .= "<h2>Multe Attive</h2>
<table border='1' cellpadding='4'>
<tr><th>Utente</th><th>Prestito</th><th>Importo (€)</th></tr>";
foreach ($multeAttive as $m) {
    $html .= "<tr>
        <td>{$m['nome']} {$m['cognome']}</td>
        <td>{$m['titolo']}</td>
        <td>".number_format($m['importo'],2,",",".")."</td>
    </tr>";
}
$html .= "</table>";

// Top libri
$html .= "<h2>Top 10 Libri</h2>
<table border='1' cellpadding='4'>
<tr><th>Libro</th><th>Prestiti</th></tr>";
foreach ($topLibri as $l) {
    $html .= "<tr><td>{$l['titolo']}</td><td>{$l['n_prestiti']}</td></tr>";
}
$html .= "</table>";

// Top utenti
$html .= "<h2>Top 10 Utenti</h2>
<table border='1' cellpadding='4'>
<tr><th>Utente</th><th>Prestiti</th></tr>";
foreach ($topUtenti as $u) {
    $html .= "<tr><td>{$u['nome']} {$u['cognome']}</td><td>{$u['tot']}</td></tr>";
}
$html .= "</table>";

// Categorie più prestate
$html .= "<h2>Categorie più Prestate</h2>
<table border='1' cellpadding='4'>
<tr><th>Categoria</th><th>Prestiti</th></tr>";
foreach ($catStoricoPrestiti as $c) {
    $html .= "<tr><td>{$c['categoria']}</td><td>{$c['conteggio']}</td></tr>";
}
$html .= "</table>";

// Prestiti prossimi 2 giorni
$html .= "<h2>Prestiti in Scadenza (Prossimi 2 giorni)</h2>
<table border='1' cellpadding='4'>
<tr><th>Titolo</th><th>Utente (email)</th><th>Scadenza</th></tr>";
foreach ($scadenzeProssime as $s) {
    $html .= "<tr>
        <td>{$s['titolo']}</td>
        <td>{$s['email']}</td>
        <td>".date('d/m/Y', strtotime($s['data_scadenza']))."</td>
    </tr>";
}
$html .= "</table>";

// Scrivi HTML nel PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Nome dinamico e download automatico
$filename = 'export_biblioteca_' . date('dmY') . '.pdf';
$pdf->Output($filename, 'D');
