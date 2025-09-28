<?php
declare(strict_types=1);

namespace Services;

class TwoFactorAuthenticationService {
    private Database $db;
    private array $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = $this->getConfig();
    }
    
    private function getConfig(): array {
        return [
            'enabled' => TWO_FACTOR_ENABLED ?? true,
            'issuer' => TWO_FACTOR_ISSUER ?? 'Forum App',
            'algorithm' => TWO_FACTOR_ALGORITHM ?? 'sha1',
            'digits' => TWO_FACTOR_DIGITS ?? 6,
            'period' => TWO_FACTOR_PERIOD ?? 30,
            'window' => TWO_FACTOR_WINDOW ?? 1,
            'backup_codes_count' => TWO_FACTOR_BACKUP_CODES_COUNT ?? 10,
            'sms_enabled' => TWO_FACTOR_SMS_ENABLED ?? false,
            'email_enabled' => TWO_FACTOR_EMAIL_ENABLED ?? true,
            'app_enabled' => TWO_FACTOR_APP_ENABLED ?? true
        ];
    }
    
    public function generateSecret(): string {
        $secret = $this->generateRandomSecret();
        
        return $secret;
    }
    
    private function generateRandomSecret(int $length = 32): string {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $secret;
    }
    
    public function generateQRCode(string $secret, string $email): string {
        $issuer = $this->config['issuer'];
        $account = $email;
        
        $otpauth = "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&algorithm={$this->config['algorithm']}&digits={$this->config['digits']}&period={$this->config['period']}";
        
        return $otpauth;
    }
    
    public function generateQRCodeImage(string $secret, string $email): string {
        $qrCodeUrl = $this->generateQRCode($secret, $email);
        
        // Use Google Charts API to generate QR code image
        $qrCodeImageUrl = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=" . urlencode($qrCodeUrl);
        
        return $qrCodeImageUrl;
    }
    
    public function generateTOTPCode(string $secret): string {
        $time = floor(time() / $this->config['period']);
        $code = $this->generateHOTPCode($secret, $time);
        
        return $code;
    }
    
    private function generateHOTPCode(string $secret, int $counter): string {
        $key = $this->base32Decode($secret);
        $counterBytes = pack('N*', 0, $counter);
        
        $hash = hash_hmac($this->config['algorithm'], $counterBytes, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0xF;
        
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % pow(10, $this->config['digits']);
        
        return str_pad($code, $this->config['digits'], '0', STR_PAD_LEFT);
    }
    
    private function base32Decode(string $input): string {
        $input = strtoupper($input);
        $input = str_replace('=', '', $input);
        
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        
        for ($i = 0; $i < strlen($input); $i += 8) {
            $chunk = substr($input, $i, 8);
            $chunk = str_pad($chunk, 8, 'A');
            
            $bits = '';
            for ($j = 0; $j < strlen($chunk); $j++) {
                $char = $chunk[$j];
                $pos = strpos($chars, $char);
                if ($pos !== false) {
                    $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
                }
            }
            
            for ($k = 0; $k < strlen($bits); $k += 8) {
                $byte = substr($bits, $k, 8);
                if (strlen($byte) == 8) {
                    $output .= chr(bindec($byte));
                }
            }
        }
        
        return $output;
    }
    
    public function verifyTOTPCode(string $secret, string $code): bool {
        $time = floor(time() / $this->config['period']);
        
        for ($i = -$this->config['window']; $i <= $this->config['window']; $i++) {
            $testCode = $this->generateHOTPCode($secret, $time + $i);
            if ($testCode === $code) {
                return true;
            }
        }
        
        return false;
    }
    
    public function enableTwoFactor(int $userId, string $secret): array {
        try {
            $this->db->beginTransaction();
            
            // Generate backup codes
            $backupCodes = $this->generateBackupCodes();
            
            // Store 2FA data
            $this->db->insert('user_two_factor', [
                'user_id' => $userId,
                'secret' => $secret,
                'backup_codes' => json_encode($backupCodes),
                'enabled' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update user 2FA status
            $this->db->update(
                'users',
                ['two_factor_enabled' => true],
                'id = :user_id',
                ['user_id' => $userId]
            );
            
            $this->db->commit();
            
            return [
                'success' => true,
                'backup_codes' => $backupCodes,
                'message' => 'Two-factor authentication enabled successfully'
            ];
            
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error enabling 2FA: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to enable two-factor authentication'
            ];
        }
    }
    
    public function disableTwoFactor(int $userId): bool {
        try {
            $this->db->beginTransaction();
            
            // Remove 2FA data
            $this->db->delete('user_two_factor', 'user_id = :user_id', ['user_id' => $userId]);
            
            // Update user 2FA status
            $this->db->update(
                'users',
                ['two_factor_enabled' => false],
                'id = :user_id',
                ['user_id' => $userId]
            );
            
            $this->db->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error disabling 2FA: " . $e->getMessage());
            return false;
        }
    }
    
    public function verifyTwoFactor(int $userId, string $code): bool {
        $twoFactor = $this->getUserTwoFactor($userId);
        
        if (!$twoFactor || !$twoFactor['enabled']) {
            return false;
        }
        
        // Check if code is a backup code
        if ($this->verifyBackupCode($userId, $code)) {
            return true;
        }
        
        // Verify TOTP code
        return $this->verifyTOTPCode($twoFactor['secret'], $code);
    }
    
    private function verifyBackupCode(int $userId, string $code): bool {
        $twoFactor = $this->getUserTwoFactor($userId);
        
        if (!$twoFactor) {
            return false;
        }
        
        $backupCodes = json_decode($twoFactor['backup_codes'], true);
        
        if (in_array($code, $backupCodes)) {
            // Remove used backup code
            $backupCodes = array_diff($backupCodes, [$code]);
            
            $this->db->update(
                'user_two_factor',
                ['backup_codes' => json_encode(array_values($backupCodes))],
                'user_id = :user_id',
                ['user_id' => $userId]
            );
            
            return true;
        }
        
        return false;
    }
    
    public function generateBackupCodes(): array {
        $codes = [];
        
        for ($i = 0; $i < $this->config['backup_codes_count']; $i++) {
            $codes[] = $this->generateRandomCode(8);
        }
        
        return $codes;
    }
    
    private function generateRandomCode(int $length = 8): string {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $code;
    }
    
    public function regenerateBackupCodes(int $userId): array {
        try {
            $backupCodes = $this->generateBackupCodes();
            
            $this->db->update(
                'user_two_factor',
                ['backup_codes' => json_encode($backupCodes)],
                'user_id = :user_id',
                ['user_id' => $userId]
            );
            
            return [
                'success' => true,
                'backup_codes' => $backupCodes
            ];
            
        } catch (\Exception $e) {
            error_log("Error regenerating backup codes: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to regenerate backup codes'
            ];
        }
    }
    
    public function getUserTwoFactor(int $userId): ?array {
        $twoFactor = $this->db->fetch(
            "SELECT * FROM user_two_factor WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        if (!$twoFactor) {
            return null;
        }
        
        $twoFactor['backup_codes'] = json_decode($twoFactor['backup_codes'], true);
        
        return $twoFactor;
    }
    
    public function isTwoFactorEnabled(int $userId): bool {
        $twoFactor = $this->getUserTwoFactor($userId);
        return $twoFactor && $twoFactor['enabled'];
    }
    
    public function sendSMSCode(int $userId, string $phoneNumber): array {
        if (!$this->config['sms_enabled']) {
            return [
                'success' => false,
                'message' => 'SMS 2FA is not enabled'
            ];
        }
        
        try {
            $code = $this->generateRandomCode(6);
            
            // Store SMS code
            $this->db->insert('sms_codes', [
                'user_id' => $userId,
                'phone_number' => $phoneNumber,
                'code' => $code,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+5 minutes')),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Send SMS (implement SMS service)
            $smsSent = $this->sendSMS($phoneNumber, "Your verification code is: {$code}");
            
            if ($smsSent) {
                return [
                    'success' => true,
                    'message' => 'SMS code sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send SMS code'
                ];
            }
            
        } catch (\Exception $e) {
            error_log("Error sending SMS code: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send SMS code'
            ];
        }
    }
    
    public function verifySMSCode(int $userId, string $code): bool {
        try {
            $smsCode = $this->db->fetch(
                "SELECT * FROM sms_codes 
                 WHERE user_id = :user_id AND code = :code AND expires_at > NOW() AND used = 0
                 ORDER BY created_at DESC LIMIT 1",
                ['user_id' => $userId, 'code' => $code]
            );
            
            if (!$smsCode) {
                return false;
            }
            
            // Mark code as used
            $this->db->update(
                'sms_codes',
                ['used' => true, 'used_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $smsCode['id']]
            );
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Error verifying SMS code: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendEmailCode(int $userId, string $email): array {
        if (!$this->config['email_enabled']) {
            return [
                'success' => false,
                'message' => 'Email 2FA is not enabled'
            ];
        }
        
        try {
            $code = $this->generateRandomCode(6);
            
            // Store email code
            $this->db->insert('email_codes', [
                'user_id' => $userId,
                'email' => $email,
                'code' => $code,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+5 minutes')),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Send email (implement email service)
            $emailSent = $this->sendEmail($email, 'Two-Factor Authentication Code', "Your verification code is: {$code}");
            
            if ($emailSent) {
                return [
                    'success' => true,
                    'message' => 'Email code sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send email code'
                ];
            }
            
        } catch (\Exception $e) {
            error_log("Error sending email code: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send email code'
            ];
        }
    }
    
    public function verifyEmailCode(int $userId, string $code): bool {
        try {
            $emailCode = $this->db->fetch(
                "SELECT * FROM email_codes 
                 WHERE user_id = :user_id AND code = :code AND expires_at > NOW() AND used = 0
                 ORDER BY created_at DESC LIMIT 1",
                ['user_id' => $userId, 'code' => $code]
            );
            
            if (!$emailCode) {
                return false;
            }
            
            // Mark code as used
            $this->db->update(
                'email_codes',
                ['used' => true, 'used_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $emailCode['id']]
            );
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Error verifying email code: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendSMS(string $phoneNumber, string $message): bool {
        // Implement SMS service (Twilio, AWS SNS, etc.)
        // This is a placeholder implementation
        return true;
    }
    
    private function sendEmail(string $email, string $subject, string $message): bool {
        // Implement email service
        // This is a placeholder implementation
        return true;
    }
    
    public function getTwoFactorStats(): array {
        return [
            'total_users' => $this->db->fetchColumn("SELECT COUNT(*) FROM users"),
            'two_factor_enabled' => $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE two_factor_enabled = 1"),
            'two_factor_disabled' => $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE two_factor_enabled = 0"),
            'enabled_percentage' => $this->getEnabledPercentage(),
            'backup_codes_used' => $this->getBackupCodesUsed(),
            'sms_codes_sent' => $this->getSMSCodesSent(),
            'email_codes_sent' => $this->getEmailCodesSent()
        ];
    }
    
    private function getEnabledPercentage(): float {
        $total = $this->db->fetchColumn("SELECT COUNT(*) FROM users");
        $enabled = $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE two_factor_enabled = 1");
        
        return $total > 0 ? round(($enabled / $total) * 100, 2) : 0;
    }
    
    private function getBackupCodesUsed(): int {
        return $this->db->fetchColumn("SELECT COUNT(*) FROM user_two_factor WHERE backup_codes_used > 0");
    }
    
    private function getSMSCodesSent(): int {
        return $this->db->fetchColumn("SELECT COUNT(*) FROM sms_codes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
    }
    
    private function getEmailCodesSent(): int {
        return $this->db->fetchColumn("SELECT COUNT(*) FROM email_codes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
    }
    
    public function getConfig(): array {
        return $this->config;
    }
    
    public function updateConfig(array $config): bool {
        try {
            $this->config = array_merge($this->config, $config);
            
            // Save to database
            $this->db->update(
                'two_factor_config',
                [
                    'config' => json_encode($this->config),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = 1'
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating 2FA config: " . $e->getMessage());
            return false;
        }
    }
    
    public function cleanupExpiredCodes(): bool {
        try {
            // Cleanup expired SMS codes
            $this->db->query("DELETE FROM sms_codes WHERE expires_at < NOW()");
            
            // Cleanup expired email codes
            $this->db->query("DELETE FROM email_codes WHERE expires_at < NOW()");
            
            return true;
        } catch (\Exception $e) {
            error_log("Error cleaning up expired codes: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTwoFactorHistory(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT 'totp' as type, created_at, 'TOTP verification' as description
             FROM user_two_factor 
             WHERE user_id = :user_id
             UNION ALL
             SELECT 'sms' as type, created_at, 'SMS verification' as description
             FROM sms_codes 
             WHERE user_id = :user_id AND used = 1
             UNION ALL
             SELECT 'email' as type, created_at, 'Email verification' as description
             FROM email_codes 
             WHERE user_id = :user_id AND used = 1
             ORDER BY created_at DESC 
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function getTwoFactorAnalytics(int $userId): array {
        $twoFactor = $this->getUserTwoFactor($userId);
        
        if (!$twoFactor) {
            return [];
        }
        
        $backupCodes = $twoFactor['backup_codes'];
        $remainingBackupCodes = count($backupCodes);
        $totalBackupCodes = $this->config['backup_codes_count'];
        $usedBackupCodes = $totalBackupCodes - $remainingBackupCodes;
        
        $smsCodesSent = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM sms_codes WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        $emailCodesSent = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM email_codes WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        return [
            'enabled' => $twoFactor['enabled'],
            'enabled_at' => $twoFactor['created_at'],
            'backup_codes' => [
                'total' => $totalBackupCodes,
                'remaining' => $remainingBackupCodes,
                'used' => $usedBackupCodes,
                'percentage_used' => $totalBackupCodes > 0 ? round(($usedBackupCodes / $totalBackupCodes) * 100, 2) : 0
            ],
            'sms_codes_sent' => $smsCodesSent,
            'email_codes_sent' => $emailCodesSent,
            'last_used' => $this->getLastUsed($userId)
        ];
    }
    
    private function getLastUsed(int $userId): ?string {
        $lastUsed = $this->db->fetchColumn(
            "SELECT MAX(created_at) FROM (
                SELECT created_at FROM sms_codes WHERE user_id = :user_id AND used = 1
                UNION ALL
                SELECT created_at FROM email_codes WHERE user_id = :user_id AND used = 1
            ) as last_used",
            ['user_id' => $userId]
        );
        
        return $lastUsed;
    }
}