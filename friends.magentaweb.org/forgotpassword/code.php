<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email']) || empty($_POST['code'])) {
    echo json_encode(['success'=>false,'message'=>'E-Mail oder Code fehlt.']);
    exit;
}

$email = trim((string)$_POST['email']);
$codeInput = trim((string)$_POST['code']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'message'=>'Ungültige E-Mail.']);
    exit;
}

$dbFile = '/home/magentaw/files/reset.sqlite';
$googleDocLink = 'https://docs.google.com/document/d/DEIN_GOOGLE_DOC_ID/view';

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>'Datenbankfehler.']);
    exit;
}

// --- Code prüfen ---
$now = time();
$stmt = $pdo->prepare("SELECT id, code, expires_at, used FROM reset_codes WHERE email=:email ORDER BY created_at DESC LIMIT 5");
$stmt->execute([':email'=>$email]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$matchId = null;
foreach ($rows as $r) {
    if ($r['used']==1) continue;        // bereits genutzt
    if ($now > $r['expires_at']) continue; // abgelaufen
    if (hash_equals((string)$r['code'], $codeInput)) {
        $matchId = $r['id'];
        break;
    }
}

if ($matchId === null) {
    echo json_encode(['success'=>false,'message'=>'Ungültiger oder abgelaufener Code.']);
    exit;
}

// --- Code als verwendet markieren ---
$stmt = $pdo->prepare("UPDATE reset_codes SET used=1 WHERE id=:id");
$stmt->execute([':id'=>$matchId]);

echo json_encode([
    'success'=>true,
    'message'=>'Code korrekt. Zugriff auf den Link unten.',
    'link'=>$googleDocLink
]);
