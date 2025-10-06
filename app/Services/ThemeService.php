<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;

/**
 * Theme Management Service
 */
class ThemeService
{
    private $db;
    private $logger;

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
        $this->logger = $app->get('logger');
    }

    /**
     * Get available themes
     */
    public function getAvailableThemes()
    {
        return [
            'default' => [
                'name' => 'Default Theme',
                'description' => 'Clean and modern default theme',
                'version' => '1.0.0',
                'author' => 'Forum Team',
                'preview' => '/themes/default/preview.png',
                'colors' => [
                    'primary' => '#6200EE',
                    'secondary' => '#03DAC6',
                    'background' => '#FFFFFF',
                    'surface' => '#F5F5F5',
                    'text' => '#000000',
                    'text_secondary' => '#666666'
                ]
            ],
            'dark' => [
                'name' => 'Dark Theme',
                'description' => 'Dark mode for comfortable viewing',
                'version' => '1.0.0',
                'author' => 'Forum Team',
                'preview' => '/themes/dark/preview.png',
                'colors' => [
                    'primary' => '#BB86FC',
                    'secondary' => '#03DAC6',
                    'background' => '#121212',
                    'surface' => '#1E1E1E',
                    'text' => '#FFFFFF',
                    'text_secondary' => '#B3B3B3'
                ]
            ],
            'blue' => [
                'name' => 'Blue Ocean',
                'description' => 'Calming blue theme',
                'version' => '1.0.0',
                'author' => 'Forum Team',
                'preview' => '/themes/blue/preview.png',
                'colors' => [
                    'primary' => '#1976D2',
                    'secondary' => '#42A5F5',
                    'background' => '#E3F2FD',
                    'surface' => '#FFFFFF',
                    'text' => '#0D47A1',
                    'text_secondary' => '#1565C0'
                ]
            ],
            'green' => [
                'name' => 'Nature Green',
                'description' => 'Fresh green theme',
                'version' => '1.0.0',
                'author' => 'Forum Team',
                'preview' => '/themes/green/preview.png',
                'colors' => [
                    'primary' => '#388E3C',
                    'secondary' => '#66BB6A',
                    'background' => '#E8F5E8',
                    'surface' => '#FFFFFF',
                    'text' => '#1B5E20',
                    'text_secondary' => '#2E7D32'
                ]
            ],
            'purple' => [
                'name' => 'Royal Purple',
                'description' => 'Elegant purple theme',
                'version' => '1.0.0',
                'author' => 'Forum Team',
                'preview' => '/themes/purple/preview.png',
                'colors' => [
                    'primary' => '#7B1FA2',
                    'secondary' => '#BA68C8',
                    'background' => '#F3E5F5',
                    'surface' => '#FFFFFF',
                    'text' => '#4A148C',
                    'text_secondary' => '#6A1B9A'
                ]
            ]
        ];
    }

    /**
     * Get user's theme preference
     */
    public function getUserTheme($userId)
    {
        $result = $this->db->fetch(
            "SELECT theme, custom_colors FROM user_themes WHERE user_id = ?",
            [$userId]
        );

        if ($result) {
            return [
                'theme' => $result['theme'],
                'custom_colors' => json_decode($result['custom_colors'], true) ?: []
            ];
        }

        return [
            'theme' => 'default',
            'custom_colors' => []
        ];
    }

    /**
     * Set user's theme
     */
    public function setUserTheme($userId, $theme, $customColors = [])
    {
        try {
            $this->db->query(
                "INSERT INTO user_themes (user_id, theme, custom_colors, updated_at) 
                 VALUES (?, ?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE 
                 theme = VALUES(theme), 
                 custom_colors = VALUES(custom_colors), 
                 updated_at = NOW()",
                [$userId, $theme, json_encode($customColors)]
            );

            $this->logger->info("Theme updated for user {$userId}: {$theme}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Theme update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get theme CSS
     */
    public function getThemeCSS($theme, $customColors = [])
    {
        $themes = $this->getAvailableThemes();
        
        if (!isset($themes[$theme])) {
            $theme = 'default';
        }

        $themeData = $themes[$theme];
        $colors = array_merge($themeData['colors'], $customColors);

        return $this->generateThemeCSS($colors);
    }

    /**
     * Generate theme CSS
     */
    private function generateThemeCSS($colors)
    {
        return "
        :root {
            --primary-color: {$colors['primary']};
            --secondary-color: {$colors['secondary']};
            --background-color: {$colors['background']};
            --surface-color: {$colors['surface']};
            --text-color: {$colors['text']};
            --text-secondary-color: {$colors['text_secondary']};
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .navbar {
            background-color: var(--primary-color);
            color: white;
        }

        .card {
            background-color: var(--surface-color);
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .text-secondary {
            color: var(--text-secondary-color) !important;
        }

        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .bg-surface {
            background-color: var(--surface-color) !important;
        }

        .thread-item {
            background-color: var(--surface-color);
            border-left: 4px solid var(--primary-color);
            transition: all 0.2s ease;
        }

        .thread-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .post-content {
            color: var(--text-color);
            line-height: 1.6;
        }

        .sidebar {
            background-color: var(--surface-color);
            border-right: 1px solid rgba(0,0,0,0.1);
        }

        .footer {
            background-color: var(--surface-color);
            color: var(--text-secondary-color);
        }

        /* Dark mode specific styles */
        @media (prefers-color-scheme: dark) {
            .card {
                border-color: rgba(255,255,255,0.1);
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--surface-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .thread-item:hover {
                transform: none;
            }
        }
        ";
    }

    /**
     * Create custom theme
     */
    public function createCustomTheme($userId, $themeName, $colors)
    {
        try {
            $themeId = Security::generateToken(16);
            
            $this->db->query(
                "INSERT INTO custom_themes (id, user_id, name, colors, created_at) VALUES (?, ?, ?, ?, NOW())",
                [$themeId, $userId, $themeName, json_encode($colors)]
            );

            $this->logger->info("Custom theme created: {$themeName} for user {$userId}");
            return $themeId;
        } catch (\Exception $e) {
            $this->logger->error('Custom theme creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get custom themes
     */
    public function getCustomThemes($userId)
    {
        return $this->db->fetchAll(
            "SELECT id, name, colors, created_at FROM custom_themes WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Delete custom theme
     */
    public function deleteCustomTheme($userId, $themeId)
    {
        try {
            $this->db->query(
                "DELETE FROM custom_themes WHERE id = ? AND user_id = ?",
                [$themeId, $userId]
            );

            $this->logger->info("Custom theme deleted: {$themeId} for user {$userId}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Custom theme deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Export theme
     */
    public function exportTheme($themeId)
    {
        $result = $this->db->fetch(
            "SELECT name, colors FROM custom_themes WHERE id = ?",
            [$themeId]
        );

        if ($result) {
            return [
                'name' => $result['name'],
                'colors' => json_decode($result['colors'], true),
                'exported_at' => date('Y-m-d H:i:s')
            ];
        }

        return null;
    }

    /**
     * Import theme
     */
    public function importTheme($userId, $themeData)
    {
        try {
            $themeId = Security::generateToken(16);
            
            $this->db->query(
                "INSERT INTO custom_themes (id, user_id, name, colors, created_at) VALUES (?, ?, ?, ?, NOW())",
                [$themeId, $userId, $themeData['name'], json_encode($themeData['colors'])]
            );

            $this->logger->info("Theme imported: {$themeData['name']} for user {$userId}");
            return $themeId;
        } catch (\Exception $e) {
            $this->logger->error('Theme import failed: ' . $e->getMessage());
            return false;
        }
    }
}