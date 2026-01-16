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

<?php
// ---------------- HTML HEADER ----------------
$title = "Contatti - Biblioteca Scrum";
$path = "./";
$page_css = "./public/css/style_index.css";
require './src/includes/header.php';
require './src/includes/navbar.php';
?>

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
<header class="index_hero">
    <img src="./public/assets/icone_categorie/Location.png" alt="Logo" class="hero_icon">
    <h1 class="hero_title">Contatti</h1>
</header>

<div class="infos_card_row instrument-sans">
    <div class="infos_card_row_div1 infos_card_row_card">
        <p><strong>Telefono:</strong> +39 0444 908 111</p>
    </div>
    <div class="infos_card_row_div2 infos_card_row_card">
        <p><strong>Email:</strong> <a href="mailto:info@rbv.biblioteche.it" style="color: inherit; text-decoration: underline;">info@rbv.biblioteche.it</a></p>
    </div>
</div>

<div class="map_section">
    <h2 class="map_title">Mappa delle Biblioteche</h2>
    <p class="map_subtitle"><em>Clicca su un marker per vedere informazioni e orari</em></p>

    <div id="map"></div>
</div>

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

    // Pulsante "Centra mappa"
    const ResetControl = L.Control.extend({
        options: { position: 'topleft' },
        onAdd: function () {
            const container = L.DomUtil.create('div', 'leaflet-control-resetmap');
            container.innerHTML = "Centra mappa";
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

<?php require_once './src/includes/footer.php'; ?>
