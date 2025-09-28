<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . '/Database.php';

use Core\Database;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "Creating core database tables...\n";

    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            avatar VARCHAR(255),
            bio TEXT,
            website VARCHAR(255),
            location VARCHAR(100),
            birth_date DATE,
            gender ENUM('male', 'female', 'other'),
            language_code VARCHAR(10) DEFAULT 'en',
            timezone VARCHAR(50) DEFAULT 'UTC',
            reputation INTEGER DEFAULT 0,
            posts_count INTEGER DEFAULT 0,
            comments_count INTEGER DEFAULT 0,
            followers_count INTEGER DEFAULT 0,
            following_count INTEGER DEFAULT 0,
            is_verified BOOLEAN DEFAULT 0,
            is_premium BOOLEAN DEFAULT 0,
            last_login DATETIME,
            email_verified_at DATETIME,
            two_factor_enabled BOOLEAN DEFAULT 0,
            two_factor_secret VARCHAR(255),
            remember_token VARCHAR(255),
            status ENUM('active', 'inactive', 'banned', 'suspended') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            color VARCHAR(7) DEFAULT '#007bff',
            icon VARCHAR(50),
            parent_id INTEGER,
            sort_order INTEGER DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id)
        )
    ");

    // Posts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            content TEXT NOT NULL,
            excerpt TEXT,
            user_id INTEGER NOT NULL,
            category_id INTEGER,
            status ENUM('draft', 'published', 'archived', 'deleted') DEFAULT 'draft',
            views_count INTEGER DEFAULT 0,
            likes_count INTEGER DEFAULT 0,
            comments_count INTEGER DEFAULT 0,
            shares_count INTEGER DEFAULT 0,
            bookmarks_count INTEGER DEFAULT 0,
            featured_image VARCHAR(255),
            meta_title VARCHAR(255),
            meta_description TEXT,
            tags TEXT,
            is_featured BOOLEAN DEFAULT 0,
            is_pinned BOOLEAN DEFAULT 0,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )
    ");

    // Comments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content TEXT NOT NULL,
            user_id INTEGER NOT NULL,
            post_id INTEGER NOT NULL,
            parent_id INTEGER,
            likes_count INTEGER DEFAULT 0,
            replies_count INTEGER DEFAULT 0,
            is_approved BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (post_id) REFERENCES posts(id),
            FOREIGN KEY (parent_id) REFERENCES comments(id)
        )
    ");

    // Sessions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INTEGER,
            ip_address VARCHAR(45),
            user_agent TEXT,
            payload TEXT,
            last_activity INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Notifications table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            data TEXT,
            is_read BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // User activities table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_activities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            action VARCHAR(100) NOT NULL,
            metadata TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Page views table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS page_views (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page VARCHAR(255) NOT NULL,
            referrer TEXT,
            user_id INTEGER,
            ip_address VARCHAR(45),
            user_agent TEXT,
            session_id VARCHAR(128),
            time_on_page_seconds INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Content interactions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_interactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content_id INTEGER NOT NULL,
            content_type VARCHAR(50) NOT NULL,
            action VARCHAR(50) NOT NULL,
            user_id INTEGER,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Engagement events table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS engagement_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            metadata TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Conversion events table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS conversion_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            conversion_type VARCHAR(100) NOT NULL,
            value DECIMAL(10,2) DEFAULT 0,
            metadata TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Revenue events table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS revenue_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            revenue_type VARCHAR(100) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            metadata TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Performance metrics table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS performance_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            metric_name VARCHAR(100) NOT NULL,
            value DECIMAL(10,4) NOT NULL,
            metadata TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Security logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS security_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            threat_type VARCHAR(100) NOT NULL,
            threat_name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
            ip_address VARCHAR(45),
            user_id INTEGER,
            user_agent TEXT,
            url TEXT,
            method VARCHAR(10),
            request_data TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Blocked IPs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blocked_ips (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address VARCHAR(45) UNIQUE NOT NULL,
            reason TEXT,
            blocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Security alerts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS security_alerts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
            threat_type VARCHAR(100),
            ip_address VARCHAR(45),
            user_id INTEGER,
            status ENUM('active', 'resolved', 'dismissed') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Email logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            to_email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            error_message TEXT
        )
    ");

    // SMS logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sms_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone_number VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            error_message TEXT
        )
    ");

    // API keys table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS api_keys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            key_hash VARCHAR(255) NOT NULL,
            permissions TEXT,
            is_active BOOLEAN DEFAULT 1,
            last_used_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // API usage logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS api_usage_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key_hash VARCHAR(255) NOT NULL,
            endpoint VARCHAR(255) NOT NULL,
            response_time INTEGER,
            status_code INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Cloud storage files table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cloud_storage_files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            file_path VARCHAR(500) NOT NULL,
            cloud_path VARCHAR(500) NOT NULL,
            file_size INTEGER,
            metadata TEXT,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // CDN cache purges table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cdn_cache_purges (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            url TEXT NOT NULL,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            purged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            error_message TEXT
        )
    ");

    // System metrics table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            metric_name VARCHAR(100) NOT NULL,
            value DECIMAL(10,4) NOT NULL,
            metadata TEXT,
            recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Backups table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS backups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            backup_id VARCHAR(100) UNIQUE NOT NULL,
            backup_type VARCHAR(50) NOT NULL,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            options TEXT,
            file_path VARCHAR(500),
            file_size INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME
        )
    ");

    // Themes table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS themes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            author_id INTEGER NOT NULL,
            version VARCHAR(20) DEFAULT '1.0.0',
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            preview_image VARCHAR(255),
            theme_file VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id)
        )
    ");

    // Theme downloads table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS theme_downloads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            theme_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            downloaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (theme_id) REFERENCES themes(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Theme ratings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS theme_ratings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            theme_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
            review TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (theme_id) REFERENCES themes(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // User themes table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_themes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            theme_id INTEGER NOT NULL,
            installed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (theme_id) REFERENCES themes(id)
        )
    ");

    // Plugins table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            author_id INTEGER NOT NULL,
            version VARCHAR(20) DEFAULT '1.0.0',
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            preview_image VARCHAR(255),
            plugin_file VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id)
        )
    ");

    // Plugin downloads table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugin_downloads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            plugin_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            downloaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (plugin_id) REFERENCES plugins(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Plugin ratings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plugin_ratings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            plugin_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
            review TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (plugin_id) REFERENCES plugins(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // User plugins table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_plugins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            plugin_id INTEGER NOT NULL,
            installed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (plugin_id) REFERENCES plugins(id)
        )
    ");

    // Custom components table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS custom_components (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            type VARCHAR(50) NOT NULL,
            code TEXT NOT NULL,
            author_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id)
        )
    ");

    // Payments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            payment_method VARCHAR(50) NOT NULL,
            status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            transaction_id VARCHAR(255),
            metadata TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Premium features table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS premium_features (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            feature_name VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            price DECIMAL(10,2) DEFAULT 0,
            category VARCHAR(50),
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Subscription plans table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscription_plans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            duration INTEGER NOT NULL COMMENT 'Duration in days',
            features TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // User subscriptions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            plan_id INTEGER NOT NULL,
            status ENUM('active', 'cancelled', 'expired') DEFAULT 'active',
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
        )
    ");

    // Languages table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS languages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code VARCHAR(10) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            native_name VARCHAR(100) NOT NULL,
            is_rtl BOOLEAN DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Translations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS translations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            translation_key VARCHAR(255) NOT NULL,
            language_code VARCHAR(10) NOT NULL,
            value TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(translation_key, language_code),
            FOREIGN KEY (language_code) REFERENCES languages(code)
        )
    ");

    // Localized content table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS localized_content (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content_id INTEGER NOT NULL,
            content_type VARCHAR(50) NOT NULL,
            language_code VARCHAR(10) NOT NULL,
            title VARCHAR(255),
            content TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (language_code) REFERENCES languages(code)
        )
    ");

    // Courses table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            instructor_id INTEGER NOT NULL,
            category VARCHAR(100),
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (instructor_id) REFERENCES users(id)
        )
    ");

    // Course enrollments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_enrollments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Course ratings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_ratings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
            review TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Quizzes table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quizzes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            course_id INTEGER,
            time_limit INTEGER COMMENT 'Time limit in minutes',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id)
        )
    ");

    // Quiz attempts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quiz_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            quiz_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            score DECIMAL(5,2),
            answers TEXT,
            completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Training modules table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS training_modules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            type VARCHAR(50) NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Training module completions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS training_module_completions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            module_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            completion_time INTEGER COMMENT 'Completion time in seconds',
            completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (module_id) REFERENCES training_modules(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Tenants table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tenants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            domain VARCHAR(255) UNIQUE NOT NULL,
            admin_id INTEGER NOT NULL,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id)
        )
    ");

    // Organizations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS organizations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            admin_id INTEGER NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id)
        )
    ");

    // Organization users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS organization_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            organization_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role VARCHAR(50) DEFAULT 'member',
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Compliance policies table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS compliance_policies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            type VARCHAR(100) NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Compliance violations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS compliance_violations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            policy_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            violation_type ENUM('minor', 'major', 'critical') NOT NULL,
            description TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (policy_id) REFERENCES compliance_policies(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Content recommendations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_recommendations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            content_id INTEGER NOT NULL,
            interaction_type VARCHAR(50) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Chatbot conversations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chatbot_conversations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            user_message TEXT NOT NULL,
            bot_response TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Workflows table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS workflows (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            trigger_event VARCHAR(100) NOT NULL,
            actions TEXT NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Workflow executions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS workflow_executions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            workflow_id INTEGER NOT NULL,
            context TEXT NOT NULL,
            execution_time INTEGER COMMENT 'Execution time in milliseconds',
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (workflow_id) REFERENCES workflows(id)
        )
    ");

    // Data analyses table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS data_analyses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            query TEXT NOT NULL,
            results TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Research projects table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS research_projects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            researcher_id INTEGER NOT NULL,
            status ENUM('active', 'completed', 'archived') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (researcher_id) REFERENCES users(id)
        )
    ");

    // Research data table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS research_data (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER NOT NULL,
            researcher_id INTEGER NOT NULL,
            data_type VARCHAR(100) NOT NULL,
            value DECIMAL(10,4),
            metadata TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES research_projects(id),
            FOREIGN KEY (researcher_id) REFERENCES users(id)
        )
    ");

    // Surveys table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS surveys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            creator_id INTEGER NOT NULL,
            questions TEXT NOT NULL,
            status ENUM('draft', 'active', 'closed') DEFAULT 'draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (creator_id) REFERENCES users(id)
        )
    ");

    // Survey responses table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS survey_responses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            survey_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            answers TEXT NOT NULL,
            response_time INTEGER COMMENT 'Response time in seconds',
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (survey_id) REFERENCES surveys(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Social media shares table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS social_media_shares (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content_id INTEGER NOT NULL,
            content_type VARCHAR(50) NOT NULL,
            platform VARCHAR(50) NOT NULL,
            user_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // IP geolocation table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ip_geolocation (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address VARCHAR(45) UNIQUE NOT NULL,
            country VARCHAR(100),
            region VARCHAR(100),
            city VARCHAR(100),
            latitude DECIMAL(10,8),
            longitude DECIMAL(11,8),
            timezone VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // User agents table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_agents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_agent TEXT UNIQUE NOT NULL,
            device_type VARCHAR(50),
            browser VARCHAR(100),
            os VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Plan features table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plan_features (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            plan_id INTEGER NOT NULL,
            feature_name VARCHAR(100) NOT NULL,
            FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
        )
    ");

    // Search logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS search_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            query TEXT NOT NULL,
            user_id INTEGER,
            results_count INTEGER DEFAULT 0,
            search_time REAL,
            ip_address TEXT,
            user_agent TEXT,
            session_id TEXT,
            referrer TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Search result logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS search_result_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            search_id INTEGER NOT NULL,
            result_type TEXT NOT NULL,
            result_id INTEGER NOT NULL,
            position INTEGER DEFAULT 0,
            clicked BOOLEAN DEFAULT 0,
            clicked_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (search_id) REFERENCES search_logs(id)
        )
    ");

    echo "Core database tables created successfully!\n";

} catch (PDOException $e) {
    error_log("Error creating core database tables: " . $e->getMessage());
    echo "Error creating core database tables: " . $e->getMessage() . "\n";
} catch (\RuntimeException $e) {
    error_log("Database connection error: " . $e->getMessage());
    echo "Database connection error: " . $e->getMessage() . "\n";
}