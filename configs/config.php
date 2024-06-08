<?php

declare(strict_types=1);

require_once 'env_load.php';

loadEnv(dirname(__DIR__));

function getEnvValue($key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

define("AMOCRM_HEADERS", ['Content-Type: application/json', 'Authorization: Bearer ' . getEnvValue('ACCESS_TOKEN')]);
define("AMOCRM_API_URI", 'https://' . getEnvValue('AMOCRM_SUBDOMAIN') . '.amocrm.ru/api/v4');
