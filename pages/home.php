<?php
// Includiamo la configurazione (la password è lì dentro)
require_once 'db_config.php';

// --- 1. TEST SCRITTURA (INSERT) ---
// Ogni volta che apri la pagina, salviamo un accesso
try {
    $stmt = $pdo->prepare("INSERT INTO visitatori (nome) VALUES (:nome)");
    $stmt->execute(['nome' => 'Utente Web']);
    $messaggio_db = "Nuovo accesso registrato nel DB!";
} catch (PDOException $e) {
    $messaggio_db = "Errore Scrittura: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Test Database</title>
    <style>
        /* 1. Impostiamo il layout flessibile sul body */
        body {
            display: flex;
            /* Mette gli elementi uno affianco all'altro */
            min-height: 100vh;
            /* Occupa tutta l'altezza della finestra */
            margin: 0;
            /* Rimuove i margini di default del browser */
            font-family: sans-serif;
        }

        /* 2. Stile per la colonna di sinistra (Links) */
        .sidebar {
            width: 200px;
            /* Larghezza fissa */
            background-color: #f4f4f4;
            /* Colore grigio chiaro */
            padding: 20px;
            border-right: 1px solid #ccc;
            /* Linea divisoria */
        }

        /* 3. Stile per la colonna di destra (Contenuto principale) */
        .container {
            flex: 1;
            /* Occupa tutto lo spazio rimanente */
            padding: 20px;
            overflow-y: auto;
            /* Se il contenuto è lungo, scolla solo questa parte */
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h3>Menu</h3>
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/home">Home</a></li>
            <li><a href="<?php echo BASE_URL; ?>/webhook">Aggiorna Server</a></li>
        </ul>
    </div>

    <div class="container">
        <h1>Test Connessione Database</h1>

        <div class="log-box">
            <?php echo isset($messaggio_db) ? $messaggio_db : ''; ?>
        </div>

        <h3>Ultimi 10 accessi registrati:</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Data e Ora</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Controllo se $pdo è definito per evitare errori fatali se la connessione manca
                if (isset($pdo)) {
                    try {
                        $sql = "SELECT * FROM visitatori ORDER BY id DESC LIMIT 10";
                        foreach ($pdo->query($sql) as $riga) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($riga['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($riga['nome']) . "</td>";
                            echo "<td>" . htmlspecialchars($riga['data_visita']) . "</td>";
                            echo "</tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='3'>Errore Lettura: " . $e->getMessage() . "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>Connessione al database non disponibile.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <p style="text-align: center; margin-top: 20px;">
            Ricarica la pagina per vedere l'ID aumentare!
        </p>
    </div>

</body>

</html>