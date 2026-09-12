CREATE TABLE IF NOT EXISTS roadmap_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage ENUM('m1','m4','tcas') NOT NULL DEFAULT 'tcas',
    title VARCHAR(255) NOT NULL,
    subject VARCHAR(100) NOT NULL DEFAULT 'ทั่วไป',
    category VARCHAR(100) NOT NULL DEFAULT 'ทั่วไป',
    due_date DATE NULL,
    points_reward INT NOT NULL DEFAULT 10,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_roadmap_stage_active (stage, is_active, sort_order),
    CONSTRAINT fk_roadmap_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roadmap_task_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_roadmap_task_user (task_id, user_id),
    INDEX idx_roadmap_progress_user (user_id, is_completed),
    CONSTRAINT fk_roadmap_progress_task FOREIGN KEY (task_id) REFERENCES roadmap_tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_roadmap_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
