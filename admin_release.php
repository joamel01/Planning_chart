<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/admin_nav.php';
require_once __DIR__ . '/lib/release_checks.php';

$user = require_admin();
$checks = release_checks();

render_header('Release Checks', $user);
render_admin_header($user, 'Release Checks', 'Public deployment checks and production hardening reminders.');
?>
<section class="panel">
    <h2><?= e(t('Release Status')) ?></h2>
    <p class="muted"><?= e(t('These checks help catch common public deployment risks. They do not change files or configuration.')) ?></p>

    <table class="data-table release-checks">
        <thead>
        <tr>
            <th><?= e(t('Check')) ?></th>
            <th><?= e(t('Status')) ?></th>
            <th><?= e(t('Summary')) ?></th>
            <th><?= e(t('Action')) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($checks as $check): ?>
            <tr>
                <td><?= e($check['label']) ?></td>
                <td>
                    <span class="status-badge <?= e($check['status']) ?>">
                        <?= $check['status'] === 'ok' ? 'OK' : e(t('Warning')) ?>
                    </span>
                </td>
                <td><?= e($check['summary']) ?></td>
                <td><?= e($check['detail']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
