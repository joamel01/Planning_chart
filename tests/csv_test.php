<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/csv.php';

function assert_csv_value(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assert_csv_value(csv_cell_value('Normal text') === 'Normal text', 'Normal text must remain unchanged.');
assert_csv_value(csv_cell_value('=SUM(A1:A2)') === "'=SUM(A1:A2)", 'Formula values must be neutralized.');
assert_csv_value(csv_cell_value(' +cmd') === "' +cmd", 'Leading whitespace must not bypass formula protection.');
assert_csv_value(csv_cell_value('-1') === "'-1", 'Leading minus values must be neutralized.');

echo "csv_test passed\n";
