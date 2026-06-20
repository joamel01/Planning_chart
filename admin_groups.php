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
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect_to('admin_groups.php');
}

$teams = db()->query(
    "SELECT t.id, t.name,
        COUNT(u.id) AS users_count
     FROM planner_teams t
     LEFT JOIN planner_users u ON u.team_id = t.id AND u.archived_at IS NULL
     GROUP BY t.id, t.name
     ORDER BY t.name"
)->fetchAll();

render_header('Groups', $user);
render_admin_header($user, 'Groups', 'Create groups and delete empty groups.');
?>
<section class="panel">
    <h2>Create Group</h2>
    <form method="post" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_team">
        <input name="name" placeholder="New group" required>
        <button type="submit">Create</button>
    </form>

    <table class="data-table">
        <thead><tr><th>Name</th><th>Active users</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($teams as $team): ?>
            <tr>
                <td><?= e($team['name']) ?></td>
                <td><?= (int) $team['users_count'] ?></td>
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
<?php render_footer(); ?>
