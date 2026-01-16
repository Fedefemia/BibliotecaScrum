<?php
require_once 'security.php';
if (!checkAccess('amministratore')) header('Location: ./');

$saveDir = __DIR__ . '/public/bookCover/';
$concurrentRequests = 5; // Abbassato a 5 per essere meno aggressivi col server

if (!file_exists($saveDir)) {
    mkdir($saveDir, 0777, true);
}

require_once 'db_config.php';

echo "<h1>Scaricamento Ibrido (Google + OpenLibrary)</h1><pre>";

try {
    // 1. Prendo tutti i libri
    $stmt = $pdo->query("SELECT isbn FROM libri WHERE isbn IS NOT NULL AND isbn != ''");
    $libri = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Filtro quelli che mancano
    $daScaricare = [];
    foreach ($libri as $l) {
        $isbn = trim($l['isbn']);
        // Pulisco l'ISBN da trattini per OpenLibrary
        $isbnClean = str_replace('-', '', $isbn);
        
        if (!file_exists($saveDir . $isbn . '.png') && !file_exists($saveDir . $isbn . '.jpg')) {
            $daScaricare[] = ['original' => $isbn, 'clean' => $isbnClean];
        }
    }

    $totale = count($daScaricare);
    echo "Libri da scaricare: $totale\n\n";

    if ($totale === 0) {
        echo "Tutto aggiornato.</pre>";
        exit;
    }

    // 3. Processo a blocchi
    $batches = array_chunk($daScaricare, $concurrentRequests);
    
    foreach ($batches as $batchIndex => $batchIsbns) {
        $mh = curl_multi_init();
        $curlHandles = [];

        // Preparo le richieste per Google
        foreach ($batchIsbns as $item) {
            $isbn = $item['original'];
            $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . $isbn;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            curl_multi_add_handle($mh, $ch);
            $curlHandles[$isbn] = $ch;
        }

        // Eseguo
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        // Raccolgo risultati
        foreach ($curlHandles as $isbn => $ch) {
            // *** CORREZIONE FUNZIONE QUI SOTTO ***
            $response = curl_multi_getcontent($ch); 
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $saved = false;
            
            // TENTATIVO 1: GOOGLE BOOKS
            if ($httpCode == 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['items'][0]['volumeInfo']['imageLinks'])) {
                    $links = $data['items'][0]['volumeInfo']['imageLinks'];
                    $imgUrl = $links['extraLarge'] ?? $links['large'] ?? $links['medium'] ?? $links['small'] ?? $links['thumbnail'] ?? null;
                    
                    if ($imgUrl) {
                        $imgUrl = str_replace('http://', 'https://', $imgUrl);
                        $imgData = @file_get_contents($imgUrl);
                        if ($imgData) {
                            file_put_contents($saveDir . $isbn . '.png', $imgData);
                            echo "[OK-GOOGLE] $isbn\n";
                            $saved = true;
                        }
                    }
                }
            }

            // TENTATIVO 2: OPEN LIBRARY (FALLBACK)
            if (!$saved) {
                // Recupero l'ISBN pulito dall'array originale
                $cleanIsbn = "";
                foreach($batchIsbns as $b) { if($b['original'] == $isbn) $cleanIsbn = $b['clean']; }

                $olUrl = "https://covers.openlibrary.org/b/isbn/" . $cleanIsbn . "-L.jpg?default=false";
                
                // OpenLibrary ritorna 404 se non trova l'immagine (grazie a default=false)
                $headers = @get_headers($olUrl);
                if ($headers && strpos($headers[0], '200')) {
                    $imgData = @file_get_contents($olUrl);
                    if ($imgData && strlen($imgData) > 100) { // Controllo che non sia un pixel vuoto
                        file_put_contents($saveDir . $isbn . '.png', $imgData);
                        echo "[OK-OPENLIB] $isbn (Recuperato dal backup)\n";
                        $saved = true;
                    }
                }
            }

            if (!$saved) {
                echo "[FALLITO] $isbn (Nessuna copertina trovata su Google o OpenLib)\n";
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        
        echo "--- Batch completato ---\n";
        flush();
        sleep(2); // Pausa leggermente più lunga per il server
    }

} catch (Exception $e) {
    echo "Errore Critico: " . $e->getMessage();
}

echo "\nFinito.</pre>";
?>