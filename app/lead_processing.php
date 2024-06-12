<?php

require_once __DIR__ . '/amoCRM_client.php';

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
                createContact($contact['phones'] ?? $lead['phones']),
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

function getContactShortName(string $fullName): string
{
    $names = explode(' ', $fullName, 3);
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
                        'value' => $phone,
                        'enum_code' => 'WORK',
                    ],
                ],

            ],
        ],
    ];
}
