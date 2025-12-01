<?php


$whitelist = [                                  //Cambia solo qua
    '/'          => 'pages/home.php',       // La home page
    '/webhook'   => 'webhook.php'           //webhook pull server
];

//stop

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

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
    echo "<p>Non hai il permesso di visualizzare: <b>$request_uri</b></p>";
    echo "<hr><p>Questo server è protetto da Whitelist.</p>";
}
?>
