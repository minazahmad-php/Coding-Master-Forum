<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;

/**
 * Performance Optimization Service
 */
class PerformanceService
{
    private $db;
    private $logger;
    private $redis;
    private $cache;

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
        $this->logger = $app->get('logger');
        $this->redis = $app->get('redis');
        $this->cache = $app->get('cache');
    }

    /**
     * Cache data
     */
    public function cache($key, $data, $ttl = 3600)
    {
        try {
            if ($this->redis) {
                $this->redis->setex($key, $ttl, json_encode($data));
            } else {
                $this->cache->put($key, $data, $ttl);
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Cache set failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cached data
     */
    public function getCache($key)
    {
        try {
            if ($this->redis) {
                $data = $this->redis->get($key);
                return $data ? json_decode($data, true) : null;
            } else {
                return $this->cache->get($key);
            }
        } catch (\Exception $e) {
            $this->logger->error('Cache get failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete cache
     */
    public function deleteCache($key)
    {
        try {
            if ($this->redis) {
                $this->redis->del($key);
            } else {
                $this->cache->forget($key);
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Cache delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all cache
     */
    public function clearAllCache()
    {
        try {
            if ($this->redis) {
                $this->redis->flushdb();
            } else {
                $this->cache->flush();
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Cache clear failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Optimize database queries
     */
    public function optimizeDatabase()
    {
        try {
            // Analyze tables
            $tables = $this->db->fetchAll("SHOW TABLES");
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                $this->db->query("ANALYZE TABLE {$tableName}");
            }

            // Optimize tables
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                $this->db->query("OPTIMIZE TABLE {$tableName}");
            }

            $this->logger->info('Database optimization completed');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Database optimization failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create database indexes
     */
    public function createIndexes()
    {
        try {
            $indexes = [
                'users' => [
                    'idx_users_email' => 'email',
                    'idx_users_username' => 'username',
                    'idx_users_status' => 'status',
                    'idx_users_created_at' => 'created_at'
                ],
                'threads' => [
                    'idx_threads_forum_id' => 'forum_id',
                    'idx_threads_user_id' => 'user_id',
                    'idx_threads_status' => 'status',
                    'idx_threads_created_at' => 'created_at',
                    'idx_threads_pinned' => 'pinned',
                    'idx_threads_locked' => 'locked'
                ],
                'posts' => [
                    'idx_posts_thread_id' => 'thread_id',
                    'idx_posts_user_id' => 'user_id',
                    'idx_posts_status' => 'status',
                    'idx_posts_created_at' => 'created_at'
                ],
                'forums' => [
                    'idx_forums_status' => 'status',
                    'idx_forums_created_at' => 'created_at'
                ],
                'post_reactions' => [
                    'idx_post_reactions_post_id' => 'post_id',
                    'idx_post_reactions_user_id' => 'user_id',
                    'idx_post_reactions_type' => 'reaction_type'
                ],
                'thread_subscriptions' => [
                    'idx_thread_subscriptions_user_id' => 'user_id',
                    'idx_thread_subscriptions_thread_id' => 'thread_id'
                ],
                'user_online_status' => [
                    'idx_user_online_status_user_id' => 'user_id',
                    'idx_user_online_status_status' => 'status',
                    'idx_user_online_status_last_seen' => 'last_seen'
                ]
            ];

            foreach ($indexes as $table => $tableIndexes) {
                foreach ($tableIndexes as $indexName => $column) {
                    try {
                        $this->db->query("CREATE INDEX {$indexName} ON {$table} ({$column})");
                    } catch (\Exception $e) {
                        // Index might already exist
                        $this->logger->info("Index {$indexName} already exists or failed to create");
                    }
                }
            }

            $this->logger->info('Database indexes created');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Index creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Compress images
     */
    public function compressImage($imagePath, $quality = 80)
    {
        try {
            $imageInfo = getimagesize($imagePath);
            $mimeType = $imageInfo['mime'];

            switch ($mimeType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($imagePath);
                    imagejpeg($image, $imagePath, $quality);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($imagePath);
                    imagepng($image, $imagePath, 9);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($imagePath);
                    imagegif($image, $imagePath);
                    break;
                case 'image/webp':
                    $image = imagecreatefromwebp($imagePath);
                    imagewebp($image, $imagePath, $quality);
                    break;
                default:
                    return false;
            }

            imagedestroy($image);
            $this->logger->info("Image compressed: {$imagePath}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Image compression failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate WebP images
     */
    public function generateWebP($imagePath)
    {
        try {
            $imageInfo = getimagesize($imagePath);
            $mimeType = $imageInfo['mime'];
            $webpPath = str_replace(['.jpg', '.jpeg', '.png', '.gif'], '.webp', $imagePath);

            switch ($mimeType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($imagePath);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($imagePath);
                    break;
                default:
                    return false;
            }

            imagewebp($image, $webpPath, 80);
            imagedestroy($image);

            $this->logger->info("WebP image generated: {$webpPath}");
            return $webpPath;
        } catch (\Exception $e) {
            $this->logger->error('WebP generation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Minify CSS
     */
    public function minifyCSS($css)
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/;\s*/', ';', $css);
        $css = preg_replace('/\s*}\s*/', '}', $css);
        $css = preg_replace('/\s*,\s*/', ',', $css);
        $css = preg_replace('/\s*:\s*/', ':', $css);
        
        // Remove trailing semicolon
        $css = preg_replace('/;}/', '}', $css);
        
        return trim($css);
    }

    /**
     * Minify JavaScript
     */
    public function minifyJS($js)
    {
        // Remove single-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        $js = preg_replace('/\s*([{}();,=+\-*\/])\s*/', '$1', $js);
        
        return trim($js);
    }

    /**
     * Minify HTML
     */
    public function minifyHTML($html)
    {
        // Remove HTML comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        
        // Remove unnecessary whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        
        return trim($html);
    }

    /**
     * Enable Gzip compression
     */
    public function enableGzip()
    {
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
    }

    /**
     * Set cache headers
     */
    public function setCacheHeaders($ttl = 3600, $type = 'public')
    {
        $expires = gmdate('D, d M Y H:i:s', time() + $ttl) . ' GMT';
        
        header("Cache-Control: {$type}, max-age={$ttl}");
        header("Expires: {$expires}");
        header("Last-Modified: " . gmdate('D, d M Y H:i:s') . ' GMT');
    }

    /**
     * Set ETag header
     */
    public function setETag($content)
    {
        $etag = md5($content);
        header("ETag: \"{$etag}\"");
        
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === "\"{$etag}\"") {
            http_response_code(304);
            exit;
        }
    }

    /**
     * Lazy load images
     */
    public function lazyLoadImages($html)
    {
        // Add loading="lazy" to img tags
        $html = preg_replace('/<img([^>]*?)src=/', '<img$1loading="lazy" src=', $html);
        
        // Add data-src for lazy loading
        $html = preg_replace('/<img([^>]*?)src="([^"]*?)"/', '<img$1data-src="$2" src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%3E%3C/svg%3E"', $html);
        
        return $html;
    }

    /**
     * Preload critical resources
     */
    public function preloadResources($resources)
    {
        $preloadTags = '';
        
        foreach ($resources as $resource) {
            $as = $resource['as'] ?? 'style';
            $href = $resource['href'];
            $preloadTags .= "<link rel=\"preload\" href=\"{$href}\" as=\"{$as}\">\n";
        }
        
        return $preloadTags;
    }

    /**
     * Defer JavaScript
     */
    public function deferJavaScript($html)
    {
        // Add defer attribute to script tags
        $html = preg_replace('/<script([^>]*?)src=/', '<script$1defer src=', $html);
        
        return $html;
    }

    /**
     * Async JavaScript
     */
    public function asyncJavaScript($html)
    {
        // Add async attribute to script tags
        $html = preg_replace('/<script([^>]*?)src=/', '<script$1async src=', $html);
        
        return $html;
    }

    /**
     * Combine CSS files
     */
    public function combineCSS($files, $outputFile)
    {
        $combinedCSS = '';
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $combinedCSS .= file_get_contents($file) . "\n";
            }
        }
        
        $minifiedCSS = $this->minifyCSS($combinedCSS);
        file_put_contents($outputFile, $minifiedCSS);
        
        return $outputFile;
    }

    /**
     * Combine JavaScript files
     */
    public function combineJS($files, $outputFile)
    {
        $combinedJS = '';
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $combinedJS .= file_get_contents($file) . ";\n";
            }
        }
        
        $minifiedJS = $this->minifyJS($combinedJS);
        file_put_contents($outputFile, $minifiedJS);
        
        return $outputFile;
    }

    /**
     * Generate critical CSS
     */
    public function generateCriticalCSS($html, $css)
    {
        // This would use a service like Critical CSS Generator
        // For now, return the first 2000 characters of CSS
        return substr($css, 0, 2000);
    }

    /**
     * Inline critical CSS
     */
    public function inlineCriticalCSS($html, $criticalCSS)
    {
        $styleTag = "<style>{$criticalCSS}</style>";
        $html = str_replace('</head>', "{$styleTag}</head>", $html);
        
        return $html;
    }

    /**
     * Optimize database queries
     */
    public function optimizeQuery($query)
    {
        // Add query optimization logic here
        // This could include query rewriting, index suggestions, etc.
        return $query;
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics()
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'database_queries' => $this->getQueryCount(),
            'cache_hits' => $this->getCacheHits(),
            'cache_misses' => $this->getCacheMisses()
        ];
    }

    /**
     * Get query count
     */
    private function getQueryCount()
    {
        // This would track database queries
        return 0;
    }

    /**
     * Get cache hits
     */
    private function getCacheHits()
    {
        // This would track cache hits
        return 0;
    }

    /**
     * Get cache misses
     */
    private function getCacheMisses()
    {
        // This would track cache misses
        return 0;
    }

    /**
     * Log performance metrics
     */
    public function logPerformanceMetrics()
    {
        $metrics = $this->getPerformanceMetrics();
        
        $this->db->query(
            "INSERT INTO performance_logs (memory_usage, memory_peak, execution_time, database_queries, cache_hits, cache_misses, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $metrics['memory_usage'],
                $metrics['memory_peak'],
                $metrics['execution_time'],
                $metrics['database_queries'],
                $metrics['cache_hits'],
                $metrics['cache_misses']
            ]
        );
    }
}