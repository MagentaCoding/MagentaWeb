<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email'])) {
    echo json_encode(['success'=>false,'message'=>'E-Mail fehlt.']);
    exit;
}

$email = trim((string)$_POST['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'message'=>'Ungültige E-Mail.']);
    exit;
}

$dbFile = '/home/magentaw/files/reset.sqlite';
$smtpHost = 'mail.magentaweb.org';
$smtpPort = 465;
$smtpUser = 'passwordreset@magentaweb.org';
$smtpPass = 'Nice88@r';
$fromEmail = 'passwordreset@magentaweb.org';
$fromName = 'MagentaWeb Passwortreset';
$codeLength = 6;
$expirySeconds = 15*60; // 15 Minuten

require __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
require __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- SQLite DB init ---
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS reset_codes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        code TEXT NOT NULL,
        created_at INTEGER NOT NULL,
        expires_at INTEGER NOT NULL,
        used INTEGER NOT NULL DEFAULT 0,
        ip TEXT
    )");
} catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>'Datenbankfehler.']);
    exit;
}

// --- Code generieren ---
$code = '';
for($i=0;$i<$codeLength;$i++){
    $code .= random_int(0,9);
}
$now = time();
$expires = $now + $expirySeconds;
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// --- Code in DB speichern ---
$stmt = $pdo->prepare("INSERT INTO reset_codes (email, code, created_at, expires_at, used, ip) VALUES (:email, :code, :created, :expires, 0, :ip)");
$stmt->execute([
    ':email'=>$email,
    ':code'=>$code,
    ':created'=>$now,
    ':expires'=>$expires,
    ':ip'=>$ip
]);

// --- Mail verschicken ---
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = 'ssl';
    $mail->Port = $smtpPort;

    $mail->setFrom($fromEmail,$fromName);
    $mail->addAddress($email);
    $mail->Subject = 'Dein Passwort-Reset-Code';
    $mail->Body = "Hallo,\n\nDu hast einen Passwort-Reset angefordert.\nDein Code lautet:\n\n    $code\n\nDer Code ist 15 Minuten gültig.\n\nWenn du diese Anfrage nicht gestellt hast, ignoriere diese Mail.";

    $mail->send();
    echo json_encode(['success'=>true,'message'=>'Code verschickt. Bitte Mail prüfen.']);
} catch (Exception $e) {
    error_log("Mail-Fehler: ".$mail->ErrorInfo);
    echo json_encode(['success'=>false,'message'=>'Fehler beim Mailversand.']);
}
