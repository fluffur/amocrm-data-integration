<?php

declare(strict_types=1);

require_once 'env_load.php';

loadEnv(dirname(__DIR__));

function getEnvironmentValue($key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

define("ACCESS_TOKEN", getEnvironmentValue('ACCESS_TOKEN'));
define("SUBDOMAIN", getEnvironmentValue('AMOCRM_SUBDOMAIN'));

const AMOCRM_HEADERS = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . ACCESS_TOKEN
];
const BASE_URI = 'https://' . SUBDOMAIN . '.amocrm.ru/';
const AMOCRM_API_URI = BASE_URI . '/api/v4';