<?php

return "
-- Insert default forums
INSERT INTO forums (name, description, slug, sort_order, status, created_at) VALUES
('General Discussion', 'General topics and discussions', 'general-discussion', 1, 'active', NOW()),
('Announcements', 'Important announcements and news', 'announcements', 2, 'active', NOW()),
('Help & Support', 'Get help and support from the community', 'help-support', 3, 'active', NOW()),
('Technology', 'Discuss technology, programming, and tech news', 'technology', 4, 'active', NOW()),
('Off Topic', 'Non-technical discussions and chit-chat', 'off-topic', 5, 'active', NOW());

-- Insert default categories
INSERT INTO categories (name, description, slug, sort_order, status, created_at) VALUES
('Programming', 'Programming languages, frameworks, and tools', 'programming', 1, 'active', NOW()),
('Web Development', 'Frontend, backend, and full-stack development', 'web-development', 2, 'active', NOW()),
('Mobile Development', 'iOS, Android, and cross-platform development', 'mobile-development', 3, 'active', NOW()),
('Data Science', 'Machine learning, AI, and data analysis', 'data-science', 4, 'active', NOW()),
('DevOps', 'Deployment, CI/CD, and infrastructure', 'devops', 5, 'active', NOW());
";