<?php
// ... [CONFIGURAZIONE DB e VARIABILI COME PRIMA] ...
$host = 'localhost';
$db   = 'database_sito';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Configurazione Logica
$giorni_validita_assegnazione = 2; // Giorni di tempo per ritirare dopo l'assegnazione
$importo_base_multa = 1.00;
$incremento_multa = 0.50;

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Errore di connessione: " . $e->getMessage());
}

function creaNotifica($pdo, $codice, $titolo, $messaggio, $tipo, $link = null) {
    $stmt = $pdo->prepare("INSERT INTO notifiche (codice_alfanumerico, titolo, messaggio, tipo, link_riferimento, dataora_invio, dataora_scadenza) VALUES (?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))");
    $stmt->execute([$codice, $titolo, $messaggio, $tipo, $link]);
}

// ... [SEZIONI 1, 2, 3, 4 (Pulizia, Prestiti, Multe) RIMANGONO INVARIATE] ...
// (Per brevità non le ricopio tutte, assumo siano uguali al messaggio precedente fino alla sezione 5)


// --------------------------------------------------------------------------
// 5. GESTIONE PRENOTAZIONI E SCORRIMENTO CODA (Logica Nuova)
// --------------------------------------------------------------------------

// A. Avviso scadenza (Domani scade la tua assegnazione)
// Se data_assegnazione esiste ed è domani il termine (data_ass + 2gg)
$stmt = $pdo->query("SELECT * FROM prenotazioni WHERE data_assegnazione IS NOT NULL AND DATE_ADD(data_assegnazione, INTERVAL $giorni_validita_assegnazione DAY) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
while ($row = $stmt->fetch()) {
    creaNotifica($pdo, $row['codice_alfanumerico'], "Prenotazione in scadenza", "Hai tempo solo fino a domani per ritirare la copia assegnata.", "avviso", "/prenotazioni");
}

// B. CANCELLAZIONE E SCORRIMENTO (Scadute oggi)
// Cerchiamo le prenotazioni ASSEGNATE che sono scadute (passati 2 giorni dall'assegnazione senza ritiro)
$sql_scadute = "SELECT * FROM prenotazioni 
                WHERE data_assegnazione IS NOT NULL 
                AND DATE_ADD(data_assegnazione, INTERVAL $giorni_validita_assegnazione DAY) < CURDATE()";
$stmt = $pdo->query($sql_scadute);
$prenotazioni_scadute = $stmt->fetchAll();

foreach ($prenotazioni_scadute as $pren) {
    $id_prenotazione_scaduta = $pren['id_prenotazione'];
    $id_copia = $pren['id_copia'];
    $codice_utente_scaduto = $pren['codice_alfanumerico'];

    // 1. Notifica all'utente che ha perso la prenotazione
    creaNotifica($pdo, $codice_utente_scaduto, "Prenotazione Scaduta", "Non hai ritirato il libro in tempo. La prenotazione è stata annullata.", "scaduto", "/prenotazioni");

    // 2. Cancella la prenotazione scaduta
    $del = $pdo->prepare("DELETE FROM prenotazioni WHERE id_prenotazione = ?");
    $del->execute([$id_prenotazione_scaduta]);

    // 3. SCORRIMENTO: Cerca il prossimo utente in coda per QUESTA copia
    // Prendi la prenotazione più vecchia (data_prenotazione ASC) che NON ha ancora una data_assegnazione
    $next_sql = "SELECT * FROM prenotazioni 
                 WHERE id_copia = ? 
                 AND data_assegnazione IS NULL 
                 ORDER BY data_prenotazione ASC, id_prenotazione ASC 
                 LIMIT 1";
    $stmt_next = $pdo->prepare($next_sql);
    $stmt_next->execute([$id_copia]);
    $next_user = $stmt_next->fetch();

    if ($next_user) {
        // 4. Assegna la copia al prossimo utente (parte il timer di 2 giorni da OGGI)
        $upd = $pdo->prepare("UPDATE prenotazioni SET data_assegnazione = CURDATE() WHERE id_prenotazione = ?");
        $upd->execute([$next_user['id_prenotazione']]);

        // 5. Avvisa il nuovo utente fortunato
        creaNotifica($pdo, $next_user['codice_alfanumerico'], "Libro Disponibile!", "La copia che avevi prenotato è ora disponibile. Hai 2 giorni per ritirarla.", "successo", "/prenotazioni");
    }
}

echo "Operazioni notturne e scorrimento code completati.";
?>