<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

$user = current_user();

if (!$user) {
    redirect_to('login.php');
}

redirect_to($user['role'] === 'central_admin' ? 'admin.php' : 'board.php');
