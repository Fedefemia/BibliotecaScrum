<?php

require_once 'security.php';
if (!checkAccess('amministratore')) header('Location: ./');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

        // ELIMINA
        if (isset($_POST['delete_id'])) {
            $stmt = $pdo->prepare("DELETE FROM biblioteche WHERE id = :id");
            $stmt->execute(['id' => $_POST['delete_id']]);
            header("Location: "."dashboard-biblioteche");
            exit;
        }

        // SALVA MODIFICA
        if (isset($_POST['edit_id'])) {
            $stmt = $pdo->prepare("
            UPDATE biblioteche 
            SET nome = :nome, indirizzo = :indirizzo, lat = :lat, lon = :lon
            WHERE id = :id
        ");
            $stmt->execute([
                'nome' => $_POST['nome'],
                'indirizzo' => $_POST['indirizzo'],
                'lat' => $_POST['lat'],
                'lon' => $_POST['lon'],
                'id' => $_POST['edit_id']
            ]);
            header("Location: "."dashboard-biblioteche");
            exit;
        }
        //AGGIUNGI
        if (isset($_POST['inserisci'])) {
            $stmt = $pdo->prepare("
            INSERT INTO biblioteche(nome,indirizzo,lat,lon,orari)
            values (:nome,:indirizzo,:lat,:lon,:orari)
        ");
            $stmt->execute([
                    'indirizzo' => $_POST['indirizzo'],
                    'lat' => $_POST['lat'],
                    'lon' => $_POST['lon'],
                    'nome' => $_POST['nome'],
                    'orari' => $_POST['orari']
            ]);
            header("Location: "."dashboard-biblioteche");
            exit;

        }

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
    <h2>Inserisci nuovo libro</h2>

    <table style="margin-bottom: 40px">
        <tr>
            <th>Nome</th>
            <th>Indirizzo</th>
            <th>Latitudine</th>
            <th>Longitudine</th>
            <th>Orari</th>
            <th>Azioni</th>
        </tr>
        <tr>
            <form method="post">
                <td><input type="text" placeholder="nome" name="nome" required></td>
                <td><input type="text" placeholder="indirizzo" name="indirizzo" required></td>
                <td><input type="text" placeholder="lat" name="lat" required></td>
                <td><input type="text" placeholder="lon" name="lon" required></td>
                <td><input type="text" placeholder="orari" name="orari"></td>
                <input type="hidden" name="inserisci" value="1">
                <td><input type="submit" value="inserisci"></td>
            </form>
        </tr>
    </table>
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Indirizzo</th>
            <th>Latitudine</th>
            <th>Longitudine</th>
            <th>Azioni</th>
        </tr>

        <?php foreach ($biblioteche as $b): ?>
            <tr>
                <form method="POST">
                    <td>
                        <?= htmlspecialchars($b['id']) ?>
                    </td>
                    <td>
                        <input type="text" name="nome"
                               value="<?= htmlspecialchars($b['nome']) ?>">
                    </td>

                    <td>
                        <input type="text" name="indirizzo"
                               value="<?= htmlspecialchars($b['indirizzo']) ?>">
                    </td>
                    <td>
                        <input type="text" name="lat"
                               value="<?= htmlspecialchars($b['lat']) ?>">
                    </td>
                    <td>
                        <input type="text" name="lon"
                               value="<?= htmlspecialchars($b['lon']) ?>">
                    </td>

                    <td>
                        <!-- SALVA -->
                        <input type="hidden" name="edit_id" value="<?= $b['id'] ?>">
                        <button type="submit">Salva</button>
                </form>

                <!-- ELIMINA -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $b['id'] ?>">
                    <button type="submit"
                            onclick="return confirm('Eliminare questa biblioteca?')">
                        Elimina
                    </button>
                </form>
                </td>
            </tr>
        <?php endforeach; ?>

    </table>



</div>


<?php require_once './src/includes/footer.php'; ?>
<style>
    th, td {
        padding: 15px;
        border: solid 1px black;
    }
</style>