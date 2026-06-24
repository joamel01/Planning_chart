<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/team_context.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Only POST is supported.'], 405);
}

verify_csrf();

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($payload)) {
    json_response(['ok' => false, 'message' => 'Invalid JSON.'], 400);
}

$teamId = (int) ($payload['team_id'] ?? 0);
$userId = (int) ($payload['user_id'] ?? 0);
$day = (int) ($payload['day'] ?? 0);
$weekMonday = (string) ($payload['week_monday'] ?? '');
$rawValue = (string) ($payload['value'] ?? '');

if (!can_access_team($user, $teamId)) {
    json_response(['ok' => false, 'message' => 'You cannot access this group.'], 403);
}

$stmt = db()->prepare('SELECT week_length FROM planner_teams WHERE id = ? AND archived_at IS NULL');
$stmt->execute([$teamId]);
$team = $stmt->fetch();
if (!$team) {
    json_response(['ok' => false, 'message' => 'The group was not found.'], 404);
}

$weekLength = normalize_week_length((int) $team['week_length']);
if ($day < 1 || $day > $weekLength) {
    json_response(['ok' => false, 'message' => 'Invalid weekday.'], 422);
}

try {
    $monday = monday_for_date($weekMonday)->format('Y-m-d');
    if ($monday !== $weekMonday) {
        throw new InvalidArgumentException('The week date must be the Monday of the week.');
    }

    $value = normalize_cell_value($rawValue);

    $stmt = db()->prepare(
        "SELECT id
         FROM planner_users
         WHERE id = ? AND team_id = ? AND role IN ('group_admin', 'user') AND is_active = 1 AND archived_at IS NULL AND is_board_visible = 1"
    );
    $stmt->execute([$userId, $teamId]);
    if (!$stmt->fetch()) {
        json_response(['ok' => false, 'message' => 'The row user does not belong to this group or is hidden.'], 403);
    }

    if ($value === '') {
        $stmt = db()->prepare(
            'DELETE FROM planner_plan_entries
             WHERE team_id = ? AND user_id = ? AND week_monday = ? AND day_of_week = ?'
        );
        $stmt->execute([$teamId, $userId, $weekMonday, $day]);
    } else {
        $stmt = db()->prepare(
            'INSERT INTO planner_plan_entries (team_id, user_id, week_monday, day_of_week, value, updated_by)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by = VALUES(updated_by), archived_at = NULL'
        );
        $stmt->execute([$teamId, $userId, $weekMonday, $day, $value, (int) $user['id']]);
    }

    json_response(['ok' => true, 'value' => $value]);
} catch (Throwable $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
}
