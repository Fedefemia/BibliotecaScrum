<?php

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


// Carica biblioteche
$lista_biblioteche = [];
try {
    $stmt = $pdo->query("SELECT nome, indirizzo, lat, lon, orari FROM biblioteche");
    //PDO::FETCH_ASSOC serve così PHP restituisce solo l’array associativo
    $lista_biblioteche = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $messaggio_db = "Errore biblioteche: " . $e->getMessage();
}
?>

<?php require_once './src/includes/header.php'; ?>
<?php require_once './src/includes/navbar.php'; ?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contatti - Rete Biblioteche Vicentine</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        .leaflet-control-resetmap {
            background: white;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #888;
            cursor: pointer;
            font-size: 13px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            margin-top: 5px;
        }
        .leaflet-control-resetmap:hover {
            background: #f0f0f0;
        }
    </style>
</head>

<body>

<header>
    <h1>Contatti</h1>
    <p>Rete Biblioteche Vicentine</p>
</header>

<main>

    <div>
        <h2>Informazioni di Contatto</h2>
        <p><strong>Telefono:</strong> +39 0444 908 111</p>
        <p><strong>Email:</strong> <a href="mailto:info@rbv.biblioteche.it">info@rbv.biblioteche.it</a></p>
    </div>

    <div>
        <h2>Mappa delle Biblioteche della Provincia</h2>
        <p><em>Clicca su un marker per vedere informazioni e orari.</em></p>

        <div id="map" style="height: 600px; width: 90%; margin:auto;"></div>
    </div>

</main>

<script>
    // Biblioteche dal DB
    const biblioteche = <?php echo json_encode($lista_biblioteche, JSON_UNESCAPED_UNICODE); ?>;

    // Limiti del Veneto
    const boundsVeneto = L.latLngBounds([44.7, 10.5], [46.8, 13.2]);

    // Inizializza mappa
    const map = L.map('map', {
        center: [45.5470, 11.5396],
        zoom: 10,
        minZoom: 9,
        maxZoom: 19,
        maxBounds: boundsVeneto,
        maxBoundsViscosity: 1.0
    });

    // Tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Pulsante "Riaccentra mappa"
    const ResetControl = L.Control.extend({
        options: { position: 'topleft' },
        onAdd: function () {
            const container = L.DomUtil.create('div', 'leaflet-control-resetmap');
            container.innerHTML = "Riaccentra mappa";
            container.onclick = () => map.setView([45.5470, 11.5396], 10);
            L.DomEvent.disableClickPropagation(container);
            return container;
        }
    });
    map.addControl(new ResetControl());

    // Orari standard
    const orariStandard = `
        <strong>Orari di apertura:</strong><br>
        Lunedì: 14:00 - 19:00<br>
        Martedì: 9:00 - 13:00, 14:00 - 19:00<br>
        Mercoledì: 9:00 - 13:00, 14:00 - 19:00<br>
        Giovedì: 9:00 - 13:00, 14:00 - 19:00<br>
        Venerdì: 9:00 - 13:00, 14:00 - 19:00<br>
        Sabato: 9:00 - 13:00<br>
        Domenica: Chiuso
    `;

    // Marker dal database
    biblioteche.forEach(bib => {
        const marker = L.marker([bib.lat, bib.lon]).addTo(map);

        const popup = `
            <div style="min-width: 250px;">
                <strong style="font-size: 14px;">${bib.nome}</strong><br>
                <span style="font-size: 12px; color: #666;">${bib.indirizzo}</span><br><br>
                <div style="font-size: 12px; line-height: 1.6;">
                    <!--se il campo orari è messo a nul allora prende gli orari standard -->
                    ${bib.orari ? bib.orari : orariStandard}
                </div>
            </div>
        `;

        marker.bindPopup(popup);
    });
</script>

</body>
</html>

<?php require_once './src/includes/footer.php'; ?>
