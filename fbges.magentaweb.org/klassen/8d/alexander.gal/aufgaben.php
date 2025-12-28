<?php
// Parameter auslesen
$fach = $_GET['fach'] ?? null;
$aufgabe = $_GET['aufgabe'] ?? null;

//  Wenn mindestens EIN Parameter fehlt → 424
if (!$fach || !$aufgabe) {
    http_response_code(424);
    readfile($_SERVER['DOCUMENT_ROOT'] . "/error/424.shtml");
    exit;
}

// Pfad zur Datei
$datei = "./$fach/$aufgabe.html";

//  Wenn Parameter da sind, aber Datei fehlt → 404
if (!file_exists($datei)) {
    http_response_code(404);
    readfile($_SERVER['DOCUMENT_ROOT'] . "/error/404.shtml");
    exit;
}

// Alles ok → Datei ausgeben
readfile($datei);
?>
