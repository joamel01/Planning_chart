<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/team_context.php';

function group_page_url(string $page, array $user, int $teamId): string
{
    $query = $user['role'] === 'central_admin' ? '?team_id=' . $teamId : '';
    return path_to($page . $query);
}

function render_group_actions(array $user, int $teamId, bool $canManageGroup): void
{
    $currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $links = ['team.php' => 'Overview'];

    if ($canManageGroup) {
        $links += [
            'team_new_user.php' => 'Add user',
            'team_sort.php' => 'Sort rows',
            'team_visibility.php' => 'Visibility',
        ];
    }

    $links += ['team_password.php' => 'Password'];
    ?>
    <nav class="admin-actions" aria-label="Group actions">
        <?php foreach ($links as $href => $label): ?>
            <?php if ($currentPage === $href): ?>
                <span class="admin-action current"><?= e($label) ?></span>
            <?php else: ?>
                <a class="admin-action" href="<?= e(group_page_url($href, $user, $teamId)) ?>"><?= e($label) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php
}

function render_group_header(array $user, array $team, int $teamId, bool $canManageGroup, string $title, string $subtitle): void
{
    $teams = $user['role'] === 'central_admin' ? all_teams() : [];
    ?>
    <section class="section-head">
        <div>
            <h1><?= e($title) ?></h1>
            <p class="muted"><?= e($team['name']) ?><?= $subtitle !== '' ? ' - ' . e($subtitle) : '' ?></p>
        </div>
        <div class="header-actions">
            <?php if ($user['role'] === 'central_admin' && count($teams) > 1): ?>
                <form method="get" class="team-picker">
                    <select name="team_id" onchange="this.form.submit()">
                        <?php foreach ($teams as $teamOption): ?>
                            <option value="<?= (int) $teamOption['id'] ?>" <?= (int) $teamOption['id'] === $teamId ? 'selected' : '' ?>>
                                <?= e($teamOption['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
            <?php render_group_actions($user, $teamId, $canManageGroup); ?>
            <?php render_nav($user); ?>
        </div>
    </section>
    <?php
}

function require_group_context(): array
{
    $user = require_login();
    $teamId = selected_team_id($user);

    if ($teamId <= 0) {
        render_header('People', $user);
        ?>
        <section class="empty-state">
            <h1>No group exists yet</h1>
            <p>Create a group in Central Admin first.</p>
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
    return [$user, $teamId, $team, can_manage_group($user, $teamId)];
}
