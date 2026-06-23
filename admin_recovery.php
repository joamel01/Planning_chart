<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/layout.php';

$recoveryKey = planner_config('PLANNER_RECOVERY_KEY', 'PLANNER_RECOVERY_KEY', PLANNER_RECOVERY_KEY);

if ($recoveryKey === '') {
    http_response_code(403);
    exit('Set PLANNER_RECOVERY_KEY in config.php first. Remove admin_recovery.php after recovery.');
}

$key = (string) ($_GET['key'] ?? $_POST['key'] ?? '');
if (!hash_equals($recoveryKey, $key)) {
    http_response_code(403);
    exit('Wrong recovery key.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $userId = (int) ($_POST['user_id'] ?? 0);
    $password = (string) ($_POST['password'] ?? '');

    if (strlen($password) < 10) {
        flash('error', 'The password must be at least 10 characters.');
    } else {
        $stmt = db()->prepare(
            "UPDATE planner_users
             SET password_hash = ?, is_active = 1, archived_at = NULL, archived_by = NULL
             WHERE id = ? AND role = 'central_admin'"
        );
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
        flash('success', 'Central admin password reset. Log in, then remove admin_recovery.php and clear PLANNER_RECOVERY_KEY.');
    }
}

$admins = db()->query(
    "SELECT id, name, username, is_active, created_at
     FROM planner_users
     WHERE role = 'central_admin'
     ORDER BY id"
)->fetchAll();

render_header('Recover central admin', null, 'auth-page');
?>
<section class="auth-card">
    <h1>Recover central admin</h1>
    <?php if (!$admins): ?>
        <p class="muted">There is no central admin in the database.</p>
    <?php else: ?>
        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <input type="hidden" name="key" value="<?= e($key) ?>">
            <label>
                Central admin
                <select name="user_id" required>
                    <?php foreach ($admins as $admin): ?>
                        <option value="<?= (int) $admin['id'] ?>">
                            <?= e($admin['username'] . ' - ' . $admin['name'] . ($admin['is_active'] ? '' : ' (inactive)')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                New password
                <input type="password" name="password" autocomplete="new-password" minlength="10" required>
            </label>
            <button type="submit">Set new password</button>
        </form>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
