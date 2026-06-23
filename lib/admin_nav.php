<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';

function render_admin_actions(): void
{
    $links = admin_action_links();
    ?>
    <nav class="admin-actions" aria-label="Admin actions">
        <?php foreach ($links as $link): ?>
            <?php if (!empty($link['current'])): ?>
                <span class="admin-action current"><?= e($link['label']) ?></span>
            <?php else: ?>
                <a class="admin-action" href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php
}

function admin_action_links(): array
{
    $currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $pages = [
        'admin.php' => 'Overview',
        'admin_groups.php' => 'Groups',
        'admin_new_user.php' => 'New user',
        'admin_passwords.php' => 'Passwords',
        'admin_archive.php' => 'Archive',
        'admin_export.php' => 'Export',
        'admin_update.php' => 'Updates',
        'admin_release.php' => 'Release',
    ];

    $links = [];
    foreach ($pages as $href => $label) {
        $links[] = [
            'href' => path_to($href),
            'label' => $label,
            'current' => $currentPage === $href,
        ];
    }

    return $links;
}

function render_admin_header(array $user, string $title, string $subtitle): void
{
    ?>
    <section class="section-head">
        <div>
            <h1><?= e($title) ?></h1>
            <p class="muted"><?= e($subtitle) ?></p>
        </div>
        <div class="header-actions">
            <?php render_action_menu(admin_action_links(), 'Admin menu'); ?>
            <?php render_nav($user); ?>
        </div>
    </section>
    <?php
}
