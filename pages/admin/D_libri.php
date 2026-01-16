
<?php
/**
 * Questo blocco DEVE essere in cima al file D_libri.php
 */
if (isset($_GET['generate_barcode'])) {
    // 1. Eliminiamo qualsiasi output sporco accumulato dal Router o da db_config
    // Questo è fondamentale perché il router include questo file "dentro" la sua esecuzione
    while (ob_get_level()) {
        ob_end_clean();
    }

    // 2. Percorso relativo alla root (dove si trova vendor)
    // Poiché il file è in pages/admin/D_libri.php, usiamo ../../
    require_once __DIR__ . '/../../vendor/autoload.php';

    $isbn = $_GET['isbn'] ?? '';
    // Pulizia dell'ISBN da caratteri non numerici
    $isbn = preg_replace('/[^0-9]/', '', $isbn);

    if ((strlen($isbn) === 10 || strlen($isbn) === 13)) {
        try {
            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();

            // 3. Header specifici per evitare il caching di immagini corrotte
            header('Content-Type: image/png');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');

            // Generazione barcode
            if (strlen($isbn) === 13) {
                echo $generator->getBarcode($isbn, $generator::TYPE_EAN_13);
            } else {
                echo $generator->getBarcode($isbn, $generator::TYPE_CODE_128);
            }
        } catch (Exception $e) {
            // Se fallisce la libreria, creiamo un'immagine con l'errore
            header('Content-Type: image/png');
            $img = imagecreate(150, 30);
            imagecolorallocate($img, 255, 255, 255);
            $text = imagecolorallocate($img, 255, 0, 0);
            imagestring($img, 2, 5, 5, "Library Error", $text);
            imagepng($img);
            imagedestroy($img);
        }
    }
    // 4. Blocca l'esecuzione: non vogliamo che il router continui a caricare HTML
    exit;
}

/**
 * LOGICA NORMALE DELLA PAGINA
 */
require_once 'security.php';
if (!checkAccess('amministratore')) header('Location: ./');

    $isbn = preg_replace('/[^0-9]/', '', $_GET['isbn'] ?? '');

// ... il resto del tuo codice per il database e l'HTML ...

$stmt = $pdo->prepare("SELECT * FROM libri ORDER BY anno_pubblicazione DESC");
$stmt->execute();
$libri = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = "Dashboard Catalogo Libri";
$path = "../";
require_once './src/includes/header.php';
require_once './src/includes/navbar.php';
?>

<div class="page_contents">
    <h2>Libri nel catalogo</h2>
    <table style="width:100%; border-collapse: collapse;">
        <thead>
        <tr>
            <th style="border: 1px solid #000; padding: 10px;">Barcode</th>
            <th style="border: 1px solid #000; padding: 10px;">Titolo</th>
            <th style="border: 1px solid #000; padding: 10px;">Azioni</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($libri as $b): ?>
            <tr>
                <td style="text-align: center; border: 1px solid #000; padding: 10px;">
                    <img src="dashboard-libri?generate_barcode=1&isbn=<?= htmlspecialchars($b['isbn']) ?>"
                         alt="Barcode"
                         style="display: block; margin: 0 auto; max-width: 150px; height: auto;">
                    <div style="margin-top: 5px; font-weight: bold;"><?= htmlspecialchars($b['isbn']) ?></div>
                </td>
                <td style="border: 1px solid #000; padding: 10px;">
                    <?= htmlspecialchars($b['titolo']) ?>
                </td>
                <td style="border: 1px solid #000; padding: 10px; text-align: center;">
                    <button>Salva</button>
                    <button>Elimina</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once './src/includes/footer.php'; ?>
