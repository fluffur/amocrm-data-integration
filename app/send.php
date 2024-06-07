<?php

declare(strict_types=1);

require_once '../configs/config.php';

/**
 * Главная функция, которая запускает процесс подготовки данных,
 * создания сложных сделок и отправки POST-запроса с данными сделок.
 *
 * @return void
 */
function main(): void
{
    $data = prepareData();

    $complexLeads = createComplexLeadsArray($data);

    [, $code] = sendPostRequest(API_V4_URI . '/leads/complex', $complexLeads, AMOCRM_HEADERS);

    echo viewOperationResult($code);

}

/**
 * Подготавливает данные для создания сложных сделок.
 *
 * Если запрос не является POST или данные отсутствуют в `$_POST['linkData']`,
 * данные загружаются из файла `../data.php`. В противном случае, данные берутся из POST-запроса.
 *
 * @return array Массив данных для создания сложных сделок.
 */
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

/**
 * Создает данные для сложного запроса добавления сделки.
 *
 * Если `contactRecord` не содержит `null`, то используется название сделки для названия **компании** (company.name).
 * В ином случае, используется название сделки для имени **контакта** (contact.name), а название **компании** остается пустым.
 *
 * @param array $leadRecord Массив с данными сделки.
 * @param array|null $contactRecord Массив с данными контакта или null.
 * @return array Массив данных для запроса создания сделки.
 */
function createComplexLeadRequestData(array $leadRecord, ?array $contactRecord): array
{


    $contactFullName = $contactRecord['name'] ?? $leadRecord['name'];
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
                'value' => $leadRecord['phones'],
                'enum_code' => 'WORK'
            ],
        ],
    ];

    $company = [
        'name' => isset($contactRecord) ? $leadRecord['name'] : '',
        'custom_fields_values' => [$phone],
    ];


    return [
        'name' => $leadRecord['name'],
        '_embedded' => [
            'contacts' => [$contact],
            'companies' => [$company],
        ],
    ];
}

/**
 * Отправляет POST-запрос на указанный URL.
 *
 * @param string $url URL для отправки запроса.
 * @param array $body Тело запроса в формате массива.
 * @param array $headers Заголовки запроса.
 * @return array Массив, содержащий ответ и код состояния HTTP.
 */
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


/**
 * Возвращает сообщение о результате операции на основе кода состояния HTTP.
 *
 * @param int $code Код состояния HTTP.
 * @return string Сообщение о результате операции.
 */
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
