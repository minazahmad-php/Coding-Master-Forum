<?php

return "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    avatar VARCHAR(255),
    role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    last_activity TIMESTAMP NULL,
    ban_reason TEXT NULL,
    banned_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";