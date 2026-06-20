<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$basePath = planner_config('PLANNER_BASE_PATH', 'PLANNER_BASE_PATH', PLANNER_BASE_PATH);

session_name(planner_config('PLANNER_SESSION_NAME', 'PLANNER_SESSION_NAME', PLANNER_SESSION_NAME));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $basePath === '' ? '/' : $basePath,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbHost = planner_config('PLANNER_DB_HOST', 'PLANNER_DB_HOST', 'localhost');
    $dbName = planner_config('PLANNER_DB_NAME', 'PLANNER_DB_NAME');
    $dbUser = planner_config('PLANNER_DB_USER', 'PLANNER_DB_USER');
    $dbPass = planner_config('PLANNER_DB_PASS', 'PLANNER_DB_PASS');

    $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function path_to(string $path = ''): string
{
    $basePath = planner_config('PLANNER_BASE_PATH', 'PLANNER_BASE_PATH', PLANNER_BASE_PATH);
    return rtrim($basePath, '/') . '/' . ltrim($path, '/');
}

function redirect_to(string $path): never
{
    header('Location: ' . path_to($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid security token. Reload the page and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function monday_for_date(?string $date): DateTimeImmutable
{
    $base = $date ? new DateTimeImmutable($date) : new DateTimeImmutable('today');
    return $base->modify('monday this week');
}

function normalize_cell_value(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    if (preg_match_all('/./us', $value, $characters) === false) {
        throw new InvalidArgumentException('The cell contains invalid text.');
    }

    if (count($characters[0]) > 3) {
        throw new InvalidArgumentException('The cell may contain at most three characters.');
    }

    return $value;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
