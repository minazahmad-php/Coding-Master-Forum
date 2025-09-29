<?php
declare(strict_types=1);

/**
 * Modern Forum - Theme Controller
 * Handles theme management and customization
 */

namespace Controllers;

use Core\Controller;
use Core\View;
use Services\ThemeService;

class ThemeController extends Controller
{
    private ThemeService $themeService;

    public function __construct()
    {
        parent::__construct();
        $this->themeService = new ThemeService();
    }

    public function index(): void
    {
        $data = [
            'title' => 'Theme Management',
            'installed_themes' => $this->themeService->getInstalledThemes(),
            'available_themes' => $this->themeService->getAvailableThemes(),
            'active_theme' => $this->themeService->getActiveTheme()
        ];

        View::render('theme/index', $data);
    }

    public function activate(string $themeName): void
    {
        if ($this->themeService->setActiveTheme($themeName)) {
            Session::flash('success', 'Theme activated successfully');
        } else {
            Session::flash('error', 'Failed to activate theme');
        }

        View::redirect('/admin/themes');
    }

    public function install(string $themeName): void
    {
        if ($this->themeService->installTheme($themeName)) {
            Session::flash('success', 'Theme installed successfully');
        } else {
            Session::flash('error', 'Failed to install theme');
        }

        View::redirect('/admin/themes');
    }

    public function uninstall(string $themeName): void
    {
        if ($this->themeService->uninstallTheme($themeName)) {
            Session::flash('success', 'Theme uninstalled successfully');
        } else {
            Session::flash('error', 'Failed to uninstall theme');
        }

        View::redirect('/admin/themes');
    }

    public function delete(string $themeName): void
    {
        if ($this->themeService->deleteTheme($themeName)) {
            Session::flash('success', 'Theme deleted successfully');
        } else {
            Session::flash('error', 'Failed to delete theme');
        }

        View::redirect('/admin/themes');
    }

    public function customize(string $themeName): void
    {
        $data = [
            'title' => 'Customize Theme - ' . $themeName,
            'theme_name' => $themeName,
            'theme_info' => $this->themeService->getThemeInfo($themeName),
            'customizations' => $this->themeService->getThemeCustomizations($themeName),
            'editor' => $this->themeService->getThemeEditor($themeName)
        ];

        View::render('theme/customize', $data);
    }

    public function updateCustomizations(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::json(['success' => false, 'error' => 'Only POST method allowed'], 405);
            return;
        }

        $themeName = $_POST['theme'] ?? '';
        $customizations = $_POST['customizations'] ?? [];

        if (empty($themeName)) {
            View::json(['success' => false, 'error' => 'Theme name required'], 400);
            return;
        }

        $success = $this->themeService->updateThemeCustomizations($themeName, $customizations);
        
        View::json([
            'success' => $success,
            'message' => $success ? 'Customizations saved successfully' : 'Failed to save customizations'
        ]);
    }

    public function updateCSS(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::json(['success' => false, 'error' => 'Only POST method allowed'], 405);
            return;
        }

        $themeName = $_POST['theme'] ?? '';
        $css = $_POST['css'] ?? '';

        if (empty($themeName)) {
            View::json(['success' => false, 'error' => 'Theme name required'], 400);
            return;
        }

        $success = $this->themeService->updateThemeCSS($themeName, $css);
        
        View::json([
            'success' => $success,
            'message' => $success ? 'CSS updated successfully' : 'Failed to update CSS'
        ]);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::json(['success' => false, 'error' => 'Only POST method allowed'], 405);
            return;
        }

        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $author = $_POST['author'] ?? '';

        if (empty($name)) {
            View::json(['success' => false, 'error' => 'Theme name required'], 400);
            return;
        }

        $success = $this->themeService->createCustomTheme($name, $description, $author);
        
        View::json([
            'success' => $success,
            'message' => $success ? 'Custom theme created successfully' : 'Failed to create theme'
        ]);
    }

    public function preview(string $themeName): void
    {
        if (!$this->themeService->themeExists($themeName)) {
            View::redirect('/admin/themes');
            return;
        }

        $data = [
            'title' => 'Theme Preview - ' . $themeName,
            'theme_name' => $themeName,
            'theme_info' => $this->themeService->getThemeInfo($themeName),
            'preview_mode' => true
        ];

        View::render('theme/preview', $data);
    }

    public function export(string $themeName): void
    {
        if (!$this->themeService->themeExists($themeName)) {
            View::json(['success' => false, 'error' => 'Theme not found'], 404);
            return;
        }

        $themeInfo = $this->themeService->getThemeInfo($themeName);
        $themePath = $this->themeService->getThemePath($themeName);

        // Create zip file
        $zipFile = tempnam(sys_get_temp_dir(), 'theme_export_');
        $zip = new \ZipArchive();
        
        if ($zip->open($zipFile, \ZipArchive::CREATE) !== TRUE) {
            View::json(['success' => false, 'error' => 'Failed to create export file'], 500);
            return;
        }

        // Add theme files
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themePath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $relativePath = substr($file->getRealPath(), strlen($themePath) + 1);
                $zip->addFile($file->getRealPath(), $relativePath);
            }
        }

        $zip->close();

        // Send file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $themeName . '_theme.zip"');
        header('Content-Length: ' . filesize($zipFile));
        
        readfile($zipFile);
        unlink($zipFile);
    }

    public function import(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::json(['success' => false, 'error' => 'Only POST method allowed'], 405);
            return;
        }

        $file = $_FILES['theme_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            View::json(['success' => false, 'error' => 'Invalid file upload'], 400);
            return;
        }

        $zipFile = $file['tmp_name'];
        $zip = new \ZipArchive();
        
        if ($zip->open($zipFile) !== TRUE) {
            View::json(['success' => false, 'error' => 'Invalid zip file'], 400);
            return;
        }

        // Extract theme info
        $configContent = $zip->getFromName('theme.json');
        if (!$configContent) {
            View::json(['success' => false, 'error' => 'Invalid theme file - missing theme.json'], 400);
            return;
        }

        $config = json_decode($configContent, true);
        if (!$config || !isset($config['name'])) {
            View::json(['success' => false, 'error' => 'Invalid theme configuration'], 400);
            return;
        }

        $themeDirectory = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $config['name']));
        $themePath = $this->themeService->getThemePath($themeDirectory);

        // Create theme directory
        if (!is_dir($themePath)) {
            mkdir($themePath, 0755, true);
        }

        // Extract files
        $zip->extractTo($themePath);
        $zip->close();

        // Install theme
        $success = $this->themeService->installTheme($themeDirectory);
        
        View::json([
            'success' => $success,
            'message' => $success ? 'Theme imported successfully' : 'Failed to import theme'
        ]);
    }
}