<?php
declare(strict_types=1);

/**
 * Modern Forum - Social Login Service
 * Handles OAuth integration with Google, Facebook, Twitter
 */

namespace Services;

use Core\Database;
use Core\Logger;
use Core\Mail;
use Models\User;

class SocialLoginService
{
    private Database $db;
    private Logger $logger;
    private Mail $mail;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->mail = new Mail();
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        $this->config = [
            'google' => [
                'client_id' => GOOGLE_CLIENT_ID,
                'client_secret' => GOOGLE_CLIENT_SECRET,
                'redirect_uri' => APP_URL . '/auth/google/callback',
                'scope' => 'openid email profile',
                'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'user_info_url' => 'https://www.googleapis.com/oauth2/v2/userinfo'
            ],
            'facebook' => [
                'client_id' => FACEBOOK_CLIENT_ID,
                'client_secret' => FACEBOOK_CLIENT_SECRET,
                'redirect_uri' => APP_URL . '/auth/facebook/callback',
                'scope' => 'email,public_profile',
                'auth_url' => 'https://www.facebook.com/v18.0/dialog/oauth',
                'token_url' => 'https://graph.facebook.com/v18.0/oauth/access_token',
                'user_info_url' => 'https://graph.facebook.com/v18.0/me'
            ],
            'twitter' => [
                'client_id' => TWITTER_CLIENT_ID,
                'client_secret' => TWITTER_CLIENT_SECRET,
                'redirect_uri' => APP_URL . '/auth/twitter/callback',
                'scope' => 'tweet.read users.read',
                'auth_url' => 'https://twitter.com/i/oauth2/authorize',
                'token_url' => 'https://api.twitter.com/2/oauth2/token',
                'user_info_url' => 'https://api.twitter.com/2/users/me'
            ]
        ];
    }

    public function getAuthUrl(string $provider): string
    {
        if (!isset($this->config[$provider])) {
            throw new \InvalidArgumentException("Unsupported provider: $provider");
        }

        $config = $this->config[$provider];
        
        // Generate state parameter for CSRF protection
        $state = $this->generateState();
        $this->storeState($state);

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => $config['scope'],
            'response_type' => 'code',
            'state' => $state
        ];

        if ($provider === 'google') {
            $params['access_type'] = 'offline';
            $params['prompt'] = 'consent';
        }

        return $config['auth_url'] . '?' . http_build_query($params);
    }

    public function handleCallback(string $provider, string $code, string $state): array
    {
        if (!isset($this->config[$provider])) {
            throw new \InvalidArgumentException("Unsupported provider: $provider");
        }

        // Verify state parameter
        if (!$this->verifyState($state)) {
            throw new \Exception('Invalid state parameter');
        }

        $config = $this->config[$provider];

        try {
            // Exchange code for access token
            $tokenData = $this->exchangeCodeForToken($provider, $code, $config);
            
            // Get user information
            $userInfo = $this->getUserInfo($provider, $tokenData['access_token'], $config);
            
            // Process user data
            $user = $this->processUserData($provider, $userInfo);
            
            // Create or update user
            $user = $this->createOrUpdateUser($user, $provider);
            
            // Log social login
            $this->logSocialLogin($user['id'], $provider, $userInfo);
            
            return [
                'success' => true,
                'user' => $user,
                'is_new_user' => $user['is_new_user'] ?? false
            ];

        } catch (\Exception $e) {
            $this->logger->error('Social login callback error', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    private function exchangeCodeForToken(string $provider, string $code, array $config): array
    {
        $postData = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $config['redirect_uri']
        ];

        $response = $this->makeHttpRequest($config['token_url'], 'POST', $postData);
        
        if (!$response || isset($response['error'])) {
            throw new \Exception('Failed to exchange code for token: ' . ($response['error_description'] ?? 'Unknown error'));
        }

        return $response;
    }

    private function getUserInfo(string $provider, string $accessToken, array $config): array
    {
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ];

        $response = $this->makeHttpRequest($config['user_info_url'], 'GET', null, $headers);
        
        if (!$response || isset($response['error'])) {
            throw new \Exception('Failed to get user info: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return $response;
    }

    private function processUserData(string $provider, array $userInfo): array
    {
        switch ($provider) {
            case 'google':
                return [
                    'provider' => 'google',
                    'provider_id' => $userInfo['id'],
                    'email' => $userInfo['email'],
                    'username' => $this->generateUsername($userInfo['name']),
                    'first_name' => $userInfo['given_name'] ?? '',
                    'last_name' => $userInfo['family_name'] ?? '',
                    'avatar' => $userInfo['picture'] ?? null,
                    'email_verified' => $userInfo['verified_email'] ?? false,
                    'raw_data' => json_encode($userInfo)
                ];

            case 'facebook':
                return [
                    'provider' => 'facebook',
                    'provider_id' => $userInfo['id'],
                    'email' => $userInfo['email'] ?? '',
                    'username' => $this->generateUsername($userInfo['name']),
                    'first_name' => $userInfo['first_name'] ?? '',
                    'last_name' => $userInfo['last_name'] ?? '',
                    'avatar' => $userInfo['picture']['data']['url'] ?? null,
                    'email_verified' => true, // Facebook emails are verified
                    'raw_data' => json_encode($userInfo)
                ];

            case 'twitter':
                return [
                    'provider' => 'twitter',
                    'provider_id' => $userInfo['data']['id'],
                    'email' => $userInfo['data']['email'] ?? '',
                    'username' => $this->generateUsername($userInfo['data']['username']),
                    'first_name' => $userInfo['data']['name'] ?? '',
                    'last_name' => '',
                    'avatar' => $userInfo['data']['profile_image_url'] ?? null,
                    'email_verified' => false, // Twitter doesn't provide email verification status
                    'raw_data' => json_encode($userInfo)
                ];

            default:
                throw new \InvalidArgumentException("Unsupported provider: $provider");
        }
    }

    private function createOrUpdateUser(array $userData, string $provider): array
    {
        try {
            // Check if user exists by provider ID
            $stmt = $this->db->prepare("
                SELECT u.* FROM users u
                JOIN social_logins sl ON u.id = sl.user_id
                WHERE sl.provider = ? AND sl.provider_id = ?
            ");
            $stmt->execute([$provider, $userData['provider_id']]);
            $existingUser = $stmt->fetch();

            if ($existingUser) {
                // Update existing user
                $this->updateUser($existingUser['id'], $userData);
                $userData['id'] = $existingUser['id'];
                $userData['is_new_user'] = false;
            } else {
                // Check if user exists by email
                if (!empty($userData['email'])) {
                    $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$userData['email']]);
                    $existingUser = $stmt->fetch();

                    if ($existingUser) {
                        // Link social account to existing user
                        $this->linkSocialAccount($existingUser['id'], $userData);
                        $userData['id'] = $existingUser['id'];
                        $userData['is_new_user'] = false;
                    } else {
                        // Create new user
                        $userId = $this->createNewUser($userData);
                        $userData['id'] = $userId;
                        $userData['is_new_user'] = true;
                    }
                } else {
                    // Create new user without email
                    $userId = $this->createNewUser($userData);
                    $userData['id'] = $userId;
                    $userData['is_new_user'] = true;
                }
            }

            return $userData;

        } catch (\Exception $e) {
            $this->logger->error('Failed to create or update user', [
                'provider' => $provider,
                'user_data' => $userData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function createNewUser(array $userData): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (
                username, email, first_name, last_name, avatar,
                email_verified_at, status, role, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $now = date('Y-m-d H:i:s');
        $emailVerifiedAt = $userData['email_verified'] ? $now : null;

        $stmt->execute([
            $userData['username'],
            $userData['email'],
            $userData['first_name'],
            $userData['last_name'],
            $userData['avatar'],
            $emailVerifiedAt,
            'active',
            'user',
            $now,
            $now
        ]);

        $userId = $this->db->lastInsertId();

        // Create social login record
        $this->createSocialLoginRecord($userId, $userData);

        // Send welcome email for new users
        if (!empty($userData['email'])) {
            $this->sendWelcomeEmail($userData);
        }

        return $userId;
    }

    private function updateUser(int $userId, array $userData): void
    {
        $stmt = $this->db->prepare("
            UPDATE users SET
                username = ?, first_name = ?, last_name = ?, avatar = ?,
                email_verified_at = COALESCE(email_verified_at, ?),
                updated_at = ?
            WHERE id = ?
        ");

        $emailVerifiedAt = $userData['email_verified'] ? date('Y-m-d H:i:s') : null;

        $stmt->execute([
            $userData['username'],
            $userData['first_name'],
            $userData['last_name'],
            $userData['avatar'],
            $emailVerifiedAt,
            date('Y-m-d H:i:s'),
            $userId
        ]);
    }

    private function linkSocialAccount(int $userId, array $userData): void
    {
        // Check if social account is already linked
        $stmt = $this->db->prepare("
            SELECT id FROM social_logins 
            WHERE user_id = ? AND provider = ? AND provider_id = ?
        ");
        $stmt->execute([$userId, $userData['provider'], $userData['provider_id']]);
        
        if (!$stmt->fetch()) {
            $this->createSocialLoginRecord($userId, $userData);
        }
    }

    private function createSocialLoginRecord(int $userId, array $userData): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO social_logins (
                user_id, provider, provider_id, email, raw_data, created_at
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $userData['provider'],
            $userData['provider_id'],
            $userData['email'],
            $userData['raw_data'],
            date('Y-m-d H:i:s')
        ]);
    }

    private function generateUsername(string $name): string
    {
        $username = strtolower(trim(preg_replace('/[^A-Za-z0-9]/', '', $name)));
        
        // Ensure username is not empty
        if (empty($username)) {
            $username = 'user' . uniqid();
        }

        // Check if username exists and make it unique
        $originalUsername = $username;
        $counter = 1;
        
        while ($this->usernameExists($username)) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        return $username;
    }

    private function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() !== false;
    }

    private function generateState(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function storeState(string $state): void
    {
        $_SESSION['oauth_state'] = $state;
    }

    private function verifyState(string $state): bool
    {
        return isset($_SESSION['oauth_state']) && hash_equals($_SESSION['oauth_state'], $state);
    }

    private function makeHttpRequest(string $url, string $method = 'GET', array $data = null, array $headers = []): ?array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge([
                'User-Agent: ModernForum/1.0',
                'Accept: application/json'
            ], $headers)
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL error: $error");
        }

        if ($httpCode >= 400) {
            throw new \Exception("HTTP error: $httpCode");
        }

        return json_decode($response, true);
    }

    private function logSocialLogin(int $userId, string $provider, array $userInfo): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO social_login_logs (
                    user_id, provider, provider_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $provider,
                $userInfo['id'] ?? $userInfo['data']['id'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to log social login', [
                'user_id' => $userId,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendWelcomeEmail(array $userData): void
    {
        try {
            $this->mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $this->mail->addAddress($userData['email']);
            $this->mail->setSubject('Welcome to ' . APP_NAME);
            $this->mail->setBody($this->getWelcomeEmailTemplate($userData));
            $this->mail->isHTML(true);
            
            $this->mail->send();
        } catch (\Exception $e) {
            $this->logger->error('Failed to send welcome email', [
                'email' => $userData['email'],
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getWelcomeEmailTemplate(array $userData): string
    {
        return "
            <h2>Welcome to " . APP_NAME . "!</h2>
            <p>Hi " . htmlspecialchars($userData['first_name']) . ",</p>
            <p>Thank you for joining our community! We're excited to have you on board.</p>
            <p>You can now:</p>
            <ul>
                <li>Create posts and join discussions</li>
                <li>Connect with other members</li>
                <li>Share your thoughts and ideas</li>
                <li>Participate in our vibrant community</li>
            </ul>
            <p>If you have any questions, feel free to reach out to our support team.</p>
            <p>Best regards,<br>The " . APP_NAME . " Team</p>
        ";
    }

    public function unlinkSocialAccount(int $userId, string $provider): bool
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM social_logins 
                WHERE user_id = ? AND provider = ?
            ");
            
            $result = $stmt->execute([$userId, $provider]);
            
            if ($result) {
                $this->logger->info('Social account unlinked', [
                    'user_id' => $userId,
                    'provider' => $provider
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to unlink social account', [
                'user_id' => $userId,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getLinkedAccounts(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT provider, provider_id, email, created_at
                FROM social_logins
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error('Failed to get linked accounts', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}