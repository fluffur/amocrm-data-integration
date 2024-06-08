<?php

declare(strict_types=1);

function loadEnv(string $dirname): void
{
    if (!file_exists($dirname . '/.env')) {
        throw new RuntimeException('Missing environment file in path: ' . $dirname);
    }

    $path = $dirname . '/.env';

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (!str_contains($line, '=') || str_starts_with(trim($line), '#')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }

    }
}

