<?php

declare(strict_types=1);

loadEnv(dirname(__DIR__));

$uri = validatePostRequestWithField('linkData');

$dataFilename = 'data_file_get.txt';
$tokensFilename = 'tokens.txt';

$contacts = getDataFromUri($uri . $dataFilename);

if (isset($_ENV['ACCESS_TOKEN'])) {
    $authHeaders = getHeadersFromEnv();
} else {
    $authHeaders = getHeadersFromTokensUri($uri . $tokensFilename);
}

$subdomain = $_ENV['AMOCRM_SUBDOMAIN'];

sendContactsDataToAmoCRM($contacts, $authHeaders, $subdomain);

exit;


function sendPost(string $uri, array $headers = [], array $body = []): array
{
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));;
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    return [curl_exec($ch), curl_getinfo($ch)];
}

function sendGet(string $uri, array $headers = [], bool $safe = true): array
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


function loadEnv(string $dirname)
{
    if (!file_exists($dirname . '/.env')) {
        throw new Exception('Missing environment file in path: ' . $dirname);
    }

    $path = $dirname . '/.env';

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (!str_contains($line, '=') || str_starts_with(trim($line), '#')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }

    }
}

function sliceArray(array $elements, int $elementsPerPage): array
{

    $totalPages = ceil(count($elements) / $elementsPerPage);

    $elementsPaging = [];
    for ($page = 0; $page < $totalPages; $page++) {
        $startIndex = $page * $elementsPerPage;
        $elementsPaging[] = array_slice($elements, $startIndex, $elementsPerPage);
    }
    return $elementsPaging;
}

function getContactFields(): array
{
    return [
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
}

function structureRawContactsData(string $rawData): array
{
    $splitData = explode(PHP_EOL, $rawData);
    $mappedData = array_map(fn($value) => json_decode($value, true), $splitData);
    $filteredData = array_filter($mappedData, fn($value) => !is_null($value));
    return array_values($filteredData);
}


function getHeadersFromEnv(): array
{
    return [
        ...getBaseHeaders(),
        'Authorization: Bearer ' . $_ENV['ACCESS_TOKEN']
    ];
}

function getHeadersFromTokensUri(string $tokensUri): array
{
    static $headers;

    if ($headers === null) {
        [$rawTokens, $info] = sendGet($tokensUri, safe: false);
        $tokens = json_decode($rawTokens, true);
        $accessToken = $tokens['access_token'];

        $headers = [
            ...getBaseHeaders(),
            'Authorization: Bearer ' . $accessToken,
        ];
    }

    return $headers;
}

function getBaseHeaders(): array
{
    return ['Content-Type: application/json'];
}

function getDataFromUri(string $path): array
{
    [$rawData, $info] = sendGet($path, safe: false);

    return structureRawContactsData($rawData);

}

function sendContactsDataToAmoCRM(array $contacts, array $headers, string $subdomain): void
{
    $apiUri = "https://$subdomain.amocrm.ru/api/v4/";

    $customFieldsUri = $apiUri . 'contacts/custom_fields';

    $contactsPaging = sliceArray($contacts, 250);

    $availableContactFields = getContactFields();

    foreach ($contactsPaging as $contacts) {
        $processedContacts = [];
        $customFields = [];

        foreach ($contacts as $contact) {
            $processedContact = [];
            $customFieldsValues = [];
            foreach ($contact as $key => $value) {
                if ($value === null) {
                    continue;
                }
                if (!in_array($key, $availableContactFields)) {

                    $type = getTypeOfField($key, $value);
                    $customFields[] = [
                        "name" => $key,
                        "type" => $type,
                        "code" => $key,
                        "is_required" => false,
                        "enums" => []

                    ];
                    var_dump($key);
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
        var_dump(sendPost($customFieldsUri, $headers, $customFields));
        var_dump(sendPost($apiUri . 'contacts', $headers, $processedContacts));

    }
}

function getContacts(array $headers, string $subdomain): array
{
    $contactsUri = "https://$subdomain.amocrm.ru/api/v4/contacts";

    return sendGet($contactsUri, $headers);
}


function validatePostRequestWithField(string $field): mixed
{

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST[$field])) {
        throw new Exception('Field linkData is required');
    }

    return $_POST[$field];
}

function getTypeOfField(string $key, mixed $value)
{
    $key = strtolower($key);
    if ($key === 'date' || $key === 'time') {
        return 'date';
    }
    if ($key === 'site') {
        return 'url';
    }
    $type = gettype($value);

    return match ($type) {
        "array" => 'select',
        "double", "integer", => 'numeric',
        "string" => 'text',
        "boolean" => 'checkbox',
        default => die('Unknown type: ' . $type)
    };

}