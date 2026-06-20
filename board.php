<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/layout.php';
require_once __DIR__ . '/lib/team_context.php';

$user = require_login();
$teamId = selected_team_id($user);

if ($teamId <= 0) {
    render_header('Board', $user);
    ?>
    <section class="empty-state">
        <h1>No group exists yet</h1>
        <p>Create a group and users before using the board.</p>
    </section>
    <?php
    render_footer();
    exit;
}

if (!can_access_team($user, $teamId)) {
    http_response_code(403);
    exit('You cannot access this group.');
}

$team = team_or_404($teamId);
$weekDays = planner_week_days((int) $team['week_length']);
$monday = monday_for_date($_GET['week'] ?? null);
$previousWeek = $monday->modify('-7 days')->format('Y-m-d');
$nextWeek = $monday->modify('+7 days')->format('Y-m-d');
$weekLabel = 'Week ' . $monday->format('W');
$members = active_team_members($teamId);
$teams = $user['role'] === 'central_admin' ? all_teams() : [];

$entries = [];
if ($members) {
    $stmt = db()->prepare(
        'SELECT user_id, day_of_week, value
         FROM planner_plan_entries
         WHERE team_id = ? AND week_monday = ? AND archived_at IS NULL'
    );
    $stmt->execute([$teamId, $monday->format('Y-m-d')]);
    foreach ($stmt->fetchAll() as $entry) {
        $entries[(int) $entry['user_id']][(int) $entry['day_of_week']] = $entry['value'];
    }
}

render_header('Planning Board', $user, 'board-page');
?>
<section class="section-head print-hidden">
    <div>
        <h1>Planning Board</h1>
        <p class="muted"><?= e($team['name']) ?>, <?= e($weekLabel) ?>, <?= e($monday->format('Y-m-d')) ?></p>
    </div>
</section>

<section class="board-actions print-hidden">
    <div class="toolbar">
        <?php if ($user['role'] === 'central_admin' && count($teams) > 1): ?>
            <form method="get" class="team-picker">
                <input type="hidden" name="week" value="<?= e($monday->format('Y-m-d')) ?>">
                <select name="team_id" onchange="this.form.submit()">
                    <?php foreach ($teams as $teamOption): ?>
                        <option value="<?= (int) $teamOption['id'] ?>" <?= (int) $teamOption['id'] === $teamId ? 'selected' : '' ?>>
                            <?= e($teamOption['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <a class="icon-link week-arrow" href="<?= e(path_to('board.php?team_id=' . $teamId . '&week=' . $previousWeek)) ?>" aria-label="Previous week" title="Previous week">
            <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                <path d="M15 18 9 12l6-6"></path>
            </svg>
        </a>
        <a class="button secondary" href="<?= e(path_to('board.php?team_id=' . $teamId)) ?>">Today</a>
        <a class="icon-link week-arrow" href="<?= e(path_to('board.php?team_id=' . $teamId . '&week=' . $nextWeek)) ?>" aria-label="Next week" title="Next week">
            <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        </a>
        <?php render_nav($user); ?>
        <button class="icon-link" type="button" onclick="window.print()" aria-label="Print or save as PDF" title="Print or save as PDF">
            <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                <path d="M6 9V3h12v6"></path>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <path d="M6 14h12v7H6z"></path>
                <path d="M18 12h.01"></path>
            </svg>
        </button>
    </div>
</section>

<section class="print-title">
    <h1><?= e($team['name']) ?> - <?= e($weekLabel) ?></h1>
</section>

<section
    class="board-shell"
    data-save-url="<?= e(path_to('api/save-cell.php')) ?>"
    data-csrf-token="<?= e(csrf_token()) ?>"
    data-team-id="<?= $teamId ?>"
    data-week-monday="<?= e($monday->format('Y-m-d')) ?>"
>
    <?php if (!$members): ?>
        <div class="empty-state">
            <h2>No visible users</h2>
            <p>Add users or show hidden users before planning the week.</p>
        </div>
    <?php else: ?>
        <div class="board-scroll">
            <table class="planning-board">
                <thead>
                    <tr>
                        <th><?= e($weekLabel) ?></th>
                        <?php foreach ($weekDays as $dayNumber => $dayName): ?>
                            <th>
                                <span><?= e($dayName) ?></span>
                                <small><?= e($monday->modify('+' . ($dayNumber - 1) . ' days')->format('j/n')) ?></small>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <th><?= e($member['name']) ?></th>
                        <?php foreach ($weekDays as $dayNumber => $dayName): ?>
                            <?php $value = $entries[(int) $member['id']][$dayNumber] ?? ''; ?>
                            <td>
                                <input
                                    class="cell-input"
                                    value="<?= e($value) ?>"
                                    maxlength="3"
                                    inputmode="text"
                                    autocomplete="off"
                                    data-user-id="<?= (int) $member['id'] ?>"
                                    data-day="<?= $dayNumber ?>"
                                    aria-label="<?= e($member['name'] . ' ' . $dayName) ?>"
                                >
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="save-status print-hidden" id="save-status" aria-live="polite"></p>
    <?php endif; ?>
</section>
<script src="<?= e(path_to('assets/board.js')) ?>"></script>
<?php render_footer(); ?>
