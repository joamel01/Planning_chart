<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/layout.php';

$setupError = null;

try {
    $hasAdmin = admin_exists();
} catch (Throwable $exception) {
    $hasAdmin = false;
    $setupError = 'The database cannot be reached or the tables are missing. Check config.php and import db/schema.sql. Technical detail: ' . $exception->getMessage();
}

if ($hasAdmin) {
    http_response_code(403);
    exit('A central admin already exists. Remove this file after installation.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || $username === '' || strlen($password) < 10) {
        flash('error', 'Enter name, username and a password of at least 10 characters.');
    } elseif ($setupError !== null) {
        flash('error', $setupError);
    } else {
        try {
            create_user(null, $name, $username, $password, 'central_admin');
            flash('success', 'Central admin created. Log in and then remove setup_admin.php.');
            redirect_to('login.php');
        } catch (Throwable $exception) {
            flash('error', 'Central admin could not be created: ' . $exception->getMessage());
        }
    }
}

render_header('Create central admin', null, 'auth-page');
?>
<section class="auth-card">
    <h1>Create first central admin</h1>
    <?php if ($setupError !== null): ?>
        <div class="flash error"><?= e($setupError) ?></div>
        <p class="muted">Check database name, username and password in <code>config.php</code>.</p>
    <?php endif; ?>
    <form method="post" class="form-stack">
        <?= csrf_field() ?>
        <label>
            Name
            <input name="name" required>
        </label>
        <label>
            Username
            <input name="username" autocomplete="username" required>
        </label>
        <label>
            Password
            <input type="password" name="password" autocomplete="new-password" minlength="10" required>
        </label>
        <button type="submit">Create central admin</button>
    </form>
</section>
<?php render_footer(); ?>
