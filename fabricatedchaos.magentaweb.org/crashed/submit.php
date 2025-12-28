<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataDir = __DIR__;
    $jsonFile = $dataDir.'/formanswers.json';
    
    // Minecraft Name
    $mcname = $_POST['mcname'] ?? '';
    $mcname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $mcname); // Sicherheit: nur erlaubte Zeichen

    // Crash Assistant
    $crashInstalled = $_POST['crashInstalled'] ?? 'no';
    $crashText = $_POST['crashText'] ?? '';

    // Upload-Verzeichnis pro User
    $uploadDir = $dataDir.'/uploads/'.$mcname.'/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $uploadedFiles = [];

    // Modliste
    if ($crashInstalled === 'yes' && isset($_FILES['modlist']) && $_FILES['modlist']['error'] === UPLOAD_ERR_OK) {
        $modlist = $_FILES['modlist'];
        $modlistName = basename($modlist['name']);
        $modlistPath = $uploadDir.$modlistName;
        move_uploaded_file($modlist['tmp_name'], $modlistPath);
        $uploadedFiles['modlist'] = "https://fabricatedchaos.magentaweb.org/crashed/uploads/$mcname/$modlistName";
    }

    // Crash File
    if (isset($_FILES['crashFile']) && $_FILES['crashFile']['error'] === UPLOAD_ERR_OK) {
        $crash = $_FILES['crashFile'];
        $crashName = basename($crash['name']);
        $crashPath = $uploadDir.$crashName;
        move_uploaded_file($crash['tmp_name'], $crashPath);
        $uploadedFiles['crashFile'] = "https://fabricatedchaos.magentaweb.org/crashed/uploads/$mcname/$crashName";
    }

    // JSON-Eintrag
    $entry = [
        'mcname' => $mcname,
        'crashInstalled' => $crashInstalled,
        'crashText' => $crashText,
        'files' => $uploadedFiles,
        'timestamp' => date('c')
    ];

    // Bestehende JSON laden oder neu erstellen
    $jsonData = [];
    if (file_exists($jsonFile)) {
        $jsonData = json_decode(file_get_contents($jsonFile), true);
        if (!is_array($jsonData)) {
            $jsonData = [];
        }
    }

    $jsonData[] = $entry;
    file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    echo "Formular erfolgreich gesendet!";
} else {
    echo "Ungültige Anfrage.";
}
?>
