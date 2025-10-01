<?php
declare(strict_types=1);

namespace Services;

class ThemeManager {
    private string $themesPath;
    private array $installedThemes;
    private string $activeTheme;
    
    public function __construct() {
        $this->themesPath = ROOT_PATH . '/themes';
        $this->installedThemes = $this->loadInstalledThemes();
        $this->activeTheme = $this->getActiveTheme();
    }
    
    private function loadInstalledThemes(): array {
        $themes = [];
        $themeDirs = glob($this->themesPath . '/*', GLOB_ONLYDIR);
        
        foreach ($themeDirs as $themeDir) {
            $themeName = basename($themeDir);
            $configFile = $themeDir . '/theme.json';
            
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                $themes[$themeName] = array_merge($config, [
                    'path' => $themeDir,
                    'installed' => true
                ]);
            }
        }
        
        return $themes;
    }
    
    public function getInstalledThemes(): array {
        return $this->installedThemes;
    }
    
    public function getActiveTheme(): string {
        $db = Database::getInstance();
        $result = $db->fetch("SELECT value FROM site_settings WHERE key = 'active_theme'");
        return $result ? $result['value'] : 'default';
    }
    
    public function setActiveTheme(string $themeName): bool {
        if (!isset($this->installedThemes[$themeName])) {
            return false;
        }
        
        $db = Database::getInstance();
        $db->update('site_settings', 
            ['value' => $themeName], 
            'key = :key', 
            ['key' => 'active_theme']
        );
        
        $this->activeTheme = $themeName;
        return true;
    }
    
    public function installTheme(string $themeZipPath): bool {
        $zip = new ZipArchive();
        
        if ($zip->open($themeZipPath) !== TRUE) {
            return false;
        }
        
        $themeName = $this->extractThemeName($zip);
        if (!$themeName) {
            $zip->close();
            return false;
        }
        
        $extractPath = $this->themesPath . '/' . $themeName;
        
        // Create theme directory
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }
        
        // Extract theme files
        $zip->extractTo($extractPath);
        $zip->close();
        
        // Validate theme
        if (!$this->validateTheme($extractPath)) {
            $this->removeTheme($themeName);
            return false;
        }
        
        // Reload themes
        $this->installedThemes = $this->loadInstalledThemes();
        
        return true;
    }
    
    private function extractThemeName(ZipArchive $zip): ?string {
        $configContent = $zip->getFromName('theme.json');
        if (!$configContent) {
            return null;
        }
        
        $config = json_decode($configContent, true);
        return $config['name'] ?? null;
    }
    
    private function validateTheme(string $themePath): bool {
        $requiredFiles = ['theme.json', 'style.css', 'index.php'];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($themePath . '/' . $file)) {
                return false;
            }
        }
        
        // Validate theme.json
        $config = json_decode(file_get_contents($themePath . '/theme.json'), true);
        $requiredFields = ['name', 'version', 'description', 'author'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    public function removeTheme(string $themeName): bool {
        if ($themeName === 'default') {
            return false; // Cannot remove default theme
        }
        
        if (!isset($this->installedThemes[$themeName])) {
            return false;
        }
        
        $themePath = $this->themesPath . '/' . $themeName;
        
        if (is_dir($themePath)) {
            $this->deleteDirectory($themePath);
        }
        
        // If this was the active theme, switch to default
        if ($this->activeTheme === $themeName) {
            $this->setActiveTheme('default');
        }
        
        // Reload themes
        $this->installedThemes = $this->loadInstalledThemes();
        
        return true;
    }
    
    private function deleteDirectory(string $dir): bool {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    public function getThemeConfig(string $themeName): ?array {
        return $this->installedThemes[$themeName] ?? null;
    }
    
    public function updateTheme(string $themeName, string $themeZipPath): bool {
        if (!isset($this->installedThemes[$themeName])) {
            return false;
        }
        
        // Backup current theme
        $backupPath = $this->themesPath . '/' . $themeName . '_backup_' . time();
        $this->copyDirectory($this->themesPath . '/' . $themeName, $backupPath);
        
        try {
            // Remove current theme
            $this->removeTheme($themeName);
            
            // Install new version
            $success = $this->installTheme($themeZipPath);
            
            if (!$success) {
                // Restore backup
                $this->copyDirectory($backupPath, $this->themesPath . '/' . $themeName);
                $this->installedThemes = $this->loadInstalledThemes();
            }
            
            // Clean up backup
            $this->deleteDirectory($backupPath);
            
            return $success;
        } catch (Exception $e) {
            // Restore backup on error
            $this->copyDirectory($backupPath, $this->themesPath . '/' . $themeName);
            $this->installedThemes = $this->loadInstalledThemes();
            $this->deleteDirectory($backupPath);
            
            return false;
        }
    }
    
    private function copyDirectory(string $src, string $dst): void {
        if (!is_dir($src)) {
            return;
        }
        
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        $files = array_diff(scandir($src), ['.', '..']);
        
        foreach ($files as $file) {
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            
            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }
    
    public function getThemeAssets(string $themeName): array {
        $themePath = $this->themesPath . '/' . $themeName;
        $assets = [];
        
        if (!is_dir($themePath)) {
            return $assets;
        }
        
        $assetDirs = ['css', 'js', 'images', 'fonts'];
        
        foreach ($assetDirs as $dir) {
            $dirPath = $themePath . '/' . $dir;
            if (is_dir($dirPath)) {
                $files = glob($dirPath . '/*');
                foreach ($files as $file) {
                    $assets[$dir][] = basename($file);
                }
            }
        }
        
        return $assets;
    }
    
    public function createCustomTheme(string $themeName, array $config): bool {
        $themePath = $this->themesPath . '/' . $themeName;
        
        if (is_dir($themePath)) {
            return false;
        }
        
        // Create theme directory structure
        mkdir($themePath, 0755, true);
        mkdir($themePath . '/css', 0755, true);
        mkdir($themePath . '/js', 0755, true);
        mkdir($themePath . '/images', 0755, true);
        
        // Create theme.json
        $themeConfig = array_merge([
            'name' => $themeName,
            'version' => '1.0.0',
            'description' => 'Custom theme',
            'author' => 'User',
            'created_at' => date('Y-m-d H:i:s')
        ], $config);
        
        file_put_contents($themePath . '/theme.json', json_encode($themeConfig, JSON_PRETTY_PRINT));
        
        // Create basic files
        $this->createBasicThemeFiles($themePath, $themeConfig);
        
        // Reload themes
        $this->installedThemes = $this->loadInstalledThemes();
        
        return true;
    }
    
    private function createBasicThemeFiles(string $themePath, array $config): void {
        // Create style.css
        $css = "/* {$config['name']} Theme */
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #fff;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 1rem 0;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 1.5rem;
    font-weight: bold;
    color: #007bff;
    text-decoration: none;
}

.nav-links {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-links li {
    margin-left: 1rem;
}

.nav-links a {
    color: #333;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.nav-links a:hover {
    background-color: #e9ecef;
}

.main-content {
    padding: 2rem 0;
}

.footer {
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    padding: 2rem 0;
    text-align: center;
    color: #6c757d;
}

/* Responsive design */
@media (max-width: 768px) {
    .navbar {
        flex-direction: column;
        gap: 1rem;
    }
    
    .nav-links {
        flex-direction: column;
        width: 100%;
    }
    
    .nav-links li {
        margin: 0;
        width: 100%;
    }
    
    .nav-links a {
        display: block;
        text-align: center;
    }
}
";
        
        file_put_contents($themePath . '/style.css', $css);
        
        // Create index.php
        $php = "<?php
/**
 * {$config['name']} Theme
 * {$config['description']}
 * 
 * @author {$config['author']}
 * @version {$config['version']}
 */

// Prevent direct access
if (!defined('ROOT_PATH')) {
    exit('Direct access not allowed');
}

// Get theme data
\$theme = \$this->getThemeData();
?>
<!DOCTYPE html>
<html lang=\"<?php echo DEFAULT_LANG; ?>\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title><?php echo \$pageTitle ?? SITE_NAME; ?></title>
    <meta name=\"description\" content=\"<?php echo \$pageDescription ?? SITE_DESCRIPTION; ?>\">
    
    <!-- Theme CSS -->
    <link rel=\"stylesheet\" href=\"<?php echo SITE_URL; ?>/themes/{$config['name']}/style.css\">
    
    <!-- Additional CSS -->
    <?php if (isset(\$additionalCSS)): ?>
        <?php foreach (\$additionalCSS as \$css): ?>
            <link rel=\"stylesheet\" href=\"<?php echo \$css; ?>\">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <header class=\"header\">
        <div class=\"container\">
            <nav class=\"navbar\">
                <a href=\"<?php echo SITE_URL; ?>\" class=\"logo\"><?php echo SITE_NAME; ?></a>
                <ul class=\"nav-links\">
                    <li><a href=\"<?php echo SITE_URL; ?>\">Home</a></li>
                    <li><a href=\"<?php echo SITE_URL; ?>/forums\">Forums</a></li>
                    <li><a href=\"<?php echo SITE_URL; ?>/users\">Users</a></li>
                    <?php if (Auth::isLoggedIn()): ?>
                        <li><a href=\"<?php echo SITE_URL; ?>/profile\">Profile</a></li>
                        <li><a href=\"<?php echo SITE_URL; ?>/logout\">Logout</a></li>
                    <?php else: ?>
                        <li><a href=\"<?php echo SITE_URL; ?>/login\">Login</a></li>
                        <li><a href=\"<?php echo SITE_URL; ?>/register\">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class=\"main-content\">
        <div class=\"container\">
            <?php echo \$content; ?>
        </div>
    </main>
    
    <footer class=\"footer\">
        <div class=\"container\">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Theme JavaScript -->
    <script src=\"<?php echo SITE_URL; ?>/themes/{$config['name']}/js/theme.js\"></script>
    
    <!-- Additional JavaScript -->
    <?php if (isset(\$additionalJS)): ?>
        <?php foreach (\$additionalJS as \$js): ?>
            <script src=\"<?php echo \$js; ?>\"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>";
        
        file_put_contents($themePath . '/index.php', $php);
        
        // Create basic JavaScript file
        $js = "// {$config['name']} Theme JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Theme initialization code here
    
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuToggle && navLinks) {
        mobileMenuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }
    
    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^=\"#\"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Theme-specific functionality
    initializeTheme();
});

function initializeTheme() {
    // Add theme-specific initialization code here
    console.log('{$config['name']} theme initialized');
}
";
        
        file_put_contents($themePath . '/js/theme.js', $js);
    }
    
    public function exportTheme(string $themeName): string {
        if (!isset($this->installedThemes[$themeName])) {
            throw new Exception('Theme not found');
        }
        
        $themePath = $this->themesPath . '/' . $themeName;
        $zipPath = TEMP_PATH . '/' . $themeName . '_export_' . time() . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Cannot create export file');
        }
        
        $this->addDirectoryToZip($zip, $themePath, '');
        $zip->close();
        
        return $zipPath;
    }
    
    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $zipPath): void {
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;
            $zipFilePath = $zipPath . $file;
            
            if (is_dir($filePath)) {
                $zip->addEmptyDir($zipFilePath);
                $this->addDirectoryToZip($zip, $filePath, $zipFilePath . '/');
            } else {
                $zip->addFile($filePath, $zipFilePath);
            }
        }
    }
}