CREATE TABLE IF NOT EXISTS `science_projects` (
    `id` VARCHAR(24) PRIMARY KEY,
    `owner_id` INT NOT NULL,
    `data` LONGTEXT NOT NULL,
    `run_state` VARCHAR(20) NOT NULL DEFAULT 'paused',
    `revision` INT NOT NULL DEFAULT 0,
    `lease` VARCHAR(24) NULL,
    `lease_until` BIGINT NOT NULL DEFAULT 0,
    `updated_at` BIGINT NOT NULL,
    INDEX `idx_science_projects_owner_updated` (`owner_id`, `updated_at`),
    CONSTRAINT `fk_science_projects_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
