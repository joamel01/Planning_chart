<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/layout.php';

$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create_team') {
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '') {
                throw new InvalidArgumentException('The group must have a name.');
            }

            $stmt = db()->prepare('INSERT INTO planner_teams (name) VALUES (?)');
            $stmt->execute([$name]);
            flash('success', 'Group created.');
        } elseif ($action === 'delete_team') {
            $teamId = (int) ($_POST['team_id'] ?? 0);
            $stmt = db()->prepare('DELETE FROM planner_teams WHERE id = ?');
            $stmt->execute([$teamId]);
            flash('success', 'Group deleted.');
        } elseif ($action === 'create_user') {
            $role = (string) ($_POST['role'] ?? 'user');
            if (!in_array($role, ['group_admin', 'user'], true)) {
                throw new InvalidArgumentException('Central admin can only create group admins and users here.');
            }

            create_user(
                (int) ($_POST['team_id'] ?? 0),
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['username'] ?? ''),
                (string) ($_POST['password'] ?? ''),
                $role
            );
            flash('success', 'User created.');
        } elseif ($action === 'reset_password') {
            reset_user_password(
                (int) ($_POST['user_id'] ?? 0),
                (string) ($_POST['password'] ?? '')
            );
            flash('success', 'Password reset.');
        } elseif ($action === 'archive_user') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId === (int) $user['id']) {
                throw new InvalidArgumentException('You cannot archive your own account.');
            }

            $pdo = db();
            $pdo->beginTransaction();

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
                 SET is_active = 0, is_board_visible = 0, archived_at = CURRENT_TIMESTAMP, archived_by = ?
                 WHERE id = ?'
            );
            $stmt->execute([(int) $user['id'], $userId]);

            $stmt = $pdo->prepare(
                'UPDATE planner_plan_entries
                 SET archived_at = CURRENT_TIMESTAMP
                 WHERE user_id = ? AND archived_at IS NULL'
            );
            $stmt->execute([$userId]);

            $stmt = $pdo->prepare(
                'INSERT INTO planner_audit_log (actor_user_id, team_id, target_user_id, action, details)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([(int) $user['id'], (int) $target['team_id'], $userId, 'archive_user', 'Archived by central admin']);

            $pdo->commit();
            flash('success', 'User and user data archived.');
        }
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', $exception->getMessage());
    }

    redirect_to('admin.php');
}

$teams = db()->query('SELECT id, name FROM planner_teams ORDER BY name')->fetchAll();
$users = db()->query(
    "SELECT u.id, u.name, u.username, u.role, u.is_active, u.archived_at, t.name AS team_name
     FROM planner_users u
     LEFT JOIN planner_teams t ON t.id = u.team_id
     WHERE u.role IN ('group_admin', 'user')
     ORDER BY t.name, u.archived_at IS NOT NULL, u.sort_order, u.name"
)->fetchAll();

render_header('Central Admin', $user);
?>
<section class="section-head">
    <div>
        <h1>Central Admin</h1>
        <p class="muted">Manage groups, users, archives and password resets.</p>
    </div>
    <?php render_nav($user); ?>
</section>

<div class="grid two">
    <section class="panel">
        <h2>Groups</h2>
        <form method="post" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_team">
            <input name="name" placeholder="New group" required>
            <button type="submit">Create</button>
        </form>

        <table class="data-table">
            <thead><tr><th>Name</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($teams as $team): ?>
                <tr>
                    <td><?= e($team['name']) ?></td>
                    <td class="actions">
                        <form method="post" onsubmit="return confirm('Delete this empty group? Groups with users cannot be deleted.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_team">
                            <input type="hidden" name="team_id" value="<?= (int) $team['id'] ?>">
                            <button class="danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="panel">
        <h2>New User</h2>
        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_user">
            <label>
                Group
                <select name="team_id" required>
                    <option value="">Choose group</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int) $team['id'] ?>"><?= e($team['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Role
                <select name="role" required>
                    <option value="user">User</option>
                    <option value="group_admin">Group admin</option>
                </select>
            </label>
            <label>
                Name
                <input name="name" required>
            </label>
            <label>
                Username
                <input name="username" required>
            </label>
            <label>
                Password
                <input type="password" name="password" minlength="8" required>
            </label>
            <button type="submit">Create user</button>
        </form>
    </section>
</div>

<section class="panel">
    <h2>Users</h2>
    <table class="data-table">
        <thead>
        <tr>
            <th>Name</th><th>Username</th><th>Role</th><th>Group</th><th>Status</th><th>Password</th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $managedUser): ?>
            <tr>
                <td><?= e($managedUser['name']) ?></td>
                <td><?= e($managedUser['username']) ?></td>
                <td><?= e(role_label($managedUser['role'])) ?></td>
                <td><?= e($managedUser['team_name']) ?></td>
                <td><?= $managedUser['archived_at'] ? 'Archived' : 'Active' ?></td>
                <td>
                    <?php if (!$managedUser['archived_at']): ?>
                        <form method="post" class="inline-form compact">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="user_id" value="<?= (int) $managedUser['id'] ?>">
                            <input type="password" name="password" placeholder="New password" minlength="8" required>
                            <button type="submit">Reset</button>
                        </form>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <?php if (!$managedUser['archived_at']): ?>
                        <form method="post" onsubmit="return confirm('Archive this user and their board data?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="archive_user">
                            <input type="hidden" name="user_id" value="<?= (int) $managedUser['id'] ?>">
                            <button class="danger" type="submit">Archive</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
