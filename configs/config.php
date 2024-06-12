<?php

require_once 'env_load.php';

loadEnv(dirname(__DIR__));

define("AMOCRM_HEADERS", ['Content-Type: application/json', 'Authorization: Bearer ' . $_ENV['ACCESS_TOKEN']]);
define("AMOCRM_API_URI", 'https://' . $_ENV['AMOCRM_SUBDOMAIN'] . '.amocrm.ru/api/v4');
