<?php
session_start();
include 'security.php';

$codice_utente = $_SESSION['codice_utente'] ?? null;
$imgprofilo = "./public/assets/base_pfp.png";
$username = "Test";
$nome = "Test";
$cf = "Test12344";
$cognome = "Test";
$email = "Test";


$recuperoDati = "SELECT * FROM utenti WHERE codice_alfanumerico = ?";
try {
    if(isset($pdo)) {
        $stmt = $pdo->prepare($recuperoDati);
        $stmt->execute([$codice_utente]);
        $utente = $stmt->fetch();
        if($utente) {
            $username = $utente["username"];
            $nome = $utente["nome"];
            $cognome = $utente["cognome"];
            $email = $utente["email"];
            //Recupero Prestiti
            $recuperoPrestiti = "SELECT l.isbn as isbn as copertina FROM prestiti p JOIN copie c ON p.ic_copia = c.id_copia JOIN libri l ON c.isbn = l.isbn WHERE p.codice_alfanumerico = :codice";
            $newStmt = $pdo->prepare($recuperoPrestiti);
            $newStmt->bindParam(":codice", $utente['codice_alfanumerico']);
            $resuPrestiti = $newStmt->fetchAll();
            if($resuPrestiti) {
                $prestiti = $resuPrestiti;
            }
            //Recupero Prenotazioni
            $recuperoPrenotazioni = "SELECT l.isbn as isbn FROM prenotazioni p JOIN libri l ON p.isbn = l.isbn JOIN copie c ON l.isbn = c.isbn  WHERE p.codice_alfanumerico = :codice";
            $newStmt = $pdo->prepare($recuperoPrenotazioni);
            $newStmt->bindParam(":codice", $utente['codice_alfanumerico']);
            $resuPrenotazioni = $newStmt->fetchAll();
            if($resuPrenotazioni) {
                $prenotazioni = $resuPrenotazioni;
            }
            //Recupero Letture
            $recuperoletture = $recuperoPrestiti . " AND p.data_restituzione < NOW();";
            $newStmt = $pdo->prepare($recuperoletture);
            $newStmt->bindParam(":codice", $utente['codice_alfanumerico']);
            $resuletture = $newStmt->fetchAll();
            if($resuletture) {
                $letture = $resuletture;
            }
        }
    } else {
        $error_msg = "Errore di connessione al Database.";
    }
} catch (PDOException $e) {
    $error_msg = "Errore di sistema: " . $e->getMessage();
    die();
}

?>

<?php include './src/includes/header.php'; ?>
<?php include './src/includes/navbar.php'; ?>

<?php if (!empty($error_msg)): ?>
    <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<div class="container" style="display:flex; flex-direction: row"> <!--deve essere orizzontale-->

    <div class="container" style="flex-direction: column"> <!--deve essere verticale-->
        <div style="display: contents">
            <img width="100" height="100"  onclick="document.getElementById("profilo_selector").click()" src="<?= $imgprofilo ?>" alt="Profilo_img">
            <input id="profilo_selector" style="display: none" type="file">
        </div>
        <div class="container">
            <label for="userEdit">Username :</label> <input id="userEdit" disabled type="text" value="<?= $username ?>">
            <h6>Nome : <?= $nome ?></h6>
            <h6>Cognome : <?= $cognome ?></h6>
            <h6>Codice Fiscale : <?= $cf ?></h6>
            <h6>Email : <?= $email ?></h6>
        </div>
    </div>

    <div class="container" style="display:flex; flex-direction: column; width: 100%"> <!--deve essere verticale-->
        <div class="container">
            <h1>Badges</h1>
            <div class="container"> <!--dove mostrare badge-->

            </div>
        </div>
        <div class="container" style="display:flex; flex-direction: column; width: 100%">
            <h1>Prestiti</h1>
            <div class="container"> <!--dove mostrare libri in prestito-->
                <?php if(!empty($prestiti)) foreach($prestiti as $libro) { ?>
                    <div class="card cover-only" data-isbn="<?= $libro['isbn'] ?>">
                        <img src="src/assets/placeholder.jpg" alt="Libro">
                    </div>
                <?php } else { ?>
                        <div>Nessun prestito attivo</div>
                <?php } ?>
            </div>
        </div>
        <div class="container" style="display:flex; flex-direction: column; width: 100%">
            <h1>Prenotazioni</h1>
            <div class="container"> <!--dove mostrare la grafica dei libri prenotati-->
                <?php if(!empty($prenotazioni)) foreach($prenotazioni as $libro) { ?>
                    <div class="card cover-only" data-isbn="<?= $libro['isbn'] ?>">
                        <img src="src/assets/placeholder.jpg" alt="Libro">
                    </div>
                <?php } else { ?>
                    <div>Nessuna prenotazione attiva</div>
                <?php } ?>
            </div>
        </div>
        <div class="container">
            <h1>Letture</h1>
            <div class="container"> <!--dove mostrare la grafica dei libri letti-->
                <div class="container"> <!--dove mostrare la grafica dei libri prenotati-->
                    <?php if(!empty($letti)) foreach($letti as $libro) { ?>
                        <div class="card cover-only" data-isbn="<?= $libro['isbn'] ?>">
                            <img src="src/assets/placeholder.jpg" alt="Libro">
                        </div>
                    <?php } else { ?>
                        <div>Non hai ancora letto niente</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    // Aggiorna tutte le copertine
    document.querySelectorAll('.card.cover-only').forEach(async card => {
        const isbn = card.dataset.isbn;
        const coverUrl = await fetchCover(isbn);
        card.querySelector('img').src = coverUrl;
    });
</script>

<?php include './src/includes/footer.php'; ?>