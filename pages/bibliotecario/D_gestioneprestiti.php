<?php
require_once 'security.php';

if (!checkAccess('amministratore') && !checkAccess('bibliotecario')) {
    header('Location: ./');
    exit;
}

require_once 'db_config.php';

$prestitiAttivi = [];
if (isset($pdo)) {
    try {
        if (isset($_POST['restituisci_id'])) {
            $data_scelta = !empty($_POST['data_fine']) ? $_POST['data_fine'] : date('Y-m-d');
            $stmt = $pdo->prepare("UPDATE prestiti SET data_restituzione = :data WHERE id_prestito = :id");
            $stmt->execute([
                    'data' => $data_scelta,
                    'id' => $_POST['restituisci_id']
            ]);
            header("Location: dashboard-prestiti.php");
            exit;
        }

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

require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

    <div class="page_contents">
        <h2>Gestione Prestiti</h2>

        <p>
            <a href="../bibliotecario/dashboard-aggiuntaprestiti">Registra Nuovo Prestito</a>
        </p>

        <table>
            <thead>
            <tr>
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
                    <td colspan="8">Nessun prestito attivo.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($prestitiAttivi as $p): ?>
                    <tr>
                        <td><?= $p['id_prestito'] ?></td>
                        <td><?= $p['nome'] ?> <?= $p['cognome'] ?></td>
                        <td><?= $p['titolo'] ?> (ID: <?= $p['id_copia'] ?>)</td>
                        <td><?= $p['data_prestito'] ?></td>
                        <td><?= $p['data_scadenza'] ?></td>
                        <td><?= $p['num_rinnovi'] ?></td>
                        <td>
                            <form method="POST">
                                <input type="date" name="data_fine" value="<?= date('Y-m-d') ?>">
                        </td>
                        <td>
                            <input type="hidden" name="restituisci_id" value="<?= $p['id_prestito'] ?>">
                            <button type="submit">Restituisci</button>

                            <a href="dettagli-prestito.php?id=<?= $p['id_prestito'] ?>">
                                Dettagli
                            </a>

                            <a href="gestione-multe.php?id_prestito=<?= $p['id_prestito'] ?>">
                                Multe
                            </a>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php require_once './src/includes/footer.php'; ?>