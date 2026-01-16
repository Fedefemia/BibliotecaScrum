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
    $id_prestito_target = filter_input(INPUT_POST, 'id_prestito', FILTER_VALIDATE_INT);
    $scadenza_attuale = $_POST['scadenza_attuale'] ?? null;

    if ($id_prestito_target && $scadenza_attuale) {
        try {
            // Verifica che il prestito appartenga all'utente loggato E controlla il numero di rinnovi
            $chkOwner = $pdo->prepare("SELECT num_rinnovi FROM prestiti WHERE id_prestito = ? AND codice_alfanumerico = ?");
            $chkOwner->execute([$id_prestito_target, $uid]);
            $loanData = $chkOwner->fetch(PDO::FETCH_ASSOC);
            
            if ($loanData) {
                // CONTROLLO LIMITE RINNOVI (Max 1)
                if ($loanData['num_rinnovi'] >= 1) {
                    $messaggio_alert = "Hai già effettuato il numero massimo di rinnovi per questo libro.";
                } else {
                    // Verifica se c'è già una richiesta pendente per questo ID PRESTITO
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
            // Verifica che la multa appartenga a un prestito dell'utente loggato
            $chk = $pdo->prepare("
                SELECT m.id_multa 
                FROM multe m
                JOIN prestiti p ON m.id_prestito = p.id_prestito
                WHERE m.id_multa = ? AND p.codice_alfanumerico = ? AND m.pagata = 0
            ");
            $chk->execute([$id_multa_target, $uid]);
            
            if ($chk->fetch()) {
                // Esegui pagamento
                $upd = $pdo->prepare("UPDATE multe SET pagata = 1 WHERE id_multa = ?");
                $upd->execute([$id_multa_target]);
                $messaggio_alert = "Pagamento registrato con successo! Grazie.";
            } else {
                $messaggio_alert = "Impossibile elaborare il pagamento.";
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

// A. MULTE ATTIVE
$stm = $pdo->prepare("
    SELECT m.id_multa, m.importo, m.causale, m.data_creata, l.titolo
    FROM multe m
    JOIN prestiti p ON m.id_prestito = p.id_prestito
    JOIN copie c ON p.id_copia = c.id_copia
    JOIN libri l ON c.isbn = l.isbn
    WHERE p.codice_alfanumerico = ? AND m.pagata = 0
    ORDER BY m.data_creata DESC
");
$stm->execute([$uid]);
$multe_attive = $stm->fetchAll(PDO::FETCH_ASSOC);

// B. PRESTITI ATTIVI
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

// C. PRENOTAZIONI ATTIVE
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

// D. STORICO LIBRI LETTI
$stm = $pdo->prepare("
    SELECT DISTINCT c.isbn FROM prestiti p JOIN copie c ON p.id_copia = c.id_copia 
    WHERE p.codice_alfanumerico = ? AND p.data_restituzione IS NOT NULL
    ORDER BY p.data_restituzione DESC
");
$stm->execute([$uid]);
$libri_letti = $stm->fetchAll(PDO::FETCH_ASSOC);

// STATISTICHE
$totale_libri_letti = count($libri_letti);
$stm = $pdo->prepare("SELECT MIN(data_restituzione) as inizio, MAX(data_restituzione) as fine FROM prestiti WHERE codice_alfanumerico = ? AND data_restituzione IS NOT NULL");
$stm->execute([$uid]);
$range_date = $stm->fetch(PDO::FETCH_ASSOC);
$media_mensile = 0;
if ($range_date['inizio']) {
    $d1 = new DateTime($range_date['inizio']); $d2 = new DateTime(); $diff = $d1->diff($d2);
    $mesi_totali = ($diff->y * 12) + $diff->m; if ($mesi_totali < 1) $mesi_totali = 1;
    $media_mensile = round($totale_libri_letti / $mesi_totali, 1);
}
$stm = $pdo->prepare("SELECT DATE_FORMAT(data_restituzione, '%b %Y') as mese, COUNT(*) as qta FROM prestiti WHERE codice_alfanumerico = ? AND data_restituzione IS NOT NULL AND data_restituzione >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY mese ORDER BY MIN(data_restituzione) ASC");
$stm->execute([$uid]);
$storico_stat = $stm->fetchAll(PDO::FETCH_ASSOC);
$max_libri_mese = 0;
foreach($storico_stat as $s) { if($s['qta'] > $max_libri_mese) $max_libri_mese = $s['qta']; }
$badges = [];

if (isset($uid) && $uid) {
    try {
        $stm = $pdo->prepare("
            SELECT b.*, ub.livello
            FROM badge b
            JOIN utente_badge ub ON b.id_badge = ub.id_badge
            WHERE ub.codice_alfanumerico = ?
            ORDER BY ub.livello DESC, b.nome ASC
        ");
        $stm->execute([$uid]);
        $badges = $stm->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $messaggio_db = "Errore caricamento badge: " . $e->getMessage();
    }
}
function badgeIconHtmlProfile(array $badge) {
    $icon = $badge['icona'] ?? '';
    // Primo tentativo: file in public/badges/
    $localPath = __DIR__ . "/../public/badges/" . $icon;
    $webPath = "./public/badges/" . $icon;
    if ($icon && file_exists($localPath)) {
        return '<img src="' . htmlspecialchars($webPath) . '" alt="' . htmlspecialchars($badge['nome']) . '" style="width:72px;height:72px;object-fit:contain;border-radius:8px;">';
    }
    // Non uso SVG inline qui per sicurezza — fallback lettera
    $letter = strtoupper(substr($badge['nome'] ?? 'B', 0, 1));
    return '<div style="width:72px;height:72px;border-radius:10px;background:#f3f3f3;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:28px;color:#666;">' .
            htmlspecialchars($letter) . '</div>';
}

require './src/includes/header.php';
require './src/includes/navbar.php';

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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Libre+Barcode+39+Text&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .grid { display: flex; flex-wrap: wrap; gap: 25px; }
        .book-item { display: flex; flex-direction: column; width: 120px; align-items: center; gap: 5px; }
        .card.cover-only { width: 120px; display: block; text-decoration: none; color: #333; margin-bottom: 0; }
        .card.cover-only img { width: 120px; height: 180px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: transform 0.2s; }
        .card.cover-only:hover img { transform: translateY(-3px); }
        .book-meta { font-size: 0.75rem; text-align: center; line-height: 1.3; font-weight: 500; width: 100%; }
        .mini-actions { width: 100%; display: flex; justify-content: center; gap: 5px; margin-top: 5px; }
        .btn-mini { padding: 4px 8px; border: none; border-radius: 4px; font-size: 0.7rem; font-weight: 600; cursor: pointer; transition: background 0.2s; width: 100%; }
        .btn-mini-danger { background-color: #e74c3c; color: white; }
        .btn-mini-action { background-color: #3498db; color: white; }
        .btn-mini-pending { background-color: #f39c12; color: white; cursor: default; }
        .btn-mini-limit { background-color: #7f8c8d; color: white; cursor: not-allowed; }
        
        .info_column { display: flex; flex-direction: column; width: auto; justify-content: flex-start; align-items: center; gap: 10px; }
        .info_line { display: flex; flex-direction: row; width: 100%; justify-content: space-between; align-items: flex-start; gap: 20px; padding-top: 20px; }
        .pfp-wrapper { position: relative; width: 240px; height: 240px; border-radius: 50%; border: 5px solid #3f5135; overflow: hidden; cursor: pointer; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .info_pfp { width: 100%; height: 100%; object-fit: cover; display: block; }
        .pfp-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); display: flex; flex-direction: column; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; color: #fff; }
        .pfp-wrapper:hover .pfp-overlay { opacity: 1; }
        .pfp-icon { font-size: 24px; margin-bottom: 5px; }
        .pfp-text { font-size: 14px; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; }
        .extend_all { width: 100%; height: 100%; justify-content: space-between; align-items: flex-start; }
        .section { width: 100%; height: auto; display: flex; flex-direction: column; margin-bottom: 30px; }
        .edit-container-wrapper { margin-top: 10px; width: 260px; display: flex; flex-direction: column; gap: 8px; }
        .edit-row { width: 100%; display: flex; align-items: center; }
        .edit-input { flex: 1; min-width: 0; padding: 8px; border: 1px solid #ccc; border-radius: 4px; color: #333; font-family: 'Poppins', sans-serif; font-size: 1em; transition: all 0.3s ease; }
        .edit-input:disabled { background: #eee; color: #666; border: 1px solid transparent; }
        .btn-slide { width: 0; padding: 0; opacity: 0; margin-left: 0; overflow: hidden; white-space: nowrap; background-color: #3f5135; color: white; border: none; border-radius: 4px; font-size: 0.9em; cursor: pointer; transition: all 0.4s ease; }
        .edit-row.changed .btn-slide { width: 80px; padding: 8px 0; opacity: 1; margin-left: 5px; }
        .email-expand-box { max-height: 0; opacity: 0; overflow: hidden; transition: all 0.5s ease; display: flex; gap: 5px; width: 100%; }
        .email-expand-box.open { max-height: 50px; opacity: 1; margin-top: -3px; }
        .otp-locked { background-color: #e0e0e0; color: #999; cursor: not-allowed; border-color: #ddd; }
        .btn-action-email { background-color: #3f5135; color: white; border: none; border-radius: 4px; padding: 8px; cursor: pointer; font-family: 'Poppins', sans-serif; width: 80px; transition: background 0.3s; }
        .btn-action-email:hover { background-color: #2c3a24; }
        .btn-success-anim { background-color: #27ae60 !important; }
        
        /* STYLE MULTE COMPATTO */
        .fine-container {
            width: 100%;
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .fine-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff5f5;
            border: 1px solid #feb2b2;
            border-left: 4px solid #c0392b;
            padding: 10px;
            border-radius: 6px;
            width: 100%;
            box-sizing: border-box;
            transition: transform 0.2s;
        }
        .fine-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        .fine-info { 
            display: flex; 
            flex-direction: column; 
            overflow: hidden; 
            margin-right: 10px;
        }
        
        .fine-title { 
            font-weight: 600; 
            font-size: 0.85rem; 
            color: #c0392b; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }
        
        .fine-meta { 
            font-size: 0.75rem; 
            color: #666; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
        }

        .fine-actions { 
            display: flex; 
            flex-direction: column; 
            align-items: flex-end; 
            gap: 4px; 
            flex-shrink: 0;
        }
        
        .fine-price { font-weight: 700; font-size: 0.95rem; color: #c0392b; }
        
        .btn-pay { 
            background-color: #27ae60; 
            color: white; 
            border: none; 
            padding: 4px 10px; 
            border-radius: 4px; 
            font-size: 0.75rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: background 0.2s; 
        }
        .btn-pay:hover { background-color: #219150; }

        /* MODAL STYLES */
        .modal-overlay { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); justify-content: center; align-items: center; backdrop-filter: blur(3px); }
        .modal-content { background-color: white; padding: 25px; border-radius: 12px; width: 100%; max-width: 400px; text-align: center; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close-modal { position: absolute; top: 15px; right: 20px; font-size: 24px; font-weight: bold; color: #999; cursor: pointer; transition: color 0.2s; }
        .close-modal:hover { color: #333; }
        .btn-tessera { margin-top: 10px; padding: 10px 20px; background-color: #3f5135; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; font-family: 'Poppins', sans-serif; transition: background 0.3s; }
        #tessera-card { font-family: 'Poppins', sans-serif; background-color: #ffffff; color: #000000; border: 2px solid #000; border-radius: 12px; width: 340px; height: 215px; margin: 25px auto; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.15); box-sizing: border-box; }
        .tessera-header { font-size: 1.1em; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; border-bottom: 2px solid #000; padding-bottom: 8px; width: 100%; text-align: center; }
        .tessera-user { font-size: 1.3em; font-weight: 500; text-align: center; word-wrap: break-word; margin-top: 10px; }
        .tessera-barcode { font-family: 'Libre Barcode 39 Text', cursive; font-size: 42px; color: #000; line-height: 1; white-space: nowrap; margin-bottom: 5px; }
        .modal-actions { display: flex; justify-content: center; gap: 15px; margin-top: 15px; }
        .btn-action { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; flex: 1; transition: all 0.2s ease; }
        .btn-print { background-color: #3f5135; color: white; }
        .btn-download { background-color: #f0f0f0; color: #333; }
        
        .btn-modal-pay { background-color: #27ae60; color: white; }
        .btn-modal-cancel { background-color: #e74c3c; color: white; }

        #notification-banner { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background-color: #222; color: white; padding: 14px 24px; border-radius: 6px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 20px; transition: bottom 0.5s; z-index: 9999; min-width: 250px; justify-content: space-between; }
        #notification-banner.show { bottom: 30px; }
        .notification-text { font-size: 15px; font-weight: 500; }
        .close-btn-banner { background: none; border: none; color: #bbb; font-size: 22px; cursor: pointer; padding: 0; line-height: 1; }
    </style>

    <div class="info_line">
        <div class="info_column">
            <?php
            $pfpPath = 'public/pfp/' . htmlspecialchars($uid) . '.png';
            if (!file_exists($pfpPath)) { $pfpPath = 'public/assets/base_pfp.png'; }
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
                    <input type="text" id="inp-username" class="edit-input" value="<?= htmlspecialchars($utente['username'] ?? '') ?>" data-original="<?= htmlspecialchars($utente['username'] ?? '') ?>" placeholder="Username">
                    <button type="button" id="btn-user" class="btn-slide" onclick="ajaxSaveUsername()">Salva</button>
                </div>
                <div class="edit-row">
                    <input type="email" id="inp-email" class="edit-input" value="<?= htmlspecialchars($utente['email'] ?? '') ?>" data-original="<?= htmlspecialchars($utente['email'] ?? '') ?>" placeholder="Email" oninput="handleEmailInput(this)">
                </div>
                <form method="post" class="email-expand-box" id="box-email-otp">
                    <input type="text" name="otp_code" id="inp-otp" class="edit-input otp-locked" placeholder="Codice" disabled autocomplete="off">
                    <button type="button" id="btn-email-action" class="btn-action-email" onclick="handleEmailAction()">Invia</button>
                    <input type="hidden" name="confirm_email_final" value="1">
                </form>
                <div class="edit-row"><input type="text" class="edit-input" disabled value="<?= htmlspecialchars($utente['nome'] ?? '') ?>"></div>
                <div class="edit-row"><input type="text" class="edit-input" disabled value="<?= htmlspecialchars($utente['cognome'] ?? '') ?>"></div>
                <div class="edit-row"><input type="text" class="edit-input" disabled value="<?= htmlspecialchars($utente['codice_fiscale'] ?? '') ?>"></div>
            </div>

            <?php if (!empty($multe_attive)): ?>
                <div class="fine-container">
                    <h4 style="margin:0; color:#c0392b; font-size:0.9rem; border-bottom:1px solid #eee; padding-bottom:5px; width:100%;">
                        Multe da saldare (<?= count($multe_attive) ?>)
                    </h4>
                    <?php foreach ($multe_attive as $m): ?>
                        <div class="fine-card">
                            <div class="fine-info">
                                <span class="fine-title" title="<?= htmlspecialchars($m['titolo']) ?>">
                                    <?= htmlspecialchars($m['titolo']) ?>
                                </span>
                                <span class="fine-meta" title="<?= htmlspecialchars($m['causale']) ?>">
                                    <?= htmlspecialchars($m['causale']) ?>
                                </span>
                            </div>
                            <div class="fine-actions">
                                <span class="fine-price">€ <?= number_format($m['importo'], 2) ?></span>
                                <button class="btn-pay" onclick="apriPagamento(<?= $m['id_multa'] ?>, '<?= number_format($m['importo'], 2) ?>')">Paga</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="info_column extend_all">
            <div class="section">
                <h2>Badge</h2>
                <?php if (!empty($badges)): ?>
                    <?php foreach ($badges as $b): ?>
                        <div class="book-item">
                            <a href="./badge?id=<?= intval($b['id_badge']) ?>" class="card cover-only">
                                <?= badgeIconHtmlProfile($b) ?>
                            </a>
                            <div class="book-meta">
                                <div class="book_main_title" style="font-size:1rem; margin:0;">
                                    <?= htmlspecialchars($b['nome']) ?>
                                </div>

                                <div class="book_authors" style="margin-top:6px; font-size:0.9rem;">
                                    Livello: <strong><?= intval($b['livello']) ?></strong>
                                    <?php if (!empty($b['target_numerico'])): ?>
                                        &nbsp;•&nbsp; Target: <?= intval($b['target_numerico']) ?>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($b['descrizione'])): ?>
                                    <div class="book_desc_text" style="margin-top:8px; font-size:0.9rem; max-height:48px; overflow:hidden;">
                                        <?= nl2br(htmlspecialchars($b['descrizione'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <h4 style="color:#888;">Nessun badge acquisito</h4>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <h2>Prestiti in corso</h2>
                <div class="grid">
                    <?php if ($prestiti_attivi): foreach ($prestiti_attivi as $libro):
                        $scadenza_data = formatCounter($libro['data_scadenza']); 
                        $rinnovi_effettuati = $libro['num_rinnovi'] ?? 0;
                        ?>
                        <div class="book-item">
                            <a href="./libro?isbn=<?= htmlspecialchars($libro['isbn']) ?>" class="card cover-only"><img src="<?= getCoverPath($libro['isbn']) ?>" alt="Libro"></a>
                            <div class="book-meta" style="color: <?= $scadenza_data[1] ?>;"><?= $scadenza_data[0] ?></div>
                            <div class="mini-actions">
                                <?php if ($libro['stato_richiesta'] == 'in_attesa'): ?>
                                    <button class="btn-mini btn-mini-pending" disabled>In attesa...</button>
                                <?php elseif ($rinnovi_effettuati >= 1): ?>
                                    <button class="btn-mini btn-mini-limit" disabled title="Hai già esteso questo prestito">Limite raggiunto</button>
                                <?php else: ?>
                                    <form method="POST" action="profilo" style="width:100%;">
                                        <input type="hidden" name="action" value="richiedi_estensione">
                                        <input type="hidden" name="id_prestito" value="<?= $libro['id_prestito'] ?>">
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
                        $queue_count = $libro['utenti_davanti'];
                        $queue_msg = ($queue_count == 0) ? '<span style="color:#27ae60; font-weight:bold; font-size:0.75rem;">Sei il prossimo!</span>' : "<span style='color:#e67e22; font-weight:bold; font-size:0.75rem;'>$queue_count utenti davanti</span>";
                        ?>
                        <div class="book-item">
                            <a href="./libro?isbn=<?= htmlspecialchars($libro['isbn']) ?>" class="card cover-only"><img src="<?= getCoverPath($libro['isbn']) ?>" alt="Libro"></a>
                            <div class="book-meta" style="color: <?= $scadenza_data[1] ?>;"><?= $scadenza_data[0] ?></div>
                            <div class="book-meta"><?= $queue_msg ?></div>
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
                            <a href="./libro?isbn=<?= htmlspecialchars($libro['isbn']) ?>" class="card cover-only"><img src="<?= getCoverPath($libro['isbn']) ?>" alt="Libro"></a>
                        </div>
                    <?php endforeach; else: ?>
                        <h4 style="color:#888;">Nessun libro ancora letto</h4>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="section">
                <h2>Le Mie Statistiche</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div style="background: #f4f7f2; padding: 15px; border-radius: 12px; border-left: 4px solid #3f5135;">
                        <span style="display: block; font-size: 0.8rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Libri Totali</span>
                        <div style="display: flex; align-items: baseline; gap: 8px;">
                            <strong style="font-size: 2rem; color: #3f5135;"><?= $totale_libri_letti ?></strong>
                            <span style="font-size: 0.9rem; color: #888;">Letti</span>
                        </div>
                    </div>
                    <div style="background: #fef9f4; padding: 15px; border-radius: 12px; border-left: 4px solid #e67e22;">
                        <span style="display: block; font-size: 0.8rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Media Mensile</span>
                        <div style="display: flex; align-items: baseline; gap: 8px;">
                            <strong style="font-size: 2rem; color: #e67e22;"><?= $media_mensile ?></strong>
                            <span style="font-size: 0.9rem; color: #888;">Libri/mese</span>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <h3 style="font-size: 1rem; margin-bottom: 20px; color: #333; font-weight: 600;">Attività Recente</h3>
                    <?php if ($storico_stat): ?>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <?php foreach ($storico_stat as $s):
                                $percentuale = ($max_libri_mese > 0) ? ($s['qta'] / $max_libri_mese) * 100 : 0;
                                $percentuale = max($percentuale, 5);
                                ?>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="width: 70px; font-size: 0.85rem; color: #666; text-align: right;"><?= $s['mese'] ?></div>
                                    <div style="flex: 1; background: #f0f0f0; height: 12px; border-radius: 6px; overflow: hidden;">
                                        <div style="width: <?= $percentuale ?>%; background: #3f5135; height: 100%; border-radius: 6px; transition: width 0.5s ease;"></div>
                                    </div>
                                    <div style="width: 60px; font-size: 0.85rem; color: #3f5135; font-weight: 600;"><?= $s['qta'] ?> <?= $s['qta'] == 1 ? 'Libro' : 'Libri' ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #888; background: #fafafa; border-radius: 12px;">Nessun dato storico disponibile per generare il grafico.</div>
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
                <div class="tessera-user"><?= htmlspecialchars(($utente['nome'] ?? '') . ' ' . ($utente['cognome'] ?? '')) ?></div>
                <div class="tessera-barcode">*8473264*</div>
            </div>
            <div class="modal-actions">
                <button class="btn-action btn-download" onclick="scaricaPNG()">Scarica PNG</button>
                <button class="btn-action btn-print" onclick="stampa()">Stampa</button>
            </div>
        </div>
    </div>

    <div id="modalPagamento" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="chiudiPagamento()">&times;</span>
            <h3 style="margin-top:0; color:#333;">Conferma Pagamento</h3>
            <p style="margin:20px 0; color:#555;">Stai per pagare una multa di:</p>
            <h2 style="color:#27ae60; margin:0;">€ <span id="payAmountDisplay">0.00</span></h2>
            
            <p style="font-size:0.85em; color:#999; margin-top:20px;">
                Il pagamento sarà simulato e registrato immediatamente nel sistema.
            </p>

            <form method="POST" action="profilo">
                <input type="hidden" name="action" value="paga_multa_user">
                <input type="hidden" name="id_multa" id="payMultaId">
                <div class="modal-actions">
                    <button type="button" class="btn-action btn-modal-cancel" onclick="chiudiPagamento()">Annulla</button>
                    <button type="submit" class="btn-action btn-modal-pay">Conferma Pagamento</button>
                </div>
            </form>
        </div>
    </div>

    <div id="notification-banner">
        <span id="banner-msg" class="notification-text">Notifica</span>
        <button class="close-btn-banner" onclick="hideNotification()">&times;</button>
    </div>

    <script>
        let timeoutId;
        function showNotification(message) {
            const banner = document.getElementById('notification-banner');
            const msgSpan = document.getElementById('banner-msg');
            msgSpan.innerText = message;
            banner.classList.add('show');
            if (timeoutId) clearTimeout(timeoutId);
            timeoutId = setTimeout(() => { hideNotification(); }, 5000);
        }
        function hideNotification() { document.getElementById('notification-banner').classList.remove('show'); }

        const serverMessage = "<?= addslashes($messaggio_alert) ?>";
        if (serverMessage.length > 0) { setTimeout(() => { showNotification(serverMessage); }, 500); }

        // GESTIONE USERNAME AJAX
        const inpUser = document.getElementById('inp-username');
        const rowUser = document.getElementById('row-username');
        const btnUser = document.getElementById('btn-user');
        inpUser.addEventListener('input', function() {
            if (this.value !== this.dataset.original) {
                rowUser.classList.add('changed');
                btnUser.innerText = "Salva"; btnUser.classList.remove('btn-success-anim');
            } else { rowUser.classList.remove('changed'); }
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
                } else { showNotification(data.message); }
            } catch (error) { showNotification("Errore di connessione."); }
        }

        // GESTIONE EMAIL OTP
        const boxEmailOtp = document.getElementById('box-email-otp');
        const inpEmail = document.getElementById('inp-email');
        const inpOtp = document.getElementById('inp-otp');
        const btnEmailAction = document.getElementById('btn-email-action');
        let emailStep = 1;
        function handleEmailInput(input) {
            if (input.value !== input.dataset.original) { boxEmailOtp.classList.add('open'); resetEmailState(); }
            else { boxEmailOtp.classList.remove('open'); }
        }
        function resetEmailState() {
            emailStep = 1; btnEmailAction.innerText = "Invia"; btnEmailAction.type = "button";
            inpOtp.disabled = true; inpOtp.classList.add('otp-locked'); inpOtp.value = "";
        }
        async function handleEmailAction() {
            if (emailStep === 1) {
                const formData = new FormData();
                formData.append('ajax_send_email_code', 1);
                formData.append('email_dest', inpEmail.value);
                btnEmailAction.innerText = "...";
                try {
                    const response = await fetch(window.location.href, { method: 'POST', body: formData });
                    const data = await response.json();
                    if (data.status === 'success') {
                        showNotification(data.message);
                        emailStep = 2; inpOtp.disabled = false; inpOtp.classList.remove('otp-locked'); inpOtp.focus();
                        btnEmailAction.innerText = "Conferma"; btnEmailAction.type = "submit";
                    } else { showNotification(data.message); btnEmailAction.innerText = "Invia"; }
                } catch (e) { showNotification("Errore di rete."); btnEmailAction.innerText = "Invia"; }
            }
        }

        // GESTIONE MODAL TESSERA
        const modalTessera = document.getElementById('modalTessera');
        function apriTessera() { modalTessera.style.display = 'flex'; }
        function chiudiTessera() { modalTessera.style.display = 'none'; }
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

        // GESTIONE MODAL PAGAMENTO
        const modalPay = document.getElementById('modalPagamento');
        function apriPagamento(id, amount) {
            document.getElementById('payMultaId').value = id;
            document.getElementById('payAmountDisplay').innerText = amount;
            modalPay.style.display = 'flex';
        }
        function chiudiPagamento() { modalPay.style.display = 'none'; }

        // CHIUSURA MODAL CLICK ESTERNO
        window.onclick = function(event) {
            if (event.target == modalTessera) chiudiTessera();
            if (event.target == modalPay) chiudiPagamento();
        }
    </script>

<?php require './src/includes/footer.php'; ?>