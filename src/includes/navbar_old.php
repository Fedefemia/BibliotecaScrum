<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- LOGICA MESSAGGI CENTRALIZZATA ---
$display_status = null;

if (isset($_SESSION['status'])) {
    $display_status = $_SESSION['status'];
    unset($_SESSION['status']);
}

if (isset($status) && !empty($status)) {
    $display_status = $status;
}

$nome_visualizzato = 'Utente';  // username da database

if (isset($_SESSION['nome_utente'])) {
    $nome_visualizzato = $_SESSION['nome_utente'];
}
?>

<style>
    nav {
        background: #333;
        padding: 10px;
        margin-bottom: 20px;
        color: white;
    }

    nav a {
        color: white;
        text-decoration: none;
        margin-right: 15px;
        font-family: sans-serif;
    }

    nav a:hover {
        text-decoration: underline;
    }

    .msg-box {
        display: inline;
        font-family: sans-serif;
        font-weight: bold;
    }
</style>

<nav>
    <a href="../..">Home</a>

    <?php if ($display_status): ?>
        <div class="msg-box">
            <?php
                echo $display_status;
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['logged']) && $_SESSION['logged'] === true): ?>
        <a href="./logout" style="color: #ff9999; float: right;">Logout</a>
        <span style="float: right; margin-right: 10px;">Ciao, <?php echo htmlspecialchars($nome_visualizzato); ?></span>
    <?php else: ?>
        <a href="./login" style="float: right;">Login</a>
    <?php endif; ?>
    <div style="clear: both;"></div>
</nav>