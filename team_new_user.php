<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/group_nav.php';

[$user, $teamId, $team, $canManageGroup] = require_group_context();
if (!$canManageGroup) {
    http_response_code(403);
    exit('You cannot create users.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        create_user(
            $teamId,
            (string) ($_POST['name'] ?? ''),
            (string) ($_POST['username'] ?? ''),
            (string) ($_POST['password'] ?? ''),
            'user'
        );
        flash('success', 'User added.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect_to('team_new_user.php' . ($user['role'] === 'central_admin' ? '?team_id=' . $teamId : ''));
}

render_header('Add User', $user);
render_group_header($user, $team, $teamId, $canManageGroup, 'Add User', 'Create users in this group');
?>
<section class="panel narrow-panel">
    <h2>User Details</h2>
    <form method="post" class="form-stack">
        <?= csrf_field() ?>
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
<?php render_footer(); ?>
