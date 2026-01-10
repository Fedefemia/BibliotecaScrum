<?php
require_once 'security.php';

// Controllo accessi
if (!checkAccess('amministratore') && !checkAccess('bibliotecario')) {
    header('Location: ../index.php');
    exit;
}

require_once 'db_config.php';

$prestitiAttivi = [];
if (isset($pdo)) {
    try {
        // Logica per processare la restituzione
        if (isset($_POST['restituisci_id'])) {
            $data_scelta = !empty($_POST['data_fine']) ? $_POST['data_fine'] : date('Y-m-d');
            $stmt = $pdo->prepare("UPDATE prestiti SET data_restituzione = :data WHERE id_prestito = :id");
            $stmt->execute([
                    'data' => $data_scelta,
                    'id' => $_POST['restituisci_id']
            ]);

            // REINDIRIZZAMENTO ALLA GESTIONE
            header("Location: ../bibliotecario/dashboard-gestioneprestiti?success=1");
            exit;
        }

        // Query per recuperare solo i prestiti attivi
        $query = "SELECT 
                    p.id_prestito, 
                    u.nome, 
                    u.cognome, 
                    l.titolo, 
                    p.id_copia, 
                    p.data_prestito, 
                    p.data_scadenza, 
                    p.num_rinnovi
                  FROM prestiti p
                  JOIN utenti u ON p.codice_alfanumerico = u.codice_alfanumerico
                  JOIN copie c ON p.id_copia = c.id_copia
                  JOIN libri l ON c.isbn = l.isbn
                  WHERE p.data_restituzione IS NULL
                  ORDER BY p.data_scadenza ASC";

        $prestitiAttivi = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Errore: " . $e->getMessage();
    }
}
?>

<?php
$title = "Gestione Prestiti";
$path = "../";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

<div class="page_contents">
    <h2>Gestione Prestiti</h2>

    <?php if (isset($_GET['success'])): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border: 1px solid #c3e6cb; border-radius: 5px;">
            Restituzione registrata con successo!
        </div>
    <?php endif; ?>

    <p>
        <a href="../bibliotecario/dashboard-aggiuntaprestiti">Registra Nuovo Prestito</a>
    </p>

    <table border="1" style="width: 100%; border-collapse: collapse;">
        <thead>
        <tr style="background-color: #f8f9fa;">
            <th>ID</th>
            <th>Utente</th>
            <th>Libro (ID Copia)</th>
            <th>Inizio</th>
            <th>Scadenza</th>
            <th>Rinnovi</th>
            <th>Data Restituzione</th>
            <th>Azioni</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($prestitiAttivi)): ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px;">Nessun prestito attivo.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($prestitiAttivi as $p): ?>
                <tr>
                    <td><?= $p['id_prestito'] ?></td>
                    <td><?= htmlspecialchars($p['nome'] . ' ' . $p['cognome']) ?></td>
                    <td><?= htmlspecialchars($p['titolo']) ?> (ID: <?= $p['id_copia'] ?>)</td>
                    <td><?= $p['data_prestito'] ?></td>
                    <td><?= $p['data_scadenza'] ?></td>
                    <td><?= $p['num_rinnovi'] ?></td>
                    <form method="POST">
                        <td>
                            <input type="date" name="data_fine" value="<?= date('Y-m-d') ?>">
                        </td>
                        <td>
                            <input type="hidden" name="restituisci_id" value="<?= $p['id_prestito'] ?>">
                            <button type="submit" onclick="return confirm('Confermi la restituzione?')">Restituisci</button>

                            <a href="Rinnovo-prestito.php?id=<?= $p['id_prestito'] ?>" style="margin-left: 10px;">
                                Rinnovo
                            </a>

                            <a href="gestione-multe.php?id_prestito=<?= $p['id_prestito'] ?>" style="margin-left: 10px;">
                                Multe
                            </a>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once './src/includes/footer.php'; ?>

