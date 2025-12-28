<?php
session_start();

// Einfacher CSRF-Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

$dataFile = __DIR__ . '/formanswers.json';
$uploadsBaseUrl = 'https://fabricatedchaos.magentaweb.org/crashed/uploads';
$uploadsDir = __DIR__ . '/uploads';

// Lade JSON
function load_entries($file) {
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    $arr = json_decode($raw, true);
    if (!is_array($arr)) return [];
    return $arr;
}

// Speichere JSON
function save_entries($file, $arr) {
    file_put_contents($file, json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// Aktionen: mark_resolved, delete
$entries = load_entries($dataFile);
$actionMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
        die('Ungültiger CSRF-Token.');
    }

    if (isset($_POST['action']) && isset($_POST['index'])) {
        $idx = intval($_POST['index']);
        $action = $_POST['action'];

        if (!array_key_exists($idx, $entries)) {
            $actionMsg = 'Eintrag nicht gefunden.';
        } else {
            if ($action === 'mark_resolved') {
                $entries[$idx]['resolved'] = true;
                $entries[$idx]['resolved_at'] = date('c');
                save_entries($dataFile, $entries);
                $actionMsg = 'Eintrag als erledigt markiert.';
            } elseif ($action === 'mark_unresolved') {
                unset($entries[$idx]['resolved']);
                unset($entries[$idx]['resolved_at']);
                save_entries($dataFile, $entries);
                $actionMsg = 'Eintrag als offen markiert.';
            } elseif ($action === 'delete') {
                // optional: lösche auch die Dateien im uploads-Ordner für diesen User
                $mc = $entries[$idx]['mcname'] ?? '';
                if ($mc) {
                    $userDir = $uploadsDir . '/' . $mc;
                    if (is_dir($userDir)) {
                        $files = glob($userDir . '/*');
                        if ($files) {
                            foreach ($files as $f) {
                                if (is_file($f)) @unlink($f);
                            }
                        }
                        @rmdir($userDir); // versucht Ordner zu entfernen (nur wenn leer)
                    }
                }
                // entferne Eintrag
                array_splice($entries, $idx, 1);
                save_entries($dataFile, $entries);
                $actionMsg = 'Eintrag gelöscht (inkl. Upload-Dateien, falls vorhanden).';
            }
        }
    }
    // reload
    $entries = load_entries($dataFile);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Crash-Report Review — Admin</title>

<style>
/* Fonts aus deiner Liste */
@font-face { font-family: 'OrbitronVar'; src: url('https://files.magentaweb.org/fonts/normal/Orbitron.ttf'); }
@font-face { font-family: 'Tomorrow'; src: url('https://files.magentaweb.org/fonts/normal/Tomorrow.ttf'); }
@font-face { font-family: 'CairoPlay'; src: url('https://files.magentaweb.org/fonts/normal/CairoPlay.ttf'); }

:root{
    --bg:#0f1113;
    --card:#141617;
    --muted:#9aa4ad;
    --accent:#ffcc00;
    --good:#4dbd7d;
    --bad:#e05555;
    --glass: rgba(255,255,255,0.03);
}
*{box-sizing:border-box}
body{
    font-family: CairoPlay, Tomorrow, Arial, sans-serif;
    background: linear-gradient(180deg,#070708 0%, #0f1113 100%);
    color:#eee;
    margin:0;
    padding:28px;
}
.container{max-width:1200px;margin:0 auto}
.header{
    display:flex;align-items:center;gap:16px;margin-bottom:18px;
}
.logo{
    width:56px;height:56px;border-radius:8px;background:linear-gradient(135deg,var(--accent),#e6b800);display:flex;align-items:center;justify-content:center;font-family:OrbitronVar;color:#0b0b0b;font-weight:700;
}
.title{font-size:20px;font-family:OrbitronVar}
.subtitle{color:var(--muted);font-size:13px;margin-top:4px}

/* Controls */
.controls{display:flex;gap:12px;margin:18px 0;flex-wrap:wrap;align-items:center}
.controls input[type="search"]{padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.04);background:var(--card);color:inherit;min-width:260px}
.controls label{font-size:13px;color:var(--muted)}
.controls select, .controls button{padding:10px;border-radius:8px;border:none;background:var(--glass);color:inherit}
.controls .small{font-size:13px;color:var(--muted)}

/* Table */
.tableWrap{background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); border-radius:10px; padding:12px;}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{padding:10px 12px;text-align:left;vertical-align:middle;border-bottom:1px solid rgba(255,255,255,0.03)}
th{font-size:12px;color:var(--muted);text-transform:uppercase}
td .mcname{font-weight:700;font-family:OrbitronVar}
td .muted{color:var(--muted);font-size:13px}
.badge{display:inline-block;padding:6px 8px;border-radius:6px;font-size:12px}
.badge.yes{background:rgba(77,189,125,0.12);color:var(--good);border:1px solid rgba(77,189,125,0.12)}
.badge.no{background:rgba(224,85,85,0.08);color:var(--bad);border:1px solid rgba(224,85,85,0.06)}
.actions button{margin-right:6px;padding:6px 8px;border-radius:6px;border:none;background:var(--glass);color:inherit;cursor:pointer}
.smalllink{font-size:13px;color:var(--accent);text-decoration:none}

/* Details panel */
.details{margin-top:14px;padding:14px;background:rgba(255,255,255,0.02);border-radius:8px;display:none}
.details pre{white-space:pre-wrap;background:transparent;border:none;color:#dfeff3;padding:0;margin:0;font-family:monospace}

/* responsive */
@media (max-width:900px){
    .controls{flex-direction:column;align-items:stretch}
    th,td{padding:8px}
}
.message{margin:12px 0;padding:10px;border-radius:8px}
.message.ok{background:rgba(77,189,125,0.08);color:var(--good)}
.message.err{background:rgba(224,85,85,0.06);color:var(--bad)}
.footer{margin-top:16px;color:var(--muted);font-size:13px}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">MG</div>
        <div>
            <div class="title">Crash-Report Review Panel</div>
            <div class="subtitle">Übersicht über eingegangene Crash-Reports — Alexander</div>
        </div>
    </div>

    <?php if($actionMsg): ?>
        <div class="message ok"><?php echo htmlspecialchars($actionMsg); ?></div>
    <?php endif; ?>

    <div class="controls">
        <input id="search" type="search" placeholder="Suche nach Minecraft-Name, CrashText, Timestamp..." oninput="applyFilter()">
        <label><input id="filterResolved" type="checkbox" onchange="applyFilter()"> Nur offene anzeigen</label>
        <label class="small">Sortiere nach:
            <select id="sortBy" onchange="applyFilter()">
                <option value="newest">Neueste zuerst</option>
                <option value="oldest">Älteste zuerst</option>
                <option value="mcname">Minecraft-Name</option>
            </select>
        </label>
        <button onclick="downloadJSON()">JSON herunterladen</button>
        <div style="flex:1"></div>
        <div class="small">Entries: <span id="count"><?php echo count($entries); ?></span></div>
    </div>

    <div class="tableWrap">
        <table id="reports">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Time</th>
                    <th>Minecraft</th>
                    <th>CrashAssistant</th>
                    <th>CrashText / Modlist</th>
                    <th>Files</th>
                    <th>Resolved</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($entries as $i => $e): 
                $mc = $e['mcname'] ?? '';
                $ts = $e['timestamp'] ?? '';
                $ci = $e['crashInstalled'] ?? 'no';
                $ct = $e['crashText'] ?? '';
                $files = $e['files'] ?? [];
                $resolved = !empty($e['resolved']);
                // safe output
                $mc_html = htmlspecialchars($mc);
                $ct_html = nl2br(htmlspecialchars($ct));
                $ts_html = htmlspecialchars($ts);
                $modLink = $files['modlist'] ?? '';
                $crashLink = $files['crashFile'] ?? '';
            ?>
                <tr data-index="<?php echo $i; ?>" data-mc="<?php echo $mc_html; ?>" data-ts="<?php echo $ts_html; ?>" data-ct="<?php echo htmlspecialchars($ct); ?>" data-resolved="<?php echo $resolved ? '1' : '0'; ?>">
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo $ts_html; ?></td>
                    <td>
                        <div class="mcname"><?php echo $mc_html; ?></div>
                        <div class="muted"><?php echo htmlspecialchars($mc); ?></div>
                    </td>
                    <td>
                        <?php if ($ci === 'yes'): ?>
                            <span class="badge yes">Ja</span>
                        <?php else: ?>
                            <span class="badge no">Nein</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?php echo $ct_html ?: '<span class="muted">—</span>'; ?></div>
                        <?php if ($modLink): ?>
                            <div><a class="smalllink" href="<?php echo htmlspecialchars($modLink); ?>" target="_blank">Modlist öffnen</a></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($crashLink): ?>
                            <a class="smalllink" href="<?php echo htmlspecialchars($crashLink); ?>" target="_blank">Crash-Datei</a><br>
                        <?php endif; ?>
                        <?php if ($modLink): ?>
                            <a class="smalllink" href="<?php echo htmlspecialchars($modLink); ?>" target="_blank">Modlist</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($resolved): ?>
                            <span style="color:var(--good);font-weight:700">Erledigt</span><br>
                            <span class="muted"><?php echo htmlspecialchars($e['resolved_at'] ?? ''); ?></span>
                        <?php else: ?>
                            <span style="color:var(--muted)">Offen</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <form method="post" style="display:inline">
                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                            <input type="hidden" name="index" value="<?php echo $i; ?>">
                            <?php if (!$resolved): ?>
                                <input type="hidden" name="action" value="mark_resolved">
                                <button type="submit" title="Als erledigt markieren">Mark erledigt</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="mark_unresolved">
                                <button type="submit" title="Als offen markieren">Mark offen</button>
                            <?php endif; ?>
                        </form>

                        <form method="post" style="display:inline" onsubmit="return confirm('Eintrag tatsächlich löschen (inkl. Dateien)?');">
                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                            <input type="hidden" name="index" value="<?php echo $i; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" title="Löschen">Delete</button>
                        </form>

                        <button onclick="showDetails(<?php echo $i; ?>)">Details</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div id="details" class="details" aria-hidden="true">
            <h3>Details</h3>
            <div id="detailsContent"><pre></pre></div>
        </div>
    </div>

    <div class="footer">
        Admin-Panel — Zeigt alle Einträge aus <code>formanswers.json</code>. Nutze die Suche und Filter, um schnell Einträge zu finden. Löschen entfernt auch Upload-Dateien aus dem entsprechenden Upload-Ordner (falls vorhanden).
    </div>
</div>

<script>
// Client-seitige Filter & Details
const rows = Array.from(document.querySelectorAll('#reports tbody tr'));
const searchInput = document.getElementById('search');
const filterResolved = document.getElementById('filterResolved');
const sortBy = document.getElementById('sortBy');

function applyFilter(){
    const q = searchInput.value.trim().toLowerCase();
    const onlyOpen = filterResolved.checked;
    const s = sortBy.value;

    let filtered = rows.filter(r => {
        const mc = r.dataset.mc.toLowerCase();
        const ts = r.dataset.ts.toLowerCase();
        const ct = r.dataset.ct.toLowerCase();
        const resolved = r.dataset.resolved === '1';
        const matches = mc.includes(q) || ts.includes(q) || ct.includes(q);
        if (onlyOpen && resolved) return false;
        return matches;
    });

    // sort
    if (s === 'newest' || s === 'oldest') {
        filtered.sort((a,b) => {
            const ta = new Date(a.dataset.ts).getTime() || 0;
            const tb = new Date(b.dataset.ts).getTime() || 0;
            return (s === 'newest') ? (tb - ta) : (ta - tb);
        });
    } else if (s === 'mcname') {
        filtered.sort((a,b) => a.dataset.mc.localeCompare(b.dataset.mc));
    }

    // re-render
    const tbody = document.querySelector('#reports tbody');
    tbody.innerHTML = '';
    filtered.forEach(r => tbody.appendChild(r));
    document.getElementById('count').textContent = filtered.length;
}

function showDetails(index){
    // find row with data-index=index
    const row = document.querySelector('#reports tbody tr[data-index="'+index+'"]');
    if (!row) return;
    const mc = row.dataset.mc;
    const ts = row.dataset.ts;
    const ct = row.dataset.ct;
    const filesCell = row.querySelectorAll('td')[5];
    const filesHtml = filesCell ? filesCell.innerHTML : '';

    const content = `
Minecraft: ${mc}
Timestamp: ${ts}

CrashAssistant:
${row.querySelectorAll('td')[3].innerText.trim()}

CrashText:
${ct || '—'}

Dateien:
${filesHtml.replace(/<br>/g, "\n").replace(/<[^>]*>/g, "")}
    `;
    const details = document.getElementById('details');
    details.style.display = 'block';
    document.querySelector('#detailsContent pre').textContent = content;
    details.scrollIntoView({behavior:'smooth'});
}

function downloadJSON(){
    window.location.href = '../formanswers.json';
}

// initial
applyFilter();
</script>
</body>
</html>
