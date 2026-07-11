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
        'admin.php' => 'admin.overview',
        'admin_groups.php' => 'admin.groups',
        'admin_new_user.php' => 'admin.new_user',
        'admin_passwords.php' => 'admin.passwords',
        'admin_archive.php' => 'admin.archive',
        'admin_export.php' => 'admin.export',
        'admin_update.php' => 'admin.updates',
        'admin_release.php' => 'admin.release',
    ];

    $links = [];
    foreach ($pages as $href => $label) {
        $links[] = [
            'href' => path_to($href),
            'label' => t($label),
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
            <h1><?= e(t($title)) ?></h1>
            <p class="muted"><?= e(t($subtitle)) ?></p>
        </div>
        <div class="header-actions">
            <?php render_action_menu(admin_action_links(), 'Admin menu'); ?>
            <?php render_nav($user); ?>
        </div>
    </section>
    <?php
}
