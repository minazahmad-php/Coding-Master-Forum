<?php
declare(strict_types=1);

namespace Services;

class OAuthIntegrationService {
    private Database $db;
    private array $providers;
    private array $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->providers = $this->getProviders();
        $this->config = $this->getConfig();
    }
    
    private function getProviders(): array {
        return [
            'google' => [
                'name' => 'Google',
                'auth_url' => 'https://accounts.google.com/o/oauth2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'user_info_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
                'scope' => 'openid email profile',
                'icon' => 'fab fa-google',
                'color' => '#4285F4'
            ],
            'facebook' => [
                'name' => 'Facebook',
                'auth_url' => 'https://www.facebook.com/v18.0/dialog/oauth',
                'token_url' => 'https://graph.facebook.com/v18.0/oauth/access_token',
                'user_info_url' => 'https://graph.facebook.com/me',
                'scope' => 'email public_profile',
                'icon' => 'fab fa-facebook',
                'color' => '#1877F2'
            ],
            'twitter' => [
                'name' => 'Twitter',
                'auth_url' => 'https://twitter.com/i/oauth2/authorize',
                'token_url' => 'https://api.twitter.com/2/oauth2/token',
                'user_info_url' => 'https://api.twitter.com/2/users/me',
                'scope' => 'tweet.read users.read',
                'icon' => 'fab fa-twitter',
                'color' => '#1DA1F2'
            ],
            'github' => [
                'name' => 'GitHub',
                'auth_url' => 'https://github.com/login/oauth/authorize',
                'token_url' => 'https://github.com/login/oauth/access_token',
                'user_info_url' => 'https://api.github.com/user',
                'scope' => 'user:email',
                'icon' => 'fab fa-github',
                'color' => '#333333'
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'auth_url' => 'https://www.linkedin.com/oauth/v2/authorization',
                'token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
                'user_info_url' => 'https://api.linkedin.com/v2/people/~',
                'scope' => 'r_liteprofile r_emailaddress',
                'icon' => 'fab fa-linkedin',
                'color' => '#0077B5'
            ],
            'microsoft' => [
                'name' => 'Microsoft',
                'auth_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
                'token_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
                'user_info_url' => 'https://graph.microsoft.com/v1.0/me',
                'scope' => 'openid email profile',
                'icon' => 'fab fa-microsoft',
                'color' => '#00BCF2'
            ],
            'discord' => [
                'name' => 'Discord',
                'auth_url' => 'https://discord.com/api/oauth2/authorize',
                'token_url' => 'https://discord.com/api/oauth2/token',
                'user_info_url' => 'https://discord.com/api/users/@me',
                'scope' => 'identify email',
                'icon' => 'fab fa-discord',
                'color' => '#5865F2'
            ],
            'apple' => [
                'name' => 'Apple',
                'auth_url' => 'https://appleid.apple.com/auth/authorize',
                'token_url' => 'https://appleid.apple.com/auth/token',
                'user_info_url' => 'https://appleid.apple.com/auth/userinfo',
                'scope' => 'name email',
                'icon' => 'fab fa-apple',
                'color' => '#000000'
            ]
        ];
    }
    
    private function getConfig(): array {
        return [
            'enabled' => OAUTH_ENABLED ?? true,
            'redirect_uri' => OAUTH_REDIRECT_URI ?? '',
            'state_secret' => OAUTH_STATE_SECRET ?? 'default_secret_change_me',
            'providers' => [
                'google' => [
                    'enabled' => OAUTH_GOOGLE_ENABLED ?? false,
                    'client_id' => OAUTH_GOOGLE_CLIENT_ID ?? '',
                    'client_secret' => OAUTH_GOOGLE_CLIENT_SECRET ?? ''
                ],
                'facebook' => [
                    'enabled' => OAUTH_FACEBOOK_ENABLED ?? false,
                    'client_id' => OAUTH_FACEBOOK_CLIENT_ID ?? '',
                    'client_secret' => OAUTH_FACEBOOK_CLIENT_SECRET ?? ''
                ],
                'twitter' => [
                    'enabled' => OAUTH_TWITTER_ENABLED ?? false,
                    'client_id' => OAUTH_TWITTER_CLIENT_ID ?? '',
                    'client_secret' => OAUTH_TWITTER_CLIENT_SECRET ?? ''
                ],
                'github' => [
                    'enabled' => OAUTH_GITHUB_ENABLED ?? false,
                    'client_id' => OAUTH_GITHUB_CLIENT_ID ?? '',
                    'client_secret' => OAUTH_GITHUB_CLIENT_SECRET ?? ''
                ],
                'linkedin' => [
                    'enabled' => OAUTH_LINKEDIN_ENABLED ?? false,
                    'client_id' => OAUTH_LINKEDIN_CLIENT_ID ?? '',
                    'client_secret' => OAUTH_LINKEDIN_CLIENT_SECRET ?? ''
                ],
                'microsoft' => [
                    'enabled' => OAUTH_MICROSOFT_ENABLED ?? false,
                    'client_id' => OAUTH_MICROSOFT_CLIENT_ID ?? '',
                    'client_secret' => OAUTH_MICROSOFT_CLIENT_SECRET ?? ''
                ],
                'discord' => [
                    'enabled' => OAUTH_DISCORD_ENABLED ?? false,
                    'client_id' => OAUTH_DISCORD_CLIENT_ID ?? '',
                    'client_secret' => OAUTH_DISCORD_CLIENT_SECRET ?? ''
                ],
                'apple' => [
                    'enabled' => OAUTH_APPLE_ENABLED ?? false,
                    'client_id' => OAUTH_APPLE_CLIENT_ID ?? '',
                    'client_secret' => OAUTH_APPLE_CLIENT_SECRET ?? ''
                ]
            ]
        ];
    }
    
    public function getAuthorizationUrl(string $provider, string $state = null): array {
        if (!$this->config['enabled']) {
            return [
                'success' => false,
                'message' => 'OAuth is not enabled'
            ];
        }
        
        if (!isset($this->providers[$provider])) {
            return [
                'success' => false,
                'message' => 'Unsupported OAuth provider'
            ];
        }
        
        $providerConfig = $this->config['providers'][$provider] ?? null;
        if (!$providerConfig || !$providerConfig['enabled']) {
            return [
                'success' => false,
                'message' => 'OAuth provider is not enabled'
            ];
        }
        
        $providerInfo = $this->providers[$provider];
        $state = $state ?: $this->generateState();
        
        $params = [
            'client_id' => $providerConfig['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => $providerInfo['scope'],
            'response_type' => 'code',
            'state' => $state
        ];
        
        // Provider-specific parameters
        switch ($provider) {
            case 'google':
                $params['access_type'] = 'offline';
                $params['prompt'] = 'consent';
                break;
            case 'facebook':
                $params['response_type'] = 'code';
                break;
            case 'twitter':
                $params['code_challenge'] = $this->generateCodeChallenge();
                $params['code_challenge_method'] = 'S256';
                break;
        }
        
        $authUrl = $providerInfo['auth_url'] . '?' . http_build_query($params);
        
        // Store state for verification
        $this->storeState($state, $provider);
        
        return [
            'success' => true,
            'auth_url' => $authUrl,
            'state' => $state
        ];
    }
    
    public function handleCallback(string $provider, string $code, string $state): array {
        if (!$this->config['enabled']) {
            return [
                'success' => false,
                'message' => 'OAuth is not enabled'
            ];
        }
        
        // Verify state
        if (!$this->verifyState($state, $provider)) {
            return [
                'success' => false,
                'message' => 'Invalid state parameter'
            ];
        }
        
        try {
            // Exchange code for access token
            $tokenResponse = $this->exchangeCodeForToken($provider, $code);
            
            if (!$tokenResponse['success']) {
                return $tokenResponse;
            }
            
            // Get user info
            $userInfo = $this->getUserInfo($provider, $tokenResponse['access_token']);
            
            if (!$userInfo['success']) {
                return $userInfo;
            }
            
            // Find or create user
            $user = $this->findOrCreateUser($provider, $userInfo['user']);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Failed to create or find user'
                ];
            }
            
            // Store OAuth connection
            $this->storeOAuthConnection($user['id'], $provider, $userInfo['user'], $tokenResponse);
            
            return [
                'success' => true,
                'user' => $user,
                'provider' => $provider
            ];
            
        } catch (\Exception $e) {
            error_log("OAuth callback error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'OAuth callback failed'
            ];
        }
    }
    
    private function exchangeCodeForToken(string $provider, string $code): array {
        $providerInfo = $this->providers[$provider];
        $providerConfig = $this->config['providers'][$provider];
        
        $data = [
            'client_id' => $providerConfig['client_id'],
            'client_secret' => $providerConfig['client_secret'],
            'code' => $code,
            'redirect_uri' => $this->config['redirect_uri'],
            'grant_type' => 'authorization_code'
        ];
        
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ];
        
        $response = $this->makeHttpRequest($providerInfo['token_url'], 'POST', $headers, http_build_query($data));
        
        if ($response['success']) {
            return [
                'success' => true,
                'access_token' => $response['data']['access_token'] ?? null,
                'refresh_token' => $response['data']['refresh_token'] ?? null,
                'expires_in' => $response['data']['expires_in'] ?? null,
                'token_type' => $response['data']['token_type'] ?? 'Bearer'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to exchange code for token: ' . $response['error']
        ];
    }
    
    private function getUserInfo(string $provider, string $accessToken): array {
        $providerInfo = $this->providers[$provider];
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ];
        
        $response = $this->makeHttpRequest($providerInfo['user_info_url'], 'GET', $headers);
        
        if ($response['success']) {
            $userData = $this->normalizeUserData($provider, $response['data']);
            
            return [
                'success' => true,
                'user' => $userData
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to get user info: ' . $response['error']
        ];
    }
    
    private function normalizeUserData(string $provider, array $data): array {
        switch ($provider) {
            case 'google':
                return [
                    'id' => $data['id'],
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'first_name' => $data['given_name'],
                    'last_name' => $data['family_name'],
                    'avatar' => $data['picture'],
                    'verified' => $data['verified_email'] ?? false
                ];
                
            case 'facebook':
                return [
                    'id' => $data['id'],
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'avatar' => $data['picture']['data']['url'] ?? null,
                    'verified' => true
                ];
                
            case 'twitter':
                return [
                    'id' => $data['data']['id'],
                    'email' => $data['data']['email'] ?? null,
                    'name' => $data['data']['name'],
                    'first_name' => explode(' ', $data['data']['name'])[0],
                    'last_name' => explode(' ', $data['data']['name'])[1] ?? '',
                    'avatar' => $data['data']['profile_image_url'] ?? null,
                    'verified' => $data['data']['verified'] ?? false
                ];
                
            case 'github':
                return [
                    'id' => $data['id'],
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'first_name' => explode(' ', $data['name'])[0],
                    'last_name' => explode(' ', $data['name'])[1] ?? '',
                    'avatar' => $data['avatar_url'],
                    'verified' => true
                ];
                
            case 'linkedin':
                return [
                    'id' => $data['id'],
                    'email' => $data['emailAddress'],
                    'name' => $data['firstName'] . ' ' . $data['lastName'],
                    'first_name' => $data['firstName'],
                    'last_name' => $data['lastName'],
                    'avatar' => $data['profilePicture']['displayImage'] ?? null,
                    'verified' => true
                ];
                
            case 'microsoft':
                return [
                    'id' => $data['id'],
                    'email' => $data['mail'] ?? $data['userPrincipalName'],
                    'name' => $data['displayName'],
                    'first_name' => $data['givenName'],
                    'last_name' => $data['surname'],
                    'avatar' => null,
                    'verified' => true
                ];
                
            case 'discord':
                return [
                    'id' => $data['id'],
                    'email' => $data['email'],
                    'name' => $data['username'],
                    'first_name' => $data['username'],
                    'last_name' => '',
                    'avatar' => $data['avatar'] ? "https://cdn.discordapp.com/avatars/{$data['id']}/{$data['avatar']}.png" : null,
                    'verified' => $data['verified'] ?? false
                ];
                
            case 'apple':
                return [
                    'id' => $data['sub'],
                    'email' => $data['email'],
                    'name' => $data['name'] ?? $data['email'],
                    'first_name' => $data['name']['firstName'] ?? '',
                    'last_name' => $data['name']['lastName'] ?? '',
                    'avatar' => null,
                    'verified' => true
                ];
                
            default:
                return $data;
        }
    }
    
    private function findOrCreateUser(string $provider, array $userData): ?array {
        // Try to find existing user by OAuth ID
        $existingConnection = $this->db->fetch(
            "SELECT u.* FROM users u
             JOIN oauth_connections oc ON u.id = oc.user_id
             WHERE oc.provider = :provider AND oc.provider_user_id = :provider_user_id",
            ['provider' => $provider, 'provider_user_id' => $userData['id']]
        );
        
        if ($existingConnection) {
            return $existingConnection;
        }
        
        // Try to find existing user by email
        if (!empty($userData['email'])) {
            $existingUser = $this->db->fetch(
                "SELECT * FROM users WHERE email = :email",
                ['email' => $userData['email']]
            );
            
            if ($existingUser) {
                return $existingUser;
            }
        }
        
        // Create new user
        try {
            $this->db->beginTransaction();
            
            $userId = $this->db->insert('users', [
                'username' => $this->generateUsername($userData['name']),
                'email' => $userData['email'],
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'avatar' => $userData['avatar'],
                'email_verified' => $userData['verified'] ? 1 : 0,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->commit();
            
            return $this->db->fetch(
                "SELECT * FROM users WHERE id = :id",
                ['id' => $userId]
            );
            
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error creating user: " . $e->getMessage());
            return null;
        }
    }
    
    private function generateUsername(string $name): string {
        $baseUsername = strtolower(str_replace(' ', '', $name));
        $username = $baseUsername;
        $counter = 1;
        
        while ($this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE username = :username", ['username' => $username])) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    private function storeOAuthConnection(int $userId, string $provider, array $userData, array $tokenData): void {
        try {
            $this->db->insert('oauth_connections', [
                'user_id' => $userId,
                'provider' => $provider,
                'provider_user_id' => $userData['id'],
                'access_token' => $this->encryptToken($tokenData['access_token']),
                'refresh_token' => $tokenData['refresh_token'] ? $this->encryptToken($tokenData['refresh_token']) : null,
                'expires_at' => $tokenData['expires_in'] ? date('Y-m-d H:i:s', time() + $tokenData['expires_in']) : null,
                'user_data' => json_encode($userData),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Error storing OAuth connection: " . $e->getMessage());
        }
    }
    
    private function encryptToken(string $token): string {
        $key = $this->config['state_secret'];
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($token, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    private function decryptToken(string $encryptedToken): string {
        $key = $this->config['state_secret'];
        $data = base64_decode($encryptedToken);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    private function generateState(): string {
        return bin2hex(random_bytes(32));
    }
    
    private function generateCodeChallenge(): string {
        $codeVerifier = bin2hex(random_bytes(32));
        return base64url_encode(hash('sha256', $codeVerifier, true));
    }
    
    private function base64url_encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private function storeState(string $state, string $provider): void {
        $this->db->insert('oauth_states', [
            'state' => $state,
            'provider' => $provider,
            'expires_at' => date('Y-m-d H:i:s', time() + 600), // 10 minutes
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    private function verifyState(string $state, string $provider): bool {
        $storedState = $this->db->fetch(
            "SELECT * FROM oauth_states 
             WHERE state = :state AND provider = :provider AND expires_at > NOW()",
            ['state' => $state, 'provider' => $provider]
        );
        
        if ($storedState) {
            // Remove used state
            $this->db->delete('oauth_states', 'state = :state', ['state' => $state]);
            return true;
        }
        
        return false;
    }
    
    private function makeHttpRequest(string $url, string $method = 'GET', array $headers = [], string $data = null): array {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
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
    
    public function getUserOAuthConnections(int $userId): array {
        return $this->db->fetchAll(
            "SELECT * FROM oauth_connections 
             WHERE user_id = :user_id
             ORDER BY created_at DESC",
            ['user_id' => $userId]
        );
    }
    
    public function disconnectOAuth(int $userId, string $provider): bool {
        try {
            $this->db->delete(
                'oauth_connections',
                'user_id = :user_id AND provider = :provider',
                ['user_id' => $userId, 'provider' => $provider]
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error disconnecting OAuth: " . $e->getMessage());
            return false;
        }
    }
    
    public function getOAuthStats(): array {
        return [
            'total_connections' => $this->db->fetchColumn("SELECT COUNT(*) FROM oauth_connections"),
            'connections_by_provider' => $this->getConnectionsByProvider(),
            'recent_connections' => $this->getRecentConnections(),
            'enabled_providers' => $this->getEnabledProviders()
        ];
    }
    
    private function getConnectionsByProvider(): array {
        return $this->db->fetchAll(
            "SELECT provider, COUNT(*) as count
             FROM oauth_connections 
             GROUP BY provider 
             ORDER BY count DESC"
        );
    }
    
    private function getRecentConnections(int $limit = 10): array {
        return $this->db->fetchAll(
            "SELECT oc.*, u.username, u.email
             FROM oauth_connections oc
             JOIN users u ON oc.user_id = u.id
             ORDER BY oc.created_at DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    private function getEnabledProviders(): array {
        $enabled = [];
        
        foreach ($this->config['providers'] as $provider => $config) {
            if ($config['enabled']) {
                $enabled[$provider] = $this->providers[$provider];
            }
        }
        
        return $enabled;
    }
    
    public function getProviders(): array {
        return $this->providers;
    }
    
    public function getConfig(): array {
        return $this->config;
    }
    
    public function updateConfig(array $config): bool {
        try {
            $this->config = array_merge($this->config, $config);
            
            // Save to database
            $this->db->update(
                'oauth_config',
                [
                    'config' => json_encode($this->config),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = 1'
            );
            
            return true;
        } catch (\Exception $e) {
            error_log("Error updating OAuth config: " . $e->getMessage());
            return false;
        }
    }
    
    public function cleanupExpiredStates(): bool {
        try {
            $this->db->query("DELETE FROM oauth_states WHERE expires_at < NOW()");
            return true;
        } catch (\Exception $e) {
            error_log("Error cleaning up expired states: " . $e->getMessage());
            return false;
        }
    }
    
    public function refreshToken(int $userId, string $provider): array {
        $connection = $this->db->fetch(
            "SELECT * FROM oauth_connections 
             WHERE user_id = :user_id AND provider = :provider",
            ['user_id' => $userId, 'provider' => $provider]
        );
        
        if (!$connection || !$connection['refresh_token']) {
            return [
                'success' => false,
                'message' => 'No refresh token available'
            ];
        }
        
        $providerInfo = $this->providers[$provider];
        $providerConfig = $this->config['providers'][$provider];
        
        $data = [
            'client_id' => $providerConfig['client_id'],
            'client_secret' => $providerConfig['client_secret'],
            'refresh_token' => $this->decryptToken($connection['refresh_token']),
            'grant_type' => 'refresh_token'
        ];
        
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ];
        
        $response = $this->makeHttpRequest($providerInfo['token_url'], 'POST', $headers, http_build_query($data));
        
        if ($response['success']) {
            // Update tokens
            $this->db->update(
                'oauth_connections',
                [
                    'access_token' => $this->encryptToken($response['data']['access_token']),
                    'refresh_token' => $response['data']['refresh_token'] ? $this->encryptToken($response['data']['refresh_token']) : $connection['refresh_token'],
                    'expires_at' => $response['data']['expires_in'] ? date('Y-m-d H:i:s', time() + $response['data']['expires_in']) : null,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'user_id = :user_id AND provider = :provider',
                ['user_id' => $userId, 'provider' => $provider]
            );
            
            return [
                'success' => true,
                'message' => 'Token refreshed successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to refresh token: ' . $response['error']
        ];
    }
}