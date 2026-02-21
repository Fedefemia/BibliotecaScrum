<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Includiamo la configurazione
require_once 'db_config.php';

// ---------------- HTML HEADER ----------------
$title = "Privacy Policy - Biblioteca Scrum";
$path = "./";
$page_css = "./public/css/style_index.css";
require './src/includes/header.php';
require './src/includes/navbar.php';
?>

<style>
    .privacy-container {
        max-width: 900px;
        margin: 50px auto;
        background: #fff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        font-family: 'Instrument Sans', sans-serif;
    }

    .privacy-header {
        text-align: center;
        margin-bottom: 40px;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 20px;
    }

    .privacy-header h1 {
        color: #2c3e50;
        font-size: 2.5rem;
        margin-bottom: 10px;
        font-weight: 700;
    }

    .privacy-intro {
        font-size: 1.1rem;
        color: #555;
        line-height: 1.6;
        text-align: center;
        margin-bottom: 40px;
    }

    .privacy-section {
        margin-bottom: 35px;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 8px;
        transition: transform 0.2s ease;
    }

    .privacy-section:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .privacy-section h3 {
        color: #3f5135; /* Colore tema biblioteca */
        font-size: 1.4rem;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .privacy-section p {
        color: #444;
        line-height: 1.7;
        margin: 0;
    }

    .privacy-icon {
        font-size: 1.2em;
        color: #3f5135;
    }

    .privacy-footer {
        text-align: center;
        margin-top: 50px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        color: #888;
        font-size: 0.9rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .privacy-container {
            margin: 20px;
            padding: 20px;
        }
        .privacy-header h1 {
            font-size: 2rem;
        }
    }
</style>

<div class="privacy-container">
    <div class="privacy-header">
        <h1>Informativa sulla Privacy</h1>
    </div>
    
    <p class="privacy-intro">
        Benvenuto nella pagina dedicata alla tua privacy. Alla Biblioteca Scrum crediamo che la trasparenza sia alla base della fiducia. 
        Qui ti spieghiamo in modo chiaro e semplice come proteggiamo i tuoi dati personali e i tuoi diritti.
    </p>

    <div class="privacy-section">
        <h3><i class="fas fa-shield-alt privacy-icon"></i> Sicurezza e Crittografia</h3>
        <p>
            La tua sicurezza non è un optional. Utilizziamo protocolli di <strong>crittografia avanzata (AES-256)</strong> per proteggere tutte le informazioni sensibili memorizzate nei nostri database. 
            Le password non vengono mai salvate in chiaro, ma sono protette tramite hashing sicuro. Questo significa che nessuno, nemmeno il nostro staff, può leggere la tua password.
        </p>
    </div>

    <div class="privacy-section">
        <h3><i class="fas fa-user-secret privacy-icon"></i> Nessuna Vendita a Terzi</h3>
        <p>
            I tuoi dati sono tuoi, e tali rimarranno. <strong>Non vendiamo, affittiamo o cediamo</strong> le tue informazioni personali a società di marketing, inserzionisti o terze parti. 
            I dati che raccogliamo (come nome, email e storico prestiti) servono esclusivamente per garantirti il servizio di prestito libri e per comunicazioni di servizio essenziali.
        </p>
    </div>

    <div class="privacy-section">
        <h3><i class="fas fa-cookie-bite privacy-icon"></i> Cookie e Tracciamento</h3>
        <p>
            Il nostro sito utilizza solo cookie tecnici essenziali per il funzionamento della sessione di login e per ricordare le tue preferenze di navigazione (come la biblioteca selezionata). 
            Non utilizziamo cookie di profilazione invasivi per tracciare le tue abitudini di lettura al di fuori del nostro portale.
        </p>
    </div>

    <div class="privacy-section">
        <h3><i class="fas fa-user-check privacy-icon"></i> I Tuoi Diritti (GDPR)</h3>
        <p>
            In conformità con il Regolamento Generale sulla Protezione dei Dati (GDPR), hai il diritto di:
            <ul style="margin-top: 10px; padding-left: 20px; color: #444;">
                <li>Accedere ai tuoi dati personali in qualsiasi momento.</li>
                <li>Richiedere la rettifica di dati inesatti.</li>
                <li>Richiedere la cancellazione completa del tuo account ("Diritto all'oblio").</li>
                <li>Esportare i tuoi dati in un formato leggibile.</li>
            </ul>
        </p>
    </div>

    <div class="privacy-section">
        <h3><i class="fas fa-envelope privacy-icon"></i> Contattaci</h3>
        <p>
            Se hai domande, dubbi o vuoi esercitare i tuoi diritti sulla privacy, il nostro Responsabile della Protezione Dati è a tua disposizione. 
            Puoi contattarci tramite la pagina <a href="./contatti" style="color: #3f5135; text-decoration: underline;">Contatti</a> o venendo direttamente in sede.
        </p>
    </div>

    <div class="privacy-footer">
        <p>Ultimo aggiornamento: <?php echo date("d/m/Y"); ?> &bull; Biblioteca Scrum &copy; <?php echo date("Y"); ?></p>
    </div>
</div>

<?php require_once './src/includes/footer.php'; ?>