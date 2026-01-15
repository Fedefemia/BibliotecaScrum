<?php
require_once 'security.php';

if (!checkAccess('amministratore') && !checkAccess('bibliotecario')) {
    header('Location: ../index.php');
    exit;
}

require_once 'db_config.php';

$id_prestito = $_GET['id_prestito'] ?? null;
$infoPrestito = null;
$messaggio = "";

if (!$id_prestito) {
    header('Location: ../bibliotecario/dashboard-gestioneprestiti');
    exit;
}

try {


    // Recupero info sul prestito
    $stmt = $pdo->prepare("SELECT p.*, u.nome, u.cognome, l.titolo 
                           FROM prestiti p
                           JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
                           JOIN copie c ON p.id_copia = c.id_copia
                           JOIN libri l ON c.isbn = l.isbn
                           WHERE p.id_prestito = :id");
    $stmt->execute(['id' => $id_prestito]);
    $infoPrestito = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$infoPrestito) {
        die("Prestito non trovato.");
    }

    // Calcolo giorni di ritardo e importo
    $oggi = new DateTime();
    $scadenza = new DateTime($infoPrestito['data_scadenza']);
    $ritardo = 0;
    $importoMulta = 0;

    if ($oggi > $scadenza && is_null($infoPrestito['data_restituzione'])) {
        $diff = $oggi->diff($scadenza);
        $ritardo = $diff->days;
        $importoMulta = $ritardo * 0.50;
    }

    // Salvataggio nel database
    if (isset($_POST['emetti_multa'])) {
        $stmtMulta = $pdo->prepare("INSERT INTO multe (id_prestito, importo, data_emissione, stato) 
                                    VALUES (:id, :importo, CURDATE(), 'da pagare')");
        $stmtMulta->execute([
            'id' => $id_prestito,
            'importo' => $_POST['importo']
        ]);
        $messaggio = "Multa registrata!";
    }

} catch (PDOException $e) {
    $messaggio = "Errore: " . $e->getMessage();
}

$title = "Gestione Multe";
$path = "../";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <div class="page_contents">
        <h2>Dettagli Sanzione</h2>
        <a href="../bibliotecario/dashboard-gestioneprestiti">Torna alla Gestione</a>

        <?php if ($messaggio): ?>
            <p><?= $messaggio ?></p>
        <?php endif; ?>

        <div>
            <h3>Info Prestito #<?= $id_prestito ?></h3>
            <p>Utente: <?= $infoPrestito['nome'] ?> <?= $infoPrestito['cognome'] ?></p>
            <p>Libro: <?= $infoPrestito['titolo'] ?></p>
            <p>Data Scadenza: <?= $infoPrestito['data_scadenza'] ?></p>

            <hr>

            <?php if ($ritardo > 0): ?>
                <p>In ritardo di <?= $ritardo ?> giorni.</p>
                <p>Importo calcolato: <?= $importoMulta ?> €</p>

                <form method="POST">
                    <label>Importo Multa:</label>
                    <input type="number" step="0.01" name="importo" value="<?= $importoMulta ?>">
                    <button type="submit" name="emetti_multa">Emetti Multa</button>
                </form>
            <?php else: ?>
                <p>Nessun ritardo rilevato.</p>
            <?php endif; ?>
        </div>
    </div>

<?php require_once './src/includes/footer.php'; ?>