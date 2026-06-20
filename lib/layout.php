<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function render_header(string $title, ?array $user = null, string $bodyClass = ''): void
{
    $flashes = take_flashes();
    $setupAdminPath = __DIR__ . '/../setup_admin.php';
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
    <?php if ($user && $user['role'] === 'central_admin' && is_file($setupAdminPath)): ?>
        <div class="flash warning">
            Security warning: <code>setup_admin.php</code> still exists. Delete it from the public server after installation.
        </div>
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

function render_nav(array $user, array $sectionLinks = []): void
{
    $currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $menuLinks = $sectionLinks;
    $menuHrefs = array_column($menuLinks, 'href');

    if ($currentPage !== 'team.php' && !in_array(path_to('team.php'), $menuHrefs, true)) {
        $menuLinks[] = [
            'href' => path_to('team.php'),
            'label' => 'People',
        ];
    }

    if ($user['role'] === 'central_admin' && $currentPage !== 'admin.php' && !in_array(path_to('admin.php'), $menuHrefs, true)) {
        $menuLinks[] = [
            'href' => path_to('admin.php'),
            'label' => 'Admin',
        ];
    }

    $menuLinks[] = [
        'href' => path_to('logout.php'),
        'label' => 'Log out',
    ];
    ?>
    <nav class="nav">
        <details class="menu">
            <summary class="icon-link menu-button" aria-label="Menu" title="Menu">
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                    <path d="M6 5v14"></path>
                    <path d="M12 5v14"></path>
                    <path d="M18 5v14"></path>
                </svg>
            </summary>
            <div class="menu-panel">
                <?php foreach ($menuLinks as $link): ?>
                    <a class="<?= !empty($link['current']) ? 'current' : '' ?>" href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </details>
        <?php if ($currentPage !== 'board.php'): ?>
            <a href="<?= e(path_to('board.php')) ?>">Board</a>
        <?php endif; ?>
    </nav>
    <?php
}
