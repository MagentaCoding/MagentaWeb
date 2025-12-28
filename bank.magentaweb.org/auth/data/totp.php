<?php
// MagentaBank TOTP-System (Alexander Edition)
// Zeitslot: 35 Sekunden, Toleranz ±5 Sekunden

// ---------- Konfiguration ----------
$PERIOD = 35;   // Zeitintervall
$TOLERANCE = 5; // Sekunden-Toleranz

// ---------- Funktionen ----------

// Base32 -> Binary
function base32_decode_custom($secret) {
    $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper($secret);
    $binary = '';
    foreach (str_split($secret) as $char) {
        $pos = strpos($base32chars, $char);
        if ($pos !== false) {
            $binary .= str_pad(base_convert($pos, 10, 2), 5, '0', STR_PAD_LEFT);
        }
    }
    $bin_data = '';
    foreach (str_split($binary, 8) as $byte) {
        $bin_data .= chr(bindec(str_pad($byte, 8, '0', STR_PAD_RIGHT)));
    }
    return $bin_data;
}

// Secret generieren
function generateSecret($length = 16) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $secret;
}

// QR-Code-URI generieren
function getQRCodeUri($issuer, $username, $secret, $period) {
    $issuer_enc = urlencode($issuer);
    $user_enc = urlencode($username);
    return "otpauth://totp/{$issuer_enc}:{$user_enc}?secret={$secret}&issuer={$issuer_enc}&period={$period}";
}

// Aktuellen TOTP-Code berechnen
function getTOTP($secret, $period, $timestamp = null) {
    if ($timestamp === null) $timestamp = time();
    $counter = floor($timestamp / $period);
    $key = base32_decode_custom($secret);
    $bin_counter = pack('N*', 0) . pack('N*', $counter);
    $hash = hash_hmac('sha1', $bin_counter, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $part = substr($hash, $offset, 4);
    $value = unpack('N', $part)[1] & 0x7FFFFFFF;
    $mod = 1000000;
    return str_pad($value % $mod, 6, '0', STR_PAD_LEFT);
}

// Code verifizieren
function verifyTOTP($secret, $code, $period, $tolerance) {
    $now = time();
    for ($offset = -$tolerance; $offset <= $tolerance; $offset++) {
        $ts = $now + $offset;
        if (getTOTP($secret, $period, $ts) === $code) {
            return true;
        }
    }
    return false;
}

// ---------- Aktionen ----------
$action = $_GET['action'] ?? 'info';
$showDemo = isset($_GET['demo']) && $_GET['demo'] === 'true';

// Secret generieren
if ($action === 'generate') {
    $user = $_GET['user'] ?? 'demo';
    $issuer = 'MagentaBank';
    $secret = generateSecret();
    $uri = getQRCodeUri($issuer, $user, $secret, $PERIOD);
    echo "<h1>2FA Setup für {$issuer}</h1>";
    echo "<p><b>Secret:</b> {$secret}</p>";
    echo "<p>QR-Code-URI:</p>";
    echo "<code>{$uri}</code><br><br>";
    echo '<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($uri) . '">';
    exit;
}

// Code verifizieren
if ($action === 'verify') {
    $secret = $_GET['secret'] ?? '';
    $code = $_GET['code'] ?? '';
    if (!$secret || !$code) {
        echo "Fehlende Parameter.";
        exit;
    }
    $ok = verifyTOTP($secret, $code, $PERIOD, $TOLERANCE);
    echo $ok ? "✅ Code gültig!" : "❌ Code ungültig!";
    exit;
}

// Demo-Ausgabe nur, wenn ?demo=true
if ($showDemo) {
    echo "<h2>MagentaBank TOTP-System aktiv</h2>";
    echo "<ul>
    <li><a href='?action=generate&user=alexander'>Neues Secret erzeugen</a></li>
    <li>Verifizieren: ?action=verify&secret=...&code=...</li>
    </ul>";
}
?>
