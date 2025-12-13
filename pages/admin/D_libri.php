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

        // ELIMINA
        if (isset($_POST['delete_id'])) {
            $stmt = $pdo->prepare("DELETE FROM libri WHERE isbn = :isbn");
            $stmt->execute(['isbn' => $_POST['delete_id']]);
            header("Location: "."dashboard-libri");
            exit;

        }

        // SALVA MODIFICA
        if (isset($_POST['edit_id'])) {
            $stmt = $pdo->prepare("
            UPDATE libri 
            SET titolo = :titolo, descrizione = :descrizione, ean = :ean
            WHERE isbn = :isbn
        ");
            $stmt->execute([
                    'titolo' => $_POST['titolo'],
                    'descrizione' => $_POST['descrizione'],
                    'ean' => $_POST['ean'],
                    'isbn' => $_POST['edit_id']
            ]);
            header("Location: "."dashboard-libri");
            exit;

        }

        //AGGIUNGI
        if (isset($_POST['inserisci'])) {
            $stmt = $pdo->prepare("
            INSERT INTO libri(isbn,titolo,descrizione,ean)
            values (:isbn,:titolo,:descrizione,:ean)
        ");
            $stmt->execute([
                    'titolo' => $_POST['titolo'],
                    'descrizione' => $_POST['descrizione'],
                    'ean' => $_POST['ean'],
                    'isbn' => $_POST['isbn']
            ]);
            header("Location: "."dashboard-libri");
            exit;

        }

        $stmt = $pdo->prepare("SELECT * FROM libri");
        $stmt->execute();
        $libri = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <th>Isbn</th>
            <th>Titolo</th>
            <th>Descrizione</th>
            <th>Ean</th>
            <th>Azioni</th>
        </tr>
        <tr>
            <form method="post">
                <td><input type="text" placeholder="isbn" name="isbn" required></td>
                <td><input type="text" placeholder="titolo" name="titolo" required></td>
                <td><input type="text" placeholder="descrizione" name="descrizione" required></td>
                <td><input type="text" placeholder="ean" name="ean" required></td>
                <input type="hidden" name="inserisci" value="1">
                <td><input type="submit" value="inserisci"></td>
            </form>
        </tr>
    </table>

    <table>
        <tr>

            <th>Titolo</th>
            <th>Descrizione</th>
            <th>Ean</th>
            <th>Azioni</th>
        </tr>

        <?php foreach ($libri as $b): ?>
            <tr>
                <form method="POST">
                    <td>
                        <input type="text" name="titolo"
                               value="<?= htmlspecialchars($b['titolo']) ?>">
                    </td>

                    <td>
                        <input type="text" name="descrizione"
                               value="<?= htmlspecialchars($b['descrizione']) ?>">
                    </td>
                    <td>
                        <input type="text" name="ean"
                               value="<?= htmlspecialchars($b['ean']) ?>">
                    </td>

                    <td>
                        <!-- SALVA -->
                        <input type="hidden" name="edit_id" value="<?= $b['isbn'] ?>">
                        <button type="submit">Salva</button>
                </form>

                <!-- ELIMINA -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $b['isbn'] ?>">
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