<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;

/**
 * Biometric Authentication Service
 */
class BiometricAuthService
{
    private $db;
    private $logger;

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
        $this->logger = $app->get('logger');
    }

    /**
     * Register biometric data
     */
    public function registerBiometric($userId, $biometricData, $type = 'fingerprint')
    {
        try {
            // Encrypt biometric data
            $encryptedData = $this->encryptBiometricData($biometricData);
            
            // Store in database
            $this->db->query(
                "INSERT INTO user_biometrics (user_id, type, data, created_at) VALUES (?, ?, ?, NOW())",
                [$userId, $type, $encryptedData]
            );

            $this->logger->info("Biometric registered for user {$userId}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Biometric registration failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify biometric data
     */
    public function verifyBiometric($userId, $biometricData, $type = 'fingerprint')
    {
        try {
            $storedData = $this->getUserBiometricData($userId, $type);
            
            if (!$storedData) {
                return false;
            }

            // Decrypt stored data
            $decryptedData = $this->decryptBiometricData($storedData);
            
            // Compare biometric data (simplified comparison)
            $similarity = $this->compareBiometricData($decryptedData, $biometricData);
            
            // Threshold for match (adjust as needed)
            $threshold = 0.85;
            
            if ($similarity >= $threshold) {
                $this->logger->info("Biometric verification successful for user {$userId}");
                return true;
            }

            $this->logger->warning("Biometric verification failed for user {$userId}");
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Biometric verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if biometric is enabled for user
     */
    public function isBiometricEnabled($userId, $type = 'fingerprint')
    {
        $result = $this->db->fetch(
            "SELECT id FROM user_biometrics WHERE user_id = ? AND type = ?",
            [$userId, $type]
        );
        
        return $result ? true : false;
    }

    /**
     * Remove biometric data
     */
    public function removeBiometric($userId, $type = 'fingerprint')
    {
        try {
            $this->db->query(
                "DELETE FROM user_biometrics WHERE user_id = ? AND type = ?",
                [$userId, $type]
            );

            $this->logger->info("Biometric removed for user {$userId}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Biometric removal failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all biometric types for user
     */
    public function getUserBiometricTypes($userId)
    {
        $results = $this->db->fetchAll(
            "SELECT type, created_at FROM user_biometrics WHERE user_id = ?",
            [$userId]
        );
        
        return $results;
    }

    /**
     * Encrypt biometric data
     */
    private function encryptBiometricData($data)
    {
        $key = config('app.key');
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt biometric data
     */
    private function decryptBiometricData($encryptedData)
    {
        $key = config('app.key');
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * Compare biometric data (simplified)
     */
    private function compareBiometricData($stored, $provided)
    {
        // This is a simplified comparison
        // In real implementation, you would use proper biometric comparison algorithms
        $storedHash = hash('sha256', $stored);
        $providedHash = hash('sha256', $provided);
        
        // Calculate similarity based on hash comparison
        $similarity = 1 - (levenshtein($storedHash, $providedHash) / max(strlen($storedHash), strlen($providedHash)));
        
        return $similarity;
    }

    /**
     * Generate biometric challenge
     */
    public function generateChallenge($userId)
    {
        $challenge = Security::generateToken(32);
        
        // Store challenge with expiration
        $this->db->query(
            "INSERT INTO biometric_challenges (user_id, challenge, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))",
            [$userId, $challenge]
        );

        return $challenge;
    }

    /**
     * Verify biometric challenge
     */
    public function verifyChallenge($userId, $challenge)
    {
        $result = $this->db->fetch(
            "SELECT id FROM biometric_challenges WHERE user_id = ? AND challenge = ? AND expires_at > NOW()",
            [$userId, $challenge]
        );

        if ($result) {
            // Remove used challenge
            $this->db->query(
                "DELETE FROM biometric_challenges WHERE id = ?",
                [$result['id']]
            );
            return true;
        }

        return false;
    }
}