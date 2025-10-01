<?php
declare(strict_types=1);

/**
 * Modern Forum - Theme Management Service
 * Handles theme installation, activation, and customization
 */

namespace Services;

use Core\Database;
use Core\Logger;
use Core\Session;

class ThemeService
{
    private Database $db;
    private Logger $logger;
    private string $themesPath;
    private string $activeTheme;
    private array $themeCache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->themesPath = THEMES_PATH ?? (ROOT_PATH . '/themes');
        $this->loadActiveTheme();
    }

    private function loadActiveTheme(): void
    {
        try {
            $stmt = $this->db->prepare("SELECT value FROM settings WHERE key = 'active_theme'");
            $stmt->execute();
            $setting = $stmt->fetch();
            
            $this->activeTheme = $setting ? $setting['value'] : 'default';
        } catch (\Exception $e) {
            $this->logger->error('Failed to load active theme', [
                'error' => $e->getMessage()
            ]);
            $this->activeTheme = 'default';
        }
    }

    public function getActiveTheme(): string
    {
        return $this->activeTheme;
    }

    public function setActiveTheme(string $themeName): bool
    {
        if (!$this->themeExists($themeName)) {
            return false;
        }

        try {
            // Update database setting
            $stmt = $this->db->prepare("
                INSERT OR REPLACE INTO settings (key, value, updated_at) 
                VALUES ('active_theme', ?, ?)
            ");
            $stmt->execute([$themeName, date('Y-m-d H:i:s')]);

            $this->activeTheme = $themeName;
            
            // Clear cache
            $this->clearThemeCache();
            
            $this->logger->info('Theme activated', [
                'theme' => $themeName
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to activate theme', [
                'theme' => $themeName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getInstalledThemes(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM themes 
                WHERE is_installed = 1 
                ORDER BY is_active DESC, name ASC
            ");
            $stmt->execute();
            $themes = $stmt->fetchAll();

            // Add file system info
            foreach ($themes as &$theme) {
                $themePath = $this->themesPath . '/' . $theme['directory'];
                $theme['exists'] = is_dir($themePath);
                $theme['version'] = $this->getThemeVersion($theme['directory']);
                $theme['last_modified'] = filemtime($themePath) ?? null;
            }

            return $themes;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get installed themes', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getAvailableThemes(): array
    {
        $themes = [];
        $installedThemes = $this->getInstalledThemes();
        $installedDirs = array_column($installedThemes, 'directory');

        if (!is_dir($this->themesPath)) {
            return $themes;
        }

        $dirs = scandir($this->themesPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || in_array($dir, $installedDirs)) {
                continue;
            }

            $themePath = $this->themesPath . '/' . $dir;
            if (is_dir($themePath) && $this->isValidTheme($themePath)) {
                $themeInfo = $this->getThemeInfo($dir);
                if ($themeInfo) {
                    $themes[] = $themeInfo;
                }
            }
        }

        return $themes;
    }

    public function installTheme(string $themeDirectory): bool
    {
        $themePath = $this->themesPath . '/' . $themeDirectory;
        
        if (!is_dir($themePath) || !$this->isValidTheme($themePath)) {
            return false;
        }

        try {
            $themeInfo = $this->getThemeInfo($themeDirectory);
            if (!$themeInfo) {
                return false;
            }

            // Check if theme already exists
            $stmt = $this->db->prepare("SELECT id FROM themes WHERE directory = ?");
            $stmt->execute([$themeDirectory]);
            
            if ($stmt->fetch()) {
                // Update existing theme
                $stmt = $this->db->prepare("
                    UPDATE themes SET
                        name = ?, description = ?, author = ?, version = ?,
                        is_installed = 1, updated_at = ?
                    WHERE directory = ?
                ");
                $stmt->execute([
                    $themeInfo['name'],
                    $themeInfo['description'],
                    $themeInfo['author'],
                    $themeInfo['version'],
                    date('Y-m-d H:i:s'),
                    $themeDirectory
                ]);
            } else {
                // Insert new theme
                $stmt = $this->db->prepare("
                    INSERT INTO themes (
                        name, directory, description, author, version,
                        is_installed, is_active, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, 1, 0, ?, ?)
                ");
                $stmt->execute([
                    $themeInfo['name'],
                    $themeDirectory,
                    $themeInfo['description'],
                    $themeInfo['author'],
                    $themeInfo['version'],
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s')
                ]);
            }

            $this->logger->info('Theme installed', [
                'theme' => $themeDirectory,
                'name' => $themeInfo['name']
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to install theme', [
                'theme' => $themeDirectory,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function uninstallTheme(string $themeDirectory): bool
    {
        try {
            // Don't allow uninstalling active theme
            if ($themeDirectory === $this->activeTheme) {
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE themes 
                SET is_installed = 0, updated_at = ?
                WHERE directory = ?
            ");
            $result = $stmt->execute([date('Y-m-d H:i:s'), $themeDirectory]);

            if ($result) {
                $this->logger->info('Theme uninstalled', [
                    'theme' => $themeDirectory
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to uninstall theme', [
                'theme' => $themeDirectory,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getThemeInfo(string $themeDirectory): ?array
    {
        $themePath = $this->themesPath . '/' . $themeDirectory;
        $configFile = $themePath . '/theme.json';

        if (!file_exists($configFile)) {
            return null;
        }

        try {
            $config = json_decode(file_get_contents($configFile), true);
            
            if (!$config) {
                return null;
            }

            return [
                'name' => $config['name'] ?? $themeDirectory,
                'directory' => $themeDirectory,
                'description' => $config['description'] ?? '',
                'author' => $config['author'] ?? 'Unknown',
                'version' => $config['version'] ?? '1.0.0',
                'screenshot' => $this->getThemeScreenshot($themeDirectory),
                'preview_url' => $this->getThemePreviewUrl($themeDirectory),
                'path' => $themePath,
                'config' => $config
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get theme info', [
                'theme' => $themeDirectory,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function isValidTheme(string $themePath): bool
    {
        $requiredFiles = ['theme.json', 'style.css'];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($themePath . '/' . $file)) {
                return false;
            }
        }

        return true;
    }

    private function getThemeVersion(string $themeDirectory): string
    {
        $themeInfo = $this->getThemeInfo($themeDirectory);
        return $themeInfo['version'] ?? '1.0.0';
    }

    private function getThemeScreenshot(string $themeDirectory): ?string
    {
        $screenshotPath = $this->themesPath . '/' . $themeDirectory . '/screenshot.png';
        
        if (file_exists($screenshotPath)) {
            return '/themes/' . $themeDirectory . '/screenshot.png';
        }

        return null;
    }

    private function getThemePreviewUrl(string $themeDirectory): string
    {
        return '/theme-preview/' . $themeDirectory;
    }

    public function getThemeCSS(string $themeName = null): string
    {
        $theme = $themeName ?? $this->activeTheme;
        
        if (isset($this->themeCache[$theme])) {
            return $this->themeCache[$theme];
        }

        $cssPath = $this->themesPath . '/' . $theme . '/style.css';
        
        if (!file_exists($cssPath)) {
            return '';
        }

        $css = file_get_contents($cssPath);
        
        // Process CSS variables and customizations
        $css = $this->processThemeCSS($css, $theme);
        
        $this->themeCache[$theme] = $css;
        return $css;
    }

    private function processThemeCSS(string $css, string $theme): string
    {
        // Get theme customizations
        $customizations = $this->getThemeCustomizations($theme);
        
        // Replace CSS variables
        foreach ($customizations as $key => $value) {
            $css = str_replace('var(--' . $key . ')', $value, $css);
        }

        return $css;
    }

    public function getThemeCustomizations(string $themeName = null): array
    {
        $theme = $themeName ?? $this->activeTheme;
        
        try {
            $stmt = $this->db->prepare("
                SELECT customizations FROM themes 
                WHERE directory = ? AND is_installed = 1
            ");
            $stmt->execute([$theme]);
            $result = $stmt->fetch();
            
            if ($result && $result['customizations']) {
                return json_decode($result['customizations'], true) ?? [];
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to get theme customizations', [
                'theme' => $theme,
                'error' => $e->getMessage()
            ]);
        }

        return [];
    }

    public function updateThemeCustomizations(string $themeName, array $customizations): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE themes 
                SET customizations = ?, updated_at = ?
                WHERE directory = ?
            ");
            
            $result = $stmt->execute([
                json_encode($customizations),
                date('Y-m-d H:i:s'),
                $themeName
            ]);

            if ($result) {
                // Clear cache
                unset($this->themeCache[$themeName]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update theme customizations', [
                'theme' => $themeName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function themeExists(string $themeName): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 1 FROM themes 
                WHERE directory = ? AND is_installed = 1
            ");
            $stmt->execute([$themeName]);
            return $stmt->fetch() !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getThemePath(string $themeName = null): string
    {
        $theme = $themeName ?? $this->activeTheme;
        return $this->themesPath . '/' . $theme;
    }

    public function getThemeUrl(string $themeName = null): string
    {
        $theme = $themeName ?? $this->activeTheme;
        return '/themes/' . $theme;
    }

    public function getThemeAsset(string $asset, string $themeName = null): string
    {
        $theme = $themeName ?? $this->activeTheme;
        return $this->getThemeUrl($theme) . '/' . $asset;
    }

    public function clearThemeCache(): void
    {
        $this->themeCache = [];
    }

    public function createCustomTheme(string $name, string $description, string $author): bool
    {
        try {
            $directory = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $name));
            $themePath = $this->themesPath . '/' . $directory;

            if (is_dir($themePath)) {
                return false; // Theme already exists
            }

            // Create theme directory
            mkdir($themePath, 0755, true);

            // Create theme.json
            $config = [
                'name' => $name,
                'description' => $description,
                'author' => $author,
                'version' => '1.0.0',
                'type' => 'custom',
                'created_at' => date('Y-m-d H:i:s')
            ];

            file_put_contents($themePath . '/theme.json', json_encode($config, JSON_PRETTY_PRINT));

            // Create basic style.css
            $css = "/* $name Theme */\n\n:root {\n    --primary-color: #3b82f6;\n    --secondary-color: #6b7280;\n    --success-color: #10b981;\n    --error-color: #ef4444;\n    --warning-color: #f59e0b;\n    --info-color: #06b6d4;\n}\n\nbody {\n    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;\n    line-height: 1.6;\n    color: #333;\n    background-color: #ffffff;\n}\n";
            file_put_contents($themePath . '/style.css', $css);

            // Install the theme
            return $this->installTheme($directory);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create custom theme', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function deleteTheme(string $themeDirectory): bool
    {
        try {
            // Don't allow deleting active theme
            if ($themeDirectory === $this->activeTheme) {
                return false;
            }

            // Remove from database
            $stmt = $this->db->prepare("DELETE FROM themes WHERE directory = ?");
            $stmt->execute([$themeDirectory]);

            // Remove files
            $themePath = $this->themesPath . '/' . $themeDirectory;
            if (is_dir($themePath)) {
                $this->deleteDirectory($themePath);
            }

            $this->logger->info('Theme deleted', [
                'theme' => $themeDirectory
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete theme', [
                'theme' => $themeDirectory,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }

    public function getThemeEditor(string $themeName = null): array
    {
        $theme = $themeName ?? $this->activeTheme;
        $cssPath = $this->themesPath . '/' . $theme . '/style.css';
        
        if (!file_exists($cssPath)) {
            return [];
        }

        $css = file_get_contents($cssPath);
        
        // Extract CSS variables
        preg_match_all('/--([^:]+):\s*([^;]+);/', $css, $matches, PREG_SET_ORDER);
        
        $variables = [];
        foreach ($matches as $match) {
            $variables[$match[1]] = trim($match[2]);
        }

        return [
            'css' => $css,
            'variables' => $variables,
            'theme' => $theme
        ];
    }

    public function updateThemeCSS(string $themeName, string $css): bool
    {
        try {
            $cssPath = $this->themesPath . '/' . $themeName . '/style.css';
            
            if (!file_exists($cssPath)) {
                return false;
            }

            $result = file_put_contents($cssPath, $css);
            
            if ($result !== false) {
                // Clear cache
                unset($this->themeCache[$themeName]);
                
                $this->logger->info('Theme CSS updated', [
                    'theme' => $themeName
                ]);
            }

            return $result !== false;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update theme CSS', [
                'theme' => $themeName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}