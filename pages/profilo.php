<?php
include 'security.php';
if(session_status() == PHP_SESSION_NONE) session_start();

$imgprofilo = "./public/assets/base_pfp.png";
$username = "Test";
$nome = "Test";
$cf = "Test12344";
$cognome = "Test";
$email = "Test";
$error_msg = '';


$recuperoDati = "SELECT * FROM utenti WHERE username = :codice OR email = :codice OR codice_fiscale = :codice";
try {
    if(isset($pdo)) {
        $stmt = $pdo->prepare($recuperoDati);
        $stmt->bindParam(":codice", $_SESSION['username']);
        $resu = $stmt->fetch();
        if($resu) {
            $username = $resu["username"];
            $nome = $resu["nome"];
            $cognome = $resu["cognome"];
            $email = $resu["email"];
            $recuperoPrestiti = "SELECT l.isbn, c.copertina FROM prestiti p JOIN copie c ON p.ic_copia = c.id_copia JOIN libri l ON c.isbn = l.isbn WHERE p.codice_alfanumerico = :codice_alfanumerico AND p.data_restituzione IS NULL;";
            $newStmt = $pdo->prepare($recuperoDati);
            $newStmt->bindParam(":codice", $_SESSION['username']);
            $resuPrestiti = $newStmt->fetchAll();
            if($resuPrestiti) {
                $prestiti = $resuPrestiti;
            }
        }
    } else {
        $error_msg = "Errore di connessione al Database.";
        die();
    }
} catch (PDOException $e) {
    $error_msg = "Errore di sistema: " . $e->getMessage();
    die();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo</title>
</head>
    <body>

        <?php include './src/includes/header.php'; ?>
        <?php include './src/includes/navbar.php'; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="error"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <div class="container"> <!--deve essere orizzontale-->

            <div class="container"> <!--deve essere verticale-->
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

            <div class="container"> <!--deve essere verticale-->
                <div class="container">
                    <title>Badges</title>
                    <div class="container"> <!--dove mostrare badge-->

                    </div>
                </div>
                <div class="container">
                    <h1>Prestiti</h1>
                    <div class="container"> <!--dove mostrare libri in prestito-->
                        <?php foreach($prestiti as $libro) { ?>
                                <img src="<?= $libro["copertina"] ?>" alt="<?= $libro["isbn"] ?>">
                        <?php } ?>
                    </div>
                </div>
                <div class="container">
                    <title>Prenotazioni</title>
                    <div class="container"> <!--dove mostrare la grafica dei libri prenotati-->

                    </div>
                </div>
                <div class="container">
                    <title>Letture</title>
                    <div class="container"> <!--dove mostrare la grafica dei libri letti-->

                    </div>
                </div>
            </div>

        </div>

    </body>
</html>