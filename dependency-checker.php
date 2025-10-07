<?php
/**
 * Smart Dependency Checker & Auto-Installer
 * Intelligently checks and installs all required dependencies
 */

class DependencyChecker {
    private $requirements = [];
    private $installLog = [];
    private $errors = [];
    
    public function __construct() {
        $this->initializeRequirements();
    }
    
    private function initializeRequirements() {
        $this->requirements = [
            'php' => [
                'name' => 'PHP 7.4+',
                'check' => 'checkPHPVersion',
                'install' => 'installPHP',
                'required' => true,
                'min_version' => '7.4.0'
            ],
            'composer' => [
                'name' => 'Composer',
                'check' => 'checkComposer',
                'install' => 'installComposer',
                'required' => true,
                'min_version' => '2.0.0'
            ],
            'nodejs' => [
                'name' => 'Node.js 14+',
                'check' => 'checkNodeJS',
                'install' => 'installNodeJS',
                'required' => true,
                'min_version' => '14.0.0'
            ],
            'npm' => [
                'name' => 'NPM 6+',
                'check' => 'checkNPM',
                'install' => 'installNPM',
                'required' => true,
                'min_version' => '6.0.0'
            ],
            'php_extensions' => [
                'name' => 'PHP Extensions',
                'check' => 'checkPHPExtensions',
                'install' => 'installPHPExtensions',
                'required' => true,
                'extensions' => ['pdo', 'pdo_mysql', 'pdo_sqlite', 'json', 'mbstring', 'openssl', 'curl', 'gd', 'zip', 'xml']
            ],
            'system_packages' => [
                'name' => 'System Packages',
                'check' => 'checkSystemPackages',
                'install' => 'installSystemPackages',
                'required' => true,
                'packages' => ['unzip', 'curl', 'wget', 'git']
            ],
            'web_server' => [
                'name' => 'Web Server',
                'check' => 'checkWebServer',
                'install' => 'installWebServer',
                'required' => false,
                'servers' => ['apache', 'nginx']
            ]
        ];
    }
    
    public function checkAllDependencies() {
        echo "🔍 Checking all dependencies...\n\n";
        
        $results = [];
        $canInstall = true;
        
        foreach ($this->requirements as $key => $requirement) {
            echo "Checking {$requirement['name']}... ";
            
            $checkResult = $this->{$requirement['check']}();
            $results[$key] = $checkResult;
            
            if ($checkResult['installed']) {
                echo "✅ {$checkResult['version']}\n";
            } else {
                echo "❌ Not installed\n";
                
                if ($requirement['required']) {
                    echo "   🔧 Attempting to install...\n";
                    $installResult = $this->{$requirement['install']}();
                    
                    if ($installResult['success']) {
                        echo "   ✅ Installed successfully!\n";
                        $results[$key]['installed'] = true;
                        $results[$key]['version'] = $installResult['version'];
                    } else {
                        echo "   ❌ Installation failed: {$installResult['error']}\n";
                        $canInstall = false;
                        $this->errors[] = "Failed to install {$requirement['name']}: {$installResult['error']}";
                    }
                } else {
                    echo "   ⚠️ Optional - skipping\n";
                }
            }
            
            echo "\n";
        }
        
        return [
            'results' => $results,
            'canInstall' => $canInstall,
            'errors' => $this->errors
        ];
    }
    
    // PHP Version Check
    private function checkPHPVersion() {
        $version = PHP_VERSION;
        $required = $this->requirements['php']['min_version'];
        
        return [
            'installed' => version_compare($version, $required, '>='),
            'version' => $version,
            'required' => $required
        ];
    }
    
    private function installPHP() {
        return [
            'success' => false,
            'error' => 'PHP installation requires system administrator privileges. Please install PHP 7.4+ manually.',
            'version' => null
        ];
    }
    
    // Composer Check
    private function checkComposer() {
        $composerPath = $this->findExecutable('composer');
        
        if ($composerPath) {
            $version = $this->getVersion($composerPath, '--version');
            return [
                'installed' => true,
                'version' => $version,
                'path' => $composerPath
            ];
        }
        
        return [
            'installed' => false,
            'version' => null,
            'path' => null
        ];
    }
    
    private function installComposer() {
        echo "   📥 Downloading Composer installer...\n";
        
        try {
            // Download Composer installer
            $installer = file_get_contents('https://getcomposer.org/installer');
            if ($installer === false) {
                throw new Exception('Failed to download Composer installer');
            }
            
            file_put_contents('composer-installer.php', $installer);
            
            // Run installer
            $output = [];
            $returnCode = 0;
            exec('php composer-installer.php 2>&1', $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception('Composer installer failed: ' . implode("\n", $output));
            }
            
            // Clean up
            unlink('composer-installer.php');
            
            // Verify installation
            $version = $this->getVersion('./composer.phar', '--version');
            
            return [
                'success' => true,
                'version' => $version,
                'path' => './composer.phar'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'version' => null
            ];
        }
    }
    
    // Node.js Check
    private function checkNodeJS() {
        $nodePath = $this->findExecutable('node');
        
        if ($nodePath) {
            $version = $this->getVersion($nodePath, '--version');
            $version = str_replace('v', '', $version);
            
            return [
                'installed' => version_compare($version, $this->requirements['nodejs']['min_version'], '>='),
                'version' => 'v' . $version,
                'path' => $nodePath
            ];
        }
        
        return [
            'installed' => false,
            'version' => null,
            'path' => null
        ];
    }
    
    private function installNodeJS() {
        echo "   📥 Installing Node.js...\n";
        
        $os = php_uname('s');
        
        if ($os === 'Linux') {
            return $this->installNodeJSLinux();
        } elseif ($os === 'Darwin') {
            return $this->installNodeJSMac();
        } elseif (strpos($os, 'Windows') !== false) {
            return $this->installNodeJSWindows();
        } else {
            return [
                'success' => false,
                'error' => "Node.js installation not supported on {$os}",
                'version' => null
            ];
        }
    }
    
    private function installNodeJSLinux() {
        try {
            // Update package list
            $this->runCommand('sudo apt-get update');
            
            // Install Node.js 18.x
            $commands = [
                'curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -',
                'sudo apt-get install -y nodejs'
            ];
            
            foreach ($commands as $cmd) {
                $this->runCommand($cmd);
            }
            
            // Verify installation
            $version = $this->getVersion('node', '--version');
            
            return [
                'success' => true,
                'version' => $version,
                'path' => 'node'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'version' => null
            ];
        }
    }
    
    private function installNodeJSMac() {
        try {
            // Check if Homebrew is installed
            $homebrewPath = $this->findExecutable('brew');
            
            if (!$homebrewPath) {
                // Install Homebrew first
                $this->runCommand('/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"');
            }
            
            // Install Node.js via Homebrew
            $this->runCommand('brew install node');
            
            // Verify installation
            $version = $this->getVersion('node', '--version');
            
            return [
                'success' => true,
                'version' => $version,
                'path' => 'node'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'version' => null
            ];
        }
    }
    
    private function installNodeJSWindows() {
        return [
            'success' => false,
            'error' => 'Please download and install Node.js from https://nodejs.org/',
            'version' => null
        ];
    }
    
    // NPM Check
    private function checkNPM() {
        $npmPath = $this->findExecutable('npm');
        
        if ($npmPath) {
            $version = $this->getVersion($npmPath, '--version');
            
            return [
                'installed' => version_compare($version, $this->requirements['npm']['min_version'], '>='),
                'version' => $version,
                'path' => $npmPath
            ];
        }
        
        return [
            'installed' => false,
            'version' => null,
            'path' => null
        ];
    }
    
    private function installNPM() {
        // NPM usually comes with Node.js
        $nodeCheck = $this->checkNodeJS();
        
        if ($nodeCheck['installed']) {
            $npmVersion = $this->getVersion('npm', '--version');
            return [
                'success' => true,
                'version' => $npmVersion,
                'path' => 'npm'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'NPM requires Node.js to be installed first',
            'version' => null
        ];
    }
    
    // PHP Extensions Check
    private function checkPHPExtensions() {
        $extensions = $this->requirements['php_extensions']['extensions'];
        $installed = [];
        $missing = [];
        
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $installed[] = $ext;
            } else {
                $missing[] = $ext;
            }
        }
        
        return [
            'installed' => empty($missing),
            'installed_extensions' => $installed,
            'missing_extensions' => $missing,
            'total' => count($extensions)
        ];
    }
    
    private function installPHPExtensions() {
        $missing = $this->checkPHPExtensions()['missing_extensions'];
        
        if (empty($missing)) {
            return [
                'success' => true,
                'version' => 'All extensions installed',
                'path' => null
            ];
        }
        
        $os = php_uname('s');
        
        if ($os === 'Linux') {
            return $this->installPHPExtensionsLinux($missing);
        } else {
            return [
                'success' => false,
                'error' => 'PHP extensions installation not supported on ' . $os,
                'version' => null
            ];
        }
    }
    
    private function installPHPExtensionsLinux($missing) {
        try {
            $packages = [];
            foreach ($missing as $ext) {
                switch ($ext) {
                    case 'pdo':
                    case 'pdo_mysql':
                        $packages[] = 'php7.4-mysql';
                        break;
                    case 'pdo_sqlite':
                        $packages[] = 'php7.4-sqlite3';
                        break;
                    case 'json':
                        $packages[] = 'php7.4-json';
                        break;
                    case 'mbstring':
                        $packages[] = 'php7.4-mbstring';
                        break;
                    case 'openssl':
                        $packages[] = 'php7.4-openssl';
                        break;
                    case 'curl':
                        $packages[] = 'php7.4-curl';
                        break;
                    case 'gd':
                        $packages[] = 'php7.4-gd';
                        break;
                    case 'zip':
                        $packages[] = 'php7.4-zip';
                        break;
                    case 'xml':
                        $packages[] = 'php7.4-xml';
                        break;
                }
            }
            
            if (!empty($packages)) {
                $packages = array_unique($packages);
                $this->runCommand('sudo apt-get install -y ' . implode(' ', $packages));
            }
            
            return [
                'success' => true,
                'version' => 'Extensions installed',
                'path' => null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'version' => null
            ];
        }
    }
    
    // System Packages Check
    private function checkSystemPackages() {
        $packages = $this->requirements['system_packages']['packages'];
        $installed = [];
        $missing = [];
        
        foreach ($packages as $package) {
            if ($this->findExecutable($package)) {
                $installed[] = $package;
            } else {
                $missing[] = $package;
            }
        }
        
        return [
            'installed' => empty($missing),
            'installed_packages' => $installed,
            'missing_packages' => $missing,
            'total' => count($packages)
        ];
    }
    
    private function installSystemPackages() {
        $missing = $this->checkSystemPackages()['missing_packages'];
        
        if (empty($missing)) {
            return [
                'success' => true,
                'version' => 'All packages installed',
                'path' => null
            ];
        }
        
        $os = php_uname('s');
        
        if ($os === 'Linux') {
            return $this->installSystemPackagesLinux($missing);
        } else {
            return [
                'success' => false,
                'error' => 'System packages installation not supported on ' . $os,
                'version' => null
            ];
        }
    }
    
    private function installSystemPackagesLinux($missing) {
        try {
            $this->runCommand('sudo apt-get update');
            $this->runCommand('sudo apt-get install -y ' . implode(' ', $missing));
            
            return [
                'success' => true,
                'version' => 'Packages installed',
                'path' => null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'version' => null
            ];
        }
    }
    
    // Web Server Check
    private function checkWebServer() {
        $servers = $this->requirements['web_server']['servers'];
        $detected = [];
        
        // Check Apache
        if (function_exists('apache_get_version') || 
            (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false)) {
            $detected[] = 'apache';
        }
        
        // Check Nginx
        if (strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'nginx') !== false) {
            $detected[] = 'nginx';
        }
        
        return [
            'installed' => !empty($detected),
            'detected_servers' => $detected,
            'available_servers' => $servers
        ];
    }
    
    private function installWebServer() {
        // Web server installation is complex and system-specific
        return [
            'success' => false,
            'error' => 'Web server installation requires manual setup. Please install Apache or Nginx.',
            'version' => null
        ];
    }
    
    // Helper Methods
    private function findExecutable($command) {
        $paths = [
            '/usr/local/bin',
            '/usr/bin',
            '/bin',
            '/opt/homebrew/bin',
            '/home/ubuntu/.local/bin',
            getcwd() . '/vendor/bin'
        ];
        
        foreach ($paths as $path) {
            $fullPath = $path . '/' . $command;
            if (is_executable($fullPath)) {
                return $fullPath;
            }
        }
        
        // Check if command is in PATH
        $output = [];
        $returnCode = 0;
        exec("which $command 2>/dev/null", $output, $returnCode);
        
        if ($returnCode === 0 && !empty($output[0])) {
            return $output[0];
        }
        
        return null;
    }
    
    private function getVersion($command, $versionFlag = '--version') {
        $output = [];
        $returnCode = 0;
        exec("$command $versionFlag 2>&1", $output, $returnCode);
        
        if ($returnCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }
        
        return 'Unknown';
    }
    
    private function runCommand($command) {
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Command failed: $command\nOutput: " . implode("\n", $output));
        }
        
        return $output;
    }
    
    public function getInstallationReport() {
        $results = $this->checkAllDependencies();
        
        $report = [
            'summary' => [
                'total_requirements' => count($this->requirements),
                'installed' => 0,
                'missing' => 0,
                'can_install' => $results['canInstall']
            ],
            'details' => [],
            'errors' => $results['errors']
        ];
        
        foreach ($results['results'] as $key => $result) {
            if ($result['installed']) {
                $report['summary']['installed']++;
            } else {
                $report['summary']['missing']++;
            }
            
            $report['details'][$key] = [
                'name' => $this->requirements[$key]['name'],
                'installed' => $result['installed'],
                'version' => $result['version'] ?? 'N/A',
                'required' => $this->requirements[$key]['required']
            ];
        }
        
        return $report;
    }
}

// Run if called directly
if (php_sapi_name() === 'cli') {
    $checker = new DependencyChecker();
    $report = $checker->getInstallationReport();
    
    echo "📊 Installation Report\n";
    echo "====================\n\n";
    
    echo "Summary:\n";
    echo "- Total Requirements: {$report['summary']['total_requirements']}\n";
    echo "- Installed: {$report['summary']['installed']}\n";
    echo "- Missing: {$report['summary']['missing']}\n";
    echo "- Can Install: " . ($report['summary']['can_install'] ? 'Yes' : 'No') . "\n\n";
    
    echo "Details:\n";
    foreach ($report['details'] as $key => $detail) {
        $status = $detail['installed'] ? '✅' : '❌';
        $required = $detail['required'] ? '(Required)' : '(Optional)';
        echo "{$status} {$detail['name']} {$required} - {$detail['version']}\n";
    }
    
    if (!empty($report['errors'])) {
        echo "\nErrors:\n";
        foreach ($report['errors'] as $error) {
            echo "❌ $error\n";
        }
    }
    
    exit($report['summary']['can_install'] ? 0 : 1);
}
?>