<?php
declare(strict_types=1);

namespace Services;

class AdvancedSecurityMonitoringService {
    private Database $db;
    private array $config;
    private array $threatTypes;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = $this->getConfig();
        $this->threatTypes = $this->getThreatTypes();
    }
    
    private function getConfig(): array {
        return [
            'enabled' => SECURITY_MONITORING_ENABLED ?? true,
            'log_level' => SECURITY_LOG_LEVEL ?? 'info',
            'alert_threshold' => SECURITY_ALERT_THRESHOLD ?? 5,
            'block_threshold' => SECURITY_BLOCK_THRESHOLD ?? 10,
            'monitoring_window' => SECURITY_MONITORING_WINDOW ?? 3600, // 1 hour
            'ip_whitelist' => SECURITY_IP_WHITELIST ?? [],
            'ip_blacklist' => SECURITY_IP_BLACKLIST ?? [],
            'rate_limit_enabled' => SECURITY_RATE_LIMIT_ENABLED ?? true,
            'rate_limit_requests' => SECURITY_RATE_LIMIT_REQUESTS ?? 100,
            'rate_limit_window' => SECURITY_RATE_LIMIT_WINDOW ?? 3600,
            'failed_login_threshold' => SECURITY_FAILED_LOGIN_THRESHOLD ?? 5,
            'suspicious_activity_threshold' => SECURITY_SUSPICIOUS_ACTIVITY_THRESHOLD ?? 3,
            'auto_block_enabled' => SECURITY_AUTO_BLOCK_ENABLED ?? true,
            'notification_enabled' => SECURITY_NOTIFICATION_ENABLED ?? true
        ];
    }
    
    private function getThreatTypes(): array {
        return [
            'brute_force' => [
                'name' => 'Brute Force Attack',
                'description' => 'Multiple failed login attempts',
                'severity' => 'high',
                'icon' => 'fas fa-hammer',
                'color' => '#F44336'
            ],
            'sql_injection' => [
                'name' => 'SQL Injection',
                'description' => 'Attempted SQL injection attack',
                'severity' => 'critical',
                'icon' => 'fas fa-database',
                'color' => '#E91E63'
            ],
            'xss_attack' => [
                'name' => 'XSS Attack',
                'description' => 'Cross-site scripting attempt',
                'severity' => 'high',
                'icon' => 'fas fa-code',
                'color' => '#FF9800'
            ],
            'csrf_attack' => [
                'name' => 'CSRF Attack',
                'description' => 'Cross-site request forgery attempt',
                'severity' => 'medium',
                'icon' => 'fas fa-shield-alt',
                'color' => '#9C27B0'
            ],
            'rate_limit_exceeded' => [
                'name' => 'Rate Limit Exceeded',
                'description' => 'Too many requests from single IP',
                'severity' => 'medium',
                'icon' => 'fas fa-tachometer-alt',
                'color' => '#FF5722'
            ],
            'suspicious_activity' => [
                'name' => 'Suspicious Activity',
                'description' => 'Unusual user behavior detected',
                'severity' => 'medium',
                'icon' => 'fas fa-exclamation-triangle',
                'color' => '#FFC107'
            ],
            'malicious_file_upload' => [
                'name' => 'Malicious File Upload',
                'description' => 'Attempted upload of malicious file',
                'severity' => 'high',
                'icon' => 'fas fa-file-upload',
                'color' => '#795548'
            ],
            'directory_traversal' => [
                'name' => 'Directory Traversal',
                'description' => 'Attempted directory traversal attack',
                'severity' => 'high',
                'icon' => 'fas fa-folder-open',
                'color' => '#607D8B'
            ],
            'command_injection' => [
                'name' => 'Command Injection',
                'description' => 'Attempted command injection attack',
                'severity' => 'critical',
                'icon' => 'fas fa-terminal',
                'color' => '#E91E63'
            ],
            'session_hijacking' => [
                'name' => 'Session Hijacking',
                'description' => 'Potential session hijacking attempt',
                'severity' => 'high',
                'icon' => 'fas fa-user-secret',
                'color' => '#9C27B0'
            ]
        ];
    }
    
    public function monitorRequest(array $requestData): array {
        if (!$this->config['enabled']) {
            return ['monitored' => false];
        }
        
        $ipAddress = $requestData['ip_address'] ?? $_SERVER['REMOTE_ADDR'];
        $userAgent = $requestData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'];
        $url = $requestData['url'] ?? $_SERVER['REQUEST_URI'];
        $method = $requestData['method'] ?? $_SERVER['REQUEST_METHOD'];
        $userId = $requestData['user_id'] ?? null;
        
        $threats = [];
        
        // Check IP whitelist/blacklist
        if (in_array($ipAddress, $this->config['ip_blacklist'])) {
            $threats[] = $this->createThreat('ip_blacklisted', 'IP address is blacklisted', 'critical', $ipAddress);
        }
        
        // Check rate limiting
        if ($this->config['rate_limit_enabled']) {
            $rateLimitThreat = $this->checkRateLimit($ipAddress);
            if ($rateLimitThreat) {
                $threats[] = $rateLimitThreat;
            }
        }
        
        // Check for SQL injection
        $sqlInjectionThreat = $this->checkSQLInjection($url, $requestData['data'] ?? []);
        if ($sqlInjectionThreat) {
            $threats[] = $sqlInjectionThreat;
        }
        
        // Check for XSS
        $xssThreat = $this->checkXSS($url, $requestData['data'] ?? []);
        if ($xssThreat) {
            $threats[] = $xssThreat;
        }
        
        // Check for CSRF
        $csrfThreat = $this->checkCSRF($requestData);
        if ($csrfThreat) {
            $threats[] = $csrfThreat;
        }
        
        // Check for directory traversal
        $directoryTraversalThreat = $this->checkDirectoryTraversal($url);
        if ($directoryTraversalThreat) {
            $threats[] = $directoryTraversalThreat;
        }
        
        // Check for command injection
        $commandInjectionThreat = $this->checkCommandInjection($url, $requestData['data'] ?? []);
        if ($commandInjectionThreat) {
            $threats[] = $commandInjectionThreat;
        }
        
        // Check for suspicious activity
        if ($userId) {
            $suspiciousActivityThreat = $this->checkSuspiciousActivity($userId, $requestData);
            if ($suspiciousActivityThreat) {
                $threats[] = $suspiciousActivityThreat;
            }
        }
        
        // Log threats
        foreach ($threats as $threat) {
            $this->logThreat($threat, $requestData);
        }
        
        // Check if IP should be blocked
        $shouldBlock = $this->shouldBlockIP($ipAddress);
        
        return [
            'monitored' => true,
            'threats' => $threats,
            'threat_count' => count($threats),
            'should_block' => $shouldBlock,
            'ip_address' => $ipAddress
        ];
    }
    
    private function checkRateLimit(string $ipAddress): ?array {
        $windowStart = time() - $this->config['rate_limit_window'];
        
        $requestCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM security_logs 
             WHERE ip_address = :ip_address AND created_at >= :window_start",
            ['ip_address' => $ipAddress, 'window_start' => date('Y-m-d H:i:s', $windowStart)]
        );
        
        if ($requestCount > $this->config['rate_limit_requests']) {
            return $this->createThreat('rate_limit_exceeded', 'Rate limit exceeded', 'medium', $ipAddress);
        }
        
        return null;
    }
    
    private function checkSQLInjection(string $url, array $data): ?array {
        $sqlPatterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\balter\b.*\btable\b)/i',
            '/(\bexec\b|\bexecute\b)/i',
            '/(\bscript\b.*\balert\b)/i',
            '/(\bscript\b.*\bdocument\b)/i'
        ];
        
        $input = $url . ' ' . json_encode($data);
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return $this->createThreat('sql_injection', 'SQL injection attempt detected', 'critical');
            }
        }
        
        return null;
    }
    
    private function checkXSS(string $url, array $data): ?array {
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>.*?<\/iframe>/i',
            '/<object[^>]*>.*?<\/object>/i',
            '/<embed[^>]*>.*?<\/embed>/i',
            '/<link[^>]*>.*?<\/link>/i',
            '/<meta[^>]*>.*?<\/meta>/i'
        ];
        
        $input = $url . ' ' . json_encode($data);
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return $this->createThreat('xss_attack', 'XSS attack attempt detected', 'high');
            }
        }
        
        return null;
    }
    
    private function checkCSRF(array $requestData): ?array {
        if ($requestData['method'] === 'POST' || $requestData['method'] === 'PUT' || $requestData['method'] === 'DELETE') {
            $csrfToken = $requestData['csrf_token'] ?? null;
            $sessionToken = $requestData['session_csrf_token'] ?? null;
            
            if (!$csrfToken || !$sessionToken || $csrfToken !== $sessionToken) {
                return $this->createThreat('csrf_attack', 'CSRF attack attempt detected', 'medium');
            }
        }
        
        return null;
    }
    
    private function checkDirectoryTraversal(string $url): ?array {
        $traversalPatterns = [
            '/\.\.\//',
            '/\.\.\\\\/',
            '/\.\.%2f/',
            '/\.\.%5c/',
            '/\.\.%252f/',
            '/\.\.%255c/'
        ];
        
        foreach ($traversalPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return $this->createThreat('directory_traversal', 'Directory traversal attempt detected', 'high');
            }
        }
        
        return null;
    }
    
    private function checkCommandInjection(string $url, array $data): ?array {
        $commandPatterns = [
            '/\bcat\b.*\b\/etc\/passwd\b/i',
            '/\bls\b.*\b-l\b/i',
            '/\bwhoami\b/i',
            '/\buname\b.*\b-a\b/i',
            '/\bping\b.*\b-c\b/i',
            '/\bnmap\b/i',
            '/\bnetstat\b/i',
            '/\bps\b.*\baux\b/i',
            '/\bwget\b/i',
            '/\bcurl\b/i'
        ];
        
        $input = $url . ' ' . json_encode($data);
        
        foreach ($commandPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return $this->createThreat('command_injection', 'Command injection attempt detected', 'critical');
            }
        }
        
        return null;
    }
    
    private function checkSuspiciousActivity(int $userId, array $requestData): ?array {
        // Check for unusual login patterns
        $recentLogins = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM user_activities 
             WHERE user_id = :user_id AND activity_type = 'login' 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            ['user_id' => $userId]
        );
        
        if ($recentLogins > 10) {
            return $this->createThreat('suspicious_activity', 'Unusual login frequency detected', 'medium', null, $userId);
        }
        
        // Check for unusual IP changes
        $recentIPs = $this->db->fetchAll(
            "SELECT DISTINCT ip_address FROM security_logs 
             WHERE user_id = :user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            ['user_id' => $userId]
        );
        
        if (count($recentIPs) > 5) {
            return $this->createThreat('suspicious_activity', 'Multiple IP addresses detected', 'medium', null, $userId);
        }
        
        return null;
    }
    
    private function createThreat(string $type, string $description, string $severity, string $ipAddress = null, int $userId = null): array {
        $threatInfo = $this->threatTypes[$type] ?? [
            'name' => 'Unknown Threat',
            'description' => $description,
            'severity' => $severity,
            'icon' => 'fas fa-exclamation-triangle',
            'color' => '#FF9800'
        ];
        
        return [
            'type' => $type,
            'name' => $threatInfo['name'],
            'description' => $description,
            'severity' => $severity,
            'icon' => $threatInfo['icon'],
            'color' => $threatInfo['color'],
            'ip_address' => $ipAddress,
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function logThreat(array $threat, array $requestData): void {
        try {
            $this->db->insert('security_logs', [
                'threat_type' => $threat['type'],
                'threat_name' => $threat['name'],
                'description' => $threat['description'],
                'severity' => $threat['severity'],
                'ip_address' => $threat['ip_address'],
                'user_id' => $threat['user_id'],
                'user_agent' => $requestData['user_agent'] ?? null,
                'url' => $requestData['url'] ?? null,
                'method' => $requestData['method'] ?? null,
                'request_data' => json_encode($requestData['data'] ?? []),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Error logging security threat: " . $e->getMessage());
        }
    }
    
    private function shouldBlockIP(string $ipAddress): bool {
        if (!$this->config['auto_block_enabled']) {
            return false;
        }
        
        $windowStart = time() - $this->config['monitoring_window'];
        
        $threatCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM security_logs 
             WHERE ip_address = :ip_address AND created_at >= :window_start",
            ['ip_address' => $ipAddress, 'window_start' => date('Y-m-d H:i:s', $windowStart)]
        );
        
        return $threatCount >= $this->config['block_threshold'];
    }
    
    public function blockIP(string $ipAddress, string $reason = 'Security threat detected'): bool {
        try {
            $this->db->insert('blocked_ips', [
                'ip_address' => $ipAddress,
                'reason' => $reason,
                'blocked_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error blocking IP: " . $e->getMessage());
            return false;
        }
    }
    
    public function unblockIP(string $ipAddress): bool {
        try {
            $this->db->delete('blocked_ips', 'ip_address = :ip_address', ['ip_address' => $ipAddress]);
            return true;
        } catch (\Exception $e) {
            error_log("Error unblocking IP: " . $e->getMessage());
            return false;
        }
    }
    
    public function isIPBlocked(string $ipAddress): bool {
        $blocked = $this->db->fetch(
            "SELECT * FROM blocked_ips 
             WHERE ip_address = :ip_address AND expires_at > NOW()",
            ['ip_address' => $ipAddress]
        );
        
        return $blocked !== null;
    }
    
    public function getSecurityStats(): array {
        return [
            'total_threats' => $this->db->fetchColumn("SELECT COUNT(*) FROM security_logs"),
            'threats_by_type' => $this->getThreatsByType(),
            'threats_by_severity' => $this->getThreatsBySeverity(),
            'blocked_ips' => $this->getBlockedIPs(),
            'recent_threats' => $this->getRecentThreats(),
            'top_threat_ips' => $this->getTopThreatIPs(),
            'threat_trends' => $this->getThreatTrends()
        ];
    }
    
    private function getThreatsByType(): array {
        return $this->db->fetchAll(
            "SELECT threat_type, COUNT(*) as count
             FROM security_logs 
             GROUP BY threat_type 
             ORDER BY count DESC"
        );
    }
    
    private function getThreatsBySeverity(): array {
        return $this->db->fetchAll(
            "SELECT severity, COUNT(*) as count
             FROM security_logs 
             GROUP BY severity 
             ORDER BY count DESC"
        );
    }
    
    private function getBlockedIPs(): array {
        return $this->db->fetchAll(
            "SELECT * FROM blocked_ips 
             WHERE expires_at > NOW()
             ORDER BY blocked_at DESC"
        );
    }
    
    private function getRecentThreats(int $limit = 20): array {
        return $this->db->fetchAll(
            "SELECT * FROM security_logs 
             ORDER BY created_at DESC 
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    private function getTopThreatIPs(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT ip_address, COUNT(*) as threat_count
             FROM security_logs 
             GROUP BY ip_address 
             ORDER BY threat_count DESC 
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    private function getThreatTrends(): array {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as threat_count
             FROM security_logs 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at) 
             ORDER BY date DESC"
        );
    }
    
    public function getSecurityAlerts(): array {
        return $this->db->fetchAll(
            "SELECT * FROM security_alerts 
             WHERE status = 'active'
             ORDER BY created_at DESC"
        );
    }
    
    public function createSecurityAlert(array $alertData): bool {
        try {
            $this->db->insert('security_alerts', [
                'title' => $alertData['title'],
                'description' => $alertData['description'],
                'severity' => $alertData['severity'],
                'threat_type' => $alertData['threat_type'],
                'ip_address' => $alertData['ip_address'] ?? null,
                'user_id' => $alertData['user_id'] ?? null,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error creating security alert: " . $e->getMessage());
            return false;
        }
    }
    
    public function resolveSecurityAlert(int $alertId): bool {
        try {
            $this->db->update(
                'security_alerts',
                [
                    'status' => 'resolved',
                    'resolved_at' => date('Y-m-d H:i:s')
                ],
                'id = :alert_id',
                ['alert_id' => $alertId]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error resolving security alert: " . $e->getMessage());
            return false;
        }
    }
    
    public function getSecurityDashboard(): array {
        return [
            'stats' => $this->getSecurityStats(),
            'alerts' => $this->getSecurityAlerts(),
            'recent_threats' => $this->getRecentThreats(10),
            'blocked_ips' => $this->getBlockedIPs(),
            'threat_types' => $this->threatTypes,
            'config' => $this->config
        ];
    }
    
    public function getConfig(): array {
        return $this->config;
    }
    
    public function updateConfig(array $config): bool {
        try {
            $this->config = array_merge($this->config, $config);
            
            // Save to database
            $this->db->update(
                'security_config',
                [
                    'config' => json_encode($this->config),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = 1'
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating security config: " . $e->getMessage());
            return false;
        }
    }
    
    public function cleanupOldLogs(int $days = 90): bool {
        try {
            $this->db->query(
                "DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)",
                ['days' => $days]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error cleaning up old security logs: " . $e->getMessage());
            return false;
        }
    }
    
    public function exportSecurityLogs(string $format = 'json'): array {
        $logs = $this->db->fetchAll(
            "SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 1000"
        );
        
        switch ($format) {
            case 'json':
                return $logs;
            case 'csv':
                return $this->convertToCsv($logs);
            default:
                return $logs;
        }
    }
    
    private function convertToCsv(array $data): array {
        if (empty($data)) {
            return [];
        }
        
        $csv = [];
        $csv[] = implode(',', array_keys($data[0]));
        
        foreach ($data as $row) {
            $csv[] = implode(',', array_values($row));
        }
        
        return $csv;
    }
    
    public function getThreatTypes(): array {
        return $this->threatTypes;
    }
    
    public function addCustomThreatType(string $type, array $config): bool {
        try {
            $this->threatTypes[$type] = $config;
            
            // Save to database
            $this->db->update(
                'security_threat_types',
                [
                    'threat_types' => json_encode($this->threatTypes),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = 1'
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error adding custom threat type: " . $e->getMessage());
            return false;
        }
    }
}