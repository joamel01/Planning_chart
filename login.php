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
    $remember = isset($_POST['remember_me']) && (string) $_POST['remember_me'] === '1';

    try {
        if (login_user($username, $password, $remember)) {
            redirect_to('index.php');
        }

        flash('error', 'Wrong username or password.');
    } catch (Throwable $exception) {
        flash('error', 'Login could not connect to the database. Check the database settings.');
    }
}

render_header('login.title', null, 'auth-page');
?>
<section class="auth-card">
    <h1><?= e(t('login.title')) ?></h1>
    <form method="post" class="form-stack">
        <?= csrf_field() ?>
        <label>
            <?= e(t('login.username')) ?>
            <input name="username" autocomplete="username" required>
        </label>
        <label>
            <?= e(t('login.password')) ?>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <label class="checkbox-label">
            <input type="checkbox" name="remember_me" value="1">
            <span>
                <?= e(t('login.remember')) ?>
                <small><?= e(t('login.remember_detail')) ?></small>
            </span>
        </label>
        <button type="submit"><?= e(t('login.title')) ?></button>
    </form>
</section>
<?php render_footer(); ?>
