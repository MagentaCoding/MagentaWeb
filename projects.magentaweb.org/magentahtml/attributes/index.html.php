<?php
/**
 * MagentaHTML Index Generator
 * Erstellt in jedem Unterordner eine index.html, falls keine existiert.
 * Autor: Alexander
 * Version: 1.0
 */

$baseDir = __DIR__; // Startpunkt: aktuelles Verzeichnis

function createIndexFiles($dir) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            $indexPath = $path . DIRECTORY_SEPARATOR . 'index.html';

            // Falls index.html noch nicht existiert
            if (!file_exists($indexPath)) {
                $elementName = basename($path);

                $htmlContent = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>$elementName – MagentaHTML-/HTML-Attributes</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 40px;
      background-color: #fafafa;
      color: #333;
    }
    h1 {
      color: #d60077;
    }
    code {
      background: #f0f0f0;
      padding: 3px 6px;
      border-radius: 5px;
    }
  </style>
</head>
<body>
  <h1>$elementName Element</h1>
  <p>Dies ist die automatische Indexseite für das <code>$elementName;</code>-Attribute in MagentaHTML.</p>
  <p>Weitere Informationen folgen bald.</p>
</body>
</html>
HTML;

                file_put_contents($indexPath, $htmlContent);
                echo "Erstellt: $indexPath\n";
            }

            // Rekursiv weitermachen
            createIndexFiles($path);
        }
    }
}

createIndexFiles($baseDir);
echo "Fertig! Alle index.html-Dateien wurden erstellt.\n";
?>
