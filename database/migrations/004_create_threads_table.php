<?php

return "
CREATE TABLE IF NOT EXISTS threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    forum_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_locked BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_forum_id (forum_id),
    INDEX idx_user_id (user_id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_is_pinned (is_pinned),
    INDEX idx_created_at (created_at),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";