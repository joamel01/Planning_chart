<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Only POST is supported.');
}

verify_csrf();
logout_user();
redirect_to('login.php');
