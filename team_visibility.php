<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/group_nav.php';

[$user, $teamId, $team, $canManageGroup] = require_group_context();
if (!$canManageGroup) {
    http_response_code(403);
    exit('You cannot change board visibility.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $memberId = (int) ($_POST['user_id'] ?? 0);
        $visible = (int) ($_POST['visible'] ?? 0) === 1 ? 1 : 0;

        $stmt = db()->prepare(
            "UPDATE planner_users
             SET is_board_visible = ?
             WHERE id = ? AND team_id = ? AND role IN ('group_admin', 'user') AND is_active = 1 AND archived_at IS NULL"
        );
        $stmt->execute([$visible, $memberId, $teamId]);

        if ($stmt->rowCount() === 0) {
            throw new InvalidArgumentException('The user could not be updated.');
        }

        flash('success', $visible ? 'User is visible on the board.' : 'User is hidden from the board.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect_to('team_visibility.php' . ($user['role'] === 'central_admin' ? '?team_id=' . $teamId : ''));
}

$members = team_members($teamId);

render_header('Visibility', $user);
render_group_header($user, $team, $teamId, $canManageGroup, 'Visibility', 'Hide or show users on the board');
?>
<section class="panel">
    <h2>Board Visibility</h2>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Board</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($members as $member): ?>
            <tr>
                <td><?= e($member['name']) ?></td>
                <td><?= e($member['username']) ?></td>
                <td><?= e(role_label($member['role'])) ?></td>
                <td><?= $member['is_board_visible'] ? 'Visible' : 'Hidden' ?></td>
                <td class="actions">
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="team_id" value="<?= $teamId ?>">
                        <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                        <input type="hidden" name="visible" value="<?= $member['is_board_visible'] ? 0 : 1 ?>">
                        <button class="secondary-action" type="submit">
                            <?= $member['is_board_visible'] ? 'Hide from board' : 'Show on board' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
