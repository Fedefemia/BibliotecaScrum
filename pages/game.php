<?php
// ---------------- 1. LOGICA PHP (Server Side) ----------------
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gestione include intelligente per il DB
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} elseif (file_exists('../db_config.php')) {
    require_once '../db_config.php';
}

$messaggio_db = "";
$final_data = []; // Dati corretti (Soluzione)
$shuffled_data = []; // Dati mescolati

// Funzione Helper corretta per bookCover
function getGameCoverPath($isbn) {
    // Percorso relativo dalla root (dove gira il router)
    $localPath = "public/bookCover/$isbn.png";
    
    // Controllo se il file esiste
    if (file_exists($localPath)) {
        return "./" . $localPath;
    }
    
    // Fallback al placeholder
    return "./public/assets/book_placeholder.jpg";
}

if (isset($pdo)) {
    try {
        // 1. PRENDIAMO 20 LIBRI CASUALI
        // DISTINCT assicura unicità DB, ma il controllo PHP sotto è la sicurezza finale
        $query = 'SELECT DISTINCT l.isbn, l.titolo, a.nome, a.cognome, ct.categoria 
                  FROM copie AS c
                  JOIN libri AS l ON c.isbn = l.isbn
                  JOIN autore_libro AS al ON al.isbn = l.isbn 
                  JOIN autori AS a ON a.id_autore = al.id_autore 
                  JOIN libro_categoria AS cl ON cl.isbn = l.isbn
                  JOIN categorie AS ct ON ct.id_categoria = cl.id_categoria
                  ORDER BY RAND() LIMIT 20';
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. FILTRO PHP CON CONTROLLO RIDONDANZA
        $valid_books = [];
        $used_isbns = []; // Array per tracciare gli ISBN già inseriti
        
        // PASSAGGIO 1: Cerchiamo libri con copertina fisica
        foreach ($candidates as $book) {
            // Se abbiamo già 4 libri, fermati
            if (count($valid_books) >= 4) break;

            // CONTROLLO RIDONDANZA: Se l'abbiamo già preso, saltalo
            if (in_array($book['isbn'], $used_isbns)) {
                continue;
            }

            // Verifica percorso (Logica corretta: public/bookCover/ISBN.png)
            $checkPath = "public/bookCover/" . $book['isbn'] . ".png";
            
            if (file_exists($checkPath)) {
                $valid_books[] = $book;
                $used_isbns[] = $book['isbn']; // Segniamo l'ISBN come usato
            }
        }

        // PASSAGGIO 2 (FALLBACK): Se non ne abbiamo trovati 4 con copertina, riempiamo i buchi
        if (count($valid_books) < 4) {
            foreach ($candidates as $book) {
                // Se abbiamo raggiunto 4, fermati
                if (count($valid_books) >= 4) break;

                // CONTROLLO RIDONDANZA: Fondamentale anche qui
                if (in_array($book['isbn'], $used_isbns)) {
                    continue; 
                }

                // Aggiungiamo il libro (avrà il placeholder)
                $valid_books[] = $book;
                $used_isbns[] = $book['isbn'];
            }
        }

        $final_data = $valid_books;

        // 3. PREPARAZIONE DATI DA MESCOLARE
        $titles = []; $authors = []; $genres = [];

        foreach ($valid_books as $book) {
            $titles[]  = ['text' => $book['titolo'], 'isbn' => $book['isbn']];
            $authors[] = ['text' => $book['nome'] . ' ' . $book['cognome'], 'isbn' => $book['isbn']];
            $genres[]  = ['text' => $book['categoria'], 'isbn' => $book['isbn']];
        }

        shuffle($titles);
        shuffle($authors);
        shuffle($genres);

        $shuffled_data = ['titles' => $titles, 'authors' => $authors, 'genres' => $genres];

    } catch (Exception $e) {
        $messaggio_db = "Errore: " . $e->getMessage();
    }
}
?>

<?php
// ---------------- 2. HTML OUTPUT ----------------
$title = "Game - Biblioteca Scrum";
$path = "./"; 

// Include Header/Navbar
if(file_exists('./src/includes/header.php')) {
    require './src/includes/header.php';
    require './src/includes/navbar.php';
} else {
    require '../src/includes/header.php';
    require '../src/includes/navbar.php';
}
?>

<style>
    .game_con { padding: 20px; max-width: 1200px; margin: 0 auto; text-align: center; font-family: 'Instrument Sans', sans-serif; }
    
    /* Titoli e Pulsanti */
    .game_title { font-family: 'Young Serif', serif; margin-bottom: 10px; color: #333; }
    .btn-classifica {
        display: inline-block;
        padding: 8px 16px;
        background-color: #333;
        color: #fff;
        text-decoration: none;
        border-radius: 20px;
        font-size: 0.9em;
        margin-bottom: 30px;
        transition: background 0.3s;
    }
    .btn-classifica:hover { background-color: #555; }

    /* Layout Libri (Target) */
    .books-container { display: flex; justify-content: space-around; gap: 15px; margin-bottom: 40px; }
    .book-column { width: 23%; display: flex; flex-direction: column; align-items: center; background: #f9f9f9; padding: 10px; border-radius: 8px; border: 2px solid #ddd; }
    .book-cover-img { width: 100px; height: 140px; object-fit: cover; border-radius: 5px; margin-bottom: 10px; box-shadow: 2px 2px 5px rgba(0,0,0,0.2); }
    
    /* Dropzone */
    .book-dropzone { width: 100%; min-height: 180px; background: #e9ecef; border: 2px dashed #adb5bd; border-radius: 5px; padding: 5px; display: flex; flex-direction: column; gap: 5px; transition: background 0.3s, border-color 0.3s; }
    
    /* Stati Dropzone */
    .book-dropzone.ready-check-correct { background-color: #d4edda; border-color: #28a745; border-style: solid; }
    .book-dropzone.ready-check-wrong { background-color: #f8d7da; border-color: #dc3545; border-style: solid; }

    /* Elementi Trascinabili */
    .draggable-item { 
        background: white; border: 1px solid #007bff; padding: 8px; border-radius: 4px; 
        cursor: grab; font-size: 0.9em; user-select: none; box-shadow: 1px 1px 3px rgba(0,0,0,0.1); 
        width: 90%; margin: 0 auto; 
    }
    .draggable-item:active { cursor: grabbing; }
    
    /* Elemento Bloccato (Vinto) */
    .locked-item { background: #28a745; color: white; border-color: #1e7e34; pointer-events: none; }

    /* Contenitori Sorgente */
    .source-row { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; min-height: 50px; padding: 10px; border-top: 1px solid #eee; margin-top: 10px; background: #fff; }
    .source-label { font-weight: bold; width: 100%; margin-bottom: 5px; color: #555; text-transform: uppercase; }
</style>

<div class="game_con">
    
    <h1 class="game_title">Indovina il Libro</h1>
    
    <a href="./classifica" class="btn-classifica">🏆 Vedi Classifica</a>

    <div class="books-container">
        <?php foreach ($final_data as $book): ?>
            <div class="book-column">
                <img src="<?= getGameCoverPath($book['isbn']) ?>" class="book-cover-img" alt="Cover">
                
                <div class="book-dropzone" data-target-isbn="<?= $book['isbn'] ?>">
                    </div>
            </div>
        <?php endforeach; ?>
    </div>

    <hr>

    <div class="source-row recycling-bin">
        <div class="source-label">Titoli</div>
        <?php foreach ($shuffled_data['titles'] as $t): ?>
            <div class="draggable-item" draggable="true" data-isbn="<?= $t['isbn'] ?>">
                <?= htmlspecialchars($t['text']) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="source-row recycling-bin">
        <div class="source-label">Autori</div>
        <?php foreach ($shuffled_data['authors'] as $a): ?>
            <div class="draggable-item" draggable="true" data-isbn="<?= $a['isbn'] ?>">
                <?= htmlspecialchars($a['text']) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="source-row recycling-bin">
        <div class="source-label">Generi</div>
        <?php foreach ($shuffled_data['genres'] as $g): ?>
            <div class="draggable-item" draggable="true" data-isbn="<?= $g['isbn'] ?>">
                <?= htmlspecialchars($g['text']) ?>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const startTime = Date.now();
    
    const draggables = document.querySelectorAll('.draggable-item');
    const dropzones = document.querySelectorAll('.book-dropzone');
    const sources = document.querySelectorAll('.recycling-bin');

    let draggedItem = null;

    // 1. GESTIONE DRAG START / END
    draggables.forEach(item => {
        item.addEventListener('dragstart', function() {
            draggedItem = this;
            setTimeout(() => this.style.opacity = '0.5', 0);
        });

        item.addEventListener('dragend', function() {
            draggedItem = null;
            this.style.opacity = '1';
        });
    });

    function handleDragOver(e) { e.preventDefault(); }

    // 2. DROP NELLE COLONNE DEI LIBRI
    dropzones.forEach(zone => {
        zone.addEventListener('dragover', handleDragOver);
        
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            if (draggedItem) {
                this.appendChild(draggedItem);
                checkZoneStatus(this);
            }
        });
    });

    // 3. DROP BACK NEI CESTINI
    sources.forEach(source => {
        source.addEventListener('dragover', handleDragOver);
        source.addEventListener('drop', function(e) {
            e.preventDefault();
            if (draggedItem) {
                this.appendChild(draggedItem);
                dropzones.forEach(zone => checkZoneStatus(zone));
            }
        });
    });

    // 4. FUNZIONE DI VERIFICA
    function checkZoneStatus(zone) {
        const items = zone.querySelectorAll('.draggable-item');
        const count = items.length;
        const targetIsbn = zone.getAttribute('data-target-isbn');

        zone.classList.remove('ready-check-correct', 'ready-check-wrong');

        if (count === 3) {
            let allCorrect = true;
            items.forEach(item => {
                if (item.getAttribute('data-isbn') !== targetIsbn) {
                    allCorrect = false;
                }
            });

            if (allCorrect) {
                zone.classList.add('ready-check-correct');
                items.forEach(item => {
                    item.setAttribute('draggable', 'false');
                    item.classList.add('locked-item');
                });
                checkGlobalWin();
            } else {
                zone.classList.add('ready-check-wrong');
            }
        }
    }

    // 5. VITTORIA TOTALE
    function checkGlobalWin() {
        if (document.querySelectorAll('.ready-check-correct').length === 4) {
            const timeTaken = Date.now() - startTime;
            
            const formData = new FormData();
            formData.append('tempo', timeTaken);

            fetch('./save-score', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log("Salvataggio OK. Redirect...");
                window.location.href = './classifica';
            })
            .catch(error => {
                console.error("Errore salvataggio:", error);
                window.location.href = './classifica';
            });
        }
    }
});
</script>

<?php 
if(file_exists('./src/includes/footer.php')) {
    require './src/includes/footer.php';
} else {
    require '../src/includes/footer.php';
}
?>