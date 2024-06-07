<?php

declare(strict_types=1);

require_once '../configs/config.php';

function main(): void
{
    $data = prepareData();

    $complexLeads = createComplexLeadsArray($data);

    [, $code] = sendPostRequest(API_V4_URI . '/leads/complex', $complexLeads, AMOCRM_HEADERS);

    echo viewOperationResult($code);

}

function prepareData(): array
{

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['linkData'])) {
        require_once '../data.php';
        return array_map(fn($value) => json_decode($value, true), getData());
    }
    return json_decode($_POST['linkData'], true);

}


function createComplexLeadsArray(array $data): array
{
    return array_map(
        fn($record) => createComplexLeadRequestData($record['lead'], $record['contact']),
        $data
    );

}

function createComplexLeadRequestData(array $lead, ?array $contact): array
{
    $contactFullName = $contact['name'] ?? $lead['name'];

    $contactNames = array_values(explode(' ', $contactFullName, 3));

    $isFioFormat = count($contactNames) === 3;

    $contact = [
        'name' => $contactFullName,
        'first_name' => $isFioFormat ? $contactNames[1] : $contactNames[0],
        'last_name' => $isFioFormat ? $contactNames[0] : $contactNames[1],
    ];

    $phone = [
        'field_code' => 'PHONE',
        'values' => [
            [
                'value' => $lead['phones'],
                'enum_code' => 'WORK'
            ],
        ],
    ];

    $company = [
        'name' => $lead['name'],
        'custom_fields_values' => [$phone],
    ];


    return [
        'name' => $lead['name'],
        '_embedded' => [
            'contacts' => [$contact],
            'companies' => [$company],
        ],
    ];
}

function sendPostRequest(string $url, array $body, array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);
    return [$response, $code];
}


function viewOperationResult(int $code): string
{

    return match ($code) {
        200 => 'Data was successfully sent!',
        401 =>
        'Invalid API auth credentials.',
        400 => 'Invalid data.',
        default => 'Unknown error occurred.'
    };
}
