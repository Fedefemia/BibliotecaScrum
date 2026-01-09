<?php
require_once 'security.php';

if (!checkAccess('amministratore') && !checkAccess('bibliotecario')) {
    header('Location: ./');
    exit;
}

require_once 'db_config.php';

$messaggio = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    $utente = $_POST['codice_alfanumerico'];
    $copia = $_POST['id_copia'];
    $data_prestito = $_POST['data_inizio'];
    $data_scadenza = date('Y-m-d', strtotime($data_prestito . ' + 30 days'));

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO prestiti (codice_alfanumerico, id_copia, data_prestito, data_scadenza, num_rinnovi) 
                VALUES (:utente, :copia, :inizio, :fine, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
                'utente' => $utente,
                'copia'  => $copia,
                'inizio' => $data_prestito,
                'fine'   => $data_scadenza
        ]);

        $pdo->commit();
        $messaggio = "Prestito registrato con successo!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $messaggio = "Errore: " . $e->getMessage();
    }
}

$utenti = [];
$libri_disponibili = [];

if (isset($pdo)) {
    try {
        $utenti = $pdo->query("SELECT codice_alfanumerico, nome, cognome FROM utenti ORDER BY cognome ASC")->fetchAll(PDO::FETCH_ASSOC);

        $queryCopie = "SELECT c.id_copia, l.titolo 
                       FROM copie c
                       JOIN libri l ON c.isbn = l.isbn
                       WHERE c.id_copia NOT IN (SELECT id_copia FROM prestiti WHERE data_restituzione IS NULL)
                       ORDER BY l.titolo ASC";
        $libri_disponibili = $pdo->query($queryCopie)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messaggio = "Errore caricamento dati: " . $e->getMessage();
    }
}

require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <div class="page_contents">
        <h2>Registra Nuovo Prestito</h2>

        <?php if ($messaggio): ?>
            <p><strong><?= $messaggio ?></strong></p>
        <?php endif; ?>

        <form method="POST">
            <div>
                <label>Cerca Utente (Nome, Cognome o Codice):</label><br>
                <input list="lista_utenti" name="codice_alfanumerico" placeholder="Scrivi per filtrare..." required style="width: 300px;">
                <datalist id="lista_utenti">
                    <?php foreach ($utenti as $u): ?>
                        <option value="<?= $u['codice_alfanumerico'] ?>">
                            <?= $u['cognome'] ?> <?= $u['nome'] ?>
                        </option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <br>

            <div>
                <label>Cerca Libro (Titolo o ID Copia):</label><br>
                <input list="lista_libri" name="id_copia" placeholder="Scrivi il titolo del libro..." required style="width: 300px;">
                <datalist id="lista_libri">
                    <?php foreach ($libri_disponibili as $l): ?>
                        <option value="<?= $l['id_copia'] ?>">
                            <?= $l['titolo'] ?>
                        </option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <br>

            <div>
                <label>Data Inizio Prestito:</label><br>
                <input type="date" name="data_inizio" value="<?= date('Y-m-d') ?>" required>
            </div>

            <br>

            <button type="submit">Conferma Prestito</button>
            <a href="../bibliotecario/dashboard-gestioneprestiti">Ritorna gestione</a>
        </form>
    </div>

<?php require_once './src/includes/footer.php'; ?>