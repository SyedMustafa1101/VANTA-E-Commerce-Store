<?php

declare(strict_types=1);

$projectRoot = str_replace('\\', '/', dirname(__DIR__));
$scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$detectedBaseUrl = '';

if ($scriptFile !== '' && $scriptName !== '' && str_starts_with($scriptFile, $projectRoot)) {
    $relativeScript = substr($scriptFile, strlen($projectRoot));
    if ($relativeScript !== '' && str_ends_with($scriptName, $relativeScript)) {
        $detectedBaseUrl = substr($scriptName, 0, -strlen($relativeScript));
    }
}

return [
    'name' => 'VANTA',
    'tagline' => 'Built for after dark.',
    'environment' => getenv('VANTA_ENV') ?: 'development',
    // An environment override is available for unusual proxy/alias setups.
    'base_url' => rtrim(getenv('VANTA_BASE_URL') ?: $detectedBaseUrl, '/'),
    'timezone' => getenv('VANTA_TIMEZONE') ?: 'Asia/Karachi',
];
