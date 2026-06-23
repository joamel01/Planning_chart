<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function release_path(string $relativePath): string
{
    return dirname(__DIR__) . '/' . ltrim($relativePath, '/');
}

function release_file_exists(string $relativePath): bool
{
    return is_file(release_path($relativePath));
}

function release_config_value(string $constantName, string $envName, string $fallback = ''): string
{
    return planner_config($constantName, $envName, $fallback);
}

function release_checks(): array
{
    $basePath = release_config_value('PLANNER_BASE_PATH', 'PLANNER_BASE_PATH', PLANNER_BASE_PATH);
    $sessionName = release_config_value('PLANNER_SESSION_NAME', 'PLANNER_SESSION_NAME', PLANNER_SESSION_NAME);
    $recoveryKey = release_config_value('PLANNER_RECOVERY_KEY', 'PLANNER_RECOVERY_KEY', PLANNER_RECOVERY_KEY);
    $dbPassword = release_config_value('PLANNER_DB_PASS', 'PLANNER_DB_PASS', PLANNER_DB_PASS);

    $checks = [];

    $checks[] = [
        'label' => 'Setup file',
        'status' => release_file_exists('setup_admin.php') ? 'warning' : 'ok',
        'summary' => release_file_exists('setup_admin.php')
            ? 'setup_admin.php is still present.'
            : 'setup_admin.php has been removed.',
        'detail' => release_file_exists('setup_admin.php')
            ? 'Remove this file from public deployments after the first central admin has been created.'
            : 'The public setup entry point is not available.',
    ];

    $recoveryFileExists = release_file_exists('admin_recovery.php');
    $recoveryEnabled = $recoveryKey !== '';
    $checks[] = [
        'label' => 'Recovery file',
        'status' => $recoveryFileExists ? 'warning' : 'ok',
        'summary' => $recoveryFileExists
            ? 'admin_recovery.php is still present.'
            : 'admin_recovery.php has been removed.',
        'detail' => $recoveryFileExists
            ? 'Keep it only while recovering a central admin account, then remove it from the public server.'
            : 'The public recovery entry point is not available.',
    ];

    $checks[] = [
        'label' => 'Recovery key',
        'status' => $recoveryEnabled ? 'warning' : 'ok',
        'summary' => $recoveryEnabled
            ? 'Recovery mode is enabled.'
            : 'Recovery mode is disabled.',
        'detail' => $recoveryEnabled
            ? 'Clear PLANNER_RECOVERY_KEY after the recovery task is complete.'
            : 'No recovery key is configured.',
    ];

    $checks[] = [
        'label' => 'Database password',
        'status' => $dbPassword === 'planning_chart_password' ? 'warning' : 'ok',
        'summary' => $dbPassword === 'planning_chart_password'
            ? 'The Docker test database password is in use.'
            : 'The database password is not the documented Docker test default.',
        'detail' => $dbPassword === 'planning_chart_password'
            ? 'Change Docker database passwords before using the stack on a public server.'
            : 'No documented test password was detected.',
    ];

    $checks[] = [
        'label' => 'Base path',
        'status' => ($basePath === '' || $basePath[0] !== '/') ? 'warning' : 'ok',
        'summary' => $basePath === '' ? 'Base path is empty.' : 'Base path is ' . $basePath . '.',
        'detail' => ($basePath === '' || $basePath[0] !== '/')
            ? 'Use / for a domain root install or a leading slash path such as /planner-en.'
            : 'Session cookies and links are scoped to this path.',
    ];

    $checks[] = [
        'label' => 'Session name',
        'status' => preg_match('/^[A-Za-z0-9_-]{6,64}$/', $sessionName) ? 'ok' : 'warning',
        'summary' => 'Session name is ' . ($sessionName === '' ? 'empty' : $sessionName) . '.',
        'detail' => 'Use a stable, app-specific session name for the installation.',
    ];

    return $checks;
}

function release_warning_messages(): array
{
    $messages = [];
    foreach (release_checks() as $check) {
        if ($check['status'] === 'warning') {
            $messages[] = $check['summary'] . ' ' . $check['detail'];
        }
    }

    return $messages;
}
