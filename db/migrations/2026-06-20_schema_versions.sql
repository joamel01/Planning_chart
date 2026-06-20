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
