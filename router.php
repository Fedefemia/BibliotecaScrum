<?php

// --- CONFIGURAZIONE ---
$whitelist = [
    '/'        => 'pages/home.php',       // La home page
    '/home'        => 'pages/home.php',       // La home page
    '/webhook' => 'webhook.php'           // Webhook pull server
];

// --- LOGICA ROUTER ---

// 1. Prendi l'URI completo (es: /BibliotecaScrum/webhook)
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 2. Calcola se siamo in una sottocartella (es: /BibliotecaScrum)
// dirname($_SERVER['SCRIPT_NAME']) ci restituisce la cartella in cui si trova il router
$base_path = dirname($_SERVER['SCRIPT_NAME']);

// 3. Rimuovi la sottocartella dall'URI per ottenere il percorso "pulito"
// Se $base_path è "/" o "\", non fare nulla. Altrimenti rimuovilo dall'URI.
if ($base_path !== '/' && $base_path !== '\\') {
    // Sostituisce "/BibliotecaScrum/webhook" con "/webhook"
    if (strpos($request_uri, $base_path) === 0) {
        $request_uri = substr($request_uri, strlen($base_path));
    }
}

// Assicuriamoci che l'URI inizi sempre con / (fix per casi vuoti)
if ($request_uri == '' || $request_uri == '/index.php' || $request_uri == '/router.php') {
    $request_uri = '/';
}


// --- CONTROLLO WHITELIST ---

if (array_key_exists($request_uri, $whitelist)) {
    
    $file_to_include = $whitelist[$request_uri];

    if (file_exists($file_to_include)) {
        
        // Carica la config DB solo se necessario e se esiste
        if (file_exists('db_config.php')) {
            include 'db_config.php'; 
        }

        include $file_to_include;
        
    } else {
        http_response_code(500);
        echo "<h1>Errore Configurazione</h1><p>Il file <b>$file_to_include</b> manca sul server.</p>";
    }

} else {
    http_response_code(403);
    echo "<h1 style='color:red'>403 ACCESSO NEGATO</h1>";
    echo "<p>Hai cercato: <b>" . htmlspecialchars($request_uri) . "</b></p>"; // Ti mostro cosa vede il sistema
    echo "<p>Ma le rotte valide sono solo: <b>" . implode(", ", array_keys($whitelist)) . "</b></p>";
    echo "<hr><p>Controlla router.php</p>";
}
?>