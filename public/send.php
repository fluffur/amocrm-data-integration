<?php

declare(strict_types=1);

require_once '../configs/config.php';

$data = prepareData();

$complexLeads = createComplexLeadsArray($data);

[$response, $code] = sendPostRequest(API_V4_URI . '/leads/complex', $complexLeads, AMOCRM_HEADERS);

echo viewOperationResult($code);

function createComplexLeadRequestData(array $lead, array $contact, array $callResult): array
{

    $contactNames = explode(' ', $contact['name'], 3);
    $isFioFormat = count($contactNames) === 3;
    $contactFirstName = $isFioFormat ? $contactNames[1] : $contactNames[0];
    $contactLastName = $isFioFormat ? $contactNames[0] : $contactNames[1];

    return [
        'name' => $lead['name'],
        '_embedded' => [
            'contacts' => [
                [
                    'name' => $contact['name'],
                    'first_name' => $contactFirstName,
                    'last_name' => $contactLastName,
                ],
            ],
            'companies' => [
                [
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
                ],
            ],
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

function prepareData(): array
{

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['linkData'])) {
        $data = json_decode($_POST['linkData'], true);
    } else {
        require_once '../data.php';
        $data = array_map(fn($value) => json_decode($value, true), getData());
    }
    return $data;
}

function viewOperationResult(int $code): string
{

    return match ($code) {
        200 => 'Data successfully sent!',
        401 =>
        'Invalid API auth credentials.',
        400 => 'Invalid data.'
    };
}

function createComplexLeadsArray(array $data): array
{

    $complexLeads = [];

    foreach ($data as $record) {

        $lead = $record['lead'];
        $contact = $record['contact'];
        $callResult = $record['call_result'];

        // Обязательное условие: наличие контакта в сделке
        // Все сделки без данных о контакте пропускаются
        if ($contact === null) {
            continue;
        }

        $complexLeads[] = createComplexLeadRequestData($lead, $contact, $callResult);
    }
    return $complexLeads;
}