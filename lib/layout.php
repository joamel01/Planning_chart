<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function render_header(string $title, ?array $user = null, string $bodyClass = ''): void
{
    $flashes = take_flashes();
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#00ABE4">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Work Planner">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?= e($title) ?> - Work Planner</title>
    <link rel="manifest" href="<?= e(path_to('manifest.php')) ?>">
    <link rel="icon" href="<?= e(path_to('assets/app-icon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= e(path_to('assets/app-icon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(path_to('assets/app.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<main class="page">
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

function render_nav(array $user): void
{
    $currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    ?>
    <nav class="nav">
        <?php if ($currentPage !== 'board.php'): ?>
            <a href="<?= e(path_to('board.php')) ?>">Board</a>
        <?php endif; ?>
        <?php if ($currentPage !== 'team.php'): ?>
            <a class="icon-link" href="<?= e(path_to('team.php')) ?>" aria-label="People" title="People">
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                    <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"></path>
                    <path d="M4.5 20a7.5 7.5 0 0 1 15 0"></path>
                </svg>
            </a>
        <?php endif; ?>
        <?php if ($user['role'] === 'central_admin' && $currentPage !== 'admin.php'): ?>
            <a href="<?= e(path_to('admin.php')) ?>">Admin</a>
        <?php endif; ?>
        <a class="icon-link" href="<?= e(path_to('logout.php')) ?>" aria-label="Log out" title="Log out">
            <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                <circle cx="7.5" cy="14.5" r="3.5"></circle>
                <path d="M10.3 12.1 20 2.5"></path>
                <path d="M15.5 6.5 18 9"></path>
                <path d="M13.2 8.8 15 10.6"></path>
            </svg>
        </a>
    </nav>
    <?php
}
