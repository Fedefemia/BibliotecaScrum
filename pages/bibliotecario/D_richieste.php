<?php
require_once 'security.php';

if (!checkAccess('amministratore') && !checkAccess('bibliotecario')) {
    header('Location: ./');
    exit;
}

require_once 'db_config.php';

$messaggio = "";
$richieste = [];

if (isset($pdo)) {
    try {
        // Query corretta con nomi di colonna espliciti e join appropriato
        $stmt = $pdo->prepare("
            SELECT 
                rb.id_richiesta,
                rb.codice_alfanumerico,
                rb.tipo_richiesta,
                rb.id_copia,
                rb.data_richiesta,
                rb.data_scadenza_richiesta,
                rb.stato,
                COUNT(m.id_multa) as numero_multe,
                COALESCE(SUM(m.importo), 0) as totale_multe
            FROM richieste_bibliotecario rb
            LEFT JOIN multe m ON rb.codice_alfanumerico = m.codice_alfanumerico
            GROUP BY rb.id_richiesta, rb.codice_alfanumerico, rb.tipo_richiesta, 
                     rb.id_copia, rb.data_richiesta, rb.data_scadenza_richiesta, rb.stato
            ORDER BY rb.data_richiesta DESC
        ");
        $stmt->execute();
        $richieste = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messaggio = "Errore caricamento dati: " . $e->getMessage();
    }
}

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id_richiesta'])) {
        $action = $_POST['action'];
        $id_richiesta = $_POST['id_richiesta'];

        try {
            switch ($action) {
                case 'approva':
                    $stmt = $pdo->prepare("UPDATE richieste_bibliotecario SET stato = 'approvata' WHERE id_richiesta = ?");
                    $stmt->execute([$id_richiesta]);
                    break;

                case 'rifiuta':
                    $stmt = $pdo->prepare("UPDATE richieste_bibliotecario SET stato = 'rifiutata' WHERE id_richiesta = ?");
                    $stmt->execute([$id_richiesta]);
                    break;
            }


            header("Location: " ."dashboard-richieste");
            exit;
        } catch (PDOException $e) {
            $messaggio = "Errore durante l'operazione: " . $e->getMessage();
        }
    }
}
?>

<?php
$path = "../";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>
    <div>
        <?php if ($messaggio): ?>
            <div>
                <?= htmlspecialchars($messaggio) ?>
            </div>
        <?php endif; ?>

        <table>
            <tr>
                <th>Id richieste</th>
                <th>Codice alfanumerico</th>
                <th>Tipo richieste</th>
                <th>Id copia</th>
                <th>Data richiesta</th>
                <th>Data scadenza richieste</th>
                <th>Stato</th>
                <th>Multe</th>
                <th>Azioni</th>
            </tr>
            <?php if (empty($richieste)): ?>
                <tr>
                    <td colspan="9">Nessuna richiesta trovata</td>
                </tr>
            <?php else: ?>
                <?php foreach ($richieste as $richiesta): ?>
                    <tr>
                        <td><?= htmlspecialchars($richiesta['id_richiesta']) ?></td>
                        <td><?= htmlspecialchars($richiesta['codice_alfanumerico']) ?></td>
                        <td><?= htmlspecialchars($richiesta['tipo_richiesta']) ?></td>
                        <td><?= htmlspecialchars($richiesta['id_copia']) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($richiesta['data_richiesta']))) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($richiesta['data_scadenza_richiesta']))) ?></td>
                        <td><?= htmlspecialchars(ucfirst($richiesta['stato'])) ?></td>
                        <td>
                            <?php if ($richiesta['numero_multe'] > 0): ?>
                                <?= $richiesta['numero_multe'] ?> multa/e - Totale: €<?= number_format($richiesta['totale_multe'], 2, ',', '.') ?>
                            <?php else: ?>
                                Nessuna multa
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="id_richiesta" value="<?= htmlspecialchars($richiesta['id_richiesta']) ?>">

                                <?php if ($richiesta['stato'] === 'in_attesa'): ?>
                                    <button type="submit" name="action" value="approva">Approva</button>
                                    <button type="submit" name="action" value="rifiuta">Rifiuta</button>
                                <?php else: ?>
                                    Nessuna azione
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>

    <style>
        th, td {
            padding: 15px;
            border: solid 1px black;
        }
    </style>
<?php require_once './src/includes/footer.php'; ?>