<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/layout.php';

if (current_user()) {
    redirect_to('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    try {
        if (login_user($username, $password)) {
            redirect_to('index.php');
        }

        flash('error', 'Wrong username or password.');
    } catch (Throwable $exception) {
        flash('error', 'Login could not connect to the database. Check the database settings.');
    }
}

render_header('Log in', null, 'auth-page');
?>
<section class="auth-card">
    <h1>Log in</h1>
    <form method="post" class="form-stack">
        <?= csrf_field() ?>
        <label>
            Username
            <input name="username" autocomplete="username" required>
        </label>
        <label>
            Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button type="submit">Log in</button>
    </form>
    <p class="muted">Create the first central admin with <code>setup_admin.php</code> after importing the database.</p>
</section>
<?php render_footer(); ?>
