<?php

function sendRequestToAmoCRM(string $uri, string $method, array $body = [])
{
    return sendRequest(AMOCRM_API_URI . $uri, $method, $body, AMOCRM_HEADERS);
}

function sendRequest(string $uri, string $method, array $body = [], array $headers = [])
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