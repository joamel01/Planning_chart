<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/admin_nav.php';

$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
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
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect_to('admin_new_user.php');
}

$teams = db()->query('SELECT id, name FROM planner_teams WHERE archived_at IS NULL ORDER BY name')->fetchAll();

render_header('New User', $user);
render_admin_header($user, 'New User', 'Create group admins and users.');
?>
<section class="panel narrow-panel">
    <h2><?= e(t('User Details')) ?></h2>
    <form method="post" class="form-stack">
        <?= csrf_field() ?>
        <label>
            <?= e(t('Group')) ?>
            <select name="team_id" required>
                <option value=""><?= e(t('Choose group')) ?></option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?= (int) $team['id'] ?>"><?= e($team['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <?= e(t('Role')) ?>
            <select name="role" required>
                <option value="user"><?= e(t('User')) ?></option>
                <option value="group_admin"><?= e(t('Group admin')) ?></option>
            </select>
        </label>
        <label>
            <?= e(t('Name')) ?>
            <input name="name" required>
        </label>
        <label>
            <?= e(t('Username')) ?>
            <input name="username" required>
        </label>
        <label>
            <?= e(t('Password')) ?>
            <input type="password" name="password" minlength="8" required>
        </label>
        <button type="submit"><?= e(t('button.create_user')) ?></button>
    </form>
</section>
<?php render_footer(); ?>
