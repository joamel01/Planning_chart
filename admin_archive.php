<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/admin_nav.php';

$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'archive_user');
    $userId = (int) ($_POST['user_id'] ?? 0);

    try {
        if ($userId === (int) $user['id']) {
            throw new InvalidArgumentException('You cannot change your own account here.');
        }

        $pdo = db();
        $pdo->beginTransaction();

        if ($action === 'archive_user') {
            $stmt = $pdo->prepare(
                "SELECT id, team_id
                 FROM planner_users
                 WHERE id = ? AND role IN ('group_admin', 'user') AND archived_at IS NULL
                 FOR UPDATE"
            );
            $stmt->execute([$userId]);
            $target = $stmt->fetch();
            if (!$target) {
                throw new InvalidArgumentException('The user could not be found.');
            }

            $stmt = $pdo->prepare(
                'UPDATE planner_users
                 SET is_active = 0, archived_at = CURRENT_TIMESTAMP, archived_by = ?
                 WHERE id = ?'
            );
            $stmt->execute([(int) $user['id'], $userId]);

            $stmt = $pdo->prepare(
                'UPDATE planner_plan_entries
                 SET archived_at = CURRENT_TIMESTAMP
                 WHERE user_id = ? AND archived_at IS NULL'
            );
            $stmt->execute([$userId]);

            revoke_user_remember_tokens($userId);

            $stmt = $pdo->prepare(
                'INSERT INTO planner_audit_log (actor_user_id, team_id, target_user_id, action, details)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([(int) $user['id'], (int) $target['team_id'], $userId, 'archive_user', 'Archived by central admin']);

            flash('success', 'User and user data archived.');
        } elseif ($action === 'restore_user') {
            $stmt = $pdo->prepare(
                "SELECT id, team_id
                 FROM planner_users
                 WHERE id = ? AND role IN ('group_admin', 'user') AND archived_at IS NOT NULL
                 FOR UPDATE"
            );
            $stmt->execute([$userId]);
            $target = $stmt->fetch();
            if (!$target) {
                throw new InvalidArgumentException('The archived user could not be found.');
            }

            $stmt = $pdo->prepare(
                'UPDATE planner_users
                 SET is_active = 1, archived_at = NULL, archived_by = NULL
                 WHERE id = ?'
            );
            $stmt->execute([$userId]);

            $stmt = $pdo->prepare('UPDATE planner_plan_entries SET archived_at = NULL WHERE user_id = ?');
            $stmt->execute([$userId]);

            $stmt = $pdo->prepare(
                'INSERT INTO planner_audit_log (actor_user_id, team_id, target_user_id, action, details)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([(int) $user['id'], (int) $target['team_id'], $userId, 'restore_user', 'Restored by central admin']);

            flash('success', 'User and user data restored.');
        } elseif ($action === 'delete_user') {
            $stmt = $pdo->prepare(
                "SELECT id, team_id, name, username
                 FROM planner_users
                 WHERE id = ? AND role IN ('group_admin', 'user') AND archived_at IS NOT NULL
                 FOR UPDATE"
            );
            $stmt->execute([$userId]);
            $target = $stmt->fetch();
            if (!$target) {
                throw new InvalidArgumentException('Only archived users can be permanently deleted.');
            }

            $stmt = $pdo->prepare('DELETE FROM planner_plan_entries WHERE user_id = ?');
            $stmt->execute([$userId]);

            $stmt = $pdo->prepare('DELETE FROM planner_users WHERE id = ?');
            $stmt->execute([$userId]);

            $stmt = $pdo->prepare(
                'INSERT INTO planner_audit_log (actor_user_id, team_id, target_user_id, action, details)
                 VALUES (?, ?, NULL, ?, ?)'
            );
            $stmt->execute([
                (int) $user['id'],
                (int) $target['team_id'],
                'delete_user',
                'Permanently deleted archived user ' . $target['username'] . ' (' . $target['name'] . ')',
            ]);

            flash('success', 'Archived user and all user data permanently deleted.');
        } else {
            throw new InvalidArgumentException('Unknown archive action.');
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', $exception->getMessage());
    }

    redirect_to('admin_archive.php');
}

$activeUsers = db()->query(
    "SELECT u.id, u.name, u.username, u.role, t.name AS team_name
     FROM planner_users u
     LEFT JOIN planner_teams t ON t.id = u.team_id
     WHERE u.role IN ('group_admin', 'user') AND u.archived_at IS NULL
     ORDER BY t.name, u.sort_order, u.name"
)->fetchAll();

$archivedUsers = db()->query(
    "SELECT u.id, u.name, u.username, u.role, u.archived_at, t.name AS team_name
     FROM planner_users u
     LEFT JOIN planner_teams t ON t.id = u.team_id
     WHERE u.role IN ('group_admin', 'user') AND u.archived_at IS NOT NULL
     ORDER BY u.archived_at DESC, u.name"
)->fetchAll();

render_header('Archive', $user);
render_admin_header($user, 'Archive', 'Archive, restore, or permanently delete users and their board data.');
?>
<section class="panel">
    <h2><?= e(t('Archive Active User')) ?></h2>
    <table class="data-table">
        <thead><tr><th><?= e(t('Name')) ?></th><th><?= e(t('Username')) ?></th><th><?= e(t('Role')) ?></th><th><?= e(t('Group')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($activeUsers as $managedUser): ?>
            <tr>
                <td><?= e($managedUser['name']) ?></td>
                <td><?= e($managedUser['username']) ?></td>
                <td><?= e(role_label($managedUser['role'])) ?></td>
                <td><?= e($managedUser['team_name']) ?></td>
                <td class="actions">
                    <form method="post" onsubmit="return confirm('<?= e(t('Archive this user and their board data?')) ?>');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="archive_user">
                        <input type="hidden" name="user_id" value="<?= (int) $managedUser['id'] ?>">
                        <button class="danger" type="submit"><?= e(t('button.archive')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <h2><?= e(t('Archived Users')) ?></h2>
    <table class="data-table">
        <thead><tr><th><?= e(t('Name')) ?></th><th><?= e(t('Username')) ?></th><th><?= e(t('Role')) ?></th><th><?= e(t('Group')) ?></th><th><?= e(t('Archived at')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($archivedUsers as $managedUser): ?>
            <tr>
                <td><?= e($managedUser['name']) ?></td>
                <td><?= e($managedUser['username']) ?></td>
                <td><?= e(role_label($managedUser['role'])) ?></td>
                <td><?= e($managedUser['team_name']) ?></td>
                <td><?= e($managedUser['archived_at']) ?></td>
                <td class="actions">
                    <form method="post" onsubmit="return confirm('<?= e(t('Restore this user and their board data?')) ?>');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="restore_user">
                        <input type="hidden" name="user_id" value="<?= (int) $managedUser['id'] ?>">
                        <button type="submit"><?= e(t('button.restore')) ?></button>
                    </form>
                    <form method="post" onsubmit="return confirm('<?= e(t('Permanently delete this archived user and all user data? This cannot be undone.')) ?>');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= (int) $managedUser['id'] ?>">
                        <button class="danger" type="submit"><?= e(t('button.delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
