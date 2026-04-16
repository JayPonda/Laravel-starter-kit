<?php

function loadEnv($path)
{
    if (! file_exists($path)) {
        return [];
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove quotes if present
        if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }

        $env[$name] = $value;
    }

    return $env;
}

function getEnvVar($env, $name, $default = null)
{
    return $env[$name] ?? $default;
}

function getAppEnv()
{
    $envPath = __DIR__.'/../.env';
    if (! file_exists($envPath)) {
        $envPath = __DIR__.'/../.env.example';
    }

    return loadEnv($envPath);
}
