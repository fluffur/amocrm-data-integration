<?php

declare(strict_types=1);

require_once '../configs/config.php';

main();
exit;


function main(): void
{
    $data = prepareData();

    $complexLeads = createComplexLeadsArray($data);

    [, $code] = sendPostRequest(API_V4_URI . '/leads/complex', $complexLeads, AMOCRM_HEADERS);

    echo viewOperationResult($code);

}

function prepareData(): array
{

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['linkData'])) {
        return json_decode($_POST['linkData'], true);
    }

    require_once '../data.php';
    return array_map(fn($value) => json_decode($value, true), getData());
}


function createComplexLeadsArray(array $data): array
{


    // Обязательное условие: наличие контакта в сделке
    // Все сделки без данных о контакте пропускаются
    $filteredData = array_filter($data, fn($record) => isset($record['lead'], $record['contact']));

    return array_map(
        fn($record) => createComplexLeadRequestData($record['lead'], $record['contact']),
        $filteredData
    );

}

function createComplexLeadRequestData(array $lead, array $contact): array
{

    $contactNames = array_values(explode(' ', $contact['name'], 3));

    $isFioFormat = count($contactNames) === 3;

    $contact = [
        'name' => $contact['name'],
        'first_name' => $isFioFormat ? $contactNames[1] : $contactNames[0],
        'last_name' => $isFioFormat ? $contactNames[0] : $contactNames[1],
    ];

    $company = [
        'name' => $lead['name'],
        'custom_fields_values' => [
            [
                'field_code' => 'PHONE',
                'values' => [
                    [
                        'value' => $lead['phones'],
                        'enum_code' => 'WORK'
                    ],
                ],
            ],
        ],
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
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$response, $code];
}


function viewOperationResult(int $code): string
{

    return match ($code) {
        200 => 'Data successfully sent!',
        401 =>
        'Invalid API auth credentials.',
        400 => 'Invalid data.',
        default => 'Unknown error occurred.'
    };
}
