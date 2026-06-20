<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/group_nav.php';

[$user, $teamId, $team, $canManageGroup] = require_group_context();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
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
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect_to('team_password.php' . ($user['role'] === 'central_admin' ? '?team_id=' . $teamId : ''));
}

render_header('Change Password', $user);
render_group_header($user, $team, $teamId, $canManageGroup, 'Change Password', 'Update your own password');
?>
<section class="panel narrow-panel">
    <h2>Password</h2>
    <form method="post" class="form-stack password-form">
        <?= csrf_field() ?>
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
<?php render_footer(); ?>
