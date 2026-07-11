<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/remember_me.php';

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        remember_me_login_from_cookie();
        if (empty($_SESSION['user_id'])) {
            return null;
        }
    }

    static $user = null;
    if ($user !== null) {
        return $user;
    }

    $stmt = db()->prepare(
        'SELECT u.id, u.team_id, u.name, u.username, u.role, u.sort_order, t.name AS group_name
         FROM planner_users u
         LEFT JOIN planner_teams t ON t.id = u.team_id
         WHERE u.id = ? AND u.is_active = 1 AND u.archived_at IS NULL'
    );
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    if (!$user) {
        logout_user();
        return null;
    }

    if (empty($_SESSION['planner_locale'])) {
        $locale = saved_user_locale((int) $user['id']);
        if ($locale !== null) {
            $_SESSION['planner_locale'] = $locale;
        }
    }

    return $user;
}

function login_user(string $username, string $password, bool $remember = false): bool
{
    $stmt = db()->prepare(
        'SELECT id, password_hash
         FROM planner_users
         WHERE username = ? AND is_active = 1 AND archived_at IS NULL
         LIMIT 1'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $locale = saved_user_locale((int) $user['id']);
    if ($locale !== null) {
        $_SESSION['planner_locale'] = $locale;
    }
    unset($_SESSION['csrf_token']);
    csrf_token();

    if ($remember) {
        create_remember_me_login((int) $user['id']);
    } else {
        clear_current_remember_me_login();
    }

    return true;
}

function logout_user(): void
{
    clear_current_remember_me_login();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect_to('login.php');
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'central_admin') {
        http_response_code(403);
        exit('Only a central administrator can access this page.');
    }

    return $user;
}

function require_group_user(): array
{
    $user = require_login();
    if ($user['role'] !== 'central_admin' && empty($user['team_id'])) {
        http_response_code(403);
        exit('The user is not assigned to a group.');
    }

    return $user;
}

function can_access_team(array $user, int $teamId): bool
{
    return $user['role'] === 'central_admin' || (int) $user['team_id'] === $teamId;
}

function can_manage_group(array $user, int $teamId): bool
{
    return $user['role'] === 'central_admin'
        || ($user['role'] === 'group_admin' && (int) $user['team_id'] === $teamId);
}

function admin_exists(): bool
{
    $stmt = db()->query("SELECT COUNT(*) AS count_admin FROM planner_users WHERE role = 'central_admin' AND is_active = 1 AND archived_at IS NULL");
    return (int) $stmt->fetch()['count_admin'] > 0;
}

function create_user(?int $teamId, string $name, string $username, string $password, string $role = 'user'): void
{
    $name = trim($name);
    $username = trim($username);

    if ($name === '' || $username === '') {
        throw new InvalidArgumentException('Name and username are required.');
    }

    if (strlen($password) < 8) {
        throw new InvalidArgumentException('The password must be at least 8 characters.');
    }

    if (!in_array($role, ['central_admin', 'group_admin', 'user'], true)) {
        throw new InvalidArgumentException('Invalid role.');
    }

    if ($role !== 'central_admin' && !$teamId) {
        throw new InvalidArgumentException('Group users must belong to a group.');
    }

    $sortOrder = 0;
    if ($teamId) {
        $stmt = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) + 10 AS next_order FROM planner_users WHERE team_id = ?');
        $stmt->execute([$teamId]);
        $sortOrder = (int) $stmt->fetch()['next_order'];
    }

    $stmt = db()->prepare(
        'INSERT INTO planner_users (team_id, name, username, password_hash, role, sort_order)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $teamId,
        $name,
        $username,
        password_hash($password, PASSWORD_DEFAULT),
        $role,
        $sortOrder,
    ]);
}

function reset_user_password(int $userId, string $password): void
{
    if (strlen($password) < 8) {
        throw new InvalidArgumentException('The password must be at least 8 characters.');
    }

    $stmt = db()->prepare('UPDATE planner_users SET password_hash = ? WHERE id = ? AND archived_at IS NULL');
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);

    if ($stmt->rowCount() === 0) {
        throw new InvalidArgumentException('The user could not be found.');
    }

    revoke_user_remember_tokens($userId);
    if (!empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $userId) {
        clear_remember_me_cookie();
    }
}

function role_label(string $role): string
{
    return match ($role) {
        'central_admin' => t('Central admin'),
        'group_admin' => t('Group admin'),
        default => t('User'),
    };
}
