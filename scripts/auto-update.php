<?php
/**
 * Auto Update Script for Forum Project
 * This script can be run via cron job for automatic updates
 */

class AutoUpdater
{
    private $config;
    private $logger;
    
    public function __construct()
    {
        $this->config = require_once __DIR__ . '/../config/app.php';
        $this->logger = new \App\Core\Logger($this->config);
    }
    
    /**
     * Check for updates
     */
    public function checkForUpdates()
    {
        try {
            $this->logger->info('Checking for updates...');
            
            // Check if update is available
            $updateAvailable = $this->isUpdateAvailable();
            
            if ($updateAvailable) {
                $this->logger->info('Update available, starting update process...');
                $this->performUpdate();
            } else {
                $this->logger->info('No updates available');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Update check failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if update is available
     */
    private function isUpdateAvailable()
    {
        // Check GitHub API for new releases
        $githubApi = 'https://api.github.com/repos/minazahmad-php/Coding-Master-Forum/releases/latest';
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Forum-AutoUpdater/1.0',
                    'Accept: application/vnd.github.v3+json'
                ]
            ]
        ]);
        
        $response = file_get_contents($githubApi, false, $context);
        
        if ($response === false) {
            return false;
        }
        
        $data = json_decode($response, true);
        $latestVersion = $data['tag_name'] ?? 'v1.0.0';
        
        // Get current version
        $currentVersion = $this->getCurrentVersion();
        
        return version_compare($latestVersion, $currentVersion, '>');
    }
    
    /**
     * Get current version
     */
    private function getCurrentVersion()
    {
        $versionFile = __DIR__ . '/../VERSION';
        
        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }
        
        return 'v1.0.0';
    }
    
    /**
     * Perform update
     */
    private function performUpdate()
    {
        try {
            // 1. Create backup
            $this->createBackup();
            
            // 2. Download latest release
            $this->downloadUpdate();
            
            // 3. Extract update
            $this->extractUpdate();
            
            // 4. Run migrations
            $this->runMigrations();
            
            // 5. Clear cache
            $this->clearCache();
            
            // 6. Update version
            $this->updateVersion();
            
            $this->logger->info('Update completed successfully');
            
        } catch (\Exception $e) {
            $this->logger->error('Update failed: ' . $e->getMessage());
            $this->rollbackUpdate();
        }
    }
    
    /**
     * Create backup
     */
    private function createBackup()
    {
        $this->logger->info('Creating backup...');
        
        $backupDir = __DIR__ . '/../storage/backups/' . date('Y-m-d_H-i-s');
        mkdir($backupDir, 0755, true);
        
        // Backup database
        $this->backupDatabase($backupDir);
        
        // Backup files
        $this->backupFiles($backupDir);
        
        $this->logger->info('Backup created: ' . $backupDir);
    }
    
    /**
     * Download update
     */
    private function downloadUpdate()
    {
        $this->logger->info('Downloading update...');
        
        $downloadUrl = 'https://github.com/minazahmad-php/Coding-Master-Forum/archive/main.zip';
        $zipFile = __DIR__ . '/../storage/temp/update.zip';
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Forum-AutoUpdater/1.0'
                ]
            ]
        ]);
        
        $zipContent = file_get_contents($downloadUrl, false, $context);
        
        if ($zipContent === false) {
            throw new \Exception('Failed to download update');
        }
        
        file_put_contents($zipFile, $zipContent);
        
        $this->logger->info('Update downloaded');
    }
    
    /**
     * Extract update
     */
    private function extractUpdate()
    {
        $this->logger->info('Extracting update...');
        
        $zipFile = __DIR__ . '/../storage/temp/update.zip';
        $extractDir = __DIR__ . '/../storage/temp/update';
        
        $zip = new ZipArchive();
        
        if ($zip->open($zipFile) === TRUE) {
            $zip->extractTo($extractDir);
            $zip->close();
            
            $this->logger->info('Update extracted');
        } else {
            throw new \Exception('Failed to extract update');
        }
    }
    
    /**
     * Run migrations
     */
    private function runMigrations()
    {
        $this->logger->info('Running migrations...');
        
        $migrateScript = __DIR__ . '/../migrate.php';
        
        if (file_exists($migrateScript)) {
            exec("php {$migrateScript}", $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \Exception('Migration failed');
            }
        }
        
        $this->logger->info('Migrations completed');
    }
    
    /**
     * Clear cache
     */
    private function clearCache()
    {
        $this->logger->info('Clearing cache...');
        
        $cacheDir = __DIR__ . '/../storage/cache';
        
        if (is_dir($cacheDir)) {
            $this->deleteDirectory($cacheDir);
        }
        
        $this->logger->info('Cache cleared');
    }
    
    /**
     * Update version
     */
    private function updateVersion()
    {
        $versionFile = __DIR__ . '/../VERSION';
        $newVersion = date('Y.m.d.H.i');
        
        file_put_contents($versionFile, $newVersion);
        
        $this->logger->info('Version updated to: ' . $newVersion);
    }
    
    /**
     * Rollback update
     */
    private function rollbackUpdate()
    {
        $this->logger->info('Rolling back update...');
        
        // Restore from backup
        $backupDir = $this->getLatestBackup();
        
        if ($backupDir) {
            $this->restoreFromBackup($backupDir);
            $this->logger->info('Update rolled back successfully');
        } else {
            $this->logger->error('No backup found for rollback');
        }
    }
    
    /**
     * Get latest backup
     */
    private function getLatestBackup()
    {
        $backupBaseDir = __DIR__ . '/../storage/backups';
        $backups = glob($backupBaseDir . '/*', GLOB_ONLYDIR);
        
        if (empty($backups)) {
            return null;
        }
        
        rsort($backups);
        return $backups[0];
    }
    
    /**
     * Restore from backup
     */
    private function restoreFromBackup($backupDir)
    {
        // Restore database
        $this->restoreDatabase($backupDir);
        
        // Restore files
        $this->restoreFiles($backupDir);
    }
    
    /**
     * Backup database
     */
    private function backupDatabase($backupDir)
    {
        $config = require_once __DIR__ . '/../config/database.php';
        
        $dbName = $config['database'];
        $dbUser = $config['username'];
        $dbPass = $config['password'];
        
        $backupFile = $backupDir . '/database.sql';
        
        $command = "mysqldump -u{$dbUser} -p{$dbPass} {$dbName} > {$backupFile}";
        exec($command);
    }
    
    /**
     * Restore database
     */
    private function restoreDatabase($backupDir)
    {
        $config = require_once __DIR__ . '/../config/database.php';
        
        $dbName = $config['database'];
        $dbUser = $config['username'];
        $dbPass = $config['password'];
        
        $backupFile = $backupDir . '/database.sql';
        
        if (file_exists($backupFile)) {
            $command = "mysql -u{$dbUser} -p{$dbPass} {$dbName} < {$backupFile}";
            exec($command);
        }
    }
    
    /**
     * Backup files
     */
    private function backupFiles($backupDir)
    {
        $filesDir = $backupDir . '/files';
        mkdir($filesDir, 0755, true);
        
        $sourceDir = __DIR__ . '/..';
        $excludeDirs = ['storage', 'vendor', 'node_modules', '.git'];
        
        $this->copyDirectory($sourceDir, $filesDir, $excludeDirs);
    }
    
    /**
     * Restore files
     */
    private function restoreFiles($backupDir)
    {
        $filesDir = $backupDir . '/files';
        
        if (is_dir($filesDir)) {
            $this->copyDirectory($filesDir, __DIR__ . '/..');
        }
    }
    
    /**
     * Copy directory
     */
    private function copyDirectory($source, $destination, $exclude = [])
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!in_array($item->getFilename(), $exclude)) {
                    mkdir($target, 0755, true);
                }
            } else {
                if (!in_array($item->getFilename(), $exclude)) {
                    copy($item, $target);
                }
            }
        }
    }
    
    /**
     * Delete directory
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        
        rmdir($dir);
    }
}

// Run auto-updater if called directly
if (php_sapi_name() === 'cli') {
    $updater = new AutoUpdater();
    $updater->checkForUpdates();
}