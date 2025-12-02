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
        body { font-family: sans-serif; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .log-box { background: #eee; padding: 10px; border-left: 5px solid green; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #333; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>Test Connessione Database</h1>
    
    <div class="log-box">
        <?php echo $messaggio_db; ?>
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
            // --- 2. TEST LETTURA (SELECT) ---
            // Scarichiamo i dati dal DB e li mostriamo in tabella
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
            ?>
        </tbody>
    </table>
    
    <p style="text-align: center; margin-top: 20px;">
        <small>Ricarica la pagina per vedere l'ID aumentare!</small>
    </p>
</div>

</body>
</html>
