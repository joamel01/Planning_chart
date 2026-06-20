<?php
declare(strict_types=1);

const PLANNER_DB_HOST = 'localhost';
const PLANNER_DB_NAME = '';
const PLANNER_DB_USER = '';
const PLANNER_DB_PASS = '';
const PLANNER_BASE_PATH = '/planner-en';
const PLANNER_SESSION_NAME = 'PLANNER_EN_SESSION';
const PLANNER_RECOVERY_KEY = '';

const PLANNER_WEEK_DAYS = [
    1 => 'MON',
    2 => 'TUE',
    3 => 'WED',
    4 => 'THU',
    5 => 'FRI',
];

$plannerLocalConfig = [];
$plannerLocalConfigPath = __DIR__ . '/config.local.php';
if (is_file($plannerLocalConfigPath)) {
    $loadedConfig = require $plannerLocalConfigPath;
    if (is_array($loadedConfig)) {
        $plannerLocalConfig = $loadedConfig;
    }
}

function planner_config(string $constantName, string $envName, string $fallback = ''): string
{
    global $plannerLocalConfig;

    if (array_key_exists($envName, $plannerLocalConfig) && (string) $plannerLocalConfig[$envName] !== '') {
        return (string) $plannerLocalConfig[$envName];
    }

    $value = getenv($envName);
    if (is_string($value) && $value !== '') {
        return $value;
    }

    return constant($constantName) ?: $fallback;
}
