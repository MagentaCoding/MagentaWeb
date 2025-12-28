<?php
session_start();

// DB-Pfad
$dbFile = __DIR__ . '/../data/users.db';

// Verbindung zur SQLite-DB
try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Datenbankfehler: " . $e->getMessage());
}

// Einbinden der TOTP-Funktionen
require_once __DIR__ . '/../data/totp.php';

// Login-Formular abgeben, wenn noch kein POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $code     = $_POST['2fa_code'] ?? '';

    // Benutzer aus DB laden
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "Benutzername oder Passwort falsch.";
    } elseif (!password_verify($password, $user['password'])) {
        $error = "Benutzername oder Passwort falsch.";
    } else {
        // Prüfen, ob 2FA aktiviert ist
        if ($user['2fa_enabled']) {
            if (!$code) {
                $error = "Bitte den 2FA-Code eingeben.";
            } elseif (!verifyTOTP($user['2fa_secret'], $code, $PERIOD, $TOLERANCE)) {
                $error = "Ungültiger 2FA-Code.";
            }
        }

        // Alles ok → einloggen
        if (!isset($error)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: /dashboard.php"); // Redirect nach Login
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>MagentaBank Login</title>
<style>
body { font-family: Arial, sans-serif; background:#f0f0f5; color:#333; }
form { max-width:400px; margin:50px auto; padding:20px; background:#fff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.2); }
input { width:100%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:4px; }
button { padding:10px 15px; background:#0073e6; color:#fff; border:none; border-radius:4px; cursor:pointer; }
.error { color:red; }
</style>
</head>
<body>
<h2 style="text-align:center;">MagentaBank Login</h2>

<?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

<form method="post">
    <label>Benutzername</label>
    <input type="text" name="username" required>

    <label>Passwort</label>
    <input type="password" name="password" required>

    <label>2FA-Code (falls aktiviert)</label>
    <input type="text" name="2fa_code" maxlength="6" pattern="\d{6}" placeholder="6-stelliger Code">

    <button type="submit">Login</button>
</form>
</body>
</html>
