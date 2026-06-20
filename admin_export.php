<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/admin_nav.php';

$user = require_admin();

function send_csv(string $filename, array $headers, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        http_response_code(500);
        exit('Could not open output stream.');
    }

    fputcsv($output, $headers);
    foreach ($rows as $row) {
        $line = [];
        foreach ($headers as $header) {
            $line[] = $row[$header] ?? '';
        }
        fputcsv($output, $line);
    }

    fclose($output);
    exit;
}

$download = (string) ($_GET['download'] ?? '');

if ($download === 'users') {
    $rows = db()->query(
        "SELECT
            u.id,
            t.name AS group_name,
            u.name,
            u.username,
            u.role,
            u.sort_order,
            u.is_active,
            u.is_board_visible,
            u.created_at,
            u.updated_at
         FROM planner_users u
         LEFT JOIN planner_teams t ON t.id = u.team_id
         WHERE u.archived_at IS NULL
         ORDER BY t.name, u.sort_order, u.name"
    )->fetchAll();
    send_csv('work-planner-users.csv', ['id', 'group_name', 'name', 'username', 'role', 'sort_order', 'is_active', 'is_board_visible', 'created_at', 'updated_at'], $rows);
}

if ($download === 'archived_users') {
    $rows = db()->query(
        "SELECT
            u.id,
            t.name AS group_name,
            u.name,
            u.username,
            u.role,
            u.archived_at,
            archived_by.username AS archived_by_username
         FROM planner_users u
         LEFT JOIN planner_teams t ON t.id = u.team_id
         LEFT JOIN planner_users archived_by ON archived_by.id = u.archived_by
         WHERE u.archived_at IS NOT NULL
         ORDER BY u.archived_at DESC, u.name"
    )->fetchAll();
    send_csv('work-planner-archived-users.csv', ['id', 'group_name', 'name', 'username', 'role', 'archived_at', 'archived_by_username'], $rows);
}

if ($download === 'plan_entries') {
    $rows = db()->query(
        "SELECT
            e.id,
            t.name AS group_name,
            u.name AS user_name,
            u.username,
            e.week_monday,
            e.day_of_week,
            e.value,
            updated_by.username AS updated_by_username,
            e.updated_at,
            e.archived_at
         FROM planner_plan_entries e
         INNER JOIN planner_teams t ON t.id = e.team_id
         INNER JOIN planner_users u ON u.id = e.user_id
         LEFT JOIN planner_users updated_by ON updated_by.id = e.updated_by
         ORDER BY t.name, e.week_monday, u.sort_order, u.name, e.day_of_week"
    )->fetchAll();
    send_csv('work-planner-plan-entries.csv', ['id', 'group_name', 'user_name', 'username', 'week_monday', 'day_of_week', 'value', 'updated_by_username', 'updated_at', 'archived_at'], $rows);
}

render_header('Export', $user);
render_admin_header($user, 'Export', 'Download CSV files for review or backup support.');
?>
<section class="panel">
    <h2>CSV Exports</h2>
    <p class="muted">These exports do not include password hashes. Use database backups for full restore capability.</p>
    <div class="admin-actions export-actions">
        <a class="admin-action" href="<?= e(path_to('admin_export.php?download=users')) ?>">Active users CSV</a>
        <a class="admin-action" href="<?= e(path_to('admin_export.php?download=archived_users')) ?>">Archived users CSV</a>
        <a class="admin-action" href="<?= e(path_to('admin_export.php?download=plan_entries')) ?>">Plan entries CSV</a>
    </div>
</section>
<?php render_footer(); ?>
