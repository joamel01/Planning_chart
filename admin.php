<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/admin_nav.php';

$user = require_admin();

$stats = db()->query(
    "SELECT
        (SELECT COUNT(*) FROM planner_teams) AS groups_count,
        (SELECT COUNT(*) FROM planner_users WHERE role IN ('group_admin', 'user') AND is_active = 1 AND archived_at IS NULL) AS active_users_count,
        (SELECT COUNT(*) FROM planner_users WHERE role = 'group_admin' AND is_active = 1 AND archived_at IS NULL) AS group_admins_count,
        (SELECT COUNT(*) FROM planner_users WHERE role IN ('group_admin', 'user') AND is_active = 1 AND archived_at IS NULL AND is_board_visible = 1) AS board_visible_count,
        (SELECT COUNT(*) FROM planner_users WHERE role IN ('group_admin', 'user') AND is_active = 1 AND archived_at IS NULL AND is_board_visible = 0) AS hidden_users_count,
        (SELECT COUNT(*) FROM planner_users WHERE role IN ('group_admin', 'user') AND archived_at IS NOT NULL) AS archived_users_count"
)->fetch();

$users = db()->query(
    "SELECT u.id, u.name, u.username, u.role, u.is_board_visible, t.name AS team_name
     FROM planner_users u
     LEFT JOIN planner_teams t ON t.id = u.team_id
     WHERE u.role IN ('group_admin', 'user') AND u.is_active = 1 AND u.archived_at IS NULL
     ORDER BY t.name, u.sort_order, u.name"
)->fetchAll();

render_header('Central Admin', $user);
render_admin_header($user, 'Central Admin', 'Overview, high-level counts and active users.');
?>
<section class="stats-grid">
    <article class="stat-card">
        <span>Groups</span>
        <strong><?= (int) $stats['groups_count'] ?></strong>
    </article>
    <article class="stat-card">
        <span>Active users</span>
        <strong><?= (int) $stats['active_users_count'] ?></strong>
    </article>
    <article class="stat-card">
        <span>Group admins</span>
        <strong><?= (int) $stats['group_admins_count'] ?></strong>
    </article>
    <article class="stat-card">
        <span>Visible on board</span>
        <strong><?= (int) $stats['board_visible_count'] ?></strong>
    </article>
    <article class="stat-card">
        <span>Hidden from board</span>
        <strong><?= (int) $stats['hidden_users_count'] ?></strong>
    </article>
    <article class="stat-card">
        <span>Archived users</span>
        <strong><?= (int) $stats['archived_users_count'] ?></strong>
    </article>
</section>

<section class="panel">
    <h2>Active Users</h2>
    <table class="data-table">
        <thead>
        <tr>
            <th>Name</th><th>Username</th><th>Role</th><th>Group</th><th>Board</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $managedUser): ?>
            <tr>
                <td><?= e($managedUser['name']) ?></td>
                <td><?= e($managedUser['username']) ?></td>
                <td><?= e(role_label($managedUser['role'])) ?></td>
                <td><?= e($managedUser['team_name']) ?></td>
                <td><?= $managedUser['is_board_visible'] ? 'Visible' : 'Hidden' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
