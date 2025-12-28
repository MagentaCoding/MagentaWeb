<?php
// File: submit.php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data and sanitize
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $firstname = htmlspecialchars($_POST['firstname']);
    $problem = htmlspecialchars($_POST['problem']);
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);

    // Prepare entry array
    $entry = [
        'email' => $email,
        'firstname' => $firstname,
        'problem' => $problem,
        'title' => $title,
        'description' => $description,
        'submitted_at' => date('Y-m-d H:i:s')
    ];

    // File path
    $file = 'formanswers.json';

    // Read existing data
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            $data = [];
        }
    } else {
        $data = [];
    }

    // Add new entry
    $data[] = $entry;

    // Save back to JSON
    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT))) {
        // Redirect to success page
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Submission Successful</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f8; text-align: center; padding: 2rem; }
                .container { background-color: #fff; padding: 2rem; border-radius: 10px; display: inline-block; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                h1 { color: #0073e6; }
                a { display: inline-block; margin-top: 1rem; text-decoration: none; color: #0073e6; font-weight: 500; }
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Form Submitted Successfully!</h1>
                <p>Thank you for submitting your privacy concern. We will process it securely.</p>
                <a href="index.html">Go Back to Form</a>
            </div>
        </body>
        </html>';
    } else {
        echo "Error: Unable to save your submission. Please try again.";
    }
} else {
    // If accessed directly, redirect to form
    header('Location: index.html');
    exit;
}
?>
