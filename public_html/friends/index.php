<?php
ob_start(); // Output Buffer starten
session_start();

// DB-Pfad
$db_path = '/home/magentaw/friends/accountinformation.db';
$pdo = new PDO("sqlite:$db_path");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password_hash, is_admin FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && hash('sha256', $password) === $user['password_hash']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = $user['is_admin'];

        // Weiterleitung: Admin oder normaler User
        header('Location: ' . ($user['is_admin'] ? './admin/' : './'.$username.'/'));
        exit;
    } else {
        $error = 'Benutzername oder Passwort falsch';
    }
}
ob_end_flush(); // Buffer ausgeben
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Aldrich&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Aldrich', sans-serif;
        background-color: #f4f4f9;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .login-container {
        background-color: #fff;
        padding: 40px 30px;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        width: 300px;
        text-align: center;
    }
    h1 {
        margin-bottom: 25px;
        color: #333;
    }
    input[type="text"], input[type="password"] {
        width: 100%;
        padding: 10px 12px;
        margin: 10px 0 20px 0;
        border: 1px solid #ccc;
        border-radius: 8px;
        box-sizing: border-box;
        font-family: 'Aldrich', sans-serif;
    }
    button {
        width: 100%;
        padding: 12px;
        background-color: #6a0dad;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        font-family: 'Aldrich', sans-serif;
    }
    button:hover {
        background-color: #520c97;
    }
    .error {
        color: red;
        margin-bottom: 15px;
    }
</style>
</head>
<body>

<div class="login-container">
    <h1>Login</h1>

    <?php if($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="username" placeholder="Benutzername" required>
        <input type="password" name="password" placeholder="Passwort" required>
        <button type="submit">Anmelden</button>
    </form>
</div>

</body>
</html>
