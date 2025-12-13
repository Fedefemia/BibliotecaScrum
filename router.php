<?php
$whitelist = [
    '/'        => 'pages/index.php',             // home page
    '/home'        => 'pages/index.php',         // home page
    '/webhook' => 'webhook.php',                 // Webhook pull server (local server)
    '/login' => 'pages/login.php',
    '/confirm-email' => 'pages/confirmemail.php',
    '/signup' => 'pages/signup.php',
    '/logout' => 'pages/logout.php',
    '/protected' => 'pages/protected.php',
    '/contatti' => 'pages/contatti.php',
    './privacy' => 'pages/privacy.php',
    './terms' => 'pages/terms.php',
    '/dashboard' => 'pages/dashboard.php',
];


// LOGICA ROUTER
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$base_path = dirname($_SERVER['SCRIPT_NAME']);

if ($base_path !== '/' && $base_path !== '\\') {
    if (strpos($request_uri, $base_path) === 0) {
        $request_uri = substr($request_uri, strlen($base_path));
    }
}

if ($request_uri == '' || $request_uri == '/index.php' || $request_uri == '/router.php') {
    $request_uri = '/';
}


if (array_key_exists($request_uri, $whitelist)) {
    
    $file_to_include = $whitelist[$request_uri];

    if (file_exists($file_to_include)) {
        
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
    echo "<p>Hai cercato: <b>" . htmlspecialchars($request_uri) . "</b></p>";
    echo "<p>Ma le rotte valide sono solo: <b>" . implode(", ", array_keys($whitelist)) . "</b></p>";
    echo "<hr><p>Controlla router.php</p>";
}
?>
