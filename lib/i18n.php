<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function locale_directory(): string
{
    return dirname(__DIR__) . '/locales';
}

function locale_catalogs(): array
{
    static $catalogs = null;
    if ($catalogs !== null) {
        return $catalogs;
    }

    $catalogs = [];
    foreach (glob(locale_directory() . '/*.php') ?: [] as $file) {
        $code = pathinfo($file, PATHINFO_FILENAME);
        if (preg_match('/\A[a-z]{2}(?:-[a-z0-9]{2,8})?\z/i', $code) !== 1) {
            continue;
        }

        $catalog = require $file;
        if (!is_array($catalog)
            || ($catalog['code'] ?? null) !== $code
            || !is_string($catalog['name'] ?? null)
            || !is_array($catalog['translations'] ?? null)) {
            continue;
        }

        $catalogs[$code] = $catalog;
    }

    uasort($catalogs, static fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));
    return $catalogs;
}

function locale_is_available(string $locale): bool
{
    return array_key_exists($locale, locale_catalogs());
}

function default_locale(): string
{
    $configured = planner_config('PLANNER_DEFAULT_LOCALE', 'PLANNER_DEFAULT_LOCALE', PLANNER_DEFAULT_LOCALE);
    if (locale_is_available($configured)) {
        return $configured;
    }

    return array_key_exists('en', locale_catalogs()) ? 'en' : (string) array_key_first(locale_catalogs());
}

function current_locale(): string
{
    $sessionLocale = $_SESSION['planner_locale'] ?? '';
    if (is_string($sessionLocale) && locale_is_available($sessionLocale)) {
        return $sessionLocale;
    }

    $cookieLocale = $_COOKIE[locale_cookie_name()] ?? '';
    if (is_string($cookieLocale) && locale_is_available($cookieLocale)) {
        $_SESSION['planner_locale'] = $cookieLocale;
        return $cookieLocale;
    }

    return default_locale();
}

function locale_cookie_name(): string
{
    return planner_config('PLANNER_SESSION_NAME', 'PLANNER_SESSION_NAME', PLANNER_SESSION_NAME) . '_LOCALE';
}

function user_locale_storage_ready(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) AS column_count
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute(['planner_users', 'locale']);
    $ready = (int) $stmt->fetch()['column_count'] > 0;
    return $ready;
}

function saved_user_locale(int $userId): ?string
{
    if (!user_locale_storage_ready()) {
        return null;
    }

    $stmt = db()->prepare('SELECT locale FROM planner_users WHERE id = ?');
    $stmt->execute([$userId]);
    $locale = $stmt->fetchColumn();

    return is_string($locale) && locale_is_available($locale) ? $locale : null;
}

function set_locale(string $locale, ?int $userId = null): void
{
    if (!locale_is_available($locale)) {
        throw new InvalidArgumentException('The selected language is not available.');
    }

    $_SESSION['planner_locale'] = $locale;
    setcookie(locale_cookie_name(), $locale, [
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => planner_config('PLANNER_BASE_PATH', 'PLANNER_BASE_PATH', PLANNER_BASE_PATH) ?: '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if ($userId !== null && user_locale_storage_ready()) {
        $stmt = db()->prepare('UPDATE planner_users SET locale = ? WHERE id = ?');
        $stmt->execute([$locale, $userId]);
    }
}

function t(string $key, array $replacements = []): string
{
    $catalogs = locale_catalogs();
    $locale = current_locale();
    $fallback = default_locale();
    $text = $catalogs[$locale]['translations'][$key]
        ?? $catalogs[$fallback]['translations'][$key]
        ?? $key;

    foreach ($replacements as $name => $value) {
        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }

    return $text;
}

function weekday_label(string $dayCode): string
{
    return t('day.' . strtolower($dayCode));
}

function language_return_page(): string
{
    $page = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    return $page . ($query === '' ? '' : '?' . $query);
}
