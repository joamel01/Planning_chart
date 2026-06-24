<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/remember_me.php';

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assert_true(remember_me_lifetime_seconds() === 30 * 24 * 60 * 60, 'Remember-me lifetime must be 30 days.');

$pair = remember_me_token_pair();
assert_true(strlen($pair['selector']) === 32, 'Selector must be 16 random bytes encoded as hex.');
assert_true(strlen($pair['token']) === 64, 'Token must be 32 random bytes encoded as hex.');
assert_true($pair['cookie_value'] === $pair['selector'] . ':' . $pair['token'], 'Cookie value must combine selector and token.');

$parsed = parse_remember_me_cookie($pair['cookie_value']);
assert_true($parsed !== null, 'A generated remember-me cookie must parse.');
assert_true($parsed['selector'] === $pair['selector'], 'Parsed selector must match.');
assert_true($parsed['token'] === $pair['token'], 'Parsed token must match.');
assert_true(parse_remember_me_cookie('broken') === null, 'Malformed cookie values must be rejected.');
assert_true(parse_remember_me_cookie(str_repeat('a', 32) . ':' . str_repeat('b', 63)) === null, 'Malformed token length must be rejected.');

$hash = remember_me_token_hash($pair['token']);
assert_true(strlen($hash) === 64, 'Token hash must be a SHA-256 hex string.');
assert_true(hash_equals($hash, remember_me_token_hash($pair['token'])), 'Token hashing must be stable.');

$options = remember_me_cookie_options();
assert_true($options['expires'] === time() + remember_me_lifetime_seconds() || $options['expires'] === time() + remember_me_lifetime_seconds() + 1, 'Cookie expiry must be about 30 days.');
assert_true($options['httponly'] === true, 'Remember-me cookie must be HttpOnly.');
assert_true($options['samesite'] === 'Lax', 'Remember-me cookie must use SameSite=Lax.');

echo "remember_me_test passed\n";
