<?php
session_start();

// DB-Pfad
$dbFile = __DIR__ . '/../data/users.db';

// Verbindung zur SQLite-DB
try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Tabelle erstellen, falls nicht existiert
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        2fa_enabled INTEGER DEFAULT 0,
        2fa_secret TEXT
    )");
} catch (Exception $e) {
    die("Datenbankfehler: " . $e->getMessage());
}

// TOTP-Funktionen einbinden
require_once __DIR__ . '/../data/totp.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $enable2FA = isset($_POST['enable2fa']);

    if (!$username || !$password) {
        $error = "Benutzername und Passwort sind erforderlich.";
    } else {
        // Prüfen, ob Benutzer existiert
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            $error = "Benutzername existiert bereits.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $twoFASecret = null;
            $twoFAEnabled = 0;
            if ($enable2FA) {
                $twoFASecret = generateSecret();
                $twoFAEnabled = 1;
            }

            // Benutzer einfügen
            $stmt = $db->prepare("INSERT INTO users (username, password, 2fa_enabled, 2fa_secret) VALUES (:username, :password, :enabled, :secret)");
            $stmt->execute([
                ':username' => $username,
                ':password' => $hashedPassword,
                ':enabled'  => $twoFAEnabled,
                ':secret'   => $twoFASecret
            ]);

            // Session setzen und ggf. QR-Code anzeigen
            $_SESSION['user_id'] = $db->lastInsertId();
            $_SESSION['username'] = $username;

            if ($enable2FA) {
                $issuer = 'MagentaBank';
                $uri = getQRCodeUri($issuer, $username, $twoFASecret, $PERIOD);
                $_SESSION['2fa_qr'] = $uri;
            }

            header("Location: /auth/register_success.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>MagentaBank Registrierung</title>
<style>
body { font-family: Arial, sans-serif; background:#f0f0f5; color:#333; }
form { max-width:400px; margin:50px auto; padding:20px; background:#fff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.2); }
input { width:100%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:4px; }
button { padding:10px 15px; background:#0073e6; color:#fff; border:none; border-radius:4px; cursor:pointer; }
.error { color:red; }
</style>
</head>
<body>
<h2 style="text-align:center;">MagentaBank Registrierung</h2>

<?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

<form method="post">
    <label>Benutzername</label>
    <input type="text" name="username" required>

    <label>Passwort</label>
    <input type="password" name="password" required>

    <label>
        <input type="checkbox" name="enable2fa"> 2FA aktivieren
    </label>

    <button type="submit">Registrieren</button>
</form>
</body>
</html>
