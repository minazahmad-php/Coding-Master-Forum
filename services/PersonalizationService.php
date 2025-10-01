<?php
declare(strict_types=1);

namespace Services;

class PersonalizationService {
    private Database $db;
    private array $userPreferences;
    private int $userId;
    
    public function __construct(int $userId = null) {
        $this->db = Database::getInstance();
        $this->userId = $userId ?? Auth::getUserId();
        $this->userPreferences = $this->loadUserPreferences();
    }
    
    private function loadUserPreferences(): array {
        if (!$this->userId) {
            return $this->getDefaultPreferences();
        }
        
        $preferences = $this->db->fetch(
            "SELECT * FROM user_preferences WHERE user_id = :user_id",
            ['user_id' => $this->userId]
        );
        
        if (!$preferences) {
            $this->createDefaultPreferences();
            return $this->getDefaultPreferences();
        }
        
        return json_decode($preferences['preferences'], true) ?? $this->getDefaultPreferences();
    }
    
    private function getDefaultPreferences(): array {
        return [
            'theme' => 'auto',
            'language' => DEFAULT_LANG,
            'timezone' => TIMEZONE,
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'posts_per_page' => POSTS_PER_PAGE,
            'threads_per_page' => THREADS_PER_PAGE,
            'notifications' => [
                'email' => true,
                'push' => true,
                'browser' => true,
                'new_posts' => true,
                'mentions' => true,
                'quotes' => true,
                'follows' => true,
                'messages' => true
            ],
            'privacy' => [
                'show_online_status' => true,
                'show_last_seen' => true,
                'allow_private_messages' => true,
                'show_email' => false,
                'show_birthday' => false,
                'show_location' => false
            ],
            'display' => [
                'show_signatures' => true,
                'show_avatars' => true,
                'show_post_numbers' => true,
                'compact_mode' => false,
                'auto_refresh' => false,
                'smooth_scrolling' => true
            ],
            'content' => [
                'auto_expand_images' => false,
                'auto_play_videos' => false,
                'show_spoilers' => true,
                'highlight_code' => true,
                'show_math_formulas' => true
            ],
            'accessibility' => [
                'high_contrast' => false,
                'large_text' => false,
                'reduced_motion' => false,
                'screen_reader' => false,
                'keyboard_navigation' => true
            ]
        ];
    }
    
    private function createDefaultPreferences(): void {
        if (!$this->userId) {
            return;
        }
        
        $this->db->insert('user_preferences', [
            'user_id' => $this->userId,
            'preferences' => json_encode($this->getDefaultPreferences()),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getPreference(string $key, $default = null) {
        return $this->getNestedValue($this->userPreferences, $key, $default);
    }
    
    public function setPreference(string $key, $value): bool {
        $this->setNestedValue($this->userPreferences, $key, $value);
        return $this->savePreferences();
    }
    
    public function updatePreferences(array $preferences): bool {
        $this->userPreferences = array_merge($this->userPreferences, $preferences);
        return $this->savePreferences();
    }
    
    private function savePreferences(): bool {
        if (!$this->userId) {
            return false;
        }
        
        try {
            $this->db->update(
                'user_preferences',
                [
                    'preferences' => json_encode($this->userPreferences),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'user_id = :user_id',
                ['user_id' => $this->userId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error saving user preferences: " . $e->getMessage());
            return false;
        }
    }
    
    private function getNestedValue(array $array, string $key, $default = null) {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    private function setNestedValue(array &$array, string $key, $value): void {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $k) {
            if (!is_array($current)) {
                $current = [];
            }
            if (!array_key_exists($k, $current)) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        
        $current = $value;
    }
    
    public function getTheme(): string {
        return $this->getPreference('theme', 'auto');
    }
    
    public function setTheme(string $theme): bool {
        $validThemes = ['light', 'dark', 'auto'];
        if (!in_array($theme, $validThemes)) {
            return false;
        }
        
        return $this->setPreference('theme', $theme);
    }
    
    public function getLanguage(): string {
        return $this->getPreference('language', DEFAULT_LANG);
    }
    
    public function setLanguage(string $language): bool {
        $validLanguages = ['en', 'bn', 'ar', 'hi'];
        if (!in_array($language, $validLanguages)) {
            return false;
        }
        
        return $this->setPreference('language', $language);
    }
    
    public function getTimezone(): string {
        return $this->getPreference('timezone', TIMEZONE);
    }
    
    public function setTimezone(string $timezone): bool {
        try {
            new \DateTimeZone($timezone);
            return $this->setPreference('timezone', $timezone);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function getDateFormat(): string {
        return $this->getPreference('date_format', 'Y-m-d');
    }
    
    public function setDateFormat(string $format): bool {
        $validFormats = ['Y-m-d', 'd-m-Y', 'm/d/Y', 'd/m/Y'];
        if (!in_array($format, $validFormats)) {
            return false;
        }
        
        return $this->setPreference('date_format', $format);
    }
    
    public function getTimeFormat(): string {
        return $this->getPreference('time_format', 'H:i');
    }
    
    public function setTimeFormat(string $format): bool {
        $validFormats = ['H:i', 'h:i A', 'h:i a'];
        if (!in_array($format, $validFormats)) {
            return false;
        }
        
        return $this->setPreference('time_format', $format);
    }
    
    public function getPostsPerPage(): int {
        return (int) $this->getPreference('posts_per_page', POSTS_PER_PAGE);
    }
    
    public function setPostsPerPage(int $count): bool {
        if ($count < 5 || $count > 100) {
            return false;
        }
        
        return $this->setPreference('posts_per_page', $count);
    }
    
    public function getThreadsPerPage(): int {
        return (int) $this->getPreference('threads_per_page', THREADS_PER_PAGE);
    }
    
    public function setThreadsPerPage(int $count): bool {
        if ($count < 5 || $count > 100) {
            return false;
        }
        
        return $this->setPreference('threads_per_page', $count);
    }
    
    public function getNotificationSettings(): array {
        return $this->getPreference('notifications', []);
    }
    
    public function setNotificationSetting(string $type, bool $enabled): bool {
        $validTypes = ['email', 'push', 'browser', 'new_posts', 'mentions', 'quotes', 'follows', 'messages'];
        if (!in_array($type, $validTypes)) {
            return false;
        }
        
        return $this->setPreference("notifications.{$type}", $enabled);
    }
    
    public function getPrivacySettings(): array {
        return $this->getPreference('privacy', []);
    }
    
    public function setPrivacySetting(string $setting, bool $value): bool {
        $validSettings = ['show_online_status', 'show_last_seen', 'allow_private_messages', 'show_email', 'show_birthday', 'show_location'];
        if (!in_array($setting, $validSettings)) {
            return false;
        }
        
        return $this->setPreference("privacy.{$setting}", $value);
    }
    
    public function getDisplaySettings(): array {
        return $this->getPreference('display', []);
    }
    
    public function setDisplaySetting(string $setting, $value): bool {
        $validSettings = ['show_signatures', 'show_avatars', 'show_post_numbers', 'compact_mode', 'auto_refresh', 'smooth_scrolling'];
        if (!in_array($setting, $validSettings)) {
            return false;
        }
        
        return $this->setPreference("display.{$setting}", $value);
    }
    
    public function getContentSettings(): array {
        return $this->getPreference('content', []);
    }
    
    public function setContentSetting(string $setting, $value): bool {
        $validSettings = ['auto_expand_images', 'auto_play_videos', 'show_spoilers', 'highlight_code', 'show_math_formulas'];
        if (!in_array($setting, $validSettings)) {
            return false;
        }
        
        return $this->setPreference("content.{$setting}", $value);
    }
    
    public function getAccessibilitySettings(): array {
        return $this->getPreference('accessibility', []);
    }
    
    public function setAccessibilitySetting(string $setting, $value): bool {
        $validSettings = ['high_contrast', 'large_text', 'reduced_motion', 'screen_reader', 'keyboard_navigation'];
        if (!in_array($setting, $validSettings)) {
            return false;
        }
        
        return $this->setPreference("accessibility.{$setting}", $value);
    }
    
    public function getAllPreferences(): array {
        return $this->userPreferences;
    }
    
    public function resetToDefaults(): bool {
        $this->userPreferences = $this->getDefaultPreferences();
        return $this->savePreferences();
    }
    
    public function exportPreferences(): string {
        return json_encode($this->userPreferences, JSON_PRETTY_PRINT);
    }
    
    public function importPreferences(string $json): bool {
        $preferences = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        $this->userPreferences = array_merge($this->getDefaultPreferences(), $preferences);
        return $this->savePreferences();
    }
    
    public function getPersonalizedContent(): array {
        $content = [];
        
        // Get user's favorite forums
        $content['favorite_forums'] = $this->getFavoriteForums();
        
        // Get recommended threads
        $content['recommended_threads'] = $this->getRecommendedThreads();
        
        // Get trending topics
        $content['trending_topics'] = $this->getTrendingTopics();
        
        // Get user's activity summary
        $content['activity_summary'] = $this->getActivitySummary();
        
        return $content;
    }
    
    private function getFavoriteForums(): array {
        if (!$this->userId) {
            return [];
        }
        
        return $this->db->fetchAll(
            "SELECT f.*, COUNT(t.id) as thread_count, COUNT(p.id) as post_count
             FROM forums f
             LEFT JOIN threads t ON f.id = t.forum_id
             LEFT JOIN posts p ON t.id = p.thread_id
             WHERE f.id IN (
                 SELECT forum_id FROM user_forum_favorites WHERE user_id = :user_id
             )
             GROUP BY f.id
             ORDER BY f.name",
            ['user_id' => $this->userId]
        );
    }
    
    private function getRecommendedThreads(): array {
        if (!$this->userId) {
            return [];
        }
        
        // Get threads from forums user is interested in
        return $this->db->fetchAll(
            "SELECT t.*, f.name as forum_name, u.username, u.avatar,
                    COUNT(p.id) as post_count,
                    MAX(p.created_at) as last_post_date
             FROM threads t
             JOIN forums f ON t.forum_id = f.id
             JOIN users u ON t.user_id = u.id
             LEFT JOIN posts p ON t.id = p.thread_id
             WHERE f.id IN (
                 SELECT forum_id FROM user_forum_favorites WHERE user_id = :user_id
             )
             AND t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY t.id
             ORDER BY t.created_at DESC
             LIMIT 10",
            ['user_id' => $this->userId]
        );
    }
    
    private function getTrendingTopics(): array {
        return $this->db->fetchAll(
            "SELECT tag, COUNT(*) as count
             FROM thread_tags
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY tag
             ORDER BY count DESC
             LIMIT 10"
        );
    }
    
    private function getActivitySummary(): array {
        if (!$this->userId) {
            return [];
        }
        
        return [
            'posts_today' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM posts WHERE user_id = :user_id AND DATE(created_at) = CURDATE()",
                ['user_id' => $this->userId]
            ),
            'threads_created' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM threads WHERE user_id = :user_id",
                ['user_id' => $this->userId]
            ),
            'reputation' => $this->db->fetchColumn(
                "SELECT reputation FROM users WHERE id = :user_id",
                ['user_id' => $this->userId]
            ) ?? 0,
            'last_activity' => $this->db->fetchColumn(
                "SELECT MAX(created_at) FROM posts WHERE user_id = :user_id",
                ['user_id' => $this->userId]
            )
        ];
    }
}