<?php
session_start();

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../');
    exit;
}

// DB-Verbindung
$db_path = '/home/magentaw/friends/accountinformation.db';
$pdo = new PDO("sqlite:$db_path");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Neuen Benutzer anlegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username']) && !empty($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $hash = hash('sha256', $password);

    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$username, $hash, $is_admin]);
        $message = "Benutzer $username erfolgreich angelegt!";
    } catch (Exception $e) {
        $message = "Fehler: " . $e->getMessage();
    }
}

// Alle Benutzer abrufen
$stmt = $pdo->query("SELECT id, username, is_admin, created_at FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin-Bereich</title>
</head>
<body>
<h1>Admin-Bereich</h1>

<?php if (!empty($message)) echo "<p>$message</p>"; ?>

<h2>Benutzerübersicht</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Admin?</th>
        <th>Erstellt am</th>
    </tr>
    <?php foreach ($users as $user): ?>
    <tr>
        <td><?= htmlspecialchars($user['id']) ?></td>
        <td><?= htmlspecialchars($user['username']) ?></td>
        <td><?= $user['is_admin'] ? 'Ja' : 'Nein' ?></td>
        <td><?= $user['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h2>Neuen Benutzer anlegen</h2>
<form method="post">
    <label>Username: <input type="text" name="username" required></label><br>
    <label>Passwort: <input type="password" name="password" required></label><br>
    <label>Admin? <input type="checkbox" name="is_admin"></label><br>
    <button type="submit">Benutzer anlegen</button>
</form>
</body>
</html>
