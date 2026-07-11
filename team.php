<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/group_nav.php';

[$user, $teamId, $team, $canManageGroup] = require_group_context();

$members = team_members($teamId);
$stats = db()->prepare(
    "SELECT
        COUNT(*) AS active_users_count,
        SUM(CASE WHEN role = 'group_admin' THEN 1 ELSE 0 END) AS group_admins_count,
        SUM(CASE WHEN is_board_visible = 1 THEN 1 ELSE 0 END) AS board_visible_count,
        SUM(CASE WHEN is_board_visible = 0 THEN 1 ELSE 0 END) AS hidden_users_count
     FROM planner_users
     WHERE team_id = ? AND role IN ('group_admin', 'user') AND is_active = 1 AND archived_at IS NULL"
);
$stats->execute([$teamId]);
$stats = $stats->fetch();

render_header('People', $user);
render_group_header($user, $team, $teamId, $canManageGroup, 'People', 'Group overview and active users');
?>
<section class="stats-grid group-stats">
    <article class="stat-card">
        <span><?= e(t('stats.active_users')) ?></span>
        <strong><?= (int) $stats['active_users_count'] ?></strong>
    </article>
    <article class="stat-card">
        <span><?= e(t('stats.group_admins')) ?></span>
        <strong><?= (int) $stats['group_admins_count'] ?></strong>
    </article>
    <article class="stat-card">
        <span><?= e(t('stats.visible_on_board')) ?></span>
        <strong><?= (int) $stats['board_visible_count'] ?></strong>
    </article>
    <article class="stat-card">
        <span><?= e(t('stats.hidden_from_board')) ?></span>
        <strong><?= (int) $stats['hidden_users_count'] ?></strong>
    </article>
</section>

<section class="panel">
    <h2><?= e(t('Active Users')) ?></h2>
    <table class="data-table">
        <thead><tr><th><?= e(t('Order')) ?></th><th><?= e(t('Name')) ?></th><th><?= e(t('Username')) ?></th><th><?= e(t('Role')) ?></th><th><?= e(t('Board')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($members as $member): ?>
            <tr>
                <td><?= (int) $member['sort_order'] ?></td>
                <td><?= e($member['name']) ?></td>
                <td><?= e($member['username']) ?></td>
                <td><?= e(role_label($member['role'])) ?></td>
                <td><?= e($member['is_board_visible'] ? t('Visible') : t('Hidden')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
