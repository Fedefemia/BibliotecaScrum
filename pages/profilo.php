<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';
require_once '../src/includes/badgesFunctions.php';
require_once './phpmailer.php';

$uid = $_SESSION['codice_utente'] ?? null;

if (!$uid) { header("Location: ./login"); exit; }
if (!isset($pdo)) { die('Errore connessione DB.'); }

/* -----------------------------------------------------------
   GENERAZIONE WALLET (Apple Wallet .pkpass) VIA POST
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_wallet' && $uid) {
    
    $stm = $pdo->prepare("SELECT nome, cognome, codice_alfanumerico FROM utenti WHERE codice_alfanumerico = ?");
    $stm->execute([$uid]);
    $u = $stm->fetch(PDO::FETCH_ASSOC);

    if ($u) {
        $cert_dir = __DIR__ . '/certificates/';
        $cert_path = $cert_dir . 'pass.pem';
        $key_path  = $cert_dir . 'key.pem';
        $wwdr_path = $cert_dir . 'wwdr.pem';

        $pass_json = json_encode([
            "formatVersion" => 1,
            "passTypeIdentifier" => "pass.com.bibliotecascrum.membership", 
            "serialNumber" => "LIB-" . $u['codice_alfanumerico'],
            "teamIdentifier" => "YOUR_TEAM_ID",
            "barcode" => [
                "message" => $u['codice_alfanumerico'],
                "format" => "PKBarcodeFormatCode128",
                "messageEncoding" => "iso-8859-1"
            ],
            "organizationName" => "Biblioteca Scrum",
            "description" => "Tessera Personale",
            "backgroundColor" => "rgb(63, 81, 53)",
            "foregroundColor" => "rgb(255, 255, 255)",
            "generic" => [
                "primaryFields" => [
                    ["key" => "member", "label" => "SOCIO", "value" => $u['nome'] . " " . $u['cognome']]
                ],
                "secondaryFields" => [
                    ["key" => "id", "label" => "ID UTENTE", "value" => $u['codice_alfanumerico']]
                ]
            ]
        ]);

        $work_dir = sys_get_temp_dir() . '/pkpass_' . uniqid();
        mkdir($work_dir);
        file_put_contents($work_dir . '/pass.json', $pass_json);
        
        $icon_src = __DIR__ . '/public/assets/icon.png'; 
        if (file_exists($icon_src)) {
            copy($icon_src, $work_dir . '/icon.png');
            copy($icon_src, $work_dir . '/icon@2x.png');
        }

        $manifest = [];
        foreach (scandir($work_dir) as $f) {
            if ($f !== '.' && $f !== '..') $manifest[$f] = sha1_file($work_dir . '/' . $f);
        }
        file_put_contents($work_dir . '/manifest.json', json_encode($manifest));

        openssl_pkcs7_sign(
            $work_dir . '/manifest.json',
            $work_dir . '/signature',
            'file://' . $cert_path,
            ['file://' . $key_path, ''], 
            [],
            PKCS7_BINARY | PKCS7_DETACHED,
            $wwdr_path
        );

        $sig = file_get_contents($work_dir . '/signature');
        $sig = base64_decode(trim(explode("\n\n", $sig)[3]));
        file_put_contents($work_dir . '/signature', $sig);

        $pkpass = $work_dir . '/tessera.pkpass';
        $zip = new ZipArchive();
        $zip->open($pkpass, ZipArchive::CREATE);
        foreach (scandir($work_dir) as $f) {
            if (!in_array($f, ['.', '..', 'tessera.pkpass'])) $zip->addFile($work_dir . '/' . $f, $f);
        }
        $zip->close();

        header('Content-Type: application/vnd.apple.pkpass');
        header('Content-Disposition: attachment; filename="Tessera_Biblioteca.pkpass"');
        readfile($pkpass);

        array_map('unlink', glob("$work_dir/*"));
        rmdir($work_dir);
        exit;
    }
}

/* -----------------------------------------------------------
   GESTIONE AJAX (Username & Email)
----------------------------------------------------------- */
if (isset($_POST['ajax_username']) && $uid) {
    header('Content-Type: application/json');
    $new_user = trim($_POST['ajax_username']);
    try {
        $chk = $pdo->prepare("SELECT 1 FROM utenti WHERE username = ? AND codice_alfanumerico != ?");
        $chk->execute([$new_user, $uid]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Username già occupato!']);
        } else {
            $upd = $pdo->prepare("UPDATE utenti SET username = ? WHERE codice_alfanumerico = ?");
            $upd->execute([$new_user, $uid]);
            echo json_encode(['status' => 'success', 'message' => 'Username aggiornato con successo!']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Errore DB: ' . $e->getMessage()]);
    }
    exit;
}
if (isset($_POST['ajax_livello']) && $uid) {
    header('Content-Type: application/json');
    $new_livello = trim($_POST['ajax_livello']);
    try {
        $chk = $pdo->prepare("SELECT 1 FROM utenti WHERE livello_privato = ? AND codice_alfanumerico != ?");
        $chk->execute([$new_livello, $uid]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Livello già occupato!']);
        } else {
            $upd = $pdo->prepare("UPDATE utenti SET livello_privato = ? WHERE codice_alfanumerico = ?");
            $upd->execute([$new_livello, $uid]);
            echo json_encode(['status' => 'success', 'message' => 'Livello aggiornato con successo!']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Errore DB: ' . $e->getMessage()]);
    }
    exit;
}

if (isset($_POST['ajax_send_email_code']) && $uid) {
    header('Content-Type: application/json');
    $new_email = trim($_POST['email_dest']);
    try {
        $chk = $pdo->prepare("SELECT 1 FROM utenti WHERE email = ? AND codice_alfanumerico != ?");
        $chk->execute([$new_email, $uid]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Questa email è già in uso!']);
            exit;
        }
        $otp = rand(100000, 999999);
        $stmt = $pdo->prepare("SELECT nome FROM utenti WHERE codice_alfanumerico = ?");
        $stmt->execute([$uid]);
        $u_data = $stmt->fetch();

        $mail = getMailer();
        $mail->addAddress($new_email, $u_data['nome']);
        $mail->isHTML(true);
        $mail->Subject = 'Codice verifica';
        $mail->Body = "Il tuo codice è: <b>$otp</b>";
        $mail->send();

        $_SESSION['temp_email_change'] = ['email' => $new_email, 'otp' => $otp];
        echo json_encode(['status' => 'success', 'message' => 'Codice inviato! Controlla la mail.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Errore invio: ' . $e->getMessage()]);
    }
    exit;
}

/* -----------------------------------------------------------
   GENERAZIONE WALLET VIA POST
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_wallet' && $uid) {
    
    // Recupero dati per il pass
    $stm = $pdo->prepare("SELECT nome, cognome, codice_alfanumerico FROM utenti WHERE codice_alfanumerico = ?");
    $stm->execute([$uid]);
    $u = $stm->fetch(PDO::FETCH_ASSOC);

    if ($u) {
        // Percorsi certificati
        $cert_dir = __DIR__ . '/certificates/';
        $cert_path = $cert_dir . 'pass.pem';
        $key_path  = $cert_dir . 'key.pem';
        $wwdr_path = $cert_dir . 'wwdr.pem';

        // 1. JSON del Pass
        $pass_json = json_encode([
            "formatVersion" => 1,
            "passTypeIdentifier" => "pass.com.tuosito.library", 
            "serialNumber" => "U-" . $u['codice_alfanumerico'],
            "teamIdentifier" => "YOURTEAMID",
            "barcode" => [
                "message" => $u['codice_alfanumerico'],
                "format" => "PKBarcodeFormatCode128",
                "messageEncoding" => "iso-8859-1"
            ],
            "organizationName" => "Biblioteca Scrum",
            "description" => "Tessera Personale",
            "backgroundColor" => "rgb(63, 81, 53)",
            "foregroundColor" => "rgb(255, 255, 255)",
            "generic" => [
                "primaryFields" => [
                    ["key" => "member", "label" => "MEMBRO", "value" => $u['nome'] . " " . $u['cognome']]
                ],
                "secondaryFields" => [
                    ["key" => "id", "label" => "ID", "value" => $u['codice_alfanumerico']]
                ]
            ]
        ]);

        // 2. Creazione cartella temporanea
        $work_dir = sys_get_temp_dir() . '/pk_' . uniqid();
        mkdir($work_dir);
        file_put_contents($work_dir . '/pass.json', $pass_json);
        
        // Copia icona obbligatoria
        $icon_src = __DIR__ . '/public/assets/icon.png';
        if (file_exists($icon_src)) copy($icon_src, $work_dir . '/icon.png');

        // 3. Manifest
        $manifest = [];
        foreach (scandir($work_dir) as $f) {
            if ($f !== '.' && $f !== '..') $manifest[$f] = sha1_file($work_dir . '/' . $f);
        }
        file_put_contents($work_dir . '/manifest.json', json_encode($manifest));

        // 4. Firma PKCS7
        openssl_pkcs7_sign(
            $work_dir . '/manifest.json',
            $work_dir . '/signature',
            'file://' . $cert_path,
            ['file://' . $key_path, ''], 
            [],
            PKCS7_BINARY | PKCS7_DETACHED,
            $wwdr_path
        );

        // Conversione firma S/MIME -> DER
        $sig = file_get_contents($work_dir . '/signature');
        $sig = base64_decode(trim(explode("\n\n", $sig)[3]));
        file_put_contents($work_dir . '/signature', $sig);

        // 5. Creazione ZIP (.pkpass)
        $pkpass = $work_dir . '/pass.pkpass';
        $zip = new ZipArchive();
        $zip->open($pkpass, ZipArchive::CREATE);
        foreach (scandir($work_dir) as $f) {
            if (!in_array($f, ['.', '..', 'pass.pkpass'])) $zip->addFile($work_dir . '/' . $f, $f);
        }
        $zip->close();

        // 6. Output headers
        header('Content-Type: application/vnd.apple.pkpass');
        header('Content-Disposition: attachment; filename="Tessera.pkpass"');
        readfile($pkpass);

        // Pulizia
        array_map('unlink', glob("$work_dir/*"));
        rmdir($work_dir);
        exit;
    }
}
/* -----------------------------------------------------------
   GESTIONE POST CLASSICA (Azioni Profilo)
----------------------------------------------------------- */

$messaggio_alert = "";

// 1. Upload Foto Profilo
if (isset($_POST['submit_pfp']) && isset($_FILES['pfp_upload'])) {
    if ($_FILES['pfp_upload']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['pfp_upload'];
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file['tmp_name']);
            $pfpDir = 'public/pfp';
            if (!is_dir($pfpDir)) { mkdir($pfpDir, 0755, true); }
            $destination = $pfpDir . '/' . $uid . '.png';
            $image->toPng()->save($destination);
            $messaggio_alert = "Immagine del profilo aggiornata!";
        } catch (Exception $e) {
            $messaggio_alert = "Errore: " . $e->getMessage();
        }
    } else {
        $messaggio_alert = "Errore durante il caricamento del file.";
    }
}

// 2. Conferma Cambio Email
if (isset($_POST['confirm_email_final'])) {
    $input_code = trim($_POST['otp_code']);
    if (isset($_SESSION['temp_email_change'])) {
        if ($input_code == $_SESSION['temp_email_change']['otp']) {
            try {
                $final_email = $_SESSION['temp_email_change']['email'];
                $upd = $pdo->prepare("UPDATE utenti SET email = ? WHERE codice_alfanumerico = ?");
                $upd->execute([$final_email, $uid]);
                unset($_SESSION['temp_email_change']);
                $messaggio_alert = "Email aggiornata con successo!";
            } catch (Exception $e) {
                $messaggio_alert = "Errore durante l'aggiornamento.";
            }
        } else {
            $messaggio_alert = "Codice errato. Riprova.";
        }
    }
}

// 3. ANNULLA PRENOTAZIONE
if (isset($_POST['action']) && $_POST['action'] === 'annulla_prenotazione') {
    $id_pren = filter_input(INPUT_POST, 'id_prenotazione', FILTER_VALIDATE_INT);
    if ($id_pren) {
        try {
            $stmt = $pdo->prepare("DELETE FROM prenotazioni WHERE id_prenotazione = ? AND codice_alfanumerico = ?");
            $stmt->execute([$id_pren, $uid]);
            $messaggio_alert = "Prenotazione annullata con successo.";
        } catch (Exception $e) {
            $messaggio_alert = "Errore durante l'annullamento.";
        }
    }
}

// 4. RICHIEDI ESTENSIONE PRESTITO
if (isset($_POST['action']) && $_POST['action'] === 'richiedi_estensione') {
    $id_prestito_target = filter_input(INPUT_POST, 'id_prestito', FILTER_VALIDATE_INT);
    $scadenza_attuale = $_POST['scadenza_attuale'] ?? null;

    if ($id_prestito_target && $scadenza_attuale) {
        try {
            $chkOwner = $pdo->prepare("SELECT num_rinnovi FROM prestiti WHERE id_prestito = ? AND codice_alfanumerico = ?");
            $chkOwner->execute([$id_prestito_target, $uid]);
            $loanData = $chkOwner->fetch(PDO::FETCH_ASSOC);

            if ($loanData) {
                if ($loanData['num_rinnovi'] >= 1) {
                    $messaggio_alert = "Hai già effettuato il numero massimo di rinnovi per questo libro.";
                } else {
                    $chk = $pdo->prepare("SELECT 1 FROM richieste_bibliotecario WHERE id_prestito = ? AND stato = 'in_attesa'");
                    $chk->execute([$id_prestito_target]);
                    if ($chk->fetch()) {
                        $messaggio_alert = "Hai già una richiesta in attesa per questo prestito.";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO richieste_bibliotecario (id_prestito, tipo_richiesta, data_scadenza_richiesta) VALUES (?, 'estensione_prestito', ?)");
                        $stmt->execute([$id_prestito_target, $scadenza_attuale]);
                        $messaggio_alert = "Richiesta di estensione inviata al bibliotecario!";
                    }
                }
            } else {
                $messaggio_alert = "Prestito non valido.";
            }
        } catch (Exception $e) {
            $messaggio_alert = "Errore richiesta: " . $e->getMessage();
        }
    }
}

// 5. PAGAMENTO MULTA (Simulazione)
if (isset($_POST['action']) && $_POST['action'] === 'paga_multa_user') {
    $id_multa_target = filter_input(INPUT_POST, 'id_multa', FILTER_VALIDATE_INT);
    
    if ($id_multa_target) {
        try {
            // CORREZIONE: Usiamo LEFT JOIN e controlliamo anche il pattern testuale
            // per permettere il pagamento delle multe manuali (senza prestito)
            $chk = $pdo->prepare("
                SELECT m.id_multa 
                FROM multe m
                LEFT JOIN prestiti p ON m.id_prestito = p.id_prestito
                WHERE m.id_multa = :id 
                  AND m.pagata = 0
                  AND (p.codice_alfanumerico = :uid OR m.causale LIKE :pattern)
            ");
            
            $chk->execute([
                ':id' => $id_multa_target, 
                ':uid' => $uid,
                ':pattern' => "%[$uid]%"
            ]);

            if ($chk->fetch()) {
                $upd = $pdo->prepare("UPDATE multe SET pagata = 1 WHERE id_multa = ?");
                $upd->execute([$id_multa_target]);
                $messaggio_alert = "Pagamento registrato con successo! Grazie.";
            } else {
                $messaggio_alert = "Impossibile elaborare il pagamento. Multa non trovata o già pagata.";
            }
        } catch (Exception $e) {
            $messaggio_alert = "Errore transazione: " . $e->getMessage();
        }
    }
}

/* ---- Recupero Dati Utente ---- */
$stm = $pdo->prepare("SELECT * FROM utenti WHERE codice_alfanumerico = ?");
$stm->execute([$uid]);
$utente = $stm->fetch(PDO::FETCH_ASSOC);

/* ---- Dati Accessori ---- */
// MULTE ATTIVE
$stm = $pdo->prepare("
    SELECT 
        m.id_multa, 
        m.importo, 
        m.causale, 
        m.data_creata, 
        COALESCE(l.titolo, 'Sanzione Amministrativa') as titolo
    FROM multe m
    LEFT JOIN prestiti p ON m.id_prestito = p.id_prestito
    LEFT JOIN copie c ON p.id_copia = c.id_copia
    LEFT JOIN libri l ON c.isbn = l.isbn
    WHERE 
        (p.codice_alfanumerico = :uid OR m.causale LIKE :pattern_uid)
        AND m.pagata = 0
    ORDER BY m.data_creata DESC
");

// Eseguiamo passando due parametri: il codice puro e il pattern per cercarlo nella stringa
$stm->execute([
    ':uid' => $uid,
    ':pattern_uid' => "%[$uid]%"
]);

$multe_attive = $stm->fetchAll(PDO::FETCH_ASSOC);

// PRESTITI ATTIVI
$stm = $pdo->prepare("
    SELECT 
        p.id_prestito,
        c.isbn, 
        c.id_copia,
        p.data_scadenza,
        p.num_rinnovi,
        r.stato as stato_richiesta
    FROM prestiti p 
    JOIN copie c ON p.id_copia = c.id_copia 
    LEFT JOIN richieste_bibliotecario r ON r.id_prestito = p.id_prestito AND r.stato = 'in_attesa'
    WHERE p.codice_alfanumerico = ? AND p.data_restituzione IS NULL
    ORDER BY p.data_scadenza ASC
");
$stm->execute([$uid]);
$prestiti_attivi = $stm->fetchAll(PDO::FETCH_ASSOC);

// PRENOTAZIONI ATTIVE
$stm = $pdo->prepare("
    SELECT c.isbn, p.data_prenotazione, p.id_prenotazione, p.id_copia,
        (SELECT COUNT(*) FROM prenotazioni p2 WHERE p2.id_copia = p.id_copia AND p2.data_assegnazione IS NULL AND (p2.data_prenotazione < p.data_prenotazione OR (p2.data_prenotazione = p.data_prenotazione AND p2.id_prenotazione < p.id_prenotazione))) as utenti_davanti
    FROM prenotazioni p 
    JOIN copie c ON p.id_copia = c.id_copia
    WHERE p.codice_alfanumerico = ? AND p.data_assegnazione IS NULL
    ORDER BY p.data_prenotazione ASC
");
$stm->execute([$uid]);
$prenotazioni = $stm->fetchAll(PDO::FETCH_ASSOC);

/// CBR STORICO LIBRI LETTI → TUTTE LE COPIE LETTE
$stm = $pdo->prepare("
    SELECT p.id_prestito, c.isbn, p.data_restituzione
    FROM prestiti p
    JOIN copie c ON p.id_copia = c.id_copia
    WHERE p.codice_alfanumerico = ? AND p.data_restituzione IS NOT NULL
    ORDER BY p.data_restituzione DESC
");
$stm->execute([$uid]);
$libri_letti = $stm->fetchAll(PDO::FETCH_ASSOC);

// CBR TOTALE LIBRI LETTI (TUTTE LE COPIE)
$totale_libri_letti = count($libri_letti);

// CBR RANGE DATE PER CALCOLO MEDIA MENSILE
$stm = $pdo->prepare("
    SELECT MIN(data_restituzione) as inizio, MAX(data_restituzione) as fine
    FROM prestiti
    WHERE codice_alfanumerico = ? AND data_restituzione IS NOT NULL
");
$stm->execute([$uid]);
$range_date = $stm->fetch(PDO::FETCH_ASSOC);

$media_mensile = 0;
$mesi_totali = 1;

if ($range_date['inizio']) {
    $d1 = new DateTime($range_date['inizio']);
    $d2 = new DateTime($range_date['fine'] ?? 'now'); // usa la data max se disponibile
    $d2->setTime(23, 59, 59);

    // Calcolo mesi totali considerando mese iniziale fino a quello finale
    $mesi_totali = ($d2->format('Y') - $d1->format('Y')) * 12 + ($d2->format('n') - $d1->format('n')) + 1;

    // Assicura almeno 1 mese per evitare divisione per zero
    $mesi_totali = max($mesi_totali, 1);

    // Calcolo media mensile
    $media_mensile = round($totale_libri_letti / $mesi_totali, 1);
}


// CBR STORICO ULTIMI 6 MESI (TUTTE LE COPIE LETTE)
$stm = $pdo->prepare("
    SELECT DATE_FORMAT(data_restituzione, '%b %Y') as mese, COUNT(*) as qta
    FROM prestiti
    WHERE codice_alfanumerico = ? AND data_restituzione IS NOT NULL
    AND data_restituzione >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mese
    ORDER BY MIN(data_restituzione) ASC
");
$stm->execute([$uid]);
$storico_stat = $stm->fetchAll(PDO::FETCH_ASSOC);

// MASSIMO LIBRI LETTI IN UN MESE
$max_libri_mese = 0;
foreach ($storico_stat as $s) {
    if ($s['qta'] > $max_libri_mese) $max_libri_mese = $s['qta'];
}

/* -----------------------------------------------------------
   LOGICA BADGE
----------------------------------------------------------- */
$badges_to_display = [];
$unlocked_badges_ids = [];
$user_stats = [];

try {
    // 1. Recupero
    calcolaBadges($uid, $pdo);
    $display_item = recuperaBadges($uid, $pdo);
    if (!empty($display_item)) {
        $badges_to_display = $display_item;
    }

} catch (PDOException $e) {
    $messaggio_alert = "Errore caricamento badge: " . $e->getMessage();
}


/* ----- FUNZIONI UTILI ----- */
function getCoverPath(string $isbn): string {
    $localPath = "public/bookCover/$isbn.png";
    return file_exists($localPath) ? $localPath : "public/assets/book_placeholder.jpg";
}

function formatCounter($dateTarget) {
    if (!$dateTarget) return ["N/D", "grey"];
    $today = new DateTime(); $target = new DateTime($dateTarget); $target->setTime(23, 59, 59);
    $diff = $today->diff($target); $days = $diff->days; if ($diff->invert) { $days = -$days; }
    $dateString = $target->format('d/m/Y'); $text = "Scadenza: $dateString";
    if ($days < 0) { return ["$text (Scaduto da " . abs($days) . " gg)", "#c0392b"]; }
    elseif ($days <= 2) { return ["$text ($days giorni)", "#e67e22"]; }
    else { return ["$text ($days giorni)", "#27ae60"]; }
}
?>

<?php
$title = "Area Personale";
$path = "./";
$page_css = "./public/css/style_profilo.css";
require './src/includes/header.php';
require './src/includes/navbar.php';
?>

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Libre+Barcode+39+Text&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <div class="info_line">
        <div class="info_column">
            <?php
            $pfpPath = 'public/pfp/' . htmlspecialchars($uid) . '.png';
            if (!file_exists($pfpPath)) { $pfpPath = 'public/assets/base_pfp.png'; }
            ?>
            <form action="profilo" method="post" enctype="multipart/form-data" id="form-pfp">
                <input type="hidden" name="submit_pfp" value="1">
                <input type="file" name="pfp_upload" id="pfp_upload" accept="image/png, image/jpeg" style="display:none;" onchange="document.getElementById('form-pfp').submit()">
                <div class="pfp_wrapper" onclick="document.getElementById('pfp_upload').click()">
                    <img class="info_pfp" alt="Foto Profilo" src="<?= $pfpPath . '?v=' . time() ?>">
                    <div class="pfp_overlay">
                        <span class="pfp_text">Modifica</span>
                    </div>
                </div>
            </form>

            <button class="btn_tessera" onclick="apriTessera()">Visualizza Tessera</button>

            <div class="edit_container_wrapper">
                <div class=" edit_row" id="row-username">
                    <input type="text" id="inp-username" class=" edit_input" value="<?= htmlspecialchars($utente['username'] ?? '') ?>" data-original="<?= htmlspecialchars($utente['username'] ?? '') ?>" placeholder="Username">
                    <button type="button" id="btn-user" class=" btn_slide" onclick="ajaxSaveUsername()">Salva</button>
                </div>
                <div class=" edit_row">
                    <input type="email" id="inp-email" class=" edit_input" value="<?= htmlspecialchars($utente['email'] ?? '') ?>" data-original="<?= htmlspecialchars($utente['email'] ?? '') ?>" placeholder="Email" oninput="handleEmailInput(this)">
                </div>
                <form method="post" id="box-email-otp" style="display:none; width:100%;">
                    <input type="text" name="otp_code" id="inp-otp" class=" edit_input" placeholder="Codice verifica" disabled autocomplete="off">
                    <button type="button" id="btn-email-action" class="btn-tessera" style="margin-top:10px;" onclick="handleEmailAction()">Invia Codice</button>
                    <input type="hidden" name="confirm_email_final" value="1">
                </form>
                <div class=" edit_row" id="row-livello">
                    <label style="width: 20%; font-size: 0.95rem;" for="inp-livello">Sicurezza:</label>
                    <select onchange="ajaxSaveLivello()" id="inp-livello" class="edit_select" data-original="<?= $utente['livello_privato'] ?? '' ?>" placeholder="Livello privacy (0-2)">
                        <option value="0">Alta</option>
                        <option value="1">Media</option>
                        <option value="2">Bassa</option>
                    </select>
                </div>
                <div class=" edit_row"><input type="text" class=" edit_input" disabled value="<?= htmlspecialchars($utente['nome'] ?? '') ?>"></div>
                <div class=" edit_row"><input type="text" class=" edit_input" disabled value="<?= htmlspecialchars($utente['cognome'] ?? '') ?>"></div>
                <div class=" edit_row"><input type="text" class=" edit_input" disabled value="<?= htmlspecialchars($utente['codice_fiscale'] ?? '') ?>"></div>
            </div>


            <?php if (!empty($multe_attive)): ?>
                <div class="fine_container">
                    <h4 class="fine_header_title">Da Saldare</h4>
                    <?php foreach ($multe_attive as $m): ?>
                        <div class="fine_card">
                            <div class="fine_info">
                                <span class="fine_title"><?= htmlspecialchars($m['titolo']) ?></span>
                                <span class="fine_meta"><?= htmlspecialchars($m['causale']) ?></span>
                            </div>
                            <div style="text-align:right;">
                                <div class="fine_price">€ <?= number_format($m['importo'], 2) ?></div>
                                <button class="btn_pay" onclick="apriPagamento(<?= $m['id_multa'] ?>, '<?= number_format($m['importo'], 2) ?>')">Paga</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="info_column extend_all">

            <div class="section">
                <h2>Panoramica</h2>
                <div class="stats_grid">
                    <div class="stat_card_total">
                        <span class="stat_label">Libri Letti</span>
                        <strong class="stat_value_total"><?= $totale_libri_letti ?></strong>
                    </div>
                    <div class="stat_card_total">
                        <span class="stat_label">Media Mensile</span>
                        <strong class="stat_value_monthly"><?= $media_mensile ?></strong>
                    </div>
                </div>
                <?php if ($storico_stat): ?>
                    <div class="chart_container">
                        <?php foreach ($storico_stat as $s):
                            $percentuale = ($max_libri_mese > 0) ? ($s['qta'] / $max_libri_mese) * 100 : 0;
                            ?>
                            <div class="chart_row">
                                <div class="chart_label"><?= $s['mese'] ?></div>
                                <div class="chart_bar_bg">
                                    <div class="chart_bar_fill" style="width: <?= $percentuale ?>%;"></div>
                                </div>
                                <div class="chart_value"><?= $s['qta'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    


                <?php endif; ?>
            </div>

            <div class="section">
                <h2>Prestiti attivi</h2>
                <div class="grid">
                    <?php if ($prestiti_attivi): foreach ($prestiti_attivi as $libro):
                        $scadenza_data = formatCounter($libro['data_scadenza']);
                        $rinnovi_effettuati = $libro['num_rinnovi'] ?? 0;
                        ?>
                        <div class="book_item">
                            <a href="./libro?isbn=<?= htmlspecialchars($libro['isbn']) ?>" class="card cover-only"><img src="<?= getCoverPath($libro['isbn']) ?>" alt="Cover"></a>
                            <div class="book-meta">
                                <div><?= $scadenza_data[0] ?></div>
                            </div>
                            <div class="mini-actions">
                                <?php if ($libro['stato_richiesta'] == 'in_attesa'): ?>
                                    <span class="link-mini link-pending">Richiesta inviata</span>
                                <?php elseif ($rinnovi_effettuati >= 1): ?>
                                    <span class="link-mini link-pending">Max rinnovi</span>
                                <?php else: ?>
                                    <form method="POST" action="profilo">
                                        <input type="hidden" name="action" value="richiedi_estensione">
                                        <input type="hidden" name="id_prestito" value="<?= $libro['id_prestito'] ?>">
                                        <input type="hidden" name="scadenza_attuale" value="<?= $libro['data_scadenza'] ?>">
                                        <button type="submit" class="link-mini link-action">Estendi prestito</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p style="color:#888;">Nessun prestito in corso.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <h2>Prenotazioni</h2>
                <div class="grid">
                    <?php if ($prenotazioni): foreach ($prenotazioni as $libro):
                        $queue_count = $libro['utenti_davanti'];
                        $queue_msg = ($queue_count == 0) ? 'Prossimo disponibile' : "$queue_count persone prima di te";
                        ?>
                        <div class="book_item">
                            <a href="./libro?isbn=<?= htmlspecialchars($libro['isbn']) ?>" class="card cover-only"><img src="<?= getCoverPath($libro['isbn']) ?>" alt="Cover"></a>
                            <div class="book-meta">
                                <div><?= $queue_msg ?></div>
                            </div>
                            <div class="mini-actions">
                                <form method="POST" action="profilo">
                                    <input type="hidden" name="action" value="annulla_prenotazione">
                                    <input type="hidden" name="id_prenotazione" value="<?= $libro['id_prenotazione'] ?>">
                                    <button type="submit" class="link-mini link-danger">Annulla</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p style="color:#888;">Nessuna prenotazione.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <h2>I tuoi Badge</h2>
                    <div class="badges_grid">
                        <?php foreach ($badges_to_display as $b):
                            $idBadge = intval($b['id_badge']);
                            $isUnlocked = $b['is_unlocked'];

                            $imgPath = $path . 'public/assets/badge/' . $idBadge . '.png';

                            $tipo = $b['tipo'] ?? '';
                            $target = intval($b['target_numerico']);

                            $currentVal = isset($user_stats[$tipo]) ? intval($user_stats[$tipo]) : 0;

                            // Logica Next Badge
                            $nextBadge = $b['next_badge'] ?? null;
                            $progressPercent = 0;
                            $nextTarget = 0;
                            $showProgress = false;

                            if ($nextBadge) {
                                $nextTarget = intval($nextBadge['target_numerico']);
                                if ($tipo !== 'numero_multe' && $nextTarget > 0) {
                                    $rawPercent = ($currentVal / $nextTarget) * 100;
                                    $progressPercent = min(100, $rawPercent);
                                    $showProgress = true;
                                }
                            } ?>

                            <div class="badge_card <?= $isUnlocked ? '' : 'locked' ?>" id="badge_<?= $idBadge ?>">

                                <div class="badge_status_pill <?= $isUnlocked ? 'unlocked' : 'locked' ?>">
                                    <?= $isUnlocked ? 'Sbloccato' : 'Bloccato' ?>
                                </div>

                                <div class="badge_image_wrapper">
                                    <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($b['nome']) ?>" class="badge_image">
                                </div>

                                <div class="badge_content">
                                    <div class="badge_info_title"><?= htmlspecialchars($b['nome']) ?></div>
                                    <div class="badge_info_desc"><?= htmlspecialchars($b['descrizione']) ?></div>
                                </div>

                                <?php if ($showProgress): ?>
                                    <div class="badge_next_level">
                                        <div class="badge_next_header">
                                            <span class="next_label">Prossimo:</span>
                                            <span class="next_name"><?= htmlspecialchars($nextBadge['nome']) ?></span>
                                        </div>

                                        <div class="badge_progress_track">
                                            <div class="badge_progress_fill" style="width: <?= $progressPercent ?>%;"></div>
                                        </div>

                                        <div class="badge_progress_numbers">
                                            <?= $currentVal ?> / <?= $nextTarget ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                </div>
            </div>

            <div class="section">
                <h2>Storico letture</h2>
                <div class="grid">
                    <?php if ($libri_letti): foreach ($libri_letti as $libro): ?>
                        <div class="book_item">
                            <a href="./libro?isbn=<?= htmlspecialchars($libro['isbn']) ?>" class="card cover-only"><img src="<?= getCoverPath($libro['isbn']) ?>" alt="Cover"></a>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div>
    </div>

    <div id="modalTessera" class="modal-overlay">
        <div class="modal_content">
            <div id="tessera-card">
                <div class="tessera-header">BibliotecaScrum</div>
                <div class="tessera-user"><?= htmlspecialchars(($utente['nome'] ?? '') . ' ' . ($utente['cognome'] ?? '')) ?></div>
                <div class="tessera-barcode">*<?= strtoupper(htmlspecialchars($utente['codice_alfanumerico'] ?? $uid)) ?>*</div>
            </div>
            <div class="modal_actions">
                <button class="btn_action btn-print" onclick="chiudiTessera()">Chiudi</button>
                <button class="btn_action btn-download" onclick="scaricaPNG()">Scarica</button>
            </div>
        </div>
    </div>

    <div id="modalPagamento" class="modal-overlay">
        <div class="modal-content">
            <h3 style="font-family:'Young Serif', serif; color:var(--color_text_dark_green); margin-bottom:20px;">Conferma Pagamento</h3>
            <p style="margin-bottom:10px;">Importo da saldare</p>
            <h2 style="font-family:'Young Serif', serif; font-size:2.5rem; margin:0 0 30px 0;">€ <span id="payAmountDisplay">0.00</span></h2>

            <form method="POST" action="profilo">
                <input type="hidden" name="action" value="paga_multa_user">
                <input type="hidden" name="id_multa" id="payMultaId">
                <div class="modal_actions">
                    <button type="button" class="btn_action btn_modal_cancel" onclick="chiudiPagamento()">Annulla</button>
                    <button type="submit" class="btn_action  btn_modal_pay">Conferma</button>
                </div>
            </form>
        </div>
    </div>

    <div id="notification-banner" style="position:fixed; bottom:20px; right:20px; background:#fff; border-left:4px solid var(--color_text_dark_green); padding:15px 25px; box-shadow:0 5px 20px rgba(0,0,0,0.1); transform:translateY(100px); transition:transform 0.3s; z-index:3000;">
        <span id="banner-msg">Notifica</span>
    </div>

    <script>
        /* Javascript Logica */
        let timeoutId;
        function showNotification(message) {
            const banner = document.getElementById('notification-banner');
            document.getElementById('banner-msg').innerText = message;
            banner.style.transform = 'translateY(0)';
            if (timeoutId) clearTimeout(timeoutId);
            timeoutId = setTimeout(() => { banner.style.transform = 'translateY(100px)'; }, 5000);
        }

        // Iniezione messaggi PHP
        <?php if(!empty($messaggio_alert)): ?>
        setTimeout(() => { showNotification("<?= addslashes($messaggio_alert) ?>"); }, 500);
        <?php endif; ?>

        // Logica Username
        const inpUser = document.getElementById('inp-username');
        const rowUser = document.getElementById('row-username');
        inpUser.addEventListener('input', function() {
            if(this.value !== this.dataset.original) rowUser.classList.add('changed');
            else rowUser.classList.remove('changed');
        });
        async function ajaxSaveUsername() {
            const val = inpUser.value;
            const formData = new FormData(); formData.append('ajax_username', val);
            try {
                let resp = await fetch(window.location.href, {method:'POST', body:formData});
                let data = await resp.json();
                showNotification(data.message);
                if(data.status==='success') { inpUser.dataset.original = val; rowUser.classList.remove('changed'); }
            } catch(e) { showNotification("Errore connessione"); }
        }

        // Logica Livello
        const inpLiv = document.getElementById('inp-livello');
        inpLiv.value = "<?= $utente['livello_privato'] ?? '' ?>"
        const rowLiv = document.getElementById('row-livello');
        async function ajaxSaveLivello() {
            const val = inpLiv.value;
            const formData = new FormData(); formData.append('ajax_livello', val);
            try {
                let resp = await fetch(window.location.href, {method:'POST', body:formData});
                let data = await resp.json();
                showNotification(data.message);
                if(data.status==='success') { inpLiv.dataset.original = val; rowLiv.classList.remove('changed'); }
            } catch(e) { showNotification("Errore connessione"); }
        }

        // Logica Email
        function handleEmailInput(input) {
            const box = document.getElementById('box-email-otp');
            if(input.value !== input.dataset.original) {
                box.style.display = 'block';
                document.getElementById('btn-email-action').innerText = "Invia Codice";
                document.getElementById('btn-email-action').type = "button";
                document.getElementById('inp-otp').disabled = true;
            } else {
                box.style.display = 'none';
            }
        }
        async function handleEmailAction() {
            const btn = document.getElementById('btn-email-action');
            if(btn.type === 'button') {
                const formData = new FormData();
                formData.append('ajax_send_email_code', 1);
                formData.append('email_dest', document.getElementById('inp-email').value);
                btn.innerText = "Attendi...";
                try {
                    let resp = await fetch(window.location.href, {method:'POST', body:formData});
                    let data = await resp.json();
                    showNotification(data.message);
                    if(data.status==='success') {
                        document.getElementById('inp-otp').disabled = false;
                        document.getElementById('inp-otp').focus();
                        btn.innerText = "Conferma cambio";
                        btn.type = "submit";
                    } else { btn.innerText = "Invia Codice"; }
                } catch(e) { showNotification("Errore invio"); }
            }
        }

        // Modals
        const modalTessera = document.getElementById('modalTessera');
        const modalPay = document.getElementById('modalPagamento');
        function apriTessera() { modalTessera.style.display = 'flex'; }
        function chiudiTessera() { modalTessera.style.display = 'none'; }
        function apriPagamento(id, amount) {
            document.getElementById('payMultaId').value = id;
            document.getElementById('payAmountDisplay').innerText = amount;
            modalPay.style.display = 'flex';
        }
        function chiudiPagamento() { modalPay.style.display = 'none'; }
        window.onclick = function(e) {
            if(e.target == modalTessera) chiudiTessera();
            if(e.target == modalPay) chiudiPagamento();
        }
        function scaricaPNG() {
            html2canvas(document.getElementById("tessera-card"), {scale: 3}).then(canvas => {
                const link = document.createElement('a');
                link.download = 'Tessera.png';
                link.href = canvas.toDataURL("image/png");
                link.click();
            });
        }
        function salvaWallet() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = ''; 

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'action';
        input.value = 'generate_wallet';

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
    </script>

<?php require './src/includes/footer.php'; ?>