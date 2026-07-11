<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/release_checks.php';

function render_header(string $title, ?array $user = null, string $bodyClass = ''): void
{
    $flashes = take_flashes();
    ?>
<!doctype html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102442">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= e(t('app.name')) ?>">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?= e(t($title)) ?> - <?= e(t('app.name')) ?></title>
    <link rel="manifest" href="<?= e(path_to('manifest.php')) ?>">
    <link rel="icon" href="<?= e(path_to('assets/app-icon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= e(path_to('assets/app-icon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(path_to('assets/app.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<main class="page">
    <form method="post" action="<?= e(path_to('language.php')) ?>" class="language-picker">
        <?= csrf_field() ?>
        <input type="hidden" name="return_page" value="<?= e(language_return_page()) ?>">
        <label>
            <span class="visually-hidden"><?= e(t('language.label')) ?></span>
            <select name="locale" aria-label="<?= e(t('language.label')) ?>" onchange="this.form.submit()">
                <?php foreach (locale_catalogs() as $locale): ?>
                    <option value="<?= e($locale['code']) ?>" <?= $locale['code'] === current_locale() ? 'selected' : '' ?>>
                        <?= e($locale['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <noscript><button type="submit"><?= e(t('language.label')) ?></button></noscript>
    </form>
    <?php if ($user && $user['role'] === 'central_admin'): ?>
        <?php foreach (release_warning_messages() as $message): ?>
            <div class="flash warning"><?= e(t('release.warning')) ?> <?= e($message) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php foreach ($flashes as $flash): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?php
}

function render_footer(): void
{
    ?>
</main>
</body>
</html>
    <?php
}

function render_action_menu(array $links, string $label = 'Menu'): void
{
    ?>
    <details class="menu">
        <summary class="icon-link menu-button" aria-label="<?= e($label) ?>" title="<?= e($label) ?>">
            <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                <path d="M6 5v14"></path>
                <path d="M12 5v14"></path>
                <path d="M18 5v14"></path>
            </svg>
        </summary>
        <div class="menu-panel">
            <?php foreach ($links as $link): ?>
                <a class="<?= !empty($link['current']) ? 'current' : '' ?>" href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a>
            <?php endforeach; ?>
        </div>
    </details>
    <?php
}

function render_nav(array $user): void
{
    $currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    ?>
    <nav class="nav">
        <?php if ($currentPage !== 'board.php'): ?>
            <a href="<?= e(path_to('board.php')) ?>"><?= e(t('nav.board')) ?></a>
        <?php endif; ?>
        <?php if ($currentPage !== 'team.php'): ?>
            <a class="icon-link" href="<?= e(path_to('team.php')) ?>" aria-label="<?= e(t('nav.people')) ?>" title="<?= e(t('nav.people')) ?>">
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                    <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"></path>
                    <path d="M4.5 20a7.5 7.5 0 0 1 15 0"></path>
                </svg>
            </a>
        <?php endif; ?>
        <?php if ($user['role'] === 'central_admin' && $currentPage !== 'admin.php'): ?>
            <a href="<?= e(path_to('admin.php')) ?>"><?= e(t('nav.admin')) ?></a>
        <?php endif; ?>
        <form method="post" action="<?= e(path_to('logout.php')) ?>" class="nav-form">
            <?= csrf_field() ?>
            <button class="icon-link" type="submit" aria-label="<?= e(t('nav.logout')) ?>" title="<?= e(t('nav.logout')) ?>">
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                    <circle cx="7.5" cy="14.5" r="3.5"></circle>
                    <path d="M10.3 12.1 20 2.5"></path>
                    <path d="M15.5 6.5 18 9"></path>
                    <path d="M13.2 8.8 15 10.6"></path>
                </svg>
            </button>
        </form>
    </nav>
    <?php
}
