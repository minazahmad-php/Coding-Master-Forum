<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

/**
 * Advanced Optimization Service
 */
class OptimizationService
{
    private $db;
    private $logger;
    
    public function __construct()
    {
        $this->db = new Database();
        $this->logger = new Logger();
    }
    
    /**
     * Run complete optimization
     */
    public function optimizeAll()
    {
        $results = [];
        
        try {
            $results['database'] = $this->optimizeDatabase();
            $results['cache'] = $this->optimizeCache();
            $results['files'] = $this->optimizeFiles();
            $results['images'] = $this->optimizeImages();
            $results['css'] = $this->optimizeCSS();
            $results['js'] = $this->optimizeJS();
            $results['html'] = $this->optimizeHTML();
            
            $this->logger->info('Complete optimization finished', $results);
            
        } catch (\Exception $e) {
            $this->logger->error('Optimization failed: ' . $e->getMessage());
            throw $e;
        }
        
        return $results;
    }
    
    /**
     * Optimize database
     */
    public function optimizeDatabase()
    {
        $results = [];
        
        try {
            // Analyze tables
            $tables = $this->db->fetchAll("SHOW TABLES");
            
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                
                // Optimize table
                $this->db->query("OPTIMIZE TABLE `{$tableName}`");
                
                // Analyze table
                $this->db->query("ANALYZE TABLE `{$tableName}`");
                
                $results['optimized'][] = $tableName;
            }
            
            // Create missing indexes
            $this->createMissingIndexes();
            
            $results['status'] = 'success';
            $results['message'] = 'Database optimized successfully';
            
        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['message'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Create missing indexes
     */
    private function createMissingIndexes()
    {
        $indexes = [
            'users' => [
                'email' => 'CREATE INDEX idx_users_email ON users(email)',
                'username' => 'CREATE INDEX idx_users_username ON users(username)',
                'status' => 'CREATE INDEX idx_users_status ON users(status)',
                'created_at' => 'CREATE INDEX idx_users_created_at ON users(created_at)'
            ],
            'forums' => [
                'status' => 'CREATE INDEX idx_forums_status ON forums(status)',
                'created_at' => 'CREATE INDEX idx_forums_created_at ON forums(created_at)'
            ],
            'threads' => [
                'forum_id' => 'CREATE INDEX idx_threads_forum_id ON threads(forum_id)',
                'user_id' => 'CREATE INDEX idx_threads_user_id ON threads(user_id)',
                'status' => 'CREATE INDEX idx_threads_status ON threads(status)',
                'created_at' => 'CREATE INDEX idx_threads_created_at ON threads(created_at)',
                'views' => 'CREATE INDEX idx_threads_views ON threads(views)'
            ],
            'posts' => [
                'thread_id' => 'CREATE INDEX idx_posts_thread_id ON posts(thread_id)',
                'user_id' => 'CREATE INDEX idx_posts_user_id ON posts(user_id)',
                'status' => 'CREATE INDEX idx_posts_status ON posts(status)',
                'created_at' => 'CREATE INDEX idx_posts_created_at ON posts(created_at)'
            ]
        ];
        
        foreach ($indexes as $table => $tableIndexes) {
            foreach ($tableIndexes as $indexName => $sql) {
                try {
                    $this->db->query($sql);
                } catch (\Exception $e) {
                    // Index might already exist
                    if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                        $this->logger->warning("Failed to create index {$indexName}: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    /**
     * Optimize cache
     */
    public function optimizeCache()
    {
        $results = [];
        
        try {
            // Clear old cache files
            $cacheDir = STORAGE_PATH . '/cache';
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*.cache');
                $deleted = 0;
                
                foreach ($files as $file) {
                    if (filemtime($file) < time() - 3600) { // Older than 1 hour
                        unlink($file);
                        $deleted++;
                    }
                }
                
                $results['deleted_files'] = $deleted;
            }
            
            $results['status'] = 'success';
            $results['message'] = 'Cache optimized successfully';
            
        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['message'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Optimize files
     */
    public function optimizeFiles()
    {
        $results = [];
        
        try {
            // Remove duplicate files
            $duplicates = $this->findDuplicateFiles();
            $removed = 0;
            
            foreach ($duplicates as $duplicate) {
                if (count($duplicate) > 1) {
                    // Keep the first file, remove others
                    for ($i = 1; $i < count($duplicate); $i++) {
                        unlink($duplicate[$i]);
                        $removed++;
                    }
                }
            }
            
            $results['duplicates_removed'] = $removed;
            $results['status'] = 'success';
            $results['message'] = 'Files optimized successfully';
            
        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['message'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Find duplicate files
     */
    private function findDuplicateFiles()
    {
        $files = [];
        $duplicates = [];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator('.', \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $hash = md5_file($file->getPathname());
                $files[$hash][] = $file->getPathname();
            }
        }
        
        foreach ($files as $hash => $fileList) {
            if (count($fileList) > 1) {
                $duplicates[] = $fileList;
            }
        }
        
        return $duplicates;
    }
    
    /**
     * Optimize images
     */
    public function optimizeImages()
    {
        $results = [];
        
        try {
            $imageDir = 'public/images';
            $optimized = 0;
            
            if (is_dir($imageDir)) {
                $images = glob($imageDir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                
                foreach ($images as $image) {
                    if ($this->optimizeImage($image)) {
                        $optimized++;
                    }
                }
            }
            
            $results['optimized_images'] = $optimized;
            $results['status'] = 'success';
            $results['message'] = 'Images optimized successfully';
            
        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['message'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Optimize single image
     */
    private function optimizeImage($imagePath)
    {
        try {
            $info = getimagesize($imagePath);
            if (!$info) return false;
            
            $mime = $info['mime'];
            $width = $info[0];
            $height = $info[1];
            
            // Skip if already small
            if ($width <= 800 && $height <= 600) return true;
            
            // Calculate new dimensions
            $maxWidth = 800;
            $maxHeight = 600;
            
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = intval($width * $ratio);
            $newHeight = intval($height * $ratio);
            
            // Create new image
            $source = null;
            switch ($mime) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($imagePath);
                    break;
                case 'image/gif':
                    $source = imagecreatefromgif($imagePath);
                    break;
            }
            
            if (!$source) return false;
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Save optimized image
            switch ($mime) {
                case 'image/jpeg':
                    imagejpeg($resized, $imagePath, 85);
                    break;
                case 'image/png':
                    imagepng($resized, $imagePath, 8);
                    break;
                case 'image/gif':
                    imagegif($resized, $imagePath);
                    break;
            }
            
            imagedestroy($source);
            imagedestroy($resized);
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Optimize CSS
     */
    public function optimizeCSS()
    {
        $results = [];
        
        try {
            $cssDir = 'public/assets/css';
            $optimized = 0;
            
            if (is_dir($cssDir)) {
                $cssFiles = glob($cssDir . '/*.css');
                
                foreach ($cssFiles as $cssFile) {
                    if ($this->minifyCSS($cssFile)) {
                        $optimized++;
                    }
                }
            }
            
            $results['optimized_css'] = $optimized;
            $results['status'] = 'success';
            $results['message'] = 'CSS optimized successfully';
            
        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['message'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Minify CSS file
     */
    private function minifyCSS($filePath)
    {
        try {
            $css = file_get_contents($filePath);
            
            // Remove comments
            $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
            
            // Remove whitespace
            $css = preg_replace('/\s+/', ' ', $css);
            $css = str_replace(['; ', ' {', '{ ', ' }', '} ', ': ', ' ,', ', '], [';', '{', '{', '}', '}', ':', ',', ','], $css);
            
            // Remove unnecessary spaces
            $css = str_replace(['  ', '   ', '    '], ' ', $css);
            $css = trim($css);
            
            file_put_contents($filePath, $css);
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Optimize JavaScript
     */
    public function optimizeJS()
    {
        $results = [];
        
        try {
            $jsDir = 'public/assets/js';
            $optimized = 0;
            
            if (is_dir($jsDir)) {
                $jsFiles = glob($jsDir . '/*.js');
                
                foreach ($jsFiles as $jsFile) {
                    if ($this->minifyJS($jsFile)) {
                        $optimized++;
                    }
                }
            }
            
            $results['optimized_js'] = $optimized;
            $results['status'] = 'success';
            $results['message'] = 'JavaScript optimized successfully';
            
        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['message'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Minify JavaScript file
     */
    private function minifyJS($filePath)
    {
        try {
            $js = file_get_contents($filePath);
            
            // Remove single-line comments
            $js = preg_replace('~//[^\r\n]*~', '', $js);
            
            // Remove multi-line comments
            $js = preg_replace('~/\*.*?\*/~s', '', $js);
            
            // Remove unnecessary whitespace
            $js = preg_replace('/\s+/', ' ', $js);
            $js = str_replace([' = ', ' == ', ' != ', ' <= ', ' >= ', ' < ', ' > ', ' && ', ' || ', ' + ', ' - ', ' * ', ' / ', ' % '], ['=', '==', '!=', '<=', '>=', '<', '>', '&&', '||', '+', '-', '*', '/', '%'], $js);
            
            // Remove unnecessary spaces
            $js = str_replace(['  ', '   ', '    '], ' ', $js);
            $js = trim($js);
            
            file_put_contents($filePath, $js);
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Optimize HTML
     */
    public function optimizeHTML()
    {
        $results = [];
        
        try {
            $viewDir = 'resources/views';
            $optimized = 0;
            
            if (is_dir($viewDir)) {
                $htmlFiles = glob($viewDir . '/**/*.php');
                
                foreach ($htmlFiles as $htmlFile) {
                    if ($this->minifyHTML($htmlFile)) {
                        $optimized++;
                    }
                }
            }
            
            $results['optimized_html'] = $optimized;
            $results['status'] = 'success';
            $results['message'] = 'HTML optimized successfully';
            
        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['message'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Minify HTML file
     */
    private function minifyHTML($filePath)
    {
        try {
            $html = file_get_contents($filePath);
            
            // Remove HTML comments (but keep PHP comments)
            $html = preg_replace('/<!--(?!\s*\[if|\s*<!\[endif).*?-->/s', '', $html);
            
            // Remove unnecessary whitespace
            $html = preg_replace('/\s+/', ' ', $html);
            $html = str_replace(['> ', ' <'], ['>', '<'], $html);
            
            // Remove unnecessary spaces
            $html = str_replace(['  ', '   ', '    '], ' ', $html);
            $html = trim($html);
            
            file_put_contents($filePath, $html);
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get optimization report
     */
    public function getOptimizationReport()
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $this->getDatabaseStats(),
            'files' => $this->getFileStats(),
            'performance' => $this->getPerformanceStats()
        ];
        
        return $report;
    }
    
    /**
     * Get database statistics
     */
    private function getDatabaseStats()
    {
        try {
            $tables = $this->db->fetchAll("SHOW TABLE STATUS");
            $totalSize = 0;
            $totalRows = 0;
            
            foreach ($tables as $table) {
                $totalSize += $table['Data_length'] + $table['Index_length'];
                $totalRows += $table['Rows'];
            }
            
            return [
                'total_tables' => count($tables),
                'total_size' => $totalSize,
                'total_rows' => $totalRows,
                'average_row_size' => $totalRows > 0 ? $totalSize / $totalRows : 0
            ];
            
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get file statistics
     */
    private function getFileStats()
    {
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'php_files' => 0,
            'css_files' => 0,
            'js_files' => 0,
            'image_files' => 0
        ];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator('.', \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $stats['total_files']++;
                $stats['total_size'] += $file->getSize();
                
                $extension = strtolower($file->getExtension());
                switch ($extension) {
                    case 'php':
                        $stats['php_files']++;
                        break;
                    case 'css':
                        $stats['css_files']++;
                        break;
                    case 'js':
                        $stats['js_files']++;
                        break;
                    case 'jpg':
                    case 'jpeg':
                    case 'png':
                    case 'gif':
                    case 'svg':
                        $stats['image_files']++;
                        break;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Get performance statistics
     */
    private function getPerformanceStats()
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'load_average' => function_exists('sys_getloadavg') ? sys_getloadavg() : null
        ];
    }
}