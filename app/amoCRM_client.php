<?php

function sendPostRequestToAmoCRM(string $uri, array $body = [])
{
    $ch = curl_init(AMOCRM_API_URI . $uri);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, AMOCRM_HEADERS);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ? json_decode($response, true) : $response;
}
