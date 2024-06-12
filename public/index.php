<?php

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../data.php';
require_once __DIR__ . '/../app/lead_processing.php';

sendRequestToAmoCRM('/leads/custom_fields', 'POST', getCustomFields());

$data = array_map(fn($value) => json_decode($value, true), getRawData());
$leads = array_map('createComplexLead', $data);

$response = sendRequestToAmoCRM('/leads/complex', 'POST', $leads);

$status = $response['status'] ?? '200';
$detail = $response['detail'] ?? 'Request has sent successfully';

echo $status . ' ' . $detail;
