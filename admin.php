<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/admin_nav.php';

$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'update_role') {
            $targetUserId = (int) ($_POST['user_id'] ?? 0);
            $role = (string) ($_POST['role'] ?? '');

            if (!in_array($role, ['group_admin', 'user'], true)) {
                throw new InvalidArgumentException('Choose User or Group admin.');
            }

            $pdo = db();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT id, team_id, role
                 FROM planner_users
                 WHERE id = ? AND role IN ('group_admin', 'user') AND is_active = 1 AND archived_at IS NULL
                 FOR UPDATE"
            );
            $stmt->execute([$targetUserId]);
            $target = $stmt->fetch();
            if (!$target) {
                throw new InvalidArgumentException('The user could not be found.');
            }

            if ((string) $target['role'] !== $role) {
                $stmt = $pdo->prepare('UPDATE planner_users SET role = ? WHERE id = ?');
                $stmt->execute([$role, $targetUserId]);

                $stmt = $pdo->prepare(
                    'INSERT INTO planner_audit_log (actor_user_id, team_id, target_user_id, action, details)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    (int) $user['id'],
                    (int) $target['team_id'],
                    $targetUserId,
                    'update_user_role',
                    'Changed role from ' . $target['role'] . ' to ' . $role,
                ]);
            }

            $pdo->commit();
            flash('success', 'User role updated.');
        }
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', $exception->getMessage());
    }

    redirect_to('admin.php');
}

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
                <td>
                    <form method="post" class="inline-form compact">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="user_id" value="<?= (int) $managedUser['id'] ?>">
                        <select name="role" aria-label="Role for <?= e($managedUser['name']) ?>">
                            <option value="user" <?= $managedUser['role'] === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="group_admin" <?= $managedUser['role'] === 'group_admin' ? 'selected' : '' ?>>Group admin</option>
                        </select>
                        <button type="submit">Save</button>
                    </form>
                </td>
                <td><?= e($managedUser['team_name']) ?></td>
                <td><?= $managedUser['is_board_visible'] ? 'Visible' : 'Hidden' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
