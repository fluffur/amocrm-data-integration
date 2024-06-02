<?php

const SCRIPTS_PATH = __DIR__ . '/../scripts/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $linkData = $_POST['linkData'] or die('Invalid request data.');
    require_once SCRIPTS_PATH . 'download_and_process.php';
    downloadAndProcessData($linkData);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Test</title>
</head>
<body>
<div style="text-align: center;">
    <form method="POST">
        <label for="linkData">example: https://response.trainity.online/</label><br><br>
        <input type="text" name="linkData" id="linkData" required placeholder="your`s link for take Data"><br><br>
        <input type="submit" value="Send Data">
    </form>
</div>
</body>
</html>