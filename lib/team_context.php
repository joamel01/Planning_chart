<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function selected_team_id(array $user): int
{
    if ($user['role'] !== 'central_admin') {
        return (int) $user['team_id'];
    }

    $requested = (int) ($_GET['team_id'] ?? $_POST['team_id'] ?? 0);
    if ($requested > 0) {
        $stmt = db()->prepare('SELECT id FROM planner_teams WHERE id = ?');
        $stmt->execute([$requested]);
        if ($stmt->fetch()) {
            return $requested;
        }
    }

    $first = db()->query('SELECT id FROM planner_teams ORDER BY name LIMIT 1')->fetch();
    return $first ? (int) $first['id'] : 0;
}

function team_or_404(int $teamId): array
{
    $stmt = db()->prepare('SELECT id, name, week_length FROM planner_teams WHERE id = ?');
    $stmt->execute([$teamId]);
    $team = $stmt->fetch();

    if (!$team) {
        http_response_code(404);
        exit('The group was not found.');
    }

    return $team;
}

function active_team_members(int $teamId): array
{
    $stmt = db()->prepare(
        "SELECT id, name, username, role, sort_order
         FROM planner_users
         WHERE team_id = ? AND role IN ('group_admin', 'user') AND is_active = 1 AND archived_at IS NULL AND is_board_visible = 1
         ORDER BY sort_order, name"
    );
    $stmt->execute([$teamId]);
    return $stmt->fetchAll();
}

function team_members(int $teamId): array
{
    $stmt = db()->prepare(
        "SELECT id, name, username, role, sort_order, is_board_visible
         FROM planner_users
         WHERE team_id = ? AND role IN ('group_admin', 'user') AND is_active = 1 AND archived_at IS NULL
         ORDER BY sort_order, name"
    );
    $stmt->execute([$teamId]);
    return $stmt->fetchAll();
}

function all_teams(): array
{
    return db()->query('SELECT id, name, week_length FROM planner_teams ORDER BY name')->fetchAll();
}
