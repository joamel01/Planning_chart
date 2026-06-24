<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function ensure_schema_versions_table(): void
{
    db()->exec(
        "CREATE TABLE IF NOT EXISTS planner_schema_versions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(180) NOT NULL,
            applied_by INT UNSIGNED NULL,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_planner_schema_versions_migration (migration),
            KEY idx_planner_schema_versions_applied_at (applied_at),
            CONSTRAINT fk_planner_schema_versions_applied_by
                FOREIGN KEY (applied_by) REFERENCES planner_users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function migration_files(): array
{
    $files = glob(__DIR__ . '/../db/migrations/*.sql') ?: [];
    $migrations = [];

    foreach ($files as $file) {
        $migrations[basename($file)] = $file;
    }

    ksort($migrations);
    return $migrations;
}

function applied_migrations(): array
{
    ensure_schema_versions_table();
    $rows = db()->query('SELECT migration, applied_at FROM planner_schema_versions ORDER BY migration')->fetchAll();
    $applied = [];

    foreach ($rows as $row) {
        $applied[(string) $row['migration']] = (string) $row['applied_at'];
    }

    return $applied;
}

function pending_migrations(): array
{
    $files = migration_files();
    $applied = applied_migrations();

    return array_diff_key($files, $applied);
}

function schema_has_column(string $table, string $column): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) AS column_count
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);

    return (int) $stmt->fetch()['column_count'] > 0;
}

function current_schema_can_be_baselined(): bool
{
    return schema_has_column('planner_teams', 'week_length')
        && schema_has_column('planner_teams', 'archived_at')
        && schema_has_column('planner_plan_entries', 'archived_at')
        && schema_has_column('planner_users', 'is_board_visible');
}

function baseline_current_migrations(int $adminUserId): int
{
    ensure_schema_versions_table();
    $files = migration_files();
    $applied = applied_migrations();
    $stmt = db()->prepare(
        'INSERT IGNORE INTO planner_schema_versions (migration, applied_by)
         VALUES (?, ?)'
    );

    $count = 0;
    foreach (array_keys($files) as $migration) {
        if (!array_key_exists($migration, $applied)) {
            $stmt->execute([$migration, $adminUserId]);
            $count += $stmt->rowCount();
        }
    }

    return $count;
}

function split_sql_statements(string $sql): array
{
    $statements = [];
    $current = '';
    $quote = null;
    $length = strlen($sql);

    for ($index = 0; $index < $length; $index++) {
        $char = $sql[$index];
        $next = $index + 1 < $length ? $sql[$index + 1] : '';

        if ($quote === null && $char === '-' && $next === '-') {
            while ($index < $length && $sql[$index] !== "\n") {
                $index++;
            }
            continue;
        }

        if ($quote === null && $char === '#') {
            while ($index < $length && $sql[$index] !== "\n") {
                $index++;
            }
            continue;
        }

        if ($quote === null && $char === '/' && $next === '*') {
            $index += 2;
            while ($index + 1 < $length && !($sql[$index] === '*' && $sql[$index + 1] === '/')) {
                $index++;
            }
            $index++;
            continue;
        }

        if (($char === "'" || $char === '"') && ($index === 0 || $sql[$index - 1] !== '\\')) {
            $quote = $quote === $char ? null : ($quote ?? $char);
        }

        if ($char === ';' && $quote === null) {
            $statement = trim($current);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $current = '';
            continue;
        }

        $current .= $char;
    }

    $statement = trim($current);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

function apply_pending_migrations(int $adminUserId): array
{
    ensure_schema_versions_table();
    $pending = pending_migrations();
    $record = db()->prepare(
        'INSERT INTO planner_schema_versions (migration, applied_by)
         VALUES (?, ?)'
    );
    $applied = [];

    foreach ($pending as $migration => $file) {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException('Could not read migration file: ' . $migration);
        }

        foreach (split_sql_statements($sql) as $statement) {
            db()->exec($statement);
        }

        $record->execute([$migration, $adminUserId]);
        $applied[] = $migration;
    }

    return $applied;
}
