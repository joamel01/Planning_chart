ALTER TABLE planner_teams
    ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL AFTER week_length,
    ADD COLUMN archived_by INT UNSIGNED NULL AFTER archived_at,
    ADD KEY idx_planner_teams_archived_at (archived_at),
    ADD CONSTRAINT fk_planner_teams_archived_by
        FOREIGN KEY (archived_by) REFERENCES planner_users(id)
        ON DELETE SET NULL;
