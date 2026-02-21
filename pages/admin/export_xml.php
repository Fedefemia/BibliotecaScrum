<?php
// ------------------------------------------------------------------
// export_xml.php - Genera e scarica XML completo della biblioteca
// ------------------------------------------------------------------

require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../../security.php';

// Controllo accesso
if (!checkAccess('amministratore')) {
    header('Location: ../../index.php');
    exit;
}

// Connessione PDO
try {


    // ------------------ KPI Generali ------------------
    $kpi = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM libri) as totale_titoli,
        (SELECT COUNT(*) FROM copie) as copie_fisiche,
        (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as prestiti_attivi,
        (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL AND data_scadenza < CURDATE()) as prestiti_scaduti,
        (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL AND data_scadenza = CURDATE()) as scadenza_oggi,
        (SELECT COUNT(*) FROM multe WHERE pagata = 0) as multe_totali,
        (SELECT COUNT(*) FROM utenti) as utenti_totali
    ")->fetch(PDO::FETCH_ASSOC);

    // ------------------ Stato Copie ------------------
    $statoCopie = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM copie) - (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as Disponibili,
            (SELECT COUNT(*) FROM prestiti WHERE data_restituzione IS NULL) as In_Prestito
    ")->fetch(PDO::FETCH_ASSOC);

    // ------------------ Ultimi 10 Prestiti ------------------
    $ultimiPrestiti = $pdo->query("
        SELECT p.data_prestito, p.data_scadenza, l.titolo, u.nome, u.cognome
        FROM prestiti p
        JOIN copie c ON p.id_copia = c.id_copia
        JOIN libri l ON c.isbn = l.isbn
        JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
        ORDER BY p.data_prestito DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // ------------------ Prestiti Scaduti ------------------
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

    // ------------------ Multe Attive ------------------
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

    // ------------------ Top 10 Libri ------------------
    $topLibri = $pdo->query("
        SELECT l.titolo, COUNT(p.id_prestito) as n_prestiti
        FROM libri l
        JOIN copie c ON l.isbn = c.isbn
        JOIN prestiti p ON c.id_copia = p.id_copia
        GROUP BY l.isbn
        ORDER BY n_prestiti DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // ------------------ Top 10 Utenti ------------------
    $topUtenti = $pdo->query("
        SELECT u.nome, u.cognome, COUNT(p.id_prestito) as tot
        FROM utenti u
        JOIN prestiti p ON u.codice_alfanumerico = p.codice_alfanumerico
        GROUP BY u.codice_alfanumerico
        ORDER BY tot DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // ------------------ Categorie più Prestate ------------------
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

    // ------------------ Prestiti in Scadenza ------------------
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

// ------------------------------------------------------------------
// Creazione XML
// ------------------------------------------------------------------
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><biblioteca></biblioteca>');

// KPI
$kpiNode = $xml->addChild('kpi');
foreach ($kpi as $key => $val) {
    $kpiNode->addChild($key, $val);
}

// Stato copie
$statoNode = $xml->addChild('stato_copie');
$statoNode->addChild('disponibili', $statoCopie['Disponibili']);
$statoNode->addChild('in_prestito', $statoCopie['In_Prestito']);

// Ultimi prestiti
$ultimiNode = $xml->addChild('ultimi_prestiti');
foreach ($ultimiPrestiti as $p) {
    $pNode = $ultimiNode->addChild('prestito');
    $pNode->addChild('titolo', $p['titolo']);
    $pNode->addChild('utente', $p['nome'].' '.$p['cognome']);
    $pNode->addChild('data_prestito', $p['data_prestito']);
    $pNode->addChild('data_scadenza', $p['data_scadenza']);
}

// Prestiti scaduti
$scadutiNode = $xml->addChild('prestiti_scaduti');
foreach ($prestitiScaduti as $ps) {
    $psNode = $scadutiNode->addChild('prestito');
    $psNode->addChild('titolo', $ps['titolo']);
    $psNode->addChild('utente', $ps['nome'].' '.$ps['cognome']);
    $psNode->addChild('ritardo_gg', $ps['ritardo']);
}

// Multe attive
$multeNode = $xml->addChild('multe_attive');
foreach ($multeAttive as $m) {
    $mNode = $multeNode->addChild('multa');
    $mNode->addChild('utente', $m['nome'].' '.$m['cognome']);
    $mNode->addChild('prestito', $m['titolo']);
    $mNode->addChild('importo', number_format($m['importo'],2,".",""));
}

// Top libri
$topLibriNode = $xml->addChild('top_libri');
foreach ($topLibri as $l) {
    $lNode = $topLibriNode->addChild('libro');
    $lNode->addChild('titolo', $l['titolo']);
    $lNode->addChild('prestiti', $l['n_prestiti']);
}

// Top utenti
$topUtentiNode = $xml->addChild('top_utenti');
foreach ($topUtenti as $u) {
    $uNode = $topUtentiNode->addChild('utente');
    $uNode->addChild('nome', $u['nome'].' '.$u['cognome']);
    $uNode->addChild('prestiti', $u['tot']);
}

// Categorie più prestate
$catNode = $xml->addChild('categorie_piu_prestate');
foreach ($catStoricoPrestiti as $c) {
    $cNode = $catNode->addChild('categoria');
    $cNode->addChild('nome', $c['categoria']);
    $cNode->addChild('prestiti', $c['conteggio']);
}

// Prestiti prossimi 2 giorni
$scadenzeNode = $xml->addChild('prestiti_in_scadenza');
foreach ($scadenzeProssime as $s) {
    $sNode = $scadenzeNode->addChild('prestito');
    $sNode->addChild('titolo', $s['titolo']);
    $sNode->addChild('email_utente', $s['email']);
    $sNode->addChild('scadenza', $s['data_scadenza']);
}

// ------------------------------------------------------------------
// Output XML per download
// ------------------------------------------------------------------
header('Content-Disposition: attachment; filename="export_biblioteca_'.date('dmY').'.xml"');
header('Content-Type: application/xml');
echo $xml->asXML();
exit;
