<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

function assert_username(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assert_username(normalize_username('  ALICE.EXAMPLE  ') === 'alice.example', 'Usernames must be trimmed and lowercased.');
assert_username(normalize_username('MiXeD-User_42') === 'mixed-user_42', 'Mixed-case usernames must be normalized.');

echo "username_test passed\n";
