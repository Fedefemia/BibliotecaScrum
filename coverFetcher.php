<?php
// Configurazione base
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0); // Rimuove il limite di tempo di esecuzione

// Percorso salvataggio (relativo alla root dove si trova questo file)
$saveDir = __DIR__ . '/public/bookCover/';

// Crea la cartella se non esiste
if (!file_exists($saveDir)) {
    if (!mkdir($saveDir, 0777, true)) {
        die("Errore: Impossibile creare la cartella $saveDir. Controlla i permessi.");
    }
}

// Connessione al DB
require_once 'db_config.php';

echo "<h1>Scaricamento Copertine Avviato (Logica Migliorata)</h1>";
echo "<pre>";

try {
    // 1. Recupero tutti gli ISBN
    $stmt = $pdo->query("SELECT isbn, titolo FROM libri");
    $libri = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Trovati " . count($libri) . " libri nel database.\n\n";

    foreach ($libri as $libro) {
        $isbn = $libro['isbn'];
        $titolo = $libro['titolo'];
        
        // Percorsi file possibili
        $pathPng = $saveDir . $isbn . '.png';
        $pathJpg = $saveDir . $isbn . '.jpg';

        // 2. Controllo se esiste già
        if (file_exists($pathPng) || file_exists($pathJpg)) {
            echo "[ESISTE] $isbn - $titolo\n";
            continue;
        }

        echo "[MANCA]  $isbn - $titolo... ";

        // 3. Fetch da Google Books API
        $apiUrl = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . $isbn;
        
        // Context per evitare blocchi user-agent
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: PHPScript/1.0'
            ]
        ]);

        $json = @file_get_contents($apiUrl, false, $context);

        if ($json === FALSE) {
            echo "ERRORE connessione API.\n";
            continue;
        }

        $data = json_decode($json, true);
        $scaricato = false;

        // 4. LOGICA MIGLIORATA: Cicla su tutti gli items, non solo il primo
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                
                // Se questo item ha le immagini
                if (isset($item['volumeInfo']['imageLinks'])) {
                    $links = $item['volumeInfo']['imageLinks'];
                    
                    // Cerca la qualità migliore disponibile (stessa logica del tuo JS)
                    $imageUrl = $links['extraLarge'] 
                             ?? $links['large'] 
                             ?? $links['medium'] 
                             ?? $links['small'] 
                             ?? $links['thumbnail'] 
                             ?? $links['smallThumbnail'] 
                             ?? null;

                    if ($imageUrl) {
                        // Forza HTTPS
                        $imageUrl = str_replace('http://', 'https://', $imageUrl);

                        // Scarica l'immagine
                        $imageContent = @file_get_contents($imageUrl, false, $context);

                        if ($imageContent) {
                            // Salva sempre come PNG per uniformità nello script, o mantieni estensione originale se preferisci
                            if (file_put_contents($pathPng, $imageContent)) {
                                echo "TROVATO e SALVATO ($isbn.png).\n";
                                $scaricato = true;
                                break; // Esce dal ciclo foreach degli items appena trova un'immagine valida
                            }
                        }
                    }
                }
            }
        }

        if (!$scaricato) {
            echo "Nessuna immagine trovata nei risultati API.\n";
        }
        
        // Piccola pausa per evitare rate limit (Google API è sensibile)
        usleep(200000); 
    }

} catch (PDOException $e) {
    echo "Errore Database: " . $e->getMessage();
}

echo "\nOperazione completata.</pre>";
?>