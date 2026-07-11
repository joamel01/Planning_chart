<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/admin_nav.php';

$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        reset_user_password(
            (int) ($_POST['user_id'] ?? 0),
            (string) ($_POST['password'] ?? '')
        );
        flash('success', 'Password reset.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect_to('admin_passwords.php');
}

$users = db()->query(
    "SELECT u.id, u.name, u.username, u.role, t.name AS team_name
     FROM planner_users u
     LEFT JOIN planner_teams t ON t.id = u.team_id
     WHERE u.role IN ('group_admin', 'user') AND u.is_active = 1 AND u.archived_at IS NULL
     ORDER BY t.name, u.sort_order, u.name"
)->fetchAll();

render_header('Passwords', $user);
render_admin_header($user, 'Passwords', 'Reset passwords for active group users.');
?>
<section class="panel">
    <h2>Password Reset</h2>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Group</th><th>New password</th></tr></thead>
        <tbody>
        <?php foreach ($users as $managedUser): ?>
            <tr>
                <td><?= e($managedUser['name']) ?></td>
                <td><?= e($managedUser['username']) ?></td>
                <td><?= e(role_label($managedUser['role'])) ?></td>
                <td><?= e($managedUser['team_name']) ?></td>
                <td>
                    <form method="post" class="inline-form compact">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $managedUser['id'] ?>">
                        <input type="password" name="password" placeholder="New password" minlength="8" required>
                        <button type="submit"><?= e(t('button.reset')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php render_footer(); ?>
