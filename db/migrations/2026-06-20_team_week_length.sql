ALTER TABLE planner_teams
    ADD COLUMN week_length TINYINT UNSIGNED NOT NULL DEFAULT 5 AFTER name,
    ADD CONSTRAINT chk_planner_teams_week_length CHECK (week_length IN (5, 7));

ALTER TABLE planner_plan_entries
    DROP CONSTRAINT chk_planner_plan_day;

ALTER TABLE planner_plan_entries
    ADD CONSTRAINT chk_planner_plan_day CHECK (day_of_week BETWEEN 1 AND 7);
