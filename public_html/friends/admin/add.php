<?php
$db_path = '/home/magentaw/friends/accountinformation.db';
$pdo = new PDO("sqlite:$db_path");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Beispiel: Admin-User Alexander
$username = 'Alexander';
$password = 'NervNet';
$is_admin = 1;

$hash = hash('sha256', $password);

$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)");
$stmt->execute([$username, $hash, $is_admin]);

echo "User $username angelegt!";
?>
