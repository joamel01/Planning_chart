<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';

function render_admin_actions(): void
{
    $currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $links = [
        'admin.php' => 'Overview',
        'admin_groups.php' => 'Groups',
        'admin_new_user.php' => 'New user',
        'admin_passwords.php' => 'Passwords',
        'admin_archive.php' => 'Archive',
        'admin_export.php' => 'Export',
        'admin_update.php' => 'Updates',
    ];
    ?>
    <nav class="admin-actions" aria-label="Admin actions">
        <?php foreach ($links as $href => $label): ?>
            <?php if ($currentPage === $href): ?>
                <span class="admin-action current"><?= e($label) ?></span>
            <?php else: ?>
                <a class="admin-action" href="<?= e(path_to($href)) ?>"><?= e($label) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php
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
            <?php render_admin_actions(); ?>
            <?php render_nav($user); ?>
        </div>
    </section>
    <?php
}
