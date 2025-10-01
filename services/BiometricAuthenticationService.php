<?php
declare(strict_types=1);

namespace Services;

class BiometricAuthenticationService {
    private Database $db;
    private array $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = $this->getConfig();
    }
    
    private function getConfig(): array {
        return [
            'enabled' => BIOMETRIC_ENABLED ?? true,
            'fingerprint_enabled' => BIOMETRIC_FINGERPRINT_ENABLED ?? true,
            'face_id_enabled' => BIOMETRIC_FACE_ID_ENABLED ?? true,
            'voice_enabled' => BIOMETRIC_VOICE_ENABLED ?? false,
            'iris_enabled' => BIOMETRIC_IRIS_ENABLED ?? false,
            'palm_print_enabled' => BIOMETRIC_PALM_PRINT_ENABLED ?? false,
            'fallback_enabled' => BIOMETRIC_FALLBACK_ENABLED ?? true,
            'max_attempts' => BIOMETRIC_MAX_ATTEMPTS ?? 3,
            'lockout_duration' => BIOMETRIC_LOCKOUT_DURATION ?? 300, // 5 minutes
            'encryption_key' => BIOMETRIC_ENCRYPTION_KEY ?? 'default_key_change_me'
        ];
    }
    
    public function registerBiometric(int $userId, string $biometricType, string $biometricData): array {
        if (!$this->config['enabled']) {
            return [
                'success' => false,
                'message' => 'Biometric authentication is not enabled'
            ];
        }
        
        if (!$this->isBiometricTypeEnabled($biometricType)) {
            return [
                'success' => false,
                'message' => 'This biometric type is not enabled'
            ];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Encrypt biometric data
            $encryptedData = $this->encryptBiometricData($biometricData);
            
            // Check if user already has this biometric type
            $existing = $this->db->fetch(
                "SELECT * FROM user_biometrics 
                 WHERE user_id = :user_id AND biometric_type = :biometric_type",
                ['user_id' => $userId, 'biometric_type' => $biometricType]
            );
            
            if ($existing) {
                // Update existing biometric
                $this->db->update(
                    'user_biometrics',
                    [
                        'biometric_data' => $encryptedData,
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    'user_id = :user_id AND biometric_type = :biometric_type',
                    ['user_id' => $userId, 'biometric_type' => $biometricType]
                );
            } else {
                // Create new biometric
                $this->db->insert('user_biometrics', [
                    'user_id' => $userId,
                    'biometric_type' => $biometricType,
                    'biometric_data' => $encryptedData,
                    'enabled' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Update user biometric status
            $this->db->update(
                'users',
                ['biometric_enabled' => true],
                'id = :user_id',
                ['user_id' => $userId]
            );
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Biometric authentication registered successfully',
                'biometric_type' => $biometricType
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error registering biometric: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to register biometric authentication'
            ];
        }
    }
    
    public function verifyBiometric(int $userId, string $biometricType, string $biometricData): array {
        if (!$this->config['enabled']) {
            return [
                'success' => false,
                'message' => 'Biometric authentication is not enabled'
            ];
        }
        
        try {
            // Check if user is locked out
            if ($this->isUserLockedOut($userId)) {
                return [
                    'success' => false,
                    'message' => 'Account is temporarily locked due to too many failed attempts',
                    'lockout_remaining' => $this->getLockoutRemaining($userId)
                ];
            }
            
            // Get stored biometric data
            $storedBiometric = $this->db->fetch(
                "SELECT * FROM user_biometrics 
                 WHERE user_id = :user_id AND biometric_type = :biometric_type AND enabled = 1",
                ['user_id' => $userId, 'biometric_type' => $biometricType]
            );
            
            if (!$storedBiometric) {
                return [
                    'success' => false,
                    'message' => 'Biometric not registered for this user'
                ];
            }
            
            // Decrypt stored data
            $storedData = $this->decryptBiometricData($storedBiometric['biometric_data']);
            
            // Verify biometric
            $isValid = $this->verifyBiometricData($biometricType, $storedData, $biometricData);
            
            if ($isValid) {
                // Reset failed attempts
                $this->resetFailedAttempts($userId);
                
                // Log successful verification
                $this->logBiometricActivity($userId, $biometricType, 'success');
                
                return [
                    'success' => true,
                    'message' => 'Biometric authentication successful',
                    'biometric_type' => $biometricType
                ];
            } else {
                // Increment failed attempts
                $this->incrementFailedAttempts($userId);
                
                // Log failed verification
                $this->logBiometricActivity($userId, $biometricType, 'failed');
                
                $failedAttempts = $this->getFailedAttempts($userId);
                $remainingAttempts = $this->config['max_attempts'] - $failedAttempts;
                
                if ($remainingAttempts <= 0) {
                    $this->lockoutUser($userId);
                    return [
                        'success' => false,
                        'message' => 'Too many failed attempts. Account locked.',
                        'lockout_duration' => $this->config['lockout_duration']
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Biometric authentication failed',
                    'remaining_attempts' => $remainingAttempts
                ];
            }
            
        } catch (\Exception $e) {
            error_log("Error verifying biometric: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Biometric verification failed'
            ];
        }
    }
    
    private function verifyBiometricData(string $biometricType, string $storedData, string $inputData): bool {
        switch ($biometricType) {
            case 'fingerprint':
                return $this->verifyFingerprint($storedData, $inputData);
            case 'face_id':
                return $this->verifyFaceId($storedData, $inputData);
            case 'voice':
                return $this->verifyVoice($storedData, $inputData);
            case 'iris':
                return $this->verifyIris($storedData, $inputData);
            case 'palm_print':
                return $this->verifyPalmPrint($storedData, $inputData);
            default:
                return false;
        }
    }
    
    private function verifyFingerprint(string $storedData, string $inputData): bool {
        // Simplified fingerprint verification
        // In a real implementation, you would use specialized biometric libraries
        $storedHash = hash('sha256', $storedData);
        $inputHash = hash('sha256', $inputData);
        
        // Use similarity comparison (simplified)
        return $this->calculateSimilarity($storedHash, $inputHash) > 0.8;
    }
    
    private function verifyFaceId(string $storedData, string $inputData): bool {
        // Simplified face ID verification
        $storedHash = hash('sha256', $storedData);
        $inputHash = hash('sha256', $inputData);
        
        return $this->calculateSimilarity($storedHash, $inputHash) > 0.85;
    }
    
    private function verifyVoice(string $storedData, string $inputData): bool {
        // Simplified voice verification
        $storedHash = hash('sha256', $storedData);
        $inputHash = hash('sha256', $inputData);
        
        return $this->calculateSimilarity($storedHash, $inputHash) > 0.75;
    }
    
    private function verifyIris(string $storedData, string $inputData): bool {
        // Simplified iris verification
        $storedHash = hash('sha256', $storedData);
        $inputHash = hash('sha256', $inputData);
        
        return $this->calculateSimilarity($storedHash, $inputHash) > 0.9;
    }
    
    private function verifyPalmPrint(string $storedData, string $inputData): bool {
        // Simplified palm print verification
        $storedHash = hash('sha256', $storedData);
        $inputHash = hash('sha256', $inputData);
        
        return $this->calculateSimilarity($storedHash, $inputHash) > 0.8;
    }
    
    private function calculateSimilarity(string $hash1, string $hash2): float {
        // Simplified similarity calculation using Hamming distance
        $distance = 0;
        $length = min(strlen($hash1), strlen($hash2));
        
        for ($i = 0; $i < $length; $i++) {
            if ($hash1[$i] !== $hash2[$i]) {
                $distance++;
            }
        }
        
        return 1 - ($distance / $length);
    }
    
    private function encryptBiometricData(string $data): string {
        $key = $this->config['encryption_key'];
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    private function decryptBiometricData(string $encryptedData): string {
        $key = $this->config['encryption_key'];
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    private function isBiometricTypeEnabled(string $biometricType): bool {
        switch ($biometricType) {
            case 'fingerprint':
                return $this->config['fingerprint_enabled'];
            case 'face_id':
                return $this->config['face_id_enabled'];
            case 'voice':
                return $this->config['voice_enabled'];
            case 'iris':
                return $this->config['iris_enabled'];
            case 'palm_print':
                return $this->config['palm_print_enabled'];
            default:
                return false;
        }
    }
    
    private function isUserLockedOut(int $userId): bool {
        $lockout = $this->db->fetch(
            "SELECT * FROM user_lockouts 
             WHERE user_id = :user_id AND locked_until > NOW()",
            ['user_id' => $userId]
        );
        
        return $lockout !== null;
    }
    
    private function getLockoutRemaining(int $userId): int {
        $lockout = $this->db->fetch(
            "SELECT locked_until FROM user_lockouts 
             WHERE user_id = :user_id AND locked_until > NOW()",
            ['user_id' => $userId]
        );
        
        if (!$lockout) {
            return 0;
        }
        
        return strtotime($lockout['locked_until']) - time();
    }
    
    private function getFailedAttempts(int $userId): int {
        $attempts = $this->db->fetchColumn(
            "SELECT failed_attempts FROM user_biometric_attempts 
             WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        return $attempts ?: 0;
    }
    
    private function incrementFailedAttempts(int $userId): void {
        $existing = $this->db->fetch(
            "SELECT * FROM user_biometric_attempts WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        if ($existing) {
            $this->db->update(
                'user_biometric_attempts',
                [
                    'failed_attempts' => $existing['failed_attempts'] + 1,
                    'last_attempt' => date('Y-m-d H:i:s')
                ],
                'user_id = :user_id',
                ['user_id' => $userId]
            );
        } else {
            $this->db->insert('user_biometric_attempts', [
                'user_id' => $userId,
                'failed_attempts' => 1,
                'last_attempt' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    private function resetFailedAttempts(int $userId): void {
        $this->db->update(
            'user_biometric_attempts',
            [
                'failed_attempts' => 0,
                'last_attempt' => date('Y-m-d H:i:s')
            ],
            'user_id = :user_id',
            ['user_id' => $userId]
        );
    }
    
    private function lockoutUser(int $userId): void {
        $lockoutUntil = date('Y-m-d H:i:s', time() + $this->config['lockout_duration']);
        
        $this->db->insert('user_lockouts', [
            'user_id' => $userId,
            'locked_until' => $lockoutUntil,
            'reason' => 'biometric_failed_attempts',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function logBiometricActivity(int $userId, string $biometricType, string $status): void {
        $this->db->insert('biometric_activity_log', [
            'user_id' => $userId,
            'biometric_type' => $biometricType,
            'status' => $status,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getUserBiometrics(int $userId): array {
        return $this->db->fetchAll(
            "SELECT * FROM user_biometrics 
             WHERE user_id = :user_id AND enabled = 1
             ORDER BY created_at ASC",
            ['user_id' => $userId]
        );
    }
    
    public function disableBiometric(int $userId, string $biometricType = null): bool {
        try {
            if ($biometricType) {
                // Disable specific biometric type
                $this->db->update(
                    'user_biometrics',
                    ['enabled' => false],
                    'user_id = :user_id AND biometric_type = :biometric_type',
                    ['user_id' => $userId, 'biometric_type' => $biometricType]
                );
            } else {
                // Disable all biometrics for user
                $this->db->update(
                    'user_biometrics',
                    ['enabled' => false],
                    'user_id = :user_id',
                    ['user_id' => $userId]
                );
                
                // Update user biometric status
                $this->db->update(
                    'users',
                    ['biometric_enabled' => false],
                    'id = :user_id',
                    ['user_id' => $userId]
                );
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Error disabling biometric: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteBiometric(int $userId, string $biometricType): bool {
        try {
            $this->db->delete(
                'user_biometrics',
                'user_id = :user_id AND biometric_type = :biometric_type',
                ['user_id' => $userId, 'biometric_type' => $biometricType]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error deleting biometric: " . $e->getMessage());
            return false;
        }
    }
    
    public function isBiometricEnabled(int $userId): bool {
        $biometrics = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM user_biometrics 
             WHERE user_id = :user_id AND enabled = 1",
            ['user_id' => $userId]
        );
        
        return $biometrics > 0;
    }
    
    public function getBiometricStats(): array {
        return [
            'total_users' => $this->db->fetchColumn("SELECT COUNT(*) FROM users"),
            'biometric_enabled_users' => $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE biometric_enabled = 1"),
            'biometric_types' => $this->getBiometricTypeStats(),
            'failed_attempts' => $this->getFailedAttemptsStats(),
            'lockouts' => $this->getLockoutStats(),
            'activity_log' => $this->getActivityLogStats()
        ];
    }
    
    private function getBiometricTypeStats(): array {
        return $this->db->fetchAll(
            "SELECT biometric_type, COUNT(*) as count, 
                    COUNT(CASE WHEN enabled = 1 THEN 1 END) as enabled_count
             FROM user_biometrics 
             GROUP BY biometric_type 
             ORDER BY count DESC"
        );
    }
    
    private function getFailedAttemptsStats(): array {
        return $this->db->fetchAll(
            "SELECT COUNT(*) as total_attempts,
                    COUNT(CASE WHEN failed_attempts > 0 THEN 1 END) as users_with_failures,
                    AVG(failed_attempts) as average_failures
             FROM user_biometric_attempts"
        );
    }
    
    private function getLockoutStats(): array {
        return $this->db->fetchAll(
            "SELECT COUNT(*) as total_lockouts,
                    COUNT(CASE WHEN locked_until > NOW() THEN 1 END) as active_lockouts
             FROM user_lockouts"
        );
    }
    
    private function getActivityLogStats(): array {
        return $this->db->fetchAll(
            "SELECT biometric_type, status, COUNT(*) as count
             FROM biometric_activity_log 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
             GROUP BY biometric_type, status
             ORDER BY count DESC"
        );
    }
    
    public function getConfig(): array {
        return $this->config;
    }
    
    public function updateConfig(array $config): bool {
        try {
            $this->config = array_merge($this->config, $config);
            
            // Save to database
            $this->db->update(
                'biometric_config',
                [
                    'config' => json_encode($this->config),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = 1'
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating biometric config: " . $e->getMessage());
            return false;
        }
    }
    
    public function getBiometricHistory(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT * FROM biometric_activity_log 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC 
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function cleanupExpiredData(): bool {
        try {
            // Cleanup expired lockouts
            $this->db->query("DELETE FROM user_lockouts WHERE locked_until < NOW()");
            
            // Cleanup old activity logs (older than 90 days)
            $this->db->query("DELETE FROM biometric_activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
            
            return true;
        } catch (\Exception $e) {
            error_log("Error cleaning up biometric data: " . $e->getMessage());
            return false;
        }
    }
    
    public function getSupportedBiometricTypes(): array {
        $types = [];
        
        if ($this->config['fingerprint_enabled']) {
            $types['fingerprint'] = [
                'name' => 'Fingerprint',
                'description' => 'Use your fingerprint to authenticate',
                'icon' => 'fas fa-fingerprint',
                'color' => '#4CAF50'
            ];
        }
        
        if ($this->config['face_id_enabled']) {
            $types['face_id'] = [
                'name' => 'Face ID',
                'description' => 'Use facial recognition to authenticate',
                'icon' => 'fas fa-user-circle',
                'color' => '#2196F3'
            ];
        }
        
        if ($this->config['voice_enabled']) {
            $types['voice'] = [
                'name' => 'Voice Recognition',
                'description' => 'Use your voice to authenticate',
                'icon' => 'fas fa-microphone',
                'color' => '#FF9800'
            ];
        }
        
        if ($this->config['iris_enabled']) {
            $types['iris'] = [
                'name' => 'Iris Recognition',
                'description' => 'Use iris scanning to authenticate',
                'icon' => 'fas fa-eye',
                'color' => '#9C27B0'
            ];
        }
        
        if ($this->config['palm_print_enabled']) {
            $types['palm_print'] = [
                'name' => 'Palm Print',
                'description' => 'Use palm print to authenticate',
                'icon' => 'fas fa-hand-paper',
                'color' => '#E91E63'
            ];
        }
        
        return $types;
    }
}