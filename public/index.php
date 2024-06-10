<?php

require_once '../configs/config.php';
require_once '../data.php';
require_once '../app/lead_processing.php';

sendPostRequestToAmoCRM('/leads/custom_fields', getCustomFields());

$data = array_map(fn($value) => json_decode($value, true), getRawData());

$leads = array_map('createComplexLead', $data);

$response = sendPostRequestToAmoCRM('/leads/complex', $leads);

$status = $response['status'] ?? '200';
$detail = $response['detail'] ?? 'Request have sent successfully';

echo $status . ' ' . $detail;
