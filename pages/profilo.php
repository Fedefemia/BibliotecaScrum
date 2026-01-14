<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', '/var/www/html/php_errors.log');

require_once __DIR__ . '/../vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';
require_once './phpmailer.php';

$uid = $_SESSION['codice_utente'] ?? null;

if (!$uid) { header("Location: ./login"); exit; }
if (!isset($pdo)) { die('Errore connessione DB.'); }

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
    $id_copia_ext = filter_input(INPUT_POST, 'id_copia', FILTER_VALIDATE_INT);
    $scadenza_attuale = $_POST['scadenza_attuale'] ?? null;

    if ($id_copia_ext && $scadenza_attuale) {
        try {
            $chk = $pdo->prepare("SELECT 1 FROM richieste_bibliotecario WHERE codice_alfanumerico = ? AND id_copia = ? AND stato = 'in_attesa'");
            $chk->execute([$uid, $id_copia_ext]);
            
            if ($chk->fetch()) {
                $messaggio_alert = "Hai già una richiesta in attesa per questo libro.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO richieste_bibliotecario (codice_alfanumerico, tipo_richiesta, id_copia, data_scadenza_richiesta) VALUES (?, 'estensione_prestito', ?, ?)");
                $stmt->execute([$uid, $id_copia_ext, $scadenza_attuale]);
                $messaggio_alert = "Richiesta di estensione inviata al bibliotecario!";
            }
        } catch (Exception $e) {
            $messaggio_alert = "Errore richiesta: " . $e->getMessage();
        }
    }
}


/* ---- Recupero Dati Utente ---- */
$stm = $pdo->prepare("SELECT * FROM utenti WHERE codice_alfanumerico = ?");
$stm->execute([$uid]);
$utente = $stm->fetch(PDO::FETCH_ASSOC);

/* ---- Dati Accessori (AGGIORNATO) ---- */

// PRESTITI ATTIVI + STATO RICHIESTA ESTENSIONE
$stm = $pdo->prepare("
    SELECT 
        c.isbn, 
        c.id_copia,
        p.data_scadenza,
        r.stato as stato_richiesta
    FROM prestiti p 
    JOIN copie c ON p.id_copia = c.id_copia 
    LEFT JOIN richieste_bibliotecario r ON r.id_copia = p.id_copia AND r.codice_alfanumerico = p.codice_alfanumerico AND r.stato = 'in_attesa'
    WHERE p.codice_alfanumerico = ? AND p.data_restituzione IS NULL
    ORDER BY p.data_scadenza ASC
");
$stm->execute([$uid]);
$prestiti_attivi = $stm->fetchAll(PDO::FETCH_ASSOC);

// PRENOTAZIONI ATTIVE + CALCOLO CODA
// La subquery conta quante prenotazioni attive per quella copia sono state fatte PRIMA della mia
$stm = $pdo->prepare("
    SELECT 
        c.isbn, 
        p.data_prenotazione, 
        p.id_prenotazione,
        p.id_copia,
        (
            SELECT COUNT(*) 
            FROM prenotazioni p2 
            WHERE p2.id_copia = p.id_copia 
              AND p2.data_assegnazione IS NULL 
              AND (
                  p2.data_prenotazione < p.data_prenotazione 
                  OR (p2.data_prenotazione = p.data_prenotazione AND p2.id_prenotazione < p.id_prenotazione)
              )
        ) as utenti_davanti
    FROM prenotazioni p 
    JOIN copie c ON p.id_copia = c.id_copia
    WHERE p.codice_alfanumerico = ? AND p.data_assegnazione IS NULL
    ORDER BY p.data_prenotazione ASC
");
$stm->execute([$uid]);
$prenotazioni = $stm->fetchAll(PDO::FETCH_ASSOC);

// STORICO LIBRI LETTI (Restituiti)
// Selezioniamo i libri che hanno una data di restituzione (non NULL)
$stm = $pdo->prepare("
    SELECT DISTINCT c.isbn 
    FROM prestiti p 
    JOIN copie c ON p.id_copia = c.id_copia 
    WHERE p.codice_alfanumerico = ? 
      AND p.data_restituzione IS NOT NULL
    ORDER BY p.data_restituzione DESC
");
$stm->execute([$uid]);
$libri_letti = $stm->fetchAll(PDO::FETCH_ASSOC);
$badges = []; // Implementare logica badge se necessario

require './src/includes/header.php';
require './src/includes/navbar.php';

function getCoverPath(string $isbn): string {
    $localPath = "public/bookCover/$isbn.png";
    if (file_exists($localPath)) { return $localPath; }
    return "public/assets/book_placeholder.jpg";
}

// Funzione Helper per calcolo giorni
function formatCounter($dateTarget) {
    if (!$dateTarget) return ["N/D", "grey"];
    
    $today = new DateTime();
    $target = new DateTime($dateTarget);
    $target->setTime(23, 59, 59); 
    
    $diff = $today->diff($target);
    $days = $diff->days;
    
    if ($diff->invert) { $days = -$days; }

    $dateString = $target->format('d/m/Y');
    $text = "Scadenza: $dateString";
    
    if ($days < 0) { return ["$text (Scaduto da " . abs($days) . " gg)", "#c0392b"]; } 
    elseif ($days <= 2) { return ["$text ($days giorni)", "#e67e22"]; } 
    else { return ["$text ($days giorni)", "#27ae60"]; }
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Libre+Barcode+39+Text&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
    /* CSS BASE */
    body { font-family: 'Poppins', sans-serif; }
    .grid { display: flex; flex-wrap: wrap; gap: 25px; } 
    
    .book-item {
        display: flex; flex-direction: column; width: 120px; align-items: center; gap: 5px;
    }

    .card.cover-only { 
        width: 120px; display: block; text-decoration: none; color: #333; margin-bottom: 0;
    }
    .card.cover-only img { 
        width: 120px; height: 180px; object-fit: cover; border-radius: 4px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: transform 0.2s;
    }
    .card.cover-only:hover img { transform: translateY(-3px); }

    .book-meta {
        font-size: 0.75rem; text-align: center; line-height: 1.3; font-weight: 500; width: 100%;
    }
    
    /* PULSANTI AZIONE SOTTO LIBRO */
    .mini-actions {
        width: 100%; display: flex; justify-content: center; gap: 5px; margin-top: 5px;
    }
    .btn-mini {
        padding: 4px 8px; border: none; border-radius: 4px; font-size: 0.7rem; 
        font-weight: 600; cursor: pointer; transition: background 0.2s; width: 100%;
    }
    .btn-mini-danger { background-color: #e74c3c; color: white; }
    .btn-mini-danger:hover { background-color: #c0392b; }
    
    .btn-mini-action { background-color: #3498db; color: white; }
    .btn-mini-action:hover { background-color: #2980b9; }

    .btn-mini-pending { background-color: #f39c12; color: white; cursor: default; }

    .info_column { display: flex; flex-direction: column; width: auto; justify-content: flex-start; align-items: center; gap: 10px; }
    .info_line { display: flex; flex-direction: row; width: 100%; justify-content: space-between; align-items: flex-start; gap: 20px; padding-top: 20px; }
    
    /* --- CSS PFP --- */
    .pfp-wrapper {
        position: relative; width: 240px; height: 240px; border-radius: 50%; 
        border: 5px solid #3f5135; overflow: hidden; cursor: pointer; 
        margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .info_pfp { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pfp-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.6); display: flex; flex-direction: column; 
        justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; color: #fff;
    }
    .pfp-wrapper:hover .pfp-overlay { opacity: 1; }
    .pfp-icon { font-size: 24px; margin-bottom: 5px; }
    .pfp-text { font-size: 14px; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; }

    .extend_all { width: 100%; height: 100%; justify-content: space-between; align-items: flex-start; }
    .section { width: 100%; height: auto; display: flex; flex-direction: column; margin-bottom: 30px; }
    
    /* --- CSS EDITING --- */
    .edit-container-wrapper { margin-top: 10px; width: 260px; display: flex; flex-direction: column; gap: 8px; }
    .edit-row { width: 100%; display: flex; align-items: center; }
    .edit-input { flex: 1; min-width: 0; padding: 8px; border: 1px solid #ccc; border-radius: 4px; color: #333; font-family: 'Poppins', sans-serif; font-size: 1em; transition: all 0.3s ease; }
    .edit-input:disabled { background: #eee; color: #666; border: 1px solid transparent; }
    .btn-slide { width: 0; padding: 0; opacity: 0; margin-left: 0; overflow: hidden; white-space: nowrap; background-color: #3f5135; color: white; border: none; border-radius: 4px; font-size: 0.9em; cursor: pointer; transition: all 0.4s ease; }
    .edit-row.changed .btn-slide { width: 80px; padding: 8px 0; opacity: 1; margin-left: 5px; }

    /* --- EMAIL ANIM --- */
    .email-expand-box { max-height: 0; opacity: 0; overflow: hidden; transition: all 0.5s ease; display: flex; gap: 5px; width: 100%; }
    .email-expand-box.open { max-height: 50px; opacity: 1; margin-top: -3px; }
    .otp-locked { background-color: #e0e0e0; color: #999; cursor: not-allowed; border-color: #ddd; }
    .btn-action-email { background-color: #3f5135; color: white; border: none; border-radius: 4px; padding: 8px; cursor: pointer; font-family: 'Poppins', sans-serif; width: 80px; transition: background 0.3s; }
    .btn-action-email:hover { background-color: #2c3a24; }
    .btn-success-anim { background-color: #27ae60 !important; }

    /* Modal */
    .modal-overlay { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); justify-content: center; align-items: center; backdrop-filter: blur(2px); }
    .modal-content { background-color: white; padding: 20px; border-radius: 16px; width: auto; max-width: 90%; text-align: center; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
    .close-modal { position: absolute; top: 10px; right: 15px; font-size: 24px; font-weight: 600; color: #aaa; cursor: pointer; z-index: 1001; font-family: sans-serif; }
    .btn-tessera { margin-top: 10px; padding: 10px 20px; background-color: #3f5135; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; font-family: 'Poppins', sans-serif; transition: background 0.3s; }
    #tessera-card { font-family: 'Poppins', sans-serif; background-color: #ffffff; color: #000000; border: 2px solid #000; border-radius: 12px; width: 340px; height: 215px; margin: 25px auto; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.15); box-sizing: border-box; }
    .tessera-header { font-size: 1.1em; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; border-bottom: 2px solid #000; padding-bottom: 8px; width: 100%; text-align: center; }
    .tessera-user { font-size: 1.3em; font-weight: 500; text-align: center; word-wrap: break-word; margin-top: 10px; }
    .tessera-barcode { font-family: 'Libre Barcode 39 Text', cursive; font-size: 42px; color: #000; line-height: 1; white-space: nowrap; margin-bottom: 5px; }
    .modal-actions { display: flex; justify-content: center; gap: 15px; margin-top: 10px; width: 340px; margin-left: auto; margin-right: auto; }
    .btn-action { padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; font-size: 14px; font-family: 'Poppins', sans-serif; font-weight: 500; flex: 1; transition: all 0.2s ease; }
    .btn-print { background-color: #3f5135; color: white; border-color: #3f5135; }
    .btn-download { background-color: #f8f9fa; color: #333; }

    /* Notifica */
    #notification-banner { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background-color: #222; color: white; padding: 14px 24px; border-radius: 6px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 20px; transition: bottom 0.5s; z-index: 9999; min-width: 250px; justify-content: space-between; }
    #notification-banner.show { bottom: 30px; }
    .notification-text { font-size: 15px; font-weight: 500; }
    .close-btn-banner { background: none; border: none; color: #bbb; font-size: 22px; cursor: pointer; padding: 0; line-height: 1; }
    .close-btn-banner:hover { color: white; }
</style>

<div class="info_line">

    <div class="info_column">

        <?php
        $pfpPath = 'public/pfp/' . htmlspecialchars($uid) . '.png';
        if (!file_exists($pfpPath)) {
            $pfpPath = 'public/assets/base_pfp.png';
        }
        ?>
        
        <form action="profilo" method="post" enctype="multipart/form-data" id="form-pfp">
            <input type="hidden" name="submit_pfp" value="1">
            <input type="file" name="pfp_upload" id="pfp_upload" accept="image/png, image/jpeg" style="display: none;" onchange="document.getElementById('form-pfp').submit()">

            <div class="pfp-wrapper" onclick="document.getElementById('pfp_upload').click()">
                <img class="info_pfp" alt="Pfp" src="<?= $pfpPath . '?v=' . time() ?>">
                <div class="pfp-overlay">
                    <span class="pfp-icon">📷</span>
                    <span class="pfp-text">Modifica</span>
                </div>
            </div>
        </form>
        <button class="btn-tessera" onclick="apriTessera()">Tessera Utente</button>

        <div class="edit-container-wrapper">

            <div class="edit-row" id="row-username">
                <input type="text" id="inp-username" class="edit-input" 
                       value="<?= htmlspecialchars($utente['username'] ?? '') ?>" 
                       data-original="<?= htmlspecialchars($utente['username'] ?? '') ?>"
                       placeholder="Username">
                <button type="button" id="btn-user" class="btn-slide" onclick="ajaxSaveUsername()">Salva</button>
            </div>

            <div class="edit-row">
                <input type="email" id="inp-email" class="edit-input" 
                       value="<?= htmlspecialchars($utente['email'] ?? '') ?>" 
                       data-original="<?= htmlspecialchars($utente['email'] ?? '') ?>"
                       placeholder="Email"
                       oninput="handleEmailInput(this)">
            </div>

            <form method="post" class="email-expand-box" id="box-email-otp">
                <input type="text" name="otp_code" id="inp-otp" 
                       class="edit-input otp-locked" 
                       placeholder="Codice" 
                       disabled 
                       autocomplete="off">
                <button type="button" id="btn-email-action" class="btn-action-email" onclick="handleEmailAction()">Invia</button>
                <input type="hidden" name="confirm_email_final" value="1">
            </form>

            <div class="edit-row">
                <input type="text" class="edit-input" disabled value="<?= htmlspecialchars($utente['nome'] ?? '') ?>">
            </div>
            <div class="edit-row">
                <input type="text" class="edit-input" disabled value="<?= htmlspecialchars($utente['cognome'] ?? '') ?>">
            </div>
            <div class="edit-row">
                <input type="text" class="edit-input" disabled value="<?= htmlspecialchars($utente['codice_fiscale'] ?? '') ?>">
            </div>

        </div>
    </div>

    <div class="info_column extend_all">
        
        <div class="section">
            <h2>Badge</h2>
            <div class="grid">
                <?php if ($badges): foreach ($badges as $badge): endforeach; else: ?>
                    <h4 style="color:#888;">Nessun badge acquisito</h4>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Prestiti in corso</h2>
            <div class="grid">
                <?php if ($prestiti_attivi): foreach ($prestiti_attivi as $libro): 
                    $scadenza_data = formatCounter($libro['data_scadenza']); 
                ?>
                        <div class="book-item">
                            <a href="./libro?isbn=<?= htmlspecialchars($libro['isbn']) ?>" class="card cover-only">
                                <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Libro">
                            </a>
                            <div class="book-meta" style="color: <?= $scadenza_data[1] ?>;">
                                <?= $scadenza_data[0] ?>
                            </div>
                            
                            <div class="mini-actions">
                                <?php if ($libro['stato_richiesta'] == 'in_attesa'): ?>
                                    <button class="btn-mini btn-mini-pending" disabled>In attesa...</button>
                                <?php else: ?>
                                    <form method="POST" action="profilo" style="width:100%;">
                                        <input type="hidden" name="action" value="richiedi_estensione">
                                        <input type="hidden" name="id_copia" value="<?= $libro['id_copia'] ?>">
                                        <input type="hidden" name="scadenza_attuale" value="<?= $libro['data_scadenza'] ?>">
                                        <button type="submit" class="btn-mini btn-mini-action">Estendi (+)</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                        </div>
                <?php endforeach; else: ?>
                    <h4 style="color:#888;">Nessun prestito attivo</h4>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Prenotazioni</h2>
            <div class="grid">
                <?php if ($prenotazioni): foreach ($prenotazioni as $libro): 
                    $data_scadenza_pren = date('Y-m-d', strtotime($libro['data_prenotazione'] . ' + 2 days'));
                    $scadenza_data = formatCounter($data_scadenza_pren);
                    
                    // Messaggio Coda
                    $queue_count = $libro['utenti_davanti'];
                    $queue_msg = ($queue_count == 0) 
                        ? '<span style="color:#27ae60; font-weight:bold; font-size:0.75rem;">Sei il prossimo!</span>' 
                        : "<span style='color:#e67e22; font-weight:bold; font-size:0.75rem;'>$queue_count utenti davanti</span>";
                ?>
                        <div class="book-item">
                            <a href="./libro?isbn=<?= htmlspecialchars($libro['isbn']) ?>" class="card cover-only">
                                <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Libro">
                            </a>
                            <div class="book-meta" style="color: <?= $scadenza_data[1] ?>;">
                                <?= $scadenza_data[0] ?>
                            </div>
                            <div class="book-meta">
                                <?= $queue_msg ?>
                            </div>
                            
                            <div class="mini-actions">
                                <form method="POST" action="profilo" style="width:100%;">
                                    <input type="hidden" name="action" value="annulla_prenotazione">
                                    <input type="hidden" name="id_prenotazione" value="<?= $libro['id_prenotazione'] ?>">
                                    <button type="submit" class="btn-mini btn-mini-danger">Annulla</button>
                                </form>
                            </div>

                        </div>
                <?php endforeach; else: ?>
                    <h4 style="color:#888;">Nessuna prenotazione attiva</h4>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Libri Letti</h2>
            <div class="grid">
                <?php if ($libri_letti): foreach ($libri_letti as $libro): ?>
                        <div class="book-item">
                            <a href="./libro?isbn=<?= htmlspecialchars($libro['isbn']) ?>" class="card cover-only">
                                <img src="<?= getCoverPath($libro['isbn']) ?>" alt="Libro">
                            </a>
                        </div>
                <?php endforeach; else: ?>
                    <h4 style="color:#888;">Nessun libro ancora letto</h4>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<div id="modalTessera" class="modal-overlay">
    <div class="modal-content">
        <span class="close-modal" onclick="chiudiTessera()">&times;</span>
        <div id="tessera-card">
            <div class="tessera-header">BibliotecaScrum</div>
            <div class="tessera-user">
                <?= htmlspecialchars(($utente['nome'] ?? '') . ' ' . ($utente['cognome'] ?? '')) ?>
            </div>
            <div class="tessera-barcode">*8473264*</div>
        </div>
        <div class="modal-actions">
            <button class="btn-action btn-download" onclick="scaricaPNG()">Scarica PNG</button>
            <button class="btn-action btn-print" onclick="stampa()">Stampa</button>
        </div>
    </div>
</div>

<div id="notification-banner">
    <span id="banner-msg" class="notification-text">Notifica</span>
    <button class="close-btn-banner" onclick="hideNotification()">&times;</button>
</div>

<script>
    // --- GESTIONE BANNER NOTIFICA ---
    let timeoutId;

    function showNotification(message) {
        const banner = document.getElementById('notification-banner');
        const msgSpan = document.getElementById('banner-msg');
        
        msgSpan.innerText = message;
        banner.classList.add('show');

        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(() => { hideNotification(); }, 5000);
    }

    function hideNotification() {
        document.getElementById('notification-banner').classList.remove('show');
    }

    // --- CONTROLLO MESSAGGIO PHP AL CARICAMENTO ---
    const serverMessage = "<?= addslashes($messaggio_alert) ?>";
    if (serverMessage.length > 0) {
        setTimeout(() => { showNotification(serverMessage); }, 500);
    }

    // --- USERNAME ---
    const inpUser = document.getElementById('inp-username');
    const rowUser = document.getElementById('row-username');
    const btnUser = document.getElementById('btn-user');

    inpUser.addEventListener('input', function() {
        if (this.value !== this.dataset.original) {
            rowUser.classList.add('changed');
            btnUser.innerText = "Salva"; btnUser.classList.remove('btn-success-anim');
        } else {
            rowUser.classList.remove('changed');
        }
    });

    async function ajaxSaveUsername() {
        const newVal = inpUser.value;
        const formData = new FormData();
        formData.append('ajax_username', newVal);

        try {
            const response = await fetch(window.location.href, { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.status === 'success') {
                showNotification(data.message);
                btnUser.innerText = "Fatto!";
                btnUser.classList.add('btn-success-anim');
                inpUser.dataset.original = newVal;
                setTimeout(() => { rowUser.classList.remove('changed'); }, 1500);
            } else { 
                showNotification(data.message);
            }
        } catch (error) { 
            showNotification("Errore di connessione al server."); 
        }
    }

    // --- EMAIL LOGIC ---
    const boxEmailOtp = document.getElementById('box-email-otp');
    const inpEmail = document.getElementById('inp-email');
    const inpOtp = document.getElementById('inp-otp');
    const btnEmailAction = document.getElementById('btn-email-action');
    let emailStep = 1; 

    function handleEmailInput(input) {
        if (input.value !== input.dataset.original) {
            boxEmailOtp.classList.add('open');
            resetEmailState(); 
        } else {
            boxEmailOtp.classList.remove('open');
        }
    }

    function resetEmailState() {
        emailStep = 1;
        btnEmailAction.innerText = "Invia";
        btnEmailAction.type = "button";
        inpOtp.disabled = true;
        inpOtp.classList.add('otp-locked');
        inpOtp.value = "";
    }

    async function handleEmailAction() {
        if (emailStep === 1) {
            const newEmail = inpEmail.value;
            const formData = new FormData();
            formData.append('ajax_send_email_code', 1);
            formData.append('email_dest', newEmail);
            
            btnEmailAction.innerText = "...";

            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();

                if (data.status === 'success') {
                    showNotification(data.message);
                    emailStep = 2;
                    inpOtp.disabled = false;
                    inpOtp.classList.remove('otp-locked');
                    inpOtp.focus();
                    btnEmailAction.innerText = "Conferma";
                    btnEmailAction.type = "submit"; 
                } else {
                    showNotification(data.message);
                    btnEmailAction.innerText = "Invia";
                }
            } catch (e) {
                console.error(e);
                showNotification("Errore di rete.");
                btnEmailAction.innerText = "Invia";
            }
        }
    }

    // Modale e Stampe
    const modal = document.getElementById('modalTessera');
    function apriTessera() { modal.style.display = 'flex'; }
    function chiudiTessera() { modal.style.display = 'none'; }
    window.onclick = function(event) { if (event.target == modal) { chiudiTessera(); } }
    function stampa() { window.print(); }
    function scaricaPNG() {
        const elemento = document.getElementById("tessera-card");
        html2canvas(elemento, { backgroundColor: "#ffffff", scale: 3 }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'Tessera_BibliotecaScrum.png';
            link.href = canvas.toDataURL("image/png");
            link.click();
        });
    }
</script>

<?php require './src/includes/footer.php'; ?>