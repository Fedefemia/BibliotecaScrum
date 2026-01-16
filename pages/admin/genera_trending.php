<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../../security.php';

if (!checkAccess('amministratore')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Accesso negato');
}

try {
    // Query completa: libro + autori + categorie multiple + copie disponibili
    $sql = "
        SELECT 
            l.isbn, 
            l.titolo, 
            l.descrizione, 
            l.anno_pubblicazione,
            GROUP_CONCAT(DISTINCT c.categoria SEPARATOR ', ') AS categorie,
            GROUP_CONCAT(DISTINCT CONCAT(a.nome, ' ', a.cognome) SEPARATOR ', ') AS autori,
            COUNT(cp.id_copia) AS copie_disponibili
        FROM libri l
        LEFT JOIN autore_libro al ON l.isbn = al.isbn
        LEFT JOIN autori a ON al.id_autore = a.id_autore
        LEFT JOIN libro_categoria lc ON l.isbn = lc.isbn
        LEFT JOIN categorie c ON lc.id_categoria = c.id_categoria
        LEFT JOIN copie cp ON l.isbn = cp.isbn
        GROUP BY l.isbn, l.titolo, l.descrizione, l.anno_pubblicazione
    ";
    $stmt = $pdo->query($sql);
    $libriDB = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Errore query DB: " . $e->getMessage());
}

$libri = [];
foreach ($libriDB as $row) {
    $years_since_pub = max(1, date('Y') - (int)$row['anno_pubblicazione']);
    $time_decay = exp(-0.1 * $years_since_pub);

    $prestiti = $row['prestiti'] ?? 0;
    $prenotazioni = $row['prenotazioni'] ?? 0;
    $rating_medio = $row['rating_medio'] ?? 5;

    // 🔹 Divisione sicura per evitare DivisionByZeroError
    $copie = $row['copie_disponibili'] ?? 0;
    $turnover_rate = $prestiti / max(1, $copie);

    $trend_score = ($prestiti * $time_decay) + ($prenotazioni * 2) + ($rating_medio * 10) + ($turnover_rate * 5);

    $row['trend_score'] = $trend_score;
    $libri[] = $row;
}

// Ordina per trend_score decrescente
usort($libri, fn($a, $b) => $b['trend_score'] <=> $a['trend_score']);

// Top 20 per categoria (considerando tutte le categorie di un libro)
$top_libri_per_categoria = [];
foreach ($libri as $libro) {
    $categorie = explode(',', $libro['categorie'] ?? 'Sconosciuta');
    foreach ($categorie as $categoria) {
        $categoria = trim($categoria);
        if (!isset($top_libri_per_categoria[$categoria])) $top_libri_per_categoria[$categoria] = [];
        if (count($top_libri_per_categoria[$categoria]) < 20) {
            $top_libri_per_categoria[$categoria][] = $libro;
        }
    }
}

// Genera XML
$xml = new SimpleXMLElement('<libri_trending/>');
foreach ($top_libri_per_categoria as $categoria => $libri_categoria) {
    $catNode = $xml->addChild('categoria');
    $catNode->addAttribute('nome', $categoria);

    foreach ($libri_categoria as $libro) {
        $libNode = $catNode->addChild('libro');
        $libNode->addChild('id', $libro['isbn']);
        $libNode->addChild('titolo', htmlspecialchars($libro['titolo']));
        $libNode->addChild('descrizione', htmlspecialchars($libro['descrizione'] ?? ''));
        $libNode->addChild('autori', htmlspecialchars($libro['autori'] ?? 'Sconosciuto'));
        $libNode->addChild('categorie', htmlspecialchars($libro['categorie'] ?? 'Sconosciuta'));
        $libNode->addChild('anno_pubblicazione', $libro['anno_pubblicazione']);
        $libNode->addChild('copie_disponibili', $libro['copie_disponibili']);
        $libNode->addChild('trend_score', round($libro['trend_score'], 4));
    }
}

// Salva XML in cartella sicura
$dataDir = __DIR__ . '/../../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}
$xmlFile = $dataDir . '/libri_trending.xml';
$xml->asXML($xmlFile);

echo "<h2>File XML dei libri trending generato correttamente!</h2>";
echo "<p>Percorso: $xmlFile</p>";
echo "<a href='/dashboard'>Torna alla Dashboard</a>";

?>
