CREATE TABLE IF NOT EXISTS planner_teams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    week_length TINYINT UNSIGNED NOT NULL DEFAULT 5,
    archived_at TIMESTAMP NULL DEFAULT NULL,
    archived_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_planner_teams_name (name),
    KEY idx_planner_teams_archived_at (archived_at),
    CONSTRAINT chk_planner_teams_week_length CHECK (week_length IN (5, 7))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS planner_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('central_admin', 'group_admin', 'user') NOT NULL DEFAULT 'user',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_board_visible TINYINT(1) NOT NULL DEFAULT 1,
    archived_at TIMESTAMP NULL DEFAULT NULL,
    archived_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_planner_users_username (username),
    KEY idx_planner_users_team_sort (team_id, sort_order, name),
    KEY idx_planner_users_role (role),
    CONSTRAINT fk_planner_users_team
        FOREIGN KEY (team_id) REFERENCES planner_teams(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_planner_users_archived_by
        FOREIGN KEY (archived_by) REFERENCES planner_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS planner_plan_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    week_monday DATE NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    value VARCHAR(3) NOT NULL DEFAULT '',
    updated_by INT UNSIGNED NULL,
    archived_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_planner_plan_cell (team_id, user_id, week_monday, day_of_week),
    KEY idx_planner_plan_week (team_id, week_monday),
    KEY idx_planner_plan_user (user_id),
    CONSTRAINT fk_planner_plan_team
        FOREIGN KEY (team_id) REFERENCES planner_teams(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_planner_plan_user
        FOREIGN KEY (user_id) REFERENCES planner_users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_planner_plan_updated_by
        FOREIGN KEY (updated_by) REFERENCES planner_users(id)
        ON DELETE SET NULL,
    CONSTRAINT chk_planner_plan_day CHECK (day_of_week BETWEEN 1 AND 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS planner_audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id INT UNSIGNED NULL,
    team_id INT UNSIGNED NULL,
    target_user_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_planner_audit_team_created (team_id, created_at),
    CONSTRAINT fk_planner_audit_actor
        FOREIGN KEY (actor_user_id) REFERENCES planner_users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_planner_audit_team
        FOREIGN KEY (team_id) REFERENCES planner_teams(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_planner_audit_target
        FOREIGN KEY (target_user_id) REFERENCES planner_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS planner_remember_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    selector CHAR(32) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    last_used_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_planner_remember_selector (selector),
    KEY idx_planner_remember_user_expires (user_id, expires_at),
    CONSTRAINT fk_planner_remember_user
        FOREIGN KEY (user_id) REFERENCES planner_users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS planner_schema_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(180) NOT NULL,
    applied_by INT UNSIGNED NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_planner_schema_versions_migration (migration),
    KEY idx_planner_schema_versions_applied_at (applied_at),
    CONSTRAINT fk_planner_schema_versions_applied_by
        FOREIGN KEY (applied_by) REFERENCES planner_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE planner_teams
    ADD CONSTRAINT fk_planner_teams_archived_by
        FOREIGN KEY (archived_by) REFERENCES planner_users(id)
        ON DELETE SET NULL;
