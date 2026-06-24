<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function remember_me_lifetime_seconds(): int
{
    return 30 * 24 * 60 * 60;
}

function remember_me_cookie_name(): string
{
    return planner_config('PLANNER_REMEMBER_COOKIE_NAME', 'PLANNER_REMEMBER_COOKIE_NAME', PLANNER_REMEMBER_COOKIE_NAME);
}

function remember_me_cookie_options(?int $expires = null): array
{
    $basePath = planner_config('PLANNER_BASE_PATH', 'PLANNER_BASE_PATH', PLANNER_BASE_PATH);

    return [
        'expires' => $expires ?? time() + remember_me_lifetime_seconds(),
        'path' => $basePath === '' ? '/' : $basePath,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function remember_me_token_pair(): array
{
    $selector = bin2hex(random_bytes(16));
    $token = bin2hex(random_bytes(32));

    return [
        'selector' => $selector,
        'token' => $token,
        'cookie_value' => $selector . ':' . $token,
    ];
}

function parse_remember_me_cookie(string $cookieValue): ?array
{
    if (!preg_match('/\\A([a-f0-9]{32}):([a-f0-9]{64})\\z/i', $cookieValue, $matches)) {
        return null;
    }

    return [
        'selector' => strtolower($matches[1]),
        'token' => strtolower($matches[2]),
    ];
}

function remember_me_token_hash(string $token): string
{
    return hash('sha256', $token);
}

function remember_me_expiry_sql(): string
{
    return (new DateTimeImmutable('+' . remember_me_lifetime_seconds() . ' seconds'))->format('Y-m-d H:i:s');
}

function store_remember_me_token(int $userId): string
{
    $pair = remember_me_token_pair();
    $stmt = db()->prepare(
        'INSERT INTO planner_remember_tokens (user_id, selector, token_hash, expires_at)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $pair['selector'],
        remember_me_token_hash($pair['token']),
        remember_me_expiry_sql(),
    ]);

    return $pair['cookie_value'];
}

function set_remember_me_cookie(string $cookieValue): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    setcookie(remember_me_cookie_name(), $cookieValue, remember_me_cookie_options());
}

function clear_remember_me_cookie(): void
{
    unset($_COOKIE[remember_me_cookie_name()]);

    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    setcookie(remember_me_cookie_name(), '', remember_me_cookie_options(time() - 42000));
}

function revoke_remember_me_selector(?string $cookieValue): void
{
    if ($cookieValue === null || $cookieValue === '') {
        return;
    }

    $parsed = parse_remember_me_cookie($cookieValue);
    if ($parsed === null) {
        return;
    }

    $stmt = db()->prepare('DELETE FROM planner_remember_tokens WHERE selector = ?');
    $stmt->execute([$parsed['selector']]);
}

function revoke_user_remember_tokens(int $userId): void
{
    $stmt = db()->prepare('DELETE FROM planner_remember_tokens WHERE user_id = ?');
    $stmt->execute([$userId]);
}

function create_remember_me_login(int $userId): void
{
    set_remember_me_cookie(store_remember_me_token($userId));
}

function clear_current_remember_me_login(): void
{
    revoke_remember_me_selector($_COOKIE[remember_me_cookie_name()] ?? null);
    clear_remember_me_cookie();
}

function remember_me_login_from_cookie(): ?int
{
    $cookieValue = (string) ($_COOKIE[remember_me_cookie_name()] ?? '');
    $parsed = parse_remember_me_cookie($cookieValue);
    if ($parsed === null) {
        clear_remember_me_cookie();
        return null;
    }

    db()->prepare('DELETE FROM planner_remember_tokens WHERE expires_at < NOW()')->execute();

    $stmt = db()->prepare(
        "SELECT rt.user_id, rt.token_hash
         FROM planner_remember_tokens rt
         INNER JOIN planner_users u ON u.id = rt.user_id
         WHERE rt.selector = ?
           AND rt.expires_at >= NOW()
           AND u.is_active = 1
           AND u.archived_at IS NULL
         LIMIT 1"
    );
    $stmt->execute([$parsed['selector']]);
    $row = $stmt->fetch();

    if (!$row || !hash_equals((string) $row['token_hash'], remember_me_token_hash($parsed['token']))) {
        revoke_remember_me_selector($cookieValue);
        clear_remember_me_cookie();
        return null;
    }

    revoke_remember_me_selector($cookieValue);
    $userId = (int) $row['user_id'];
    create_remember_me_login($userId);

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    unset($_SESSION['csrf_token']);
    csrf_token();

    return $userId;
}
