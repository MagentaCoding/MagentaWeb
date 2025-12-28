<?php
$submitMessage = '';
$mcValid = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mcname = trim($_POST['mcname'] ?? '');
    $crashInstalled = $_POST['crashInstalled'] ?? 'no';
    $crashText = $_POST['crashText'] ?? '';

    $uploadedFiles = [];
    $baseUploadDir = __DIR__ . '/uploads';
    if (!file_exists($baseUploadDir)) mkdir($baseUploadDir, 0777, true);

    // Ordner für den User erstellen
    $userDir = $baseUploadDir . '/' . $mcname;
    if (!file_exists($userDir)) mkdir($userDir, 0777, true);

    // --- USERNAME-VALIDIERUNG ---
    $mcValid = false;
    $url = "https://api.mojang.com/users/profiles/minecraft/" . urlencode($mcname);
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['id'])) {
            $mcValid = true;
        }
    }

    if ($mcValid) {
        // --- DATEI-UPLOADS ---
        if ($crashInstalled === 'yes' && isset($_FILES['modlist']) && $_FILES['modlist']['error'] === 0) {
            $modlist = $_FILES['modlist'];
            $modlistPath = $userDir . '/' . basename($modlist['name']);
            move_uploaded_file($modlist['tmp_name'], $modlistPath);
            $uploadedFiles['modlist'] = 'https://files.magentaweb.org/forms/fabricatedchaos/' . urlencode($mcname) . '/' . basename($modlist['name']);
        }

        if (isset($_FILES['crashFile']) && $_FILES['crashFile']['error'] === 0) {
            $crashFile = $_FILES['crashFile'];
            $crashPath = $userDir . '/' . basename($crashFile['name']);
            move_uploaded_file($crashFile['tmp_name'], $crashPath);
            $uploadedFiles['crashFile'] = 'https://files.magentaweb.org/forms/fabricatedchaos/' . urlencode($mcname) . '/' . basename($crashFile['name']);
        }

        // --- JSON SPEICHERN ---
        $jsonFile = __DIR__ . '/formanswers.json';
        $entry = [
            'mcname' => $mcname,
            'crashInstalled' => $crashInstalled,
            'crashText' => $crashText,
            'files' => $uploadedFiles,
            'timestamp' => date('c')
        ];

        if (file_exists($jsonFile)) {
            $jsonData = json_decode(file_get_contents($jsonFile), true);
            if (!is_array($jsonData)) $jsonData = [];
        } else {
            $jsonData = [];
        }

        $jsonData[] = $entry;
        file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT));

        $submitMessage = "✔ Formular erfolgreich gesendet!";
    } else {
        $submitMessage = "✖ Minecraft-Username '$mcname' ungültig!";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Crash Report Formular</title>
<style>
body { font-family: Arial, sans-serif; background-color: #1b1b1b; color: #f0f0f0; padding: 20px; }
h1 { text-align: center; color: #ffcc00; }
form { max-width: 600px; margin: auto; background: #2a2a2a; padding: 25px; border-radius: 10px; }
label { display: block; margin-top: 15px; }
input, textarea { width: 100%; margin-top: 5px; padding: 8px; border-radius: 5px; border: none; }
textarea { resize: vertical; min-height: 80px; }
.hidden { display: none; }
button { background-color: #ffcc00; color: #1b1b1b; padding: 10px 20px; border: none; border-radius: 7px; cursor: pointer; margin-top: 15px; }
button:disabled { background-color: #555; color: #aaa; cursor: not-allowed; }
button:not(:disabled):hover { background-color: #e6b800; }
#submitMessage { text-align: center; margin-bottom: 15px; font-weight: bold; }
</style>
<script>
function checkForm() {
    const mcName = document.getElementById('mcname').value.trim();
    const crashInstalled = document.querySelector('input[name="crashInstalled"]:checked');
    const crashText = document.getElementById('crashText').value.trim();
    const modlist = document.getElementById('modlist').files.length;
    const crashFile = document.getElementById('crashFile').files.length;

    let enable = mcName && crashInstalled && crashFile;
    if (crashInstalled && crashInstalled.value === "yes") {
        enable = enable && crashText && modlist;
    }
    document.getElementById('submitBtn').disabled = !enable;
}

function toggleCrashFields() {
    const crashYes = document.getElementById('crashYes').checked;
    document.getElementById('crashFields').className = crashYes ? '' : 'hidden';
    checkForm();
}
</script>
</head>
<body>
<h1>Crash Report Formular</h1>

<?php if($submitMessage): ?>
    <div id="submitMessage"><?php echo htmlspecialchars($submitMessage); ?></div>
<?php endif; ?>

<form action="" method="post" enctype="multipart/form-data">
    <label for="mcname">Minecraft Name (ERFORDERLICH)</label>
    <input type="text" id="mcname" name="mcname" value="<?php echo htmlspecialchars($_POST['mcname'] ?? ''); ?>" oninput="checkForm();" required>
    <span>
        <?php 
        if($mcValid === true) echo "✔ Username gültig";
        if($mcValid === false) echo "✖ Username ungültig";
        ?>
    </span>

    <label>Ist Crash Assistant installiert? (ERFORDERLICH)</label>
    <input type="radio" id="crashYes" name="crashInstalled" value="yes" onclick="toggleCrashFields()" <?php if(($_POST['crashInstalled'] ?? '') === 'yes') echo 'checked'; ?> required> Ja
    <input type="radio" id="crashNo" name="crashInstalled" value="no" onclick="toggleCrashFields()" <?php if(($_POST['crashInstalled'] ?? '') === 'no') echo 'checked'; ?>> Nein

    <div id="crashFields" class="<?php echo (($_POST['crashInstalled'] ?? '') === 'yes') ? '' : 'hidden'; ?>">
        <label for="crashText">Was hat Crash Assistant gesagt? (ERFORDERLICH)</label>
        <textarea id="crashText" name="crashText" oninput="checkForm()"><?php echo htmlspecialchars($_POST['crashText'] ?? ''); ?></textarea>

        <label for="modlist">Modliste hochladen (modlist.txt, ERFORDERLICH)</label>
        <input type="file" id="modlist" name="modlist" accept=".txt" onchange="checkForm()">
    </div>

    <label for="crashFile">Crash-File oder latest.log (ERFORDERLICH)</label>
    <input type="file" id="crashFile" name="crashFile" accept=".txt,.log" onchange="checkForm()">

    <button type="submit" id="submitBtn" disabled>Senden</button>
</form>

<script>checkForm();</script>
</body>
</html>
