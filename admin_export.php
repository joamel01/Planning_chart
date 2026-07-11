<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/admin_nav.php';
require_once __DIR__ . '/lib/csv.php';
require_once __DIR__ . '/lib/team_context.php';

$user = require_admin();

$download = (string) ($_GET['download'] ?? '');

if ($download === 'group_week') {
    $teamId = (int) ($_GET['team_id'] ?? 0);
    if ($teamId <= 0) {
        http_response_code(422);
        exit('Choose a group to export.');
    }

    $team = team_or_404($teamId);
    $weekMonday = monday_for_date((string) ($_GET['week'] ?? ''))->format('Y-m-d');
    $weekDays = planner_week_days((int) $team['week_length']);
    $members = active_team_members($teamId);

    $stmt = db()->prepare(
        'SELECT user_id, day_of_week, value
         FROM planner_plan_entries
         WHERE team_id = ? AND week_monday = ? AND archived_at IS NULL'
    );
    $stmt->execute([$teamId, $weekMonday]);

    $entries = [];
    foreach ($stmt->fetchAll() as $entry) {
        $entries[(int) $entry['user_id']][(int) $entry['day_of_week']] = $entry['value'];
    }

    $headers = ['group_name', 'week_monday', 'user_name', 'username'];
    foreach ($weekDays as $dayName) {
        $headers[] = $dayName;
    }

    $rows = [];
    foreach ($members as $member) {
        $row = [
            'group_name' => $team['name'],
            'week_monday' => $weekMonday,
            'user_name' => $member['name'],
            'username' => $member['username'],
        ];

        foreach ($weekDays as $dayNumber => $dayName) {
            $row[$dayName] = $entries[(int) $member['id']][$dayNumber] ?? '';
        }

        $rows[] = $row;
    }

    $safeGroupName = preg_replace('/[^a-z0-9]+/i', '-', strtolower($team['name'])) ?: 'group';
    send_csv_download('work-planner-' . trim($safeGroupName, '-') . '-' . $weekMonday . '.csv', $headers, $rows);
}

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
    send_csv_download('work-planner-users.csv', ['id', 'group_name', 'name', 'username', 'role', 'sort_order', 'is_active', 'is_board_visible', 'created_at', 'updated_at'], $rows);
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
    send_csv_download('work-planner-archived-users.csv', ['id', 'group_name', 'name', 'username', 'role', 'archived_at', 'archived_by_username'], $rows);
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
    send_csv_download('work-planner-plan-entries.csv', ['id', 'group_name', 'user_name', 'username', 'week_monday', 'day_of_week', 'value', 'updated_by_username', 'updated_at', 'archived_at'], $rows);
}

$teams = all_teams();
$selectedTeamId = (int) ($_GET['team_id'] ?? ($teams[0]['id'] ?? 0));
$selectedWeek = monday_for_date((string) ($_GET['week'] ?? ''))->format('Y-m-d');

render_header('Export', $user);
render_admin_header($user, 'Export', 'Download CSV files for review or backup support.');
?>
<section class="panel">
    <h2>CSV Exports</h2>
    <p class="muted">These exports do not include password hashes. Use database backups for full restore capability.</p>

    <form method="get" class="inline-form export-week-form">
        <input type="hidden" name="download" value="group_week">
        <select name="team_id" aria-label="Group" required>
            <?php foreach ($teams as $teamOption): ?>
                <option value="<?= (int) $teamOption['id'] ?>" <?= (int) $teamOption['id'] === $selectedTeamId ? 'selected' : '' ?>>
                    <?= e($teamOption['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="week" value="<?= e($selectedWeek) ?>" aria-label="Week">
        <button type="submit">Group week CSV</button>
    </form>

    <div class="admin-actions export-actions">
        <a class="admin-action" href="<?= e(path_to('admin_export.php?download=users')) ?>">Active users CSV</a>
        <a class="admin-action" href="<?= e(path_to('admin_export.php?download=archived_users')) ?>">Archived users CSV</a>
        <a class="admin-action" href="<?= e(path_to('admin_export.php?download=plan_entries')) ?>">Plan entries CSV</a>
    </div>
</section>
<?php render_footer(); ?>
