<?php
declare(strict_types=1);

/**
 * Modern Forum - Plugin Controller
 * Handles plugin management and settings
 */

namespace Controllers;

use Core\Controller;
use Core\View;
use Services\PluginService;

class PluginController extends Controller
{
    private PluginService $pluginService;

    public function __construct()
    {
        parent::__construct();
        $this->pluginService = new PluginService();
    }

    public function index(): void
    {
        $data = [
            'title' => 'Plugin Management',
            'installed_plugins' => $this->pluginService->getInstalledPlugins(),
            'available_plugins' => $this->pluginService->getAvailablePlugins(),
            'loaded_plugins' => $this->pluginService->getLoadedPlugins()
        ];

        View::render('plugin/index', $data);
    }

    public function install(string $pluginDirectory): void
    {
        if ($this->pluginService->installPlugin($pluginDirectory)) {
            Session::flash('success', 'Plugin installed successfully');
        } else {
            Session::flash('error', 'Failed to install plugin');
        }

        View::redirect('/admin/plugins');
    }

    public function activate(string $pluginDirectory): void
    {
        if ($this->pluginService->activatePlugin($pluginDirectory)) {
            Session::flash('success', 'Plugin activated successfully');
        } else {
            Session::flash('error', 'Failed to activate plugin');
        }

        View::redirect('/admin/plugins');
    }

    public function deactivate(string $pluginDirectory): void
    {
        if ($this->pluginService->deactivatePlugin($pluginDirectory)) {
            Session::flash('success', 'Plugin deactivated successfully');
        } else {
            Session::flash('error', 'Failed to deactivate plugin');
        }

        View::redirect('/admin/plugins');
    }

    public function uninstall(string $pluginDirectory): void
    {
        if ($this->pluginService->uninstallPlugin($pluginDirectory)) {
            Session::flash('success', 'Plugin uninstalled successfully');
        } else {
            Session::flash('error', 'Failed to uninstall plugin');
        }

        View::redirect('/admin/plugins');
    }

    public function settings(string $pluginDirectory): void
    {
        $pluginInfo = $this->pluginService->getPluginInfo($pluginDirectory);
        if (!$pluginInfo) {
            View::redirect('/admin/plugins');
            return;
        }

        $data = [
            'title' => 'Plugin Settings - ' . $pluginInfo['name'],
            'plugin_directory' => $pluginDirectory,
            'plugin_info' => $pluginInfo,
            'settings' => $this->pluginService->getPluginSettings($pluginDirectory)
        ];

        View::render('plugin/settings', $data);
    }

    public function updateSettings(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::json(['success' => false, 'error' => 'Only POST method allowed'], 405);
            return;
        }

        $pluginDirectory = $_POST['plugin'] ?? '';
        $settings = $_POST['settings'] ?? [];

        if (empty($pluginDirectory)) {
            View::json(['success' => false, 'error' => 'Plugin directory required'], 400);
            return;
        }

        $success = $this->pluginService->updatePluginSettings($pluginDirectory, $settings);
        
        View::json([
            'success' => $success,
            'message' => $success ? 'Settings saved successfully' : 'Failed to save settings'
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
            View::json(['success' => false, 'error' => 'Plugin name required'], 400);
            return;
        }

        $success = $this->pluginService->createPlugin($name, $description, $author);
        
        View::json([
            'success' => $success,
            'message' => $success ? 'Plugin created successfully' : 'Failed to create plugin'
        ]);
    }

    public function hooks(): void
    {
        $data = [
            'title' => 'Plugin Hooks',
            'hooks' => $this->pluginService->getPluginHooks(),
            'loaded_plugins' => $this->pluginService->getLoadedPlugins()
        ];

        View::render('plugin/hooks', $data);
    }

    public function export(string $pluginDirectory): void
    {
        $pluginInfo = $this->pluginService->getPluginInfo($pluginDirectory);
        if (!$pluginInfo) {
            View::json(['success' => false, 'error' => 'Plugin not found'], 404);
            return;
        }

        $pluginPath = $this->pluginService->getPluginPath($pluginDirectory);

        // Create zip file
        $zipFile = tempnam(sys_get_temp_dir(), 'plugin_export_');
        $zip = new \ZipArchive();
        
        if ($zip->open($zipFile, \ZipArchive::CREATE) !== TRUE) {
            View::json(['success' => false, 'error' => 'Failed to create export file'], 500);
            return;
        }

        // Add plugin files
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginPath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $relativePath = substr($file->getRealPath(), strlen($pluginPath) + 1);
                $zip->addFile($file->getRealPath(), $relativePath);
            }
        }

        $zip->close();

        // Send file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $pluginDirectory . '_plugin.zip"');
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

        $file = $_FILES['plugin_file'] ?? null;

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

        // Extract plugin info
        $configContent = $zip->getFromName('plugin.json');
        if (!$configContent) {
            View::json(['success' => false, 'error' => 'Invalid plugin file - missing plugin.json'], 400);
            return;
        }

        $config = json_decode($configContent, true);
        if (!$config || !isset($config['name'])) {
            View::json(['success' => false, 'error' => 'Invalid plugin configuration'], 400);
            return;
        }

        $pluginDirectory = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $config['name']));
        $pluginPath = $this->pluginService->getPluginPath($pluginDirectory);

        // Create plugin directory
        if (!is_dir($pluginPath)) {
            mkdir($pluginPath, 0755, true);
        }

        // Extract files
        $zip->extractTo($pluginPath);
        $zip->close();

        // Install plugin
        $success = $this->pluginService->installPlugin($pluginDirectory);
        
        View::json([
            'success' => $success,
            'message' => $success ? 'Plugin imported successfully' : 'Failed to import plugin'
        ]);
    }
}