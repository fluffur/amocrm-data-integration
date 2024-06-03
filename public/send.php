<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method');
}

// POST

$uri = $_POST['linkData'] or die('Invalid request data.');
$dataUri = $uri . 'data_file_get.txt';
[$rawData, $info] = getContent($dataUri, safe: false);
$contacts = array_values(array_filter(
    array_map(fn($value) => json_decode($value, true), explode(PHP_EOL, $rawData)),
    fn($item) => !empty($item)
));

// API

$tokensUri = $uri . 'tokens.txt';
[$tokensRaw, $info] = getContent($tokensUri, safe: false);
$tokens = json_decode($tokensRaw);
$accessToken = $tokens['access_token'];

// assume that subdomain of provided linkData uri = subdomain of api uri
$protocolAndSubdomain = explode('.', $uri)[0];
$apiUri = $protocolAndSubdomain . '.amocrm.ru/api/v4/';
$contactsWithLeadsUri = $apiUri . 'contacts?with=leads';
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken,
];

function postContent(string $uri, array $headers = [], array $body = []): array
{
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));;
    return [curl_exec($ch), curl_getinfo($ch)];
}

function getContent(string $uri, array $headers = [], bool $safe = true): array
{
    $ch = curl_init($uri);
    if (!$safe) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    return [curl_exec($ch), curl_getinfo($ch)];
}

