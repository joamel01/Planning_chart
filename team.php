<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/layout.php';
require_once __DIR__ . '/lib/team_context.php';

$user = require_login();
$teamId = selected_team_id($user);

if ($teamId <= 0) {
    render_header('People', $user);
    ?>
    <section class="empty-state">
        <h1>No group exists yet</h1>
        <p>Create a group in Central Admin first.</p>
    </section>
    <?php
    render_footer();
    exit;
}

if (!can_access_team($user, $teamId)) {
    http_response_code(403);
    exit('You cannot access this group.');
}

$team = team_or_404($teamId);
$canManageGroup = can_manage_group($user, $teamId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create_user') {
            if (!$canManageGroup) {
                throw new RuntimeException('You cannot create users.');
            }

            create_user(
                $teamId,
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['username'] ?? ''),
                (string) ($_POST['password'] ?? ''),
                'user'
            );
            flash('success', 'User added.');
        } elseif ($action === 'update_sort') {
            if (!$canManageGroup) {
                throw new RuntimeException('You cannot sort users.');
            }

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
        } elseif ($action === 'change_password') {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            if (strlen($newPassword) < 8) {
                throw new InvalidArgumentException('The new password must be at least 8 characters.');
            }

            if ($newPassword !== $confirmPassword) {
                throw new InvalidArgumentException('The new password and confirmation do not match.');
            }

            $stmt = db()->prepare('SELECT password_hash FROM planner_users WHERE id = ? AND is_active = 1 AND archived_at IS NULL');
            $stmt->execute([(int) $user['id']]);
            $passwordRow = $stmt->fetch();

            if (!$passwordRow || !password_verify($currentPassword, $passwordRow['password_hash'])) {
                throw new InvalidArgumentException('The current password is incorrect.');
            }

            reset_user_password((int) $user['id'], $newPassword);
            flash('success', 'Password changed.');
        } elseif ($action === 'toggle_board_visibility') {
            if (!$canManageGroup) {
                throw new RuntimeException('You cannot change board visibility.');
            }

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
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect_to('team.php?team_id=' . $teamId);
}

$members = team_members($teamId);
$teams = $user['role'] === 'central_admin' ? all_teams() : [];

render_header('People', $user);
?>
<section class="section-head">
    <div>
        <h1>People</h1>
        <p class="muted"><?= e($team['name']) ?></p>
    </div>
    <div class="header-actions">
        <?php if ($user['role'] === 'central_admin' && count($teams) > 1): ?>
            <form method="get" class="team-picker">
                <select name="team_id" onchange="this.form.submit()">
                    <?php foreach ($teams as $teamOption): ?>
                        <option value="<?= (int) $teamOption['id'] ?>" <?= (int) $teamOption['id'] === $teamId ? 'selected' : '' ?>>
                            <?= e($teamOption['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <?php render_nav($user); ?>
    </div>
</section>

<div class="grid two">
    <?php if ($canManageGroup): ?>
        <section class="panel">
            <h2>Add User</h2>
            <form method="post" class="form-stack">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_user">
                <input type="hidden" name="team_id" value="<?= $teamId ?>">
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
                <button type="submit">Add user</button>
            </form>
        </section>

        <section class="panel">
            <h2>Sort Board Rows</h2>
            <form method="post" class="form-stack">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_sort">
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
    <?php endif; ?>

    <section class="panel">
        <h2>Change Password</h2>
        <form method="post" class="form-stack password-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="team_id" value="<?= $teamId ?>">
            <label>
                Current password
                <input type="password" name="current_password" autocomplete="current-password" required>
            </label>
            <label>
                New password
                <input type="password" name="new_password" autocomplete="new-password" minlength="8" required>
            </label>
            <label>
                Repeat new password
                <input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required>
            </label>
            <button type="submit">Change password</button>
        </form>
    </section>
</div>

<section class="panel">
    <h2>Active Users</h2>
    <table class="data-table">
        <thead><tr><th>Order</th><th>Name</th><th>Username</th><th>Role</th><th>Board</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($members as $member): ?>
            <tr>
                <td><?= (int) $member['sort_order'] ?></td>
                <td><?= e($member['name']) ?></td>
                <td><?= e($member['username']) ?></td>
                <td><?= e(role_label($member['role'])) ?></td>
                <td><?= $member['is_board_visible'] ? 'Visible' : 'Hidden' ?></td>
                <td class="actions">
                    <?php if ($canManageGroup): ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle_board_visibility">
                            <input type="hidden" name="team_id" value="<?= $teamId ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                            <input type="hidden" name="visible" value="<?= $member['is_board_visible'] ? 0 : 1 ?>">
                            <button class="secondary-action" type="submit">
                                <?= $member['is_board_visible'] ? 'Hide from board' : 'Show on board' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
