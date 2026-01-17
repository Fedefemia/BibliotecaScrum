<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_config.php';

// ---------------- HTML HEADER ----------------
$title = "Termini e Condizioni - Biblioteca Scrum";
$path = "./";
$page_css = "./public/css/style_index.css"; // Usa il CSS base se necessario
require './src/includes/header.php';
require './src/includes/navbar.php';
?>

<style>
    .terms-container {
        max-width: 900px;
        margin: 50px auto;
        background: #fff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        font-family: 'Instrument Sans', sans-serif;
    }

    .terms-header {
        text-align: center;
        margin-bottom: 40px;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 20px;
    }

    .terms-header h1 {
        color: #2c3e50;
        font-size: 2.5rem;
        margin-bottom: 10px;
        font-weight: 700;
    }

    .terms-intro {
        font-size: 1.1rem;
        color: #555;
        line-height: 1.6;
        text-align: center;
        margin-bottom: 40px;
    }

    .terms-section {
        margin-bottom: 30px;
    }

    .terms-section h3 {
        color: #3f5135; /* Colore tema biblioteca */
        font-size: 1.4rem;
        margin-bottom: 15px;
        padding-bottom: 5px;
        border-bottom: 1px solid #eee;
    }

    .terms-section p, .terms-section li {
        color: #444;
        line-height: 1.7;
        margin-bottom: 10px;
    }

    .terms-section ul {
        padding-left: 20px;
        list-style-type: disc;
    }

    .terms-footer {
        text-align: center;
        margin-top: 50px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        color: #888;
        font-size: 0.9rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .terms-container {
            margin: 20px;
            padding: 20px;
        }
        .terms-header h1 {
            font-size: 2rem;
        }
    }
</style>

<div class="terms-container">
    <div class="terms-header">
        <h1>Termini e Condizioni d'Uso</h1>
    </div>
    
    <p class="terms-intro">
        L'accesso e l'utilizzo dei servizi offerti dalla Biblioteca Scrum sono subordinati all'accettazione dei seguenti termini e condizioni. 
        Ti preghiamo di leggerli attentamente.
    </p>

    <div class="terms-section">
        <h3>1. Accettazione dei Termini</h3>
        <p>
            Utilizzando il nostro sito web e i servizi di prestito, dichiari di aver letto, compreso e accettato di essere vincolato da questi Termini e Condizioni, nonché dalla nostra <a href="./privacy" style="color: #3f5135;">Informativa sulla Privacy</a>.
        </p>
    </div>

    <div class="terms-section">
        <h3>2. Responsabilità dell'Utente</h3>
        <ul>
            <li><strong>Registrazione:</strong> Per accedere ai servizi di prestito è necessario creare un account fornendo informazioni veritiere e complete.</li>
            <li><strong>Custodia dell'Account:</strong> Sei responsabile della riservatezza delle tue credenziali di accesso e di tutte le attività svolte tramite il tuo account.</li>
            <li><strong>Cura dei Materiali:</strong> I libri e gli altri materiali presi in prestito devono essere trattati con cura. L'utente è responsabile per qualsiasi danno, smarrimento o furto.</li>
        </ul>
    </div>

    <div class="terms-section">
        <h3>3. Regole del Servizio di Prestito</h3>
        <ul>
            <li><strong>Durata del Prestito:</strong> La durata standard del prestito è di 30 giorni, salvo diversa indicazione per specifici materiali.</li>
            <li><strong>Restituzione:</strong> I materiali devono essere restituiti entro la data di scadenza. Ritardi nella restituzione possono comportare la sospensione temporanea del servizio di prestito.</li>
            <li><strong>Sanzioni:</strong> In caso di mancata restituzione o danneggiamento grave di un libro, la biblioteca si riserva il diritto di addebitare all'utente il costo di sostituzione del materiale e di sospendere l'account fino alla risoluzione.</li>
        </ul>
    </div>

    <div class="terms-section">
        <h3>4. Condotta e Uso Consentito</h3>
        <p>
            L'utente si impegna a non utilizzare il servizio per scopi illegali o non autorizzati. È vietato tentare di accedere illegalmente ai sistemi informatici della biblioteca, interferire con il servizio o adottare comportamenti molesti nei confronti di altri utenti o del personale.
        </p>
    </div>

    <div class="terms-section">
        <h3>5. Limitazione di Responsabilità</h3>
        <p>
            La Biblioteca Scrum fornisce il servizio "così com'è", senza garanzie di alcun tipo. Non saremo responsabili per eventuali danni diretti o indiretti derivanti dall'uso o dall'impossibilità di utilizzare il servizio.
        </p>
    </div>

    <div class="terms-section">
        <h3>6. Modifiche ai Termini</h3>
        <p>
            Ci riserviamo il diritto di modificare questi termini in qualsiasi momento. Le modifiche entreranno in vigore al momento della loro pubblicazione su questa pagina. Ti invitiamo a consultare periodicamente questa sezione per rimanere aggiornato.
        </p>
    </div>

    <div class="terms-footer">
        <p>Ultimo aggiornamento: <?php echo date("d/m/Y"); ?> &bull; Biblioteca Scrum &copy; <?php echo date("Y"); ?></p>
    </div>
</div>

<?php require_once './src/includes/footer.php'; ?>