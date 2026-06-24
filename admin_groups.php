<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/admin_nav.php';

$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create_team') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $weekLength = normalize_week_length((int) ($_POST['week_length'] ?? 5));
            if ($name === '') {
                throw new InvalidArgumentException('The group must have a name.');
            }

            $stmt = db()->prepare('INSERT INTO planner_teams (name, week_length) VALUES (?, ?)');
            $stmt->execute([$name, $weekLength]);
            flash('success', 'Group created.');
        } elseif ($action === 'update_week_length') {
            $teamId = (int) ($_POST['team_id'] ?? 0);
            $weekLength = normalize_week_length((int) ($_POST['week_length'] ?? 5));
            $stmt = db()->prepare('UPDATE planner_teams SET week_length = ? WHERE id = ? AND archived_at IS NULL');
            $stmt->execute([$weekLength, $teamId]);
            flash('success', 'Week setting updated.');
        } elseif ($action === 'archive_team') {
            $teamId = (int) ($_POST['team_id'] ?? 0);
            $pdo = db();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT id FROM planner_teams WHERE id = ? AND archived_at IS NULL FOR UPDATE');
            $stmt->execute([$teamId]);
            if (!$stmt->fetch()) {
                throw new InvalidArgumentException('The active group could not be found.');
            }

            $stmt = $pdo->prepare('UPDATE planner_teams SET archived_at = CURRENT_TIMESTAMP, archived_by = ? WHERE id = ?');
            $stmt->execute([(int) $user['id'], $teamId]);

            $stmt = $pdo->prepare(
                'UPDATE planner_users
                 SET is_active = 0, is_board_visible = 0, archived_at = COALESCE(archived_at, CURRENT_TIMESTAMP), archived_by = COALESCE(archived_by, ?)
                 WHERE team_id = ? AND role IN (\'group_admin\', \'user\')'
            );
            $stmt->execute([(int) $user['id'], $teamId]);

            $stmt = $pdo->prepare('UPDATE planner_plan_entries SET archived_at = COALESCE(archived_at, CURRENT_TIMESTAMP) WHERE team_id = ?');
            $stmt->execute([$teamId]);

            $stmt = $pdo->prepare(
                'INSERT INTO planner_audit_log (actor_user_id, team_id, action, details)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([(int) $user['id'], $teamId, 'archive_team', 'Archived group and group data']);

            $pdo->commit();
            flash('success', 'Group, users, and board data archived.');
        } elseif ($action === 'restore_team') {
            $teamId = (int) ($_POST['team_id'] ?? 0);
            $pdo = db();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT id FROM planner_teams WHERE id = ? AND archived_at IS NOT NULL FOR UPDATE');
            $stmt->execute([$teamId]);
            if (!$stmt->fetch()) {
                throw new InvalidArgumentException('The archived group could not be found.');
            }

            $stmt = $pdo->prepare('UPDATE planner_teams SET archived_at = NULL, archived_by = NULL WHERE id = ?');
            $stmt->execute([$teamId]);

            $stmt = $pdo->prepare(
                'UPDATE planner_users
                 SET is_active = 1, archived_at = NULL, archived_by = NULL
                 WHERE team_id = ? AND role IN (\'group_admin\', \'user\')'
            );
            $stmt->execute([$teamId]);

            $stmt = $pdo->prepare('UPDATE planner_plan_entries SET archived_at = NULL WHERE team_id = ?');
            $stmt->execute([$teamId]);

            $stmt = $pdo->prepare(
                'INSERT INTO planner_audit_log (actor_user_id, team_id, action, details)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([(int) $user['id'], $teamId, 'restore_team', 'Restored group and group data']);

            $pdo->commit();
            flash('success', 'Group, users, and board data restored.');
        }
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', $exception->getMessage());
    }

    redirect_to('admin_groups.php');
}

$teams = db()->query(
    "SELECT t.id, t.name, t.week_length,
        COUNT(u.id) AS users_count
     FROM planner_teams t
     LEFT JOIN planner_users u ON u.team_id = t.id AND u.archived_at IS NULL
     WHERE t.archived_at IS NULL
     GROUP BY t.id, t.name, t.week_length
     ORDER BY t.name"
)->fetchAll();

$archivedTeams = db()->query(
    "SELECT t.id, t.name, t.week_length, t.archived_at,
        COUNT(u.id) AS users_count
     FROM planner_teams t
     LEFT JOIN planner_users u ON u.team_id = t.id
     WHERE t.archived_at IS NOT NULL
     GROUP BY t.id, t.name, t.week_length, t.archived_at
     ORDER BY t.archived_at DESC, t.name"
)->fetchAll();

render_header('Groups', $user);
render_admin_header($user, 'Groups', 'Create, archive, and restore groups.');
?>
<section class="panel">
    <h2>Create Group</h2>
    <form method="post" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_team">
        <input name="name" placeholder="New group" required>
        <select name="week_length" aria-label="Week length">
            <option value="5">5 day week</option>
            <option value="7">7 day week</option>
        </select>
        <button type="submit">Create</button>
    </form>

    <table class="data-table">
        <thead><tr><th>Name</th><th>Week</th><th>Active users</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($teams as $team): ?>
            <tr>
                <td><?= e($team['name']) ?></td>
                <td>
                    <form method="post" class="inline-form compact">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_week_length">
                        <input type="hidden" name="team_id" value="<?= (int) $team['id'] ?>">
                        <select name="week_length" aria-label="Week length for <?= e($team['name']) ?>">
                            <option value="5" <?= (int) $team['week_length'] === 5 ? 'selected' : '' ?>>5 days</option>
                            <option value="7" <?= (int) $team['week_length'] === 7 ? 'selected' : '' ?>>7 days</option>
                        </select>
                        <button type="submit">Save</button>
                    </form>
                </td>
                <td><?= (int) $team['users_count'] ?></td>
                <td class="actions">
                    <form method="post" onsubmit="return confirm('Archive this group, its users, and its board data?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="archive_team">
                        <input type="hidden" name="team_id" value="<?= (int) $team['id'] ?>">
                        <button class="danger" type="submit">Archive</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <h2>Archived Groups</h2>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Week</th><th>Users</th><th>Archived at</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($archivedTeams as $team): ?>
            <tr>
                <td><?= e($team['name']) ?></td>
                <td><?= (int) $team['week_length'] ?> days</td>
                <td><?= (int) $team['users_count'] ?></td>
                <td><?= e($team['archived_at']) ?></td>
                <td class="actions">
                    <form method="post" onsubmit="return confirm('Restore this group, its users, and its board data?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="restore_team">
                        <input type="hidden" name="team_id" value="<?= (int) $team['id'] ?>">
                        <button type="submit">Restore</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
