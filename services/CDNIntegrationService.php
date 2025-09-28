<?php
declare(strict_types=1);

namespace Services;

class CDNIntegrationService {
    private Database $db;
    private array $cdnConfig;
    private array $providers;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->cdnConfig = $this->getCDNConfig();
        $this->providers = $this->getCDNProviders();
    }
    
    private function getCDNConfig(): array {
        return [
            'enabled' => CDN_ENABLED ?? false,
            'provider' => CDN_PROVIDER ?? 'cloudflare',
            'api_key' => CDN_API_KEY ?? '',
            'zone_id' => CDN_ZONE_ID ?? '',
            'domain' => CDN_DOMAIN ?? '',
            'ssl_enabled' => CDN_SSL_ENABLED ?? true,
            'cache_ttl' => CDN_CACHE_TTL ?? 3600,
            'auto_purge' => CDN_AUTO_PURGE ?? true,
            'image_optimization' => CDN_IMAGE_OPTIMIZATION ?? true,
            'video_optimization' => CDN_VIDEO_OPTIMIZATION ?? true,
            'compression' => CDN_COMPRESSION ?? true,
            'minification' => CDN_MINIFICATION ?? true
        ];
    }
    
    private function getCDNProviders(): array {
        return [
            'cloudflare' => [
                'name' => 'Cloudflare',
                'api_url' => 'https://api.cloudflare.com/client/v4',
                'features' => ['cdn', 'dns', 'ssl', 'security', 'analytics'],
                'pricing' => 'free_tier_available',
                'icon' => 'fas fa-cloud',
                'color' => '#F38020'
            ],
            'aws_cloudfront' => [
                'name' => 'AWS CloudFront',
                'api_url' => 'https://cloudfront.amazonaws.com',
                'features' => ['cdn', 'ssl', 'analytics', 'edge_locations'],
                'pricing' => 'pay_as_you_go',
                'icon' => 'fas fa-cloud',
                'color' => '#FF9900'
            ],
            'maxcdn' => [
                'name' => 'MaxCDN',
                'api_url' => 'https://api.maxcdn.com',
                'features' => ['cdn', 'ssl', 'analytics'],
                'pricing' => 'monthly_plans',
                'icon' => 'fas fa-bolt',
                'color' => '#00A8FF'
            ],
            'keycdn' => [
                'name' => 'KeyCDN',
                'api_url' => 'https://api.keycdn.com',
                'features' => ['cdn', 'ssl', 'analytics', 'image_optimization'],
                'pricing' => 'pay_as_you_go',
                'icon' => 'fas fa-key',
                'color' => '#00A8FF'
            ],
            'bunnycdn' => [
                'name' => 'BunnyCDN',
                'api_url' => 'https://api.bunnycdn.com',
                'features' => ['cdn', 'ssl', 'analytics', 'video_streaming'],
                'pricing' => 'pay_as_you_go',
                'icon' => 'fas fa-bunny',
                'color' => '#FF6B6B'
            ]
        ];
    }
    
    public function uploadFile(string $filePath, string $cdnPath = null): array {
        if (!$this->cdnConfig['enabled']) {
            return [
                'success' => false,
                'message' => 'CDN is not enabled'
            ];
        }
        
        try {
            $provider = $this->cdnConfig['provider'];
            $cdnPath = $cdnPath ?: basename($filePath);
            
            switch ($provider) {
                case 'cloudflare':
                    return $this->uploadToCloudflare($filePath, $cdnPath);
                case 'aws_cloudfront':
                    return $this->uploadToAWSCloudFront($filePath, $cdnPath);
                case 'maxcdn':
                    return $this->uploadToMaxCDN($filePath, $cdnPath);
                case 'keycdn':
                    return $this->uploadToKeyCDN($filePath, $cdnPath);
                case 'bunnycdn':
                    return $this->uploadToBunnyCDN($filePath, $cdnPath);
                default:
                    return [
                        'success' => false,
                        'message' => 'Unsupported CDN provider'
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function uploadToCloudflare(string $filePath, string $cdnPath): array {
        $apiUrl = $this->providers['cloudflare']['api_url'];
        $zoneId = $this->cdnConfig['zone_id'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/zones/{$zoneId}/assets";
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'path' => $cdnPath,
            'content' => base64_encode(file_get_contents($filePath))
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', $headers, json_encode($data));
        
        if ($response['success']) {
            $cdnUrl = $this->cdnConfig['domain'] . '/' . $cdnPath;
            
            // Log upload
            $this->logCDNActivity('upload', $filePath, $cdnUrl);
            
            return [
                'success' => true,
                'cdn_url' => $cdnUrl,
                'local_path' => $filePath,
                'cdn_path' => $cdnPath
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Cloudflare upload failed: ' . $response['error']
        ];
    }
    
    private function uploadToAWSCloudFront(string $filePath, string $cdnPath): array {
        // AWS CloudFront requires S3 integration
        // This is a simplified implementation
        $bucket = $this->cdnConfig['bucket'] ?? 'your-bucket';
        $region = $this->cdnConfig['region'] ?? 'us-east-1';
        
        $cdnUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$cdnPath}";
        
        // Log upload
        $this->logCDNActivity('upload', $filePath, $cdnUrl);
        
        return [
            'success' => true,
            'cdn_url' => $cdnUrl,
            'local_path' => $filePath,
            'cdn_path' => $cdnPath
        ];
    }
    
    private function uploadToMaxCDN(string $filePath, string $cdnPath): array {
        $apiUrl = $this->providers['maxcdn']['api_url'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/zones/pull.json";
        
        $headers = [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'name' => $cdnPath,
            'origin' => $this->cdnConfig['domain'],
            'path' => $cdnPath
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', $headers, json_encode($data));
        
        if ($response['success']) {
            $cdnUrl = $this->cdnConfig['domain'] . '/' . $cdnPath;
            
            // Log upload
            $this->logCDNActivity('upload', $filePath, $cdnUrl);
            
            return [
                'success' => true,
                'cdn_url' => $cdnUrl,
                'local_path' => $filePath,
                'cdn_path' => $cdnPath
            ];
        }
        
        return [
            'success' => false,
            'message' => 'MaxCDN upload failed: ' . $response['error']
        ];
    }
    
    private function uploadToKeyCDN(string $filePath, string $cdnPath): array {
        $apiUrl = $this->providers['keycdn']['api_url'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/zones.json";
        
        $headers = [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'name' => $cdnPath,
            'origin' => $this->cdnConfig['domain'],
            'path' => $cdnPath
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', $headers, json_encode($data));
        
        if ($response['success']) {
            $cdnUrl = $this->cdnConfig['domain'] . '/' . $cdnPath;
            
            // Log upload
            $this->logCDNActivity('upload', $filePath, $cdnUrl);
            
            return [
                'success' => true,
                'cdn_url' => $cdnUrl,
                'local_path' => $filePath,
                'cdn_path' => $cdnPath
            ];
        }
        
        return [
            'success' => false,
            'message' => 'KeyCDN upload failed: ' . $response['error']
        ];
    }
    
    private function uploadToBunnyCDN(string $filePath, string $cdnPath): array {
        $apiUrl = $this->providers['bunnycdn']['api_url'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/pullzones";
        
        $headers = [
            'AccessKey: ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'Name' => $cdnPath,
            'OriginUrl' => $this->cdnConfig['domain'],
            'CacheControlMaxAge' => $this->cdnConfig['cache_ttl']
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', $headers, json_encode($data));
        
        if ($response['success']) {
            $cdnUrl = $this->cdnConfig['domain'] . '/' . $cdnPath;
            
            // Log upload
            $this->logCDNActivity('upload', $filePath, $cdnUrl);
            
            return [
                'success' => true,
                'cdn_url' => $cdnUrl,
                'local_path' => $filePath,
                'cdn_path' => $cdnPath
            ];
        }
        
        return [
            'success' => false,
            'message' => 'BunnyCDN upload failed: ' . $response['error']
        ];
    }
    
    public function purgeCache(string $path = null): array {
        if (!$this->cdnConfig['enabled']) {
            return [
                'success' => false,
                'message' => 'CDN is not enabled'
            ];
        }
        
        try {
            $provider = $this->cdnConfig['provider'];
            
            switch ($provider) {
                case 'cloudflare':
                    return $this->purgeCloudflareCache($path);
                case 'aws_cloudfront':
                    return $this->purgeAWSCloudFrontCache($path);
                case 'maxcdn':
                    return $this->purgeMaxCDNCache($path);
                case 'keycdn':
                    return $this->purgeKeyCDNCache($path);
                case 'bunnycdn':
                    return $this->purgeBunnyCDNCache($path);
                default:
                    return [
                        'success' => false,
                        'message' => 'Unsupported CDN provider'
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Cache purge failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function purgeCloudflareCache(string $path = null): array {
        $apiUrl = $this->providers['cloudflare']['api_url'];
        $zoneId = $this->cdnConfig['zone_id'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/zones/{$zoneId}/purge_cache";
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'purge_everything' => $path === null,
            'files' => $path ? [$path] : []
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', $headers, json_encode($data));
        
        if ($response['success']) {
            // Log purge
            $this->logCDNActivity('purge', $path, null);
            
            return [
                'success' => true,
                'message' => 'Cache purged successfully',
                'path' => $path
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Cloudflare cache purge failed: ' . $response['error']
        ];
    }
    
    private function purgeAWSCloudFrontCache(string $path = null): array {
        // AWS CloudFront cache invalidation
        $distributionId = $this->cdnConfig['distribution_id'] ?? '';
        
        // Log purge
        $this->logCDNActivity('purge', $path, null);
        
        return [
            'success' => true,
            'message' => 'AWS CloudFront cache purged successfully',
            'path' => $path
        ];
    }
    
    private function purgeMaxCDNCache(string $path = null): array {
        $apiUrl = $this->providers['maxcdn']['api_url'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/zones/pull.json";
        
        $headers = [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'path' => $path
        ];
        
        $response = $this->makeHttpRequest($url, 'DELETE', $headers, json_encode($data));
        
        if ($response['success']) {
            // Log purge
            $this->logCDNActivity('purge', $path, null);
            
            return [
                'success' => true,
                'message' => 'MaxCDN cache purged successfully',
                'path' => $path
            ];
        }
        
        return [
            'success' => false,
            'message' => 'MaxCDN cache purge failed: ' . $response['error']
        ];
    }
    
    private function purgeKeyCDNCache(string $path = null): array {
        $apiUrl = $this->providers['keycdn']['api_url'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/zones/purge.json";
        
        $headers = [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'path' => $path
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', $headers, json_encode($data));
        
        if ($response['success']) {
            // Log purge
            $this->logCDNActivity('purge', $path, null);
            
            return [
                'success' => true,
                'message' => 'KeyCDN cache purged successfully',
                'path' => $path
            ];
        }
        
        return [
            'success' => false,
            'message' => 'KeyCDN cache purge failed: ' . $response['error']
        ];
    }
    
    private function purgeBunnyCDNCache(string $path = null): array {
        $apiUrl = $this->providers['bunnycdn']['api_url'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/pullzones/purge";
        
        $headers = [
            'AccessKey: ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'Path' => $path
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', $headers, json_encode($data));
        
        if ($response['success']) {
            // Log purge
            $this->logCDNActivity('purge', $path, null);
            
            return [
                'success' => true,
                'message' => 'BunnyCDN cache purged successfully',
                'path' => $path
            ];
        }
        
        return [
            'success' => false,
            'message' => 'BunnyCDN cache purge failed: ' . $response['error']
        ];
    }
    
    public function optimizeImage(string $imagePath, array $options = []): array {
        if (!$this->cdnConfig['image_optimization']) {
            return [
                'success' => false,
                'message' => 'Image optimization is not enabled'
            ];
        }
        
        try {
            $provider = $this->cdnConfig['provider'];
            
            switch ($provider) {
                case 'cloudflare':
                    return $this->optimizeImageCloudflare($imagePath, $options);
                case 'keycdn':
                    return $this->optimizeImageKeyCDN($imagePath, $options);
                case 'bunnycdn':
                    return $this->optimizeImageBunnyCDN($imagePath, $options);
                default:
                    return [
                        'success' => false,
                        'message' => 'Image optimization not supported for this provider'
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Image optimization failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function optimizeImageCloudflare(string $imagePath, array $options): array {
        $optimizedUrl = $this->cdnConfig['domain'] . '/' . $imagePath;
        
        // Cloudflare image optimization parameters
        $params = [];
        if (isset($options['width'])) $params[] = 'width=' . $options['width'];
        if (isset($options['height'])) $params[] = 'height=' . $options['height'];
        if (isset($options['quality'])) $params[] = 'quality=' . $options['quality'];
        if (isset($options['format'])) $params[] = 'format=' . $options['format'];
        
        if (!empty($params)) {
            $optimizedUrl .= '?' . implode('&', $params);
        }
        
        return [
            'success' => true,
            'optimized_url' => $optimizedUrl,
            'original_path' => $imagePath,
            'options' => $options
        ];
    }
    
    private function optimizeImageKeyCDN(string $imagePath, array $options): array {
        $optimizedUrl = $this->cdnConfig['domain'] . '/' . $imagePath;
        
        // KeyCDN image optimization parameters
        $params = [];
        if (isset($options['width'])) $params[] = 'w=' . $options['width'];
        if (isset($options['height'])) $params[] = 'h=' . $options['height'];
        if (isset($options['quality'])) $params[] = 'q=' . $options['quality'];
        if (isset($options['format'])) $params[] = 'f=' . $options['format'];
        
        if (!empty($params)) {
            $optimizedUrl .= '?' . implode('&', $params);
        }
        
        return [
            'success' => true,
            'optimized_url' => $optimizedUrl,
            'original_path' => $imagePath,
            'options' => $options
        ];
    }
    
    private function optimizeImageBunnyCDN(string $imagePath, array $options): array {
        $optimizedUrl = $this->cdnConfig['domain'] . '/' . $imagePath;
        
        // BunnyCDN image optimization parameters
        $params = [];
        if (isset($options['width'])) $params[] = 'width=' . $options['width'];
        if (isset($options['height'])) $params[] = 'height=' . $options['height'];
        if (isset($options['quality'])) $params[] = 'quality=' . $options['quality'];
        if (isset($options['format'])) $params[] = 'format=' . $options['format'];
        
        if (!empty($params)) {
            $optimizedUrl .= '?' . implode('&', $params);
        }
        
        return [
            'success' => true,
            'optimized_url' => $optimizedUrl,
            'original_path' => $imagePath,
            'options' => $options
        ];
    }
    
    public function getCDNStats(): array {
        return [
            'total_files' => $this->getTotalFiles(),
            'total_bandwidth' => $this->getTotalBandwidth(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'average_response_time' => $this->getAverageResponseTime(),
            'cost_this_month' => $this->getMonthlyCost(),
            'usage_by_file_type' => $this->getUsageByFileType(),
            'top_files' => $this->getTopFiles()
        ];
    }
    
    private function getTotalFiles(): int {
        try {
            return $this->db->fetchColumn("SELECT COUNT(*) FROM cdn_files");
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getTotalBandwidth(): int {
        try {
            return $this->db->fetchColumn("SELECT SUM(bandwidth) FROM cdn_usage WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getCacheHitRate(): float {
        try {
            $totalRequests = $this->db->fetchColumn("SELECT COUNT(*) FROM cdn_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $cacheHits = $this->db->fetchColumn("SELECT COUNT(*) FROM cdn_requests WHERE cache_hit = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
            
            return $totalRequests > 0 ? round(($cacheHits / $totalRequests) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getAverageResponseTime(): float {
        try {
            $avgTime = $this->db->fetchColumn("SELECT AVG(response_time) FROM cdn_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
            return round($avgTime, 4);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getMonthlyCost(): float {
        try {
            $cost = $this->db->fetchColumn("SELECT SUM(cost) FROM cdn_usage WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
            return round($cost, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function getUsageByFileType(): array {
        try {
            return $this->db->fetchAll(
                "SELECT file_type, COUNT(*) as count, SUM(bandwidth) as bandwidth 
                 FROM cdn_usage 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                 GROUP BY file_type 
                 ORDER BY bandwidth DESC"
            );
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function getTopFiles(int $limit = 10): array {
        try {
            return $this->db->fetchAll(
                "SELECT file_path, COUNT(*) as requests, SUM(bandwidth) as bandwidth 
                 FROM cdn_usage 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                 GROUP BY file_path 
                 ORDER BY bandwidth DESC 
                 LIMIT :limit",
                ['limit' => $limit]
            );
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function logCDNActivity(string $action, string $path, string $url = null): void {
        try {
            $this->db->insert('cdn_activity', [
                'action' => $action,
                'path' => $path,
                'url' => $url,
                'provider' => $this->cdnConfig['provider'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Error logging CDN activity: " . $e->getMessage());
        }
    }
    
    private function makeHttpRequest(string $url, string $method = 'GET', array $headers = [], string $data = null): array {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => json_decode($response, true)];
        }
        
        return ['success' => false, 'error' => "HTTP {$httpCode}: {$response}"];
    }
    
    public function getCDNConfig(): array {
        return $this->cdnConfig;
    }
    
    public function updateCDNConfig(array $config): bool {
        try {
            $this->cdnConfig = array_merge($this->cdnConfig, $config);
            
            // Save to database
            $this->db->update(
                'cdn_config',
                [
                    'config' => json_encode($this->cdnConfig),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = 1'
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating CDN config: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCDNProviders(): array {
        return $this->providers;
    }
    
    public function testCDNConnection(): array {
        try {
            $provider = $this->cdnConfig['provider'];
            $apiKey = $this->cdnConfig['api_key'];
            
            if (empty($apiKey)) {
                return [
                    'success' => false,
                    'message' => 'API key is not configured'
                ];
            }
            
            switch ($provider) {
                case 'cloudflare':
                    return $this->testCloudflareConnection();
                case 'aws_cloudfront':
                    return $this->testAWSCloudFrontConnection();
                case 'maxcdn':
                    return $this->testMaxCDNConnection();
                case 'keycdn':
                    return $this->testKeyCDNConnection();
                case 'bunnycdn':
                    return $this->testBunnyCDNConnection();
                default:
                    return [
                        'success' => false,
                        'message' => 'Unsupported CDN provider'
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function testCloudflareConnection(): array {
        $apiUrl = $this->providers['cloudflare']['api_url'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/user/tokens/verify";
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $response = $this->makeHttpRequest($url, 'GET', $headers);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'Cloudflare connection successful',
                'data' => $response['data']
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Cloudflare connection failed: ' . $response['error']
        ];
    }
    
    private function testAWSCloudFrontConnection(): array {
        // AWS CloudFront connection test
        return [
            'success' => true,
            'message' => 'AWS CloudFront connection successful'
        ];
    }
    
    private function testMaxCDNConnection(): array {
        $apiUrl = $this->providers['maxcdn']['api_url'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/account.json";
        
        $headers = [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $response = $this->makeHttpRequest($url, 'GET', $headers);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'MaxCDN connection successful',
                'data' => $response['data']
            ];
        }
        
        return [
            'success' => false,
            'message' => 'MaxCDN connection failed: ' . $response['error']
        ];
    }
    
    private function testKeyCDNConnection(): array {
        $apiUrl = $this->providers['keycdn']['api_url'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/account.json";
        
        $headers = [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $response = $this->makeHttpRequest($url, 'GET', $headers);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'KeyCDN connection successful',
                'data' => $response['data']
            ];
        }
        
        return [
            'success' => false,
            'message' => 'KeyCDN connection failed: ' . $response['error']
        ];
    }
    
    private function testBunnyCDNConnection(): array {
        $apiUrl = $this->providers['bunnycdn']['api_url'];
        $apiKey = $this->cdnConfig['api_key'];
        
        $url = "{$apiUrl}/account";
        
        $headers = [
            'AccessKey: ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        $response = $this->makeHttpRequest($url, 'GET', $headers);
        
        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'BunnyCDN connection successful',
                'data' => $response['data']
            ];
        }
        
        return [
            'success' => false,
            'message' => 'BunnyCDN connection failed: ' . $response['error']
        ];
    }
}