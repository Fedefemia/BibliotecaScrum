<?php
// --- 1. CONFIGURAZIONE WHITELIST ---
// Qui decidi quali percorsi URL corrispondono a quali file fisici.
// Se un URL non è qui, l'utente vede un errore 404/403.
$whitelist = [
    '/'          => 'home.php',       // La home page
    '/test'      => 'test.php',       // Il file di test
    '/chi-siamo' => 'about.php',      // Esempio
    '/api/libri' => 'get_books.php'   // Esempio API
];

// --- 2. GESTIONE RICHIESTA ---
// Otteniamo il percorso richiesto dall'utente (es. /test)
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --- 3. LOGICA DI SICUREZZA ---
if (array_key_exists($request_uri, $whitelist)) {
    
    // Il file fisico da caricare
    $file_to_include = $whitelist[$request_uri];

    // Verifica che il file fisico esista davvero
    if (file_exists($file_to_include)) {
        
        // Includiamo la connessione DB così è disponibile ovunque
        if (file_exists('db_config.php')) {
            include 'db_config.php'; 
        }

        // Carichiamo il file richiesto
        include $file_to_include;
        
    } else {
        // L'URL è in whitelist, ma hai dimenticato di creare il file PHP!
        http_response_code(500);
        echo "<h1>Errore Configurazione</h1><p>Il file <b>$file_to_include</b> manca sul server.</p>";
    }

} else {
    // --- 4. BLOCCO ACCESSO (Non in whitelist) ---
    // Qualsiasi altro file (db_config.php, file nascosti, ecc.) viene bloccato qui.
    http_response_code(403); // O 404 per non dare indizi
    
    echo "<h1 style='color:red'>403 ACCESSO NEGATO</h1>";
    echo "<p>Non hai il permesso di visualizzare: <b>$request_uri</b></p>";
    echo "<hr><p>Questo server è protetto da Whitelist.</p>";
}
?>
