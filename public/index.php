<?php

require_once '../configs/config.php';
require_once '../data.php';

$data = array_map(fn($value) => json_decode($value, true), getData());

sendRequest(AMOCRM_API_URI . '/leads/custom_fields', 'POST', AMOCRM_HEADERS, getCustomFields());

$leads = array_map('createComplexLead', $data);

$response = sendRequest(AMOCRM_API_URI . '/leads/complex', 'POST', AMOCRM_HEADERS, $leads);

$status = $response['status'] ?? 200;
$detail = $response['detail'] ?? 'Request has sent successfully';
echo $status . ' ' . $detail;

function sendRequest(string $uri, string $method, array $headers = [], array $body = [])
{
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
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
    $phone = $lead['phones'];

    $contact = $record['contact'] ?? null;
    $companyName = $lead['name'];

    if ($contact === null) {
        $companyName = '';
        $contactName = $lead['name'];
    } else {
        $contactName = $contact['name'];
    }
    $names = explode(" ", $contactName, 3);

    $contactShortName = count($names) === 3 ? $names[1] : $names[0];

    return [
        'custom_fields_values' => [
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
        ],
        'name' => $contactName,
        '_embedded' => [
            'contacts' => [
                [
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
                ],

            ],
        ],
    ];
}

