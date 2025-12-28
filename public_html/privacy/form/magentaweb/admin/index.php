<?php
// File: admin/index.html (als PHP-Datei um JSON zu lesen)
$jsonFile = '../formanswers.json';

// Read JSON data
$data = [];
if (file_exists($jsonFile)) {
    $json = file_get_contents($jsonFile);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $data = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MagentaWeb Admin - Form Submissions</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Code&family=Tomorrow&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Google Sans Code', 'Tomorrow', sans-serif;
            background-color: #f4f4f8;
            color: #222;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #fff;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }

        header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 0.75rem;
            text-align: left;
        }

        th {
            background-color: #0073e6;
            color: #fff;
        }

        tr:nth-child(even) {
            background-color: #f0f4f8;
        }

        caption {
            caption-side: top;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>MagentaWeb Admin - Form Submissions</h1>
        <p>All submissions from the privacy form are displayed below.</p>
    </header>

    <main>
        <?php if (empty($data)) : ?>
            <p>No submissions found.</p>
        <?php else : ?>
            <table>
                <caption>Form Submissions</caption>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Email</th>
                        <th>First Name</th>
                        <th>Problem</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $index => $entry) : ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($entry['email']); ?></td>
                            <td><?php echo htmlspecialchars($entry['firstname']); ?></td>
                            <td><?php echo htmlspecialchars($entry['problem']); ?></td>
                            <td><?php echo htmlspecialchars($entry['title']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($entry['description'])); ?></td>
                            <td><?php echo htmlspecialchars($entry['submitted_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</body>
</html>
