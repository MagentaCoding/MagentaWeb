<?php
// Prüfen, ob cookieName gesetzt ist
if (!isset($_GET['cookieName']) || empty($_GET['cookieName'])) {
    header("Location: /error/424.shtml");
    exit;
}

$cookieName = preg_replace("/[^a-zA-Z0-9_-]/", "", $_GET['cookieName']);
$cookieValue = $cookieName;
$expire = 2147483647; // dauerhaft
$path = "/";
$domain = ".magentaweb.org";
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

// Cookie setzen
setcookie($cookieName, $cookieValue, $expire, $path, $domain, $secure, true);

// HTML-Ausgabe
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Cookie gesetzt</title>
</head>
<body>
<h1>Cookie erfolgreich gesetzt!</h1>
<p>Cookie-Name: <strong><?php echo htmlspecialchars($cookieName); ?></strong></p>
<p>Cookie-Wert: <strong><?php echo htmlspecialchars($cookieValue); ?></strong></p>
<p>Gültig für: <strong><?php echo $domain; ?></strong></p>
<p><a href="/">Zurück zur Startseite</a></p>
</body>
</html>
