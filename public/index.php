<?php

require_once '../configs/config.php';
require_once '../data.php';

sendPostRequest(AMOCRM_API_URI . '/leads/custom_fields', getCustomFields());

$data = array_map(fn($value) => json_decode($value, true), getRawData());

$leads = array_map('createComplexLead', $data);

$response = sendPostRequest(AMOCRM_API_URI . '/leads/complex', $leads);

echo $response['status'] . ' ' . $response['detail'];


function sendPostRequest(string $uri, array $body = [], array $headers = AMOCRM_HEADERS)
{
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ? json_decode($response, true) : $response;
}

function createComplexLead(array $record): array
{

    $lead = $record['lead'];
    $contact = $record['contact'] ?? null;

    $contactName = $contact['name'] ?? $lead['name'];
    $companyName = $contact ? $lead['name'] : '';
    $contactShortName = getContactShortName($contactName);


    return [
        'custom_fields_values' => createCustomFieldsValues($contactShortName, $companyName),
        'name' => $contactName,
        '_embedded' => [
            'contacts' => [
                createContact($lead['phones']),
            ],
        ],
    ];
}

function createCustomFieldsValues(string $contactShortName, $companyName): array
{
    return [
        [
            'field_code' => 'NAME',
            'values' => [
                ['value' => $contactShortName],
            ],
        ],
        [
            'field_code' => 'COMPANY_NAME',
            'values' => [
                ['value' => $companyName],
            ],
        ],
    ];
}

function getContactShortName(string $contactName): string
{
    $names = explode(' ', $contactName, 3);
    return count($names) === 3 ? $names[1] : $names[0];
}

function createContact(string $phone): array
{
    return [
        'custom_fields_values' => [
            [
                'field_code' => 'PHONE',
                'values' => [
                    [
                        'enum_code' => 'WORK',
                        'value' => $phone,
                    ],
                ],

            ],
        ],
    ];
}
