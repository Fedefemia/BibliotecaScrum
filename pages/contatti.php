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

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contatti - Rete Biblioteche Vicentine</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
        <p><em>Clicca su un marker per visualizzare i dettagli e gli orari di apertura della biblioteca.</em></p>
        <div id="map" style="height: 600px; width: 80%; margin: auto;"></div>
    </div>
</main>

<script>
    // Inizializza la mappa centrata sulla provincia di Vicenza
    const map = L.map('map').setView([45.5470, 11.5396], 10);

    // Aggiungi tile layer di OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Orari standard per tutte le biblioteche
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

    // Array esteso di biblioteche con coordinate
    const biblioteche = [
        // Vicenza città
        { nome: "Biblioteca Bertoliana - Palazzo San Giacomo", indirizzo: "Contrà Riale, 5, Vicenza", lat: 45.5470, lon: 11.5396 },
        { nome: "Biblioteca Palazzo Costantini", indirizzo: "Contrà Riale, 13, Vicenza", lat: 45.5475, lon: 11.5398 },
        { nome: "Biblioteca Anconetta", indirizzo: "Via Dall'Acqua, 16, Vicenza", lat: 45.5580, lon: 11.5450 },
        { nome: "Biblioteca Laghetto", indirizzo: "Via Lago di Pusiano, 3, Vicenza", lat: 45.5320, lon: 11.5250 },
        { nome: "Biblioteca Riviera Berica", indirizzo: "Via Riviera Berica, 631, Vicenza", lat: 45.5200, lon: 11.5650 },
        { nome: "Biblioteca Villa Tacchi", indirizzo: "Viale della Pace, 89, Vicenza", lat: 45.5620, lon: 11.5280 },
        { nome: "Biblioteca Villaggio del Sole", indirizzo: "Via Colombo, 41/A, Vicenza", lat: 45.5580, lon: 11.5600 },

        // Altri comuni principali
        { nome: "Biblioteca Asiago", indirizzo: "Piazza Carli, Asiago", lat: 45.8733, lon: 11.5092 },
        { nome: "Biblioteca Arzignano", indirizzo: "Via Spagnolo, 2, Arzignano", lat: 45.5176, lon: 11.3366 },
        { nome: "Biblioteca Bassano del Grappa", indirizzo: "Via Roma, 130, Bassano del Grappa", lat: 45.7664, lon: 11.7340 },
        { nome: "Biblioteca Breganze", indirizzo: "Piazza Mazzini, Breganze", lat: 45.7083, lon: 11.5667 },
        { nome: "Biblioteca Brendola", indirizzo: "Piazza Marconi, Brendola", lat: 45.4833, lon: 11.4167 },
        { nome: "Biblioteca Caldogno", indirizzo: "Piazza Dante, Caldogno", lat: 45.5917, lon: 11.5167 },
        { nome: "Biblioteca Cassola", indirizzo: "Piazza Martiri, Cassola", lat: 45.7333, lon: 11.8000 },
        { nome: "Biblioteca Chiampo", indirizzo: "Piazza Zanini, Chiampo", lat: 45.5500, lon: 11.2833 },
        { nome: "Biblioteca Cornedo Vicentino", indirizzo: "Via Garibaldi, Cornedo Vicentino", lat: 45.6167, lon: 11.3333 },
        { nome: "Biblioteca Creazzo", indirizzo: "Piazza Mazzini, Creazzo", lat: 45.5333, lon: 11.4833 },
        { nome: "Biblioteca Dueville", indirizzo: "Piazza Monza, Dueville", lat: 45.6167, lon: 11.5500 },
        { nome: "Biblioteca Lonigo", indirizzo: "Corso Padova, 50, Lonigo", lat: 45.3881, lon: 11.3867 },
        { nome: "Biblioteca Malo", indirizzo: "Piazza Zanini, Malo", lat: 45.6667, lon: 11.4000 },
        { nome: "Biblioteca Marostica", indirizzo: "Piazza Castello, Marostica", lat: 45.7500, lon: 11.6500 },
        { nome: "Biblioteca Montecchio Maggiore", indirizzo: "Piazza Marconi, 1, Montecchio Maggiore", lat: 45.5097, lon: 11.4093 },
        { nome: "Biblioteca Mussolente", indirizzo: "Piazza Marconi, Mussolente", lat: 45.7833, lon: 11.7833 },
        { nome: "Biblioteca Nove", indirizzo: "Piazza De Fabris, Nove", lat: 45.7333, lon: 11.7000 },
        { nome: "Biblioteca Romano d'Ezzelino", indirizzo: "Via Cavin, Romano d'Ezzelino", lat: 45.7833, lon: 11.7667 },
        { nome: "Biblioteca Rosà", indirizzo: "Via Schiavonetti, Rosà", lat: 45.7167, lon: 11.7667 },
        { nome: "Biblioteca Sandrigo", indirizzo: "Piazza Zanellato, Sandrigo", lat: 45.6667, lon: 11.6000 },
        { nome: "Biblioteca Santorso", indirizzo: "Piazza Zanchi, Santorso", lat: 45.7500, lon: 11.3833 },
        { nome: "Biblioteca Schio", indirizzo: "Piazza Rossi, 23, Schio", lat: 45.7137, lon: 11.3556 },
        { nome: "Biblioteca Thiene", indirizzo: "Corso Garibaldi, 55, Thiene", lat: 45.7068, lon: 11.4797 },
        { nome: "Biblioteca Valdagno", indirizzo: "Via Luigi Marzotto, 17, Valdagno", lat: 45.6461, lon: 11.2987 },
        { nome: "Biblioteca Zanè", indirizzo: "Piazza Lioy, Zanè", lat: 45.7167, lon: 11.4667 }
    ];

    // Aggiungi marker per ogni biblioteca
    biblioteche.forEach(bib => {
        const marker = L.marker([bib.lat, bib.lon]).addTo(map);

        // Popup con informazioni e orari
        const popupContent = `
                <div style="min-width: 250px;">
                    <strong style="font-size: 14px;">${bib.nome}</strong><br>
                    <span style="font-size: 12px; color: #666;">${bib.indirizzo}</span><br><br>
                    <div style="font-size: 12px; line-height: 1.6;">
                        ${orariStandard}
                    </div>
                </div>
            `;

        marker.bindPopup(popupContent);
    });
</script>
</body>
</html>

<!-- FINE DEL BODY -->

<?php require_once './src/includes/footer.php'; ?>
