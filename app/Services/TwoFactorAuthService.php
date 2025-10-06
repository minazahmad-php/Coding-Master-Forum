<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;
use PDO;

/**
 * Two-Factor Authentication Service
 */
class TwoFactorAuthService
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
     * Generate 2FA secret
     */
    public function generateSecret($userId)
    {
        try {
            $secret = $this->generateRandomSecret();
            
            // Store secret in database
            $this->db->query(
                "INSERT INTO user_2fa (user_id, secret, created_at) VALUES (?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE secret = ?, updated_at = NOW()",
                [$userId, $secret, $secret]
            );

            return $secret;
        } catch (\Exception $e) {
            $this->logger->error('2FA secret generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate QR code for 2FA setup
     */
    public function generateQRCode($userId, $email)
    {
        $secret = $this->getUserSecret($userId);
        $issuer = config('app.name');
        $accountName = $email;
        
        $otpauth = "otpauth://totp/{$issuer}:{$accountName}?secret={$secret}&issuer={$issuer}";
        
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($otpauth);
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode($userId, $code)
    {
        try {
            $secret = $this->getUserSecret($userId);
            
            if (!$secret) {
                return false;
            }

            // Verify TOTP code
            $currentTime = floor(time() / 30);
            
            for ($i = -1; $i <= 1; $i++) {
                $time = $currentTime + $i;
                $expectedCode = $this->generateTOTP($secret, $time);
                
                if (hash_equals($expectedCode, $code)) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error('2FA verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enable 2FA for user
     */
    public function enable2FA($userId, $code)
    {
        if ($this->verifyCode($userId, $code)) {
            $this->db->query(
                "UPDATE user_2fa SET enabled = 1, enabled_at = NOW() WHERE user_id = ?",
                [$userId]
            );
            return true;
        }
        return false;
    }

    /**
     * Disable 2FA for user
     */
    public function disable2FA($userId, $code)
    {
        if ($this->verifyCode($userId, $code)) {
            $this->db->query(
                "DELETE FROM user_2fa WHERE user_id = ?",
                [$userId]
            );
            return true;
        }
        return false;
    }

    /**
     * Check if 2FA is enabled for user
     */
    public function is2FAEnabled($userId)
    {
        $result = $this->db->fetch(
            "SELECT enabled FROM user_2fa WHERE user_id = ? AND enabled = 1",
            [$userId]
        );
        
        return $result ? true : false;
    }

    /**
     * Generate backup codes
     */
    public function generateBackupCodes($userId)
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = Security::generateToken(8);
        }

        $this->db->query(
            "UPDATE user_2fa SET backup_codes = ? WHERE user_id = ?",
            [json_encode($codes), $userId]
        );

        return $codes;
    }

    /**
     * Verify backup code
     */
    public function verifyBackupCode($userId, $code)
    {
        $result = $this->db->fetch(
            "SELECT backup_codes FROM user_2fa WHERE user_id = ?",
            [$userId]
        );

        if (!$result) {
            return false;
        }

        $backupCodes = json_decode($result['backup_codes'], true);
        $key = array_search($code, $backupCodes);

        if ($key !== false) {
            // Remove used backup code
            unset($backupCodes[$key]);
            $this->db->query(
                "UPDATE user_2fa SET backup_codes = ? WHERE user_id = ?",
                [json_encode(array_values($backupCodes)), $userId]
            );
            return true;
        }

        return false;
    }

    /**
     * Generate random secret
     */
    private function generateRandomSecret()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    /**
     * Get user's 2FA secret
     */
    private function getUserSecret($userId)
    {
        $result = $this->db->fetch(
            "SELECT secret FROM user_2fa WHERE user_id = ?",
            [$userId]
        );
        
        return $result ? $result['secret'] : null;
    }

    /**
     * Generate TOTP code
     */
    private function generateTOTP($secret, $time)
    {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $time);
        $hmac = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hmac[19]) & 0xf;
        $code = (
            ((ord($hmac[$offset + 0]) & 0x7f) << 24) |
            ((ord($hmac[$offset + 1]) & 0xff) << 16) |
            ((ord($hmac[$offset + 2]) & 0xff) << 8) |
            (ord($hmac[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Base32 decode
     */
    private function base32Decode($input)
    {
        $map = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $input = strtoupper($input);
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($input); $i++) {
            $v <<= 5;
            $v += $map[$input[$i]];
            $vbits += 5;

            if ($vbits >= 8) {
                $output .= chr(($v >> ($vbits - 8)) & 255);
                $vbits -= 8;
            }
        }

        return $output;
    }
}