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
$tokens = json_decode($tokensRaw, true);
$accessToken = $tokens['access_token'];
// assume that subdomain of provided linkData uri = subdomain of api uri
$protocolAndSubdomain = explode('.', $uri)[0];
$apiUri = $protocolAndSubdomain . '.amocrm.ru/api/v4/';
$contactsWithLeadsUri = $apiUri . 'contacts?with=leads';
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken,
];

$availableContactFields = [
    'name',
    'first_name',
    'last_name',
    'responsible_user_id',
    'created_at',
    'updated_at',
    'custom_fields_values',
    'tags_to_add',
    '_embedded',
    'request_id'
];



$contactsPaging = [];

$contactsPerPage = 250;

// Общее количество страниц
$totalPages = ceil(count($contacts) / $contactsPerPage);

// Разделение массива на страницы
for ($page = 1; $page <= $totalPages; $page++) {
    $startIndex = ($page - 1) * $contactsPerPage;
    $contactsPaging[] = array_slice($contacts, $startIndex, $contactsPerPage);
}

foreach ($contactsPaging as $contacts) {
    $processedContacts = [];

    foreach ($contacts as $contact) {
        $processedContact = [];
        $customFieldsValues = [];
        foreach ($contact as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (!in_array($key, $availableContactFields)) {

                if (in_array($key, ['site', 'time', 'region', 'ref', 'date'])) {
                    $type = convertToContactDatatype($key);
                } else {
                    $type = convertToContactDatatype(gettype($value));
                }
                $customFieldsValues[] = [
                    'field_name' => $key,
                    'field_type' => $type,
                    'field_code' => $key,
                    'values' => $value
                ];
            } else {
                $processedContact[$key] = $value;
            }
        }
        if (!empty($customFieldsValues)) {
            $processedContact['custom_fields_values'] = $customFieldsValues;
        }
         $processedContacts[] = $processedContact;
    }
    [$data, $info] = postContent($apiUri . 'contacts', $headers, $processedContacts);

    var_dump($info, $data, $processedContacts[0]['custom_fields_values']);
}



function convertToContactDatatype(string $type): string
{
    return match ($type) {
        'string' => 'text',
        'integer', 'int', 'float' => 'numeric',
        'array' => 'select',
        'bool' => 'checkbox',
        'time', 'date' => 'date',
        'region' => 'smart_address',
        'site', 'ref' => 'url',
        default => die('invalid type: ' . $type),
    };
}

function postContent(string $uri, array $headers = [], array $body = []): array
{
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));;
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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


