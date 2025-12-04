<?php
session_start();

// Redirect se già loggato
if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) {
    header("Location: ./");
    exit;
}

// Inizializziamo la variabile per gestire errori locali
$status = null; 

// LOGICA DI LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === 'admin' && $pass === '12345') {
        session_regenerate_id(true);
        $_SESSION['logged'] = true;
        setcookie('auth', 'ok', time() + 604800, '/', '', false, true);
        $_SESSION['status'] = 'Login riuscito';
        header("Location: ./");
        exit;
    } else {
        $status = 'Login non riuscito';
        $_SESSION['status'] = 'Login non riuscito';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div style="padding: 20px;">
        <h2>Accedi</h2>
        <form method="post">
            <input name="username" type="text" placeholder="Username" required>
            <input name="password" type="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <br>
        <a href="./signup">Non hai un account? Registrati</a>
    </div>

</body>
</html>