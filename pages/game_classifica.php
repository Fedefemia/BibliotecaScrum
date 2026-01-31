<?php
// ---------------- LOGICA PHP ----------------
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusione DB intelligente
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    require_once '../db_config.php';
}

$messaggio_db = "";
$classifica = [];

if (isset($pdo)) {
    try {
        $query = "
            SELECT c.tempo, c.data, u.username 
            FROM classifica as c
            JOIN utenti as u ON u.codice_alfanumerico = c.codice_alfanumerico
            ORDER BY c.tempo ASC, c.data ASC
            LIMIT 50
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $classifica = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $messaggio_db = "Errore Database: " . $e->getMessage();
    }
}
?>

<?php
// ---------------- HTML OUTPUT ----------------
$title = "Classifica - Biblioteca Scrum";
$path = "./";

// --- FIX CACHE ---
// Aggiungiamo ?v=time() per costringere il browser a caricare il nuovo CSS
$page_css = "./public/css/style_classifica.css?v=" . time(); 

if(file_exists('./src/includes/header.php')) {
    require './src/includes/header.php';
    require './src/includes/navbar.php';
} else {
    require '../src/includes/header.php';
    require '../src/includes/navbar.php';
}
?>

<div class="classifica-wrapper">
    <?php if($messaggio_db): ?>
        <p style="color: red; font-weight: bold; text-align: center;"><?= htmlspecialchars($messaggio_db) ?></p>
    <?php endif; ?>

    <table class="classifica-table">
        <thead>
            <tr>
                <th style="width: 100px; text-align: center;">POSIZIONE</th>
                <th>UTENTE</th>
                <th style="text-align: right;">TEMPO</th>
                <th style="text-align: right; padding-right: 40px;">DATA</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($classifica)): ?>
                <?php foreach ($classifica as $index => $row): ?>
                    <?php 
                        $pos = $index + 1;
                        $iconBasePath = "./public/assets/icone_classifica/";
                    ?>
                    <tr>
                        <td class="rank-cell">
                            <?php if ($pos <= 5): ?>
                                <img src="<?= $iconBasePath . 'classifica' . $pos . '.png' ?>" alt="<?= $pos ?>" class="rank-icon-top">
                            <?php else: ?>
                                <div class="rank-base-wrapper">
                                    <img src="<?= $iconBasePath . 'classifcaBase.png' ?>" alt="" class="rank-icon-base">
                                    <span class="rank-number"><?= $pos ?></span>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="user-cell"><?= htmlspecialchars($row['username']) ?></td>
                        
                        <td class="time-cell">
                            <?= number_format($row['tempo'] / 1000, 3) ?> s
                        </td>
                        
                        <td class="date-cell">
                            <?= date('d/m/Y', strtotime($row['data'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding: 30px;">
                        Nessun record presente in classifica. Gioca per essere il primo!
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="text-align: center;">
        <a href="./game" class="play-again-btn">Gioca</a>
    </div>
</div>

<?php 
if(file_exists('./src/includes/footer.php')) {
    require './src/includes/footer.php';
} else {
    require '../src/includes/footer.php';
}
?>