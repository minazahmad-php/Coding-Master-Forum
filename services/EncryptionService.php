<?php
declare(strict_types=1);

namespace Services;

class EncryptionService {
    private string $algorithm;
    private string $key;
    private string $iv;
    
    public function __construct() {
        $this->algorithm = 'AES-256-GCM';
        $this->key = $this->getEncryptionKey();
        $this->iv = $this->generateIV();
    }
    
    private function getEncryptionKey(): string {
        // In production, this should be stored securely
        $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default-encryption-key-change-in-production';
        
        // Ensure key is 32 bytes for AES-256
        return hash('sha256', $key, true);
    }
    
    private function generateIV(): string {
        return random_bytes(12); // 12 bytes for GCM mode
    }
    
    public function encrypt(string $data): string {
        if (empty($data)) {
            return $data;
        }
        
        try {
            $iv = $this->generateIV();
            $encrypted = openssl_encrypt($data, $this->algorithm, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
            
            if ($encrypted === false) {
                throw new \Exception('Encryption failed');
            }
            
            // Combine IV, tag, and encrypted data
            $result = base64_encode($iv . $tag . $encrypted);
            
            return $result;
        } catch (\Exception $e) {
            error_log("Encryption error: " . $e->getMessage());
            throw new \Exception('Failed to encrypt data');
        }
    }
    
    public function decrypt(string $encryptedData): string {
        if (empty($encryptedData)) {
            return $encryptedData;
        }
        
        try {
            $data = base64_decode($encryptedData);
            
            if ($data === false) {
                throw new \Exception('Invalid base64 data');
            }
            
            // Extract IV, tag, and encrypted data
            $iv = substr($data, 0, 12);
            $tag = substr($data, 12, 16);
            $encrypted = substr($data, 28);
            
            $decrypted = openssl_decrypt($encrypted, $this->algorithm, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
            
            if ($decrypted === false) {
                throw new \Exception('Decryption failed');
            }
            
            return $decrypted;
        } catch (\Exception $e) {
            error_log("Decryption error: " . $e->getMessage());
            throw new \Exception('Failed to decrypt data');
        }
    }
    
    public function encryptFile(string $filePath): string {
        if (!file_exists($filePath)) {
            throw new \Exception('File not found');
        }
        
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new \Exception('Failed to read file');
        }
        
        return $this->encrypt($fileContent);
    }
    
    public function decryptFile(string $encryptedContent, string $outputPath): bool {
        try {
            $decryptedContent = $this->decrypt($encryptedContent);
            
            $result = file_put_contents($outputPath, $decryptedContent);
            
            return $result !== false;
        } catch (\Exception $e) {
            error_log("File decryption error: " . $e->getMessage());
            return false;
        }
    }
    
    public function encryptArray(array $data): string {
        $jsonData = json_encode($data);
        if ($jsonData === false) {
            throw new \Exception('Failed to encode array to JSON');
        }
        
        return $this->encrypt($jsonData);
    }
    
    public function decryptArray(string $encryptedData): array {
        $jsonData = $this->decrypt($encryptedData);
        $data = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to decode JSON data');
        }
        
        return $data;
    }
    
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }
    
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    public function generateSecureToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
    
    public function generateAPIKey(): string {
        return 'ufh_' . bin2hex(random_bytes(32));
    }
    
    public function generateSessionToken(): string {
        return bin2hex(random_bytes(32));
    }
    
    public function hashSensitiveData(string $data): string {
        return hash('sha256', $data . $this->key);
    }
    
    public function encryptSensitiveField(string $value): string {
        // For database fields that need encryption
        return $this->encrypt($value);
    }
    
    public function decryptSensitiveField(string $encryptedValue): string {
        // For database fields that need decryption
        return $this->decrypt($encryptedValue);
    }
    
    public function encryptUserData(array $userData): array {
        $sensitiveFields = ['email', 'phone', 'address', 'bio'];
        $encryptedData = $userData;
        
        foreach ($sensitiveFields as $field) {
            if (isset($userData[$field]) && !empty($userData[$field])) {
                $encryptedData[$field] = $this->encrypt($userData[$field]);
            }
        }
        
        return $encryptedData;
    }
    
    public function decryptUserData(array $userData): array {
        $sensitiveFields = ['email', 'phone', 'address', 'bio'];
        $decryptedData = $userData;
        
        foreach ($sensitiveFields as $field) {
            if (isset($userData[$field]) && !empty($userData[$field])) {
                try {
                    $decryptedData[$field] = $this->decrypt($userData[$field]);
                } catch (\Exception $e) {
                    // If decryption fails, keep original value
                    $decryptedData[$field] = $userData[$field];
                }
            }
        }
        
        return $decryptedData;
    }
    
    public function encryptMessageContent(string $content, int $senderId, int $recipientId): string {
        // Add metadata for additional security
        $metadata = [
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'timestamp' => time(),
            'content' => $content
        ];
        
        return $this->encryptArray($metadata);
    }
    
    public function decryptMessageContent(string $encryptedContent): array {
        return $this->decryptArray($encryptedContent);
    }
    
    public function encryptAttachment(string $filePath, string $originalName): array {
        $fileContent = file_get_contents($filePath);
        $encryptedContent = $this->encrypt($fileContent);
        
        // Generate secure filename
        $secureFilename = $this->generateSecureToken() . '.enc';
        
        // Save encrypted file
        $encryptedPath = UPLOADS_PATH . '/encrypted/' . $secureFilename;
        file_put_contents($encryptedPath, $encryptedContent);
        
        return [
            'original_name' => $originalName,
            'encrypted_name' => $secureFilename,
            'file_path' => $encryptedPath,
            'file_size' => strlen($encryptedContent),
            'encrypted' => true
        ];
    }
    
    public function decryptAttachment(string $encryptedPath, string $outputPath): bool {
        $encryptedContent = file_get_contents($encryptedPath);
        return $this->decryptFile($encryptedContent, $outputPath);
    }
    
    public function generateKeyPair(): array {
        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        
        $res = openssl_pkey_new($config);
        
        if ($res === false) {
            throw new \Exception('Failed to generate key pair');
        }
        
        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)['key'];
        
        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey
        ];
    }
    
    public function encryptWithPublicKey(string $data, string $publicKey): string {
        $encrypted = '';
        $result = openssl_public_encrypt($data, $encrypted, $publicKey);
        
        if ($result === false) {
            throw new \Exception('Public key encryption failed');
        }
        
        return base64_encode($encrypted);
    }
    
    public function decryptWithPrivateKey(string $encryptedData, string $privateKey): string {
        $data = base64_decode($encryptedData);
        $decrypted = '';
        $result = openssl_private_decrypt($data, $decrypted, $privateKey);
        
        if ($result === false) {
            throw new \Exception('Private key decryption failed');
        }
        
        return $decrypted;
    }
    
    public function signData(string $data, string $privateKey): string {
        $signature = '';
        $result = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA512);
        
        if ($result === false) {
            throw new \Exception('Data signing failed');
        }
        
        return base64_encode($signature);
    }
    
    public function verifySignature(string $data, string $signature, string $publicKey): bool {
        $signature = base64_decode($signature);
        $result = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA512);
        
        return $result === 1;
    }
    
    public function generateHMAC(string $data, string $key = null): string {
        $key = $key ?? $this->key;
        return hash_hmac('sha256', $data, $key);
    }
    
    public function verifyHMAC(string $data, string $signature, string $key = null): bool {
        $expectedSignature = $this->generateHMAC($data, $key);
        return hash_equals($expectedSignature, $signature);
    }
    
    public function secureRandomString(int $length = 32): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $string;
    }
    
    public function obfuscateEmail(string $email): string {
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 2) {
            return $username . '@' . $domain;
        }
        
        $obfuscated = $username[0] . str_repeat('*', strlen($username) - 2) . $username[-1];
        return $obfuscated . '@' . $domain;
    }
    
    public function obfuscatePhone(string $phone): string {
        if (strlen($phone) <= 4) {
            return str_repeat('*', strlen($phone));
        }
        
        return substr($phone, 0, 2) . str_repeat('*', strlen($phone) - 4) . substr($phone, -2);
    }
    
    public function getEncryptionInfo(): array {
        return [
            'algorithm' => $this->algorithm,
            'key_length' => strlen($this->key) * 8,
            'iv_length' => strlen($this->iv) * 8,
            'supported_algorithms' => openssl_get_cipher_methods(),
            'openssl_version' => OPENSSL_VERSION_TEXT
        ];
    }
}