<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/group_nav.php';

[$user, $teamId, $team, $canManageGroup] = require_group_context();
if (!$canManageGroup) {
    http_response_code(403);
    exit('You cannot sort users.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $orders = $_POST['sort_order'] ?? [];
        if (!is_array($orders)) {
            throw new InvalidArgumentException('The sort order could not be read.');
        }

        $stmt = db()->prepare(
            "UPDATE planner_users
             SET sort_order = ?
             WHERE id = ? AND team_id = ? AND role IN ('group_admin', 'user') AND archived_at IS NULL"
        );
        foreach ($orders as $memberId => $sortOrder) {
            $stmt->execute([(int) $sortOrder, (int) $memberId, $teamId]);
        }
        flash('success', 'Sort order saved.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect_to('team_sort.php' . ($user['role'] === 'central_admin' ? '?team_id=' . $teamId : ''));
}

$members = team_members($teamId);

render_header('Sort Rows', $user);
render_group_header($user, $team, $teamId, $canManageGroup, 'Sort Rows', 'Set the board row order');
?>
<section class="panel narrow-panel">
    <h2>Board Row Order</h2>
    <form method="post" class="form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="team_id" value="<?= $teamId ?>">
        <div class="sort-list">
            <?php foreach ($members as $member): ?>
                <label class="sort-row">
                    <span><?= e($member['name']) ?></span>
                    <input type="number" name="sort_order[<?= (int) $member['id'] ?>]" value="<?= (int) $member['sort_order'] ?>" step="10">
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit">Save order</button>
    </form>
</section>
<?php render_footer(); ?>
