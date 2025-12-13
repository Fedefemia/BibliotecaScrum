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

        //guarda se l'utente è un amministratore
        /*
        $stmt = $pdo->prepare("select * from utenti where name = :name
                                join ruoli on utenti.alfanumerico = ruoli.alfanumerico
                                having ruoli.amministratore = 1");
        $stmt->execute([':name' => $nome_visitatore]);
        $IsAmministratore = $stmt->fatchall();

        if(isset($IsAmministratore[0])){*/
        $stmt = $pdo->prepare("SELECT * FROM biblioteche");
        $stmt->execute();
        $biblioteche = $stmt->fetchAll(PDO::FETCH_ASSOC);
        /*}else{
            header("Location: ./index");
        }*/

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


<?php require_once './src/includes/header.php'; ?>
<?php require_once './src/includes/navbar.php'; ?>

<!-- INIZIO DEL BODY -->

<div class="page_contents">

    <?php if (!empty($biblioteche)): ?>
        <table border="1">
            <tr>
                <th>Id</th>
                <th>Nome</th>
                <th>Indirizzo</th>
                <th>Lat</th>
                <th>Lon</th>
                <th>Orari</th>
                <th>Modifica</th>
                <th>Elmina</th>

            </tr>

            <?php $orari = 0; foreach ($biblioteche as $biblioteca): ?>
                <tr>
                    <td><?= htmlspecialchars($biblioteca['id']) ?></td>
                    <td><?= htmlspecialchars($biblioteca['nome']) ?></td>
                    <td><?= htmlspecialchars($biblioteca['indirizzo']) ?></td>
                    <td><?= htmlspecialchars($biblioteca['lat']) ?></td>
                    <td><?= htmlspecialchars($biblioteca['lon']) ?></td>
                    <td><?php if($biblioteca['orari'] == null){
                            $orari = "orari standard";
                        }else{
                            $orari= htmlspecialchars($biblioteca['orari']);
                        }?> <?= $orari?> </td>
                   <td><button id="modifica<?=$biblioteca['id']?>">Modifica</button></td>
                    <td><button id="elimina<?=$biblioteca['id']?>">Elimina</button></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nessuna biblioteca trovata.</p>
    <?php endif; ?>


</div>


<?php require_once './src/includes/footer.php'; ?>
<style>
    th, td {
        padding: 15px;
        border: solid 1px black;
    }
</style>

<script>

</script>
