<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/admin_nav.php';
require_once __DIR__ . '/lib/migrations.php';

$user = require_admin();

ensure_schema_versions_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'baseline') {
            if (!current_schema_can_be_baselined()) {
                throw new InvalidArgumentException('The current database does not look ready to baseline. Run pending migrations instead.');
            }

            $count = baseline_current_migrations((int) $user['id']);
            flash('success', $count === 0 ? 'Migration tracking was already up to date.' : 'Migration tracking initialized.');
        } elseif ($action === 'migrate') {
            $applied = apply_pending_migrations((int) $user['id']);
            flash('success', count($applied) === 0 ? 'No pending migrations.' : 'Applied migrations: ' . implode(', ', $applied));
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect_to('admin_update.php');
}

$files = migration_files();
$applied = applied_migrations();
$pending = pending_migrations();
$canBaseline = count($applied) === 0 && current_schema_can_be_baselined() && count($files) > 0;

render_header('Updates', $user);
render_admin_header($user, 'Updates', 'Track and apply database migrations.');
?>
<section class="panel">
    <h2><?= e(t('Database Migrations')) ?></h2>
    <p class="muted"><?= e(t('Use this page after uploading a new version that contains new SQL files in db/migrations.')) ?></p>

    <?php if ($canBaseline): ?>
        <div class="flash warning">
            This installation appears to already have the current database structure, but migration tracking has not been initialized yet.
        </div>
        <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="baseline">
            <button type="submit"><?= e(t('button.initialize_migrations')) ?></button>
        </form>
    <?php else: ?>
        <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="migrate">
            <button type="submit" <?= count($pending) === 0 ? 'disabled' : '' ?>><?= e(t('button.apply_migrations')) ?></button>
        </form>
    <?php endif; ?>

    <table class="data-table">
        <thead><tr><th><?= e(t('Migration')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Applied at')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($files as $migration => $file): ?>
            <tr>
                <td><?= e($migration) ?></td>
                <td><?= array_key_exists($migration, $applied) ? e(t('Applied')) : e(t('Pending')) ?></td>
                <td><?= e($applied[$migration] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
