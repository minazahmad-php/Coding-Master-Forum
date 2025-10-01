<?php
declare(strict_types=1);

/**
 * Modern Forum - Plugin Management Service
 * Handles plugin installation, activation, and management
 */

namespace Services;

use Core\Database;
use Core\Logger;
use Core\Session;

class PluginService
{
    private Database $db;
    private Logger $logger;
    private string $pluginsPath;
    private array $loadedPlugins = [];
    private array $pluginHooks = [];
    private array $pluginCache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->pluginsPath = PLUGINS_PATH ?? (ROOT_PATH . '/plugins');
        $this->loadActivePlugins();
    }

    private function loadActivePlugins(): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM plugins 
                WHERE is_active = 1 AND is_installed = 1
                ORDER BY priority ASC, name ASC
            ");
            $stmt->execute();
            $plugins = $stmt->fetchAll();

            foreach ($plugins as $plugin) {
                $this->loadPlugin($plugin['directory']);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to load active plugins', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function loadPlugin(string $pluginDirectory): bool
    {
        $pluginPath = $this->pluginsPath . '/' . $pluginDirectory;
        $mainFile = $pluginPath . '/' . $pluginDirectory . '.php';

        if (!file_exists($mainFile)) {
            return false;
        }

        try {
            // Load plugin file
            require_once $mainFile;
            
            // Get plugin class name
            $className = $this->getPluginClassName($pluginDirectory);
            
            if (!class_exists($className)) {
                return false;
            }

            // Instantiate plugin
            $plugin = new $className();
            
            // Register plugin
            $this->loadedPlugins[$pluginDirectory] = [
                'instance' => $plugin,
                'path' => $pluginPath,
                'class' => $className
            ];

            // Register hooks
            $this->registerPluginHooks($pluginDirectory, $plugin);

            $this->logger->info('Plugin loaded', [
                'plugin' => $pluginDirectory
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to load plugin', [
                'plugin' => $pluginDirectory,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function getPluginClassName(string $pluginDirectory): string
    {
        return 'Plugin' . str_replace(' ', '', ucwords(str_replace('-', ' ', $pluginDirectory)));
    }

    private function registerPluginHooks(string $pluginDirectory, $plugin): void
    {
        if (!method_exists($plugin, 'getHooks')) {
            return;
        }

        $hooks = $plugin->getHooks();
        
        foreach ($hooks as $hook => $callback) {
            if (!isset($this->pluginHooks[$hook])) {
                $this->pluginHooks[$hook] = [];
            }
            
            $this->pluginHooks[$hook][] = [
                'plugin' => $pluginDirectory,
                'callback' => $callback,
                'priority' => $plugin->getPriority() ?? 10
            ];
        }

        // Sort hooks by priority
        foreach ($this->pluginHooks as $hook => &$callbacks) {
            usort($callbacks, function($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });
        }
    }

    public function getInstalledPlugins(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM plugins 
                WHERE is_installed = 1 
                ORDER BY is_active DESC, name ASC
            ");
            $stmt->execute();
            $plugins = $stmt->fetchAll();

            // Add runtime info
            foreach ($plugins as &$plugin) {
                $plugin['is_loaded'] = isset($this->loadedPlugins[$plugin['directory']]);
                $plugin['has_update'] = $this->checkForUpdates($plugin['directory']);
            }

            return $plugins;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get installed plugins', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getAvailablePlugins(): array
    {
        $plugins = [];
        $installedPlugins = $this->getInstalledPlugins();
        $installedDirs = array_column($installedPlugins, 'directory');

        if (!is_dir($this->pluginsPath)) {
            return $plugins;
        }

        $dirs = scandir($this->pluginsPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || in_array($dir, $installedDirs)) {
                continue;
            }

            $pluginPath = $this->pluginsPath . '/' . $dir;
            if (is_dir($pluginPath) && $this->isValidPlugin($pluginPath)) {
                $pluginInfo = $this->getPluginInfo($dir);
                if ($pluginInfo) {
                    $plugins[] = $pluginInfo;
                }
            }
        }

        return $plugins;
    }

    public function installPlugin(string $pluginDirectory): bool
    {
        $pluginPath = $this->pluginsPath . '/' . $pluginDirectory;
        
        if (!is_dir($pluginPath) || !$this->isValidPlugin($pluginPath)) {
            return false;
        }

        try {
            $pluginInfo = $this->getPluginInfo($pluginDirectory);
            if (!$pluginInfo) {
                return false;
            }

            // Check if plugin already exists
            $stmt = $this->db->prepare("SELECT id FROM plugins WHERE directory = ?");
            $stmt->execute([$pluginDirectory]);
            
            if ($stmt->fetch()) {
                // Update existing plugin
                $stmt = $this->db->prepare("
                    UPDATE plugins SET
                        name = ?, description = ?, author = ?, version = ?,
                        is_installed = 1, updated_at = ?
                    WHERE directory = ?
                ");
                $stmt->execute([
                    $pluginInfo['name'],
                    $pluginInfo['description'],
                    $pluginInfo['author'],
                    $pluginInfo['version'],
                    date('Y-m-d H:i:s'),
                    $pluginDirectory
                ]);
            } else {
                // Insert new plugin
                $stmt = $this->db->prepare("
                    INSERT INTO plugins (
                        name, directory, description, author, version,
                        is_installed, is_active, priority, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, 1, 0, ?, ?, ?)
                ");
                $stmt->execute([
                    $pluginInfo['name'],
                    $pluginDirectory,
                    $pluginInfo['description'],
                    $pluginInfo['author'],
                    $pluginInfo['version'],
                    $pluginInfo['priority'] ?? 10,
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s')
                ]);
            }

            // Run plugin installation
            $this->runPluginInstallation($pluginDirectory);

            $this->logger->info('Plugin installed', [
                'plugin' => $pluginDirectory,
                'name' => $pluginInfo['name']
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to install plugin', [
                'plugin' => $pluginDirectory,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function activatePlugin(string $pluginDirectory): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE plugins 
                SET is_active = 1, updated_at = ?
                WHERE directory = ? AND is_installed = 1
            ");
            $result = $stmt->execute([date('Y-m-d H:i:s'), $pluginDirectory]);

            if ($result) {
                // Load plugin
                $this->loadPlugin($pluginDirectory);
                
                $this->logger->info('Plugin activated', [
                    'plugin' => $pluginDirectory
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to activate plugin', [
                'plugin' => $pluginDirectory,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function deactivatePlugin(string $pluginDirectory): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE plugins 
                SET is_active = 0, updated_at = ?
                WHERE directory = ? AND is_installed = 1
            ");
            $result = $stmt->execute([date('Y-m-d H:i:s'), $pluginDirectory]);

            if ($result) {
                // Unload plugin
                $this->unloadPlugin($pluginDirectory);
                
                $this->logger->info('Plugin deactivated', [
                    'plugin' => $pluginDirectory
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to deactivate plugin', [
                'plugin' => $pluginDirectory,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function uninstallPlugin(string $pluginDirectory): bool
    {
        try {
            // Deactivate first
            $this->deactivatePlugin($pluginDirectory);

            // Run plugin uninstallation
            $this->runPluginUninstallation($pluginDirectory);

            // Remove from database
            $stmt = $this->db->prepare("DELETE FROM plugins WHERE directory = ?");
            $result = $stmt->execute([$pluginDirectory]);

            if ($result) {
                $this->logger->info('Plugin uninstalled', [
                    'plugin' => $pluginDirectory
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to uninstall plugin', [
                'plugin' => $pluginDirectory,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function unloadPlugin(string $pluginDirectory): void
    {
        if (isset($this->loadedPlugins[$pluginDirectory])) {
            // Remove hooks
            foreach ($this->pluginHooks as $hook => &$callbacks) {
                $this->pluginHooks[$hook] = array_filter($callbacks, function($callback) use ($pluginDirectory) {
                    return $callback['plugin'] !== $pluginDirectory;
                });
            }

            // Remove from loaded plugins
            unset($this->loadedPlugins[$pluginDirectory]);
        }
    }

    public function runHook(string $hookName, ...$args): array
    {
        $results = [];
        
        if (!isset($this->pluginHooks[$hookName])) {
            return $results;
        }

        foreach ($this->pluginHooks[$hookName] as $hook) {
            try {
                $plugin = $this->loadedPlugins[$hook['plugin']]['instance'];
                $callback = $hook['callback'];
                
                if (is_callable([$plugin, $callback])) {
                    $result = call_user_func_array([$plugin, $callback], $args);
                    $results[] = $result;
                }
            } catch (\Exception $e) {
                $this->logger->error('Plugin hook error', [
                    'hook' => $hookName,
                    'plugin' => $hook['plugin'],
                    'callback' => $hook['callback'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    public function addHook(string $hookName, callable $callback, int $priority = 10): void
    {
        if (!isset($this->pluginHooks[$hookName])) {
            $this->pluginHooks[$hookName] = [];
        }

        $this->pluginHooks[$hookName][] = [
            'plugin' => 'core',
            'callback' => $callback,
            'priority' => $priority
        ];

        // Sort by priority
        usort($this->pluginHooks[$hookName], function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
    }

    public function removeHook(string $hookName, callable $callback): void
    {
        if (!isset($this->pluginHooks[$hookName])) {
            return;
        }

        $this->pluginHooks[$hookName] = array_filter($this->pluginHooks[$hookName], function($hook) use ($callback) {
            return $hook['callback'] !== $callback;
        });
    }

    private function isValidPlugin(string $pluginPath): bool
    {
        $pluginName = basename($pluginPath);
        $mainFile = $pluginPath . '/' . $pluginName . '.php';
        
        return file_exists($mainFile);
    }

    private function getPluginInfo(string $pluginDirectory): ?array
    {
        $pluginPath = $this->pluginsPath . '/' . $pluginDirectory;
        $configFile = $pluginPath . '/plugin.json';

        if (!file_exists($configFile)) {
            return null;
        }

        try {
            $config = json_decode(file_get_contents($configFile), true);
            
            if (!$config) {
                return null;
            }

            return [
                'name' => $config['name'] ?? $pluginDirectory,
                'directory' => $pluginDirectory,
                'description' => $config['description'] ?? '',
                'author' => $config['author'] ?? 'Unknown',
                'version' => $config['version'] ?? '1.0.0',
                'priority' => $config['priority'] ?? 10,
                'dependencies' => $config['dependencies'] ?? [],
                'path' => $pluginPath,
                'config' => $config
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get plugin info', [
                'plugin' => $pluginDirectory,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function runPluginInstallation(string $pluginDirectory): void
    {
        if (isset($this->loadedPlugins[$pluginDirectory])) {
            $plugin = $this->loadedPlugins[$pluginDirectory]['instance'];
            
            if (method_exists($plugin, 'install')) {
                try {
                    $plugin->install();
                } catch (\Exception $e) {
                    $this->logger->error('Plugin installation hook failed', [
                        'plugin' => $pluginDirectory,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    private function runPluginUninstallation(string $pluginDirectory): void
    {
        if (isset($this->loadedPlugins[$pluginDirectory])) {
            $plugin = $this->loadedPlugins[$pluginDirectory]['instance'];
            
            if (method_exists($plugin, 'uninstall')) {
                try {
                    $plugin->uninstall();
                } catch (\Exception $e) {
                    $this->logger->error('Plugin uninstallation hook failed', [
                        'plugin' => $pluginDirectory,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    private function checkForUpdates(string $pluginDirectory): bool
    {
        // This would check against a plugin repository
        // For now, return false
        return false;
    }

    public function getPluginSettings(string $pluginDirectory): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT settings FROM plugins 
                WHERE directory = ? AND is_installed = 1
            ");
            $stmt->execute([$pluginDirectory]);
            $result = $stmt->fetch();
            
            if ($result && $result['settings']) {
                return json_decode($result['settings'], true) ?? [];
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to get plugin settings', [
                'plugin' => $pluginDirectory,
                'error' => $e->getMessage()
            ]);
        }

        return [];
    }

    public function updatePluginSettings(string $pluginDirectory, array $settings): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE plugins 
                SET settings = ?, updated_at = ?
                WHERE directory = ?
            ");
            
            $result = $stmt->execute([
                json_encode($settings),
                date('Y-m-d H:i:s'),
                $pluginDirectory
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update plugin settings', [
                'plugin' => $pluginDirectory,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getPluginHooks(): array
    {
        return $this->pluginHooks;
    }

    public function getLoadedPlugins(): array
    {
        return array_keys($this->loadedPlugins);
    }

    public function isPluginLoaded(string $pluginDirectory): bool
    {
        return isset($this->loadedPlugins[$pluginDirectory]);
    }

    public function getPluginInstance(string $pluginDirectory)
    {
        return $this->loadedPlugins[$pluginDirectory]['instance'] ?? null;
    }

    public function createPlugin(string $name, string $description, string $author): bool
    {
        try {
            $directory = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $name));
            $pluginPath = $this->pluginsPath . '/' . $directory;

            if (is_dir($pluginPath)) {
                return false; // Plugin already exists
            }

            // Create plugin directory
            mkdir($pluginPath, 0755, true);

            // Create plugin.json
            $config = [
                'name' => $name,
                'description' => $description,
                'author' => $author,
                'version' => '1.0.0',
                'priority' => 10,
                'dependencies' => [],
                'hooks' => [],
                'created_at' => date('Y-m-d H:i:s')
            ];

            file_put_contents($pluginPath . '/plugin.json', json_encode($config, JSON_PRETTY_PRINT));

            // Create main plugin file
            $className = $this->getPluginClassName($directory);
            $phpContent = "<?php
declare(strict_types=1);

/**
 * $name Plugin
 * $description
 * 
 * @author $author
 * @version 1.0.0
 */

class $className
{
    private string \$name = '$name';
    private string \$version = '1.0.0';
    private string \$author = '$author';

    public function __construct()
    {
        // Plugin initialization
    }

    public function getName(): string
    {
        return \$this->name;
    }

    public function getVersion(): string
    {
        return \$this->version;
    }

    public function getAuthor(): string
    {
        return \$this->author;
    }

    public function getPriority(): int
    {
        return 10;
    }

    public function getHooks(): array
    {
        return [
            // 'hook_name' => 'method_name',
        ];
    }

    public function install(): void
    {
        // Plugin installation logic
    }

    public function uninstall(): void
    {
        // Plugin uninstallation logic
    }

    public function activate(): void
    {
        // Plugin activation logic
    }

    public function deactivate(): void
    {
        // Plugin deactivation logic
    }
}";

            file_put_contents($pluginPath . '/' . $directory . '.php', $phpContent);

            // Install the plugin
            return $this->installPlugin($directory);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create plugin', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}