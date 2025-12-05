<?php
// 1. IMPORTANTE: Avviamo la sessione per vedere se l'utente è loggato
session_start();

// Includiamo la configurazione
require_once 'db_config.php';

// Inizializziamo il messaggio per evitare errori "Undefined variable"
$messaggio_db = ""; 

// --- 1. TEST SCRITTURA (INSERT) ---
// Eseguiamo l'INSERT solo se la connessione ($pdo) esiste
if (isset($pdo)) {
    try {
        // Se l'utente è loggato, usiamo il suo nome nel DB, altrimenti "Utente Web"
        $nome_visitatore = isset($_SESSION['username']) ? $_SESSION['username'] . ' (Logged)' : 'Utente Web';
        
        $stmt = $pdo->prepare("INSERT INTO visitatori (nome) VALUES (:nome)");
        $stmt->execute(['nome' => $nome_visitatore]);
        $messaggio_db = "Nuovo accesso registrato nel DB!";
        $class_messaggio = "success"; 
    } catch (PDOException $e) {
        $messaggio_db = "Errore Scrittura: " . $e->getMessage();
        $class_messaggio = "error"; 
    }
} else {
    $messaggio_db = "Connessione al Database non riuscita (controlla db_config.php).";
    $class_messaggio = "error";
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Test Database</title>
    <style>
        /* --- LAYOUT GENERALE --- */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background-color: #f4f4f4; /* Sfondo grigio chiaro per tutta la pagina */
        }

        /* --- CONTENUTO CENTRALE --- */
        /* Ora che abbiamo la navbar, il contenitore deve essere centrato e non flex */
        .container {
            max-width: 1000px; /* Larghezza massima per non disperdere il contenuto */
            margin: 30px auto; /* Centrato orizzontalmente */
            padding: 30px;
            background-color: white; /* Sfondo bianco tipo "foglio" */
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); /* Ombretta elegante */
        }

        h1 { margin-top: 0; color: #2c3e50; }

        /* --- LOG BOX (MESSAGGI DB) --- */
        .log-box {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
            display: block; /* Occupa tutta la larghezza disponibile */
        }
        
        .log-box.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .log-box.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* --- TABELLA --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            border: 1px solid #ddd;
        }

        th, td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }

        th { 
            background-color: #2c3e50; /* Blu scuro in linea con la navbar */
            color: white; 
            text-transform: uppercase;
            font-size: 0.9em;
        }

        tr:nth-child(even) { background-color: #f9f9f9; } /* Righe alterne */
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>

<body>

    <?php require_once './src/includes/navbar.php'; ?>

    <div class="container">
        <h1>Test Connessione Database</h1>

        <?php if (!empty($messaggio_db)): ?>
            <div class="log-box <?php echo $class_messaggio; ?>">
                <?php echo $messaggio_db; ?>
            </div>
        <?php endif; ?>

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
                        echo "<tr><td colspan='3' style='color:red;'>Errore Lettura: " . $e->getMessage() . "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center;'>⚠️ Connessione al database non disponibile.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <p style="text-align: center; margin-top: 30px; color: #777;">
            <em>Ricarica la pagina per generare un nuovo inserimento.</em>
        </p>
    </div>

</body>
</html>