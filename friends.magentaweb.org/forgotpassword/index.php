<?php
session_start();
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Passwort zurücksetzen</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;color:#222;padding:24px}
.box{max-width:600px;margin:20px auto;background:#fff;padding:22px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
input[type="email"],input[type="text"]{width:100%;padding:12px;font-size:1rem;border-radius:6px;border:1px solid #ccc;box-sizing:border-box}
button{margin-top:12px;padding:10px 16px;background:#1b5e20;color:#fff;border:0;border-radius:6px;cursor:pointer}
.msg{padding:10px;margin-top:12px;border-radius:6px}
.msg.success{background:#e6f4ea;color:#1b5e20}
.msg.error{background:#fdecea;color:#b00020}
a{color:#1b5e20}
</style>
</head>
<body>
<div class="box">
<h1>Passwort zurücksetzen</h1>

<div id="messages"></div>

<div id="emailForm">
<p>Gib deine E-Mail ein. Du erhältst einen Sicherheitscode von <strong>passwordreset@magentaweb.org</strong>.</p>
<form id="requestForm" novalidate>
<label for="email">E-Mail:</label><br>
<input id="email" name="email" type="email" required placeholder="deine@example.de"><br>
<button type="submit">Code anfordern</button>
</form>
</div>

<div id="codeForm" style="display:none;">
<p>Gib den Code aus der Mail ein:</p>
<form id="verifyForm" novalidate>
<label for="code">Code:</label><br>
<input id="code" name="code" type="text" required placeholder="6-stelliger Code"><br>
<button type="submit">Code prüfen</button>
</form>
</div>

<div id="linkBox" style="display:none;">
<h2>Verifizierung erfolgreich</h2>
<p>Hier ist der Link zur Google-Docs-Seite:</p>
<p><a id="docLink" href="" target="_blank" rel="noopener noreferrer"></a></p>
</div>

<script>
// Ajax-Funktion für Mailversand
document.getElementById('requestForm').addEventListener('submit', function(e){
    e.preventDefault();
    const email = document.getElementById('email').value;
    fetch('mail.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'email='+encodeURIComponent(email)
    })
    .then(r=>r.json())
    .then(data=>{
        showMessage(data);
        if(data.success){
            document.getElementById('emailForm').style.display='none';
            document.getElementById('codeForm').style.display='block';
        }
    })
    .catch(err=>console.error(err));
});

// Ajax-Funktion für Codeprüfung
document.getElementById('verifyForm').addEventListener('submit', function(e){
    e.preventDefault();
    const code = document.getElementById('code').value;
    const email = document.getElementById('email').value;
    fetch('code.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'email='+encodeURIComponent(email)+'&code='+encodeURIComponent(code)
    })
    .then(r=>r.json())
    .then(data=>{
        showMessage(data);
        if(data.success){
            document.getElementById('codeForm').style.display='none';
            const linkEl = document.getElementById('docLink');
            linkEl.href = data.link;
            linkEl.textContent = data.link;
            document.getElementById('linkBox').style.display='block';
        }
    })
    .catch(err=>console.error(err));
});

function showMessage(data){
    const box = document.getElementById('messages');
    box.innerHTML = '';
    if(data.message){
        const div = document.createElement('div');
        div.className = 'msg '+(data.success?'success':'error');
        div.textContent = data.message;
        box.appendChild(div);
    }
}
</script>
</div>
</body>
</html>
