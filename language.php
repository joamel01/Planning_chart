<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/i18n.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Only POST is supported.');
}

verify_csrf();

$locale = (string) ($_POST['locale'] ?? '');
$target = (string) ($_POST['return_page'] ?? 'index.php');
if (preg_match('/\A[a-zA-Z0-9_-]+\.php(?:\?[a-zA-Z0-9_&=.%\-]+)?\z/', $target) !== 1) {
    $target = 'index.php';
}

try {
    $user = current_user();
    set_locale($locale, $user ? (int) $user['id'] : null);
} catch (Throwable $exception) {
    http_response_code(422);
    exit('The selected language is not available.');
}

redirect_to($target);
