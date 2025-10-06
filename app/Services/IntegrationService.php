<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;

/**
 * Third-party Integration Service
 */
class IntegrationService
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
     * Social Media Login Integration
     */
    public function handleSocialLogin($provider, $userData)
    {
        try {
            switch ($provider) {
                case 'google':
                    return $this->handleGoogleLogin($userData);
                case 'facebook':
                    return $this->handleFacebookLogin($userData);
                case 'twitter':
                    return $this->handleTwitterLogin($userData);
                case 'github':
                    return $this->handleGitHubLogin($userData);
                case 'linkedin':
                    return $this->handleLinkedInLogin($userData);
                default:
                    throw new \Exception('Unsupported provider: ' . $provider);
            }
        } catch (\Exception $e) {
            $this->logger->error('Social login failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle Google Login
     */
    private function handleGoogleLogin($userData)
    {
        $existingUser = $this->db->fetch(
            "SELECT u.* FROM users u 
             LEFT JOIN user_social_accounts usa ON u.id = usa.user_id 
             WHERE usa.provider = 'google' AND usa.provider_id = ?",
            [$userData['id']]
        );

        if ($existingUser) {
            return $existingUser;
        }

        // Check if user exists by email
        $userByEmail = $this->db->fetch(
            "SELECT * FROM users WHERE email = ?",
            [$userData['email']]
        );

        if ($userByEmail) {
            // Link social account to existing user
            $this->linkSocialAccount($userByEmail['id'], 'google', $userData['id'], $userData);
            return $userByEmail;
        }

        // Create new user
        $userId = $this->createUserFromSocialData($userData, 'google');
        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    /**
     * Handle Facebook Login
     */
    private function handleFacebookLogin($userData)
    {
        $existingUser = $this->db->fetch(
            "SELECT u.* FROM users u 
             LEFT JOIN user_social_accounts usa ON u.id = usa.user_id 
             WHERE usa.provider = 'facebook' AND usa.provider_id = ?",
            [$userData['id']]
        );

        if ($existingUser) {
            return $existingUser;
        }

        $userByEmail = $this->db->fetch(
            "SELECT * FROM users WHERE email = ?",
            [$userData['email']]
        );

        if ($userByEmail) {
            $this->linkSocialAccount($userByEmail['id'], 'facebook', $userData['id'], $userData);
            return $userByEmail;
        }

        $userId = $this->createUserFromSocialData($userData, 'facebook');
        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    /**
     * Handle Twitter Login
     */
    private function handleTwitterLogin($userData)
    {
        $existingUser = $this->db->fetch(
            "SELECT u.* FROM users u 
             LEFT JOIN user_social_accounts usa ON u.id = usa.user_id 
             WHERE usa.provider = 'twitter' AND usa.provider_id = ?",
            [$userData['id']]
        );

        if ($existingUser) {
            return $existingUser;
        }

        $userId = $this->createUserFromSocialData($userData, 'twitter');
        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    /**
     * Handle GitHub Login
     */
    private function handleGitHubLogin($userData)
    {
        $existingUser = $this->db->fetch(
            "SELECT u.* FROM users u 
             LEFT JOIN user_social_accounts usa ON u.id = usa.user_id 
             WHERE usa.provider = 'github' AND usa.provider_id = ?",
            [$userData['id']]
        );

        if ($existingUser) {
            return $existingUser;
        }

        $userByEmail = $this->db->fetch(
            "SELECT * FROM users WHERE email = ?",
            [$userData['email']]
        );

        if ($userByEmail) {
            $this->linkSocialAccount($userByEmail['id'], 'github', $userData['id'], $userData);
            return $userByEmail;
        }

        $userId = $this->createUserFromSocialData($userData, 'github');
        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    /**
     * Handle LinkedIn Login
     */
    private function handleLinkedInLogin($userData)
    {
        $existingUser = $this->db->fetch(
            "SELECT u.* FROM users u 
             LEFT JOIN user_social_accounts usa ON u.id = usa.user_id 
             WHERE usa.provider = 'linkedin' AND usa.provider_id = ?",
            [$userData['id']]
        );

        if ($existingUser) {
            return $existingUser;
        }

        $userByEmail = $this->db->fetch(
            "SELECT * FROM users WHERE email = ?",
            [$userData['email']]
        );

        if ($userByEmail) {
            $this->linkSocialAccount($userByEmail['id'], 'linkedin', $userData['id'], $userData);
            return $userByEmail;
        }

        $userId = $this->createUserFromSocialData($userData, 'linkedin');
        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    /**
     * Create user from social data
     */
    private function createUserFromSocialData($userData, $provider)
    {
        $userId = Security::generateToken(16);
        $username = $this->generateUniqueUsername($userData['name'] ?? $userData['login'] ?? 'user');
        
        $this->db->query(
            "INSERT INTO users (id, username, email, display_name, avatar, status, role, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, 'active', 'user', NOW(), NOW())",
            [
                $userId,
                $username,
                $userData['email'] ?? '',
                $userData['name'] ?? $userData['login'] ?? 'User',
                $userData['avatar_url'] ?? $userData['picture'] ?? '',
            ]
        );

        // Link social account
        $this->linkSocialAccount($userId, $provider, $userData['id'], $userData);

        return $userId;
    }

    /**
     * Link social account to user
     */
    private function linkSocialAccount($userId, $provider, $providerId, $userData)
    {
        $this->db->query(
            "INSERT INTO user_social_accounts (user_id, provider, provider_id, provider_data, created_at) 
             VALUES (?, ?, ?, ?, NOW()) 
             ON DUPLICATE KEY UPDATE 
             provider_data = VALUES(provider_data), 
             updated_at = NOW()",
            [$userId, $provider, $providerId, json_encode($userData)]
        );
    }

    /**
     * Generate unique username
     */
    private function generateUniqueUsername($baseName)
    {
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $baseName));
        $originalUsername = $username;
        $counter = 1;

        while ($this->usernameExists($username)) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Check if username exists
     */
    private function usernameExists($username)
    {
        $result = $this->db->fetch(
            "SELECT id FROM users WHERE username = ?",
            [$username]
        );

        return $result ? true : false;
    }

    /**
     * Email Service Integration
     */
    public function sendEmail($to, $subject, $body, $template = null, $data = [])
    {
        try {
            $emailService = config('email.service', 'smtp');
            
            switch ($emailService) {
                case 'sendgrid':
                    return $this->sendViaSendGrid($to, $subject, $body, $template, $data);
                case 'mailgun':
                    return $this->sendViaMailgun($to, $subject, $body, $template, $data);
                case 'ses':
                    return $this->sendViaSES($to, $subject, $body, $template, $data);
                default:
                    return $this->sendViaSMTP($to, $subject, $body, $template, $data);
            }
        } catch (\Exception $e) {
            $this->logger->error('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via SendGrid
     */
    private function sendViaSendGrid($to, $subject, $body, $template, $data)
    {
        $apiKey = config('email.sendgrid_api_key');
        $url = 'https://api.sendgrid.com/v3/mail/send';
        
        $payload = [
            'personalizations' => [
                [
                    'to' => [['email' => $to]],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => config('email.from_address'),
                'name' => config('email.from_name')
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $body
                ]
            ]
        ];

        if ($template) {
            $payload['template_id'] = $template;
            $payload['personalizations'][0]['dynamic_template_data'] = $data;
        }

        return $this->makeAPIRequest($url, $payload, $apiKey);
    }

    /**
     * Send via Mailgun
     */
    private function sendViaMailgun($to, $subject, $body, $template, $data)
    {
        $apiKey = config('email.mailgun_api_key');
        $domain = config('email.mailgun_domain');
        $url = "https://api.mailgun.net/v3/{$domain}/messages";
        
        $payload = [
            'from' => config('email.from_address'),
            'to' => $to,
            'subject' => $subject,
            'html' => $body
        ];

        if ($template) {
            $payload['template'] = $template;
            $payload['h:X-Mailgun-Variables'] = json_encode($data);
        }

        return $this->makeAPIRequest($url, $payload, $apiKey, 'POST', true);
    }

    /**
     * Send via AWS SES
     */
    private function sendViaSES($to, $subject, $body, $template, $data)
    {
        // This would integrate with AWS SES SDK
        $this->logger->info("SES email sent to {$to}: {$subject}");
        return true;
    }

    /**
     * Send via SMTP
     */
    private function sendViaSMTP($to, $subject, $body, $template, $data)
    {
        // This would use PHPMailer or similar
        $this->logger->info("SMTP email sent to {$to}: {$subject}");
        return true;
    }

    /**
     * Cloud Storage Integration
     */
    public function uploadToCloud($file, $path, $provider = 'aws')
    {
        try {
            switch ($provider) {
                case 'aws':
                    return $this->uploadToAWS($file, $path);
                case 'google':
                    return $this->uploadToGoogleCloud($file, $path);
                case 'azure':
                    return $this->uploadToAzure($file, $path);
                default:
                    return $this->uploadToAWS($file, $path);
            }
        } catch (\Exception $e) {
            $this->logger->error('Cloud upload failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload to AWS S3
     */
    private function uploadToAWS($file, $path)
    {
        // This would use AWS SDK
        $this->logger->info("File uploaded to AWS S3: {$path}");
        return "https://s3.amazonaws.com/bucket/{$path}";
    }

    /**
     * Upload to Google Cloud
     */
    private function uploadToGoogleCloud($file, $path)
    {
        // This would use Google Cloud SDK
        $this->logger->info("File uploaded to Google Cloud: {$path}");
        return "https://storage.googleapis.com/bucket/{$path}";
    }

    /**
     * Upload to Azure
     */
    private function uploadToAzure($file, $path)
    {
        // This would use Azure SDK
        $this->logger->info("File uploaded to Azure: {$path}");
        return "https://storage.azure.com/bucket/{$path}";
    }

    /**
     * CDN Integration
     */
    public function purgeCDN($urls, $provider = 'cloudflare')
    {
        try {
            switch ($provider) {
                case 'cloudflare':
                    return $this->purgeCloudflare($urls);
                case 'aws':
                    return $this->purgeAWS($urls);
                case 'keycdn':
                    return $this->purgeKeyCDN($urls);
                default:
                    return $this->purgeCloudflare($urls);
            }
        } catch (\Exception $e) {
            $this->logger->error('CDN purge failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Purge Cloudflare cache
     */
    private function purgeCloudflare($urls)
    {
        $apiKey = config('cdn.cloudflare_api_key');
        $zoneId = config('cdn.cloudflare_zone_id');
        $url = "https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache";
        
        $payload = [
            'purge_everything' => false,
            'files' => $urls
        ];

        return $this->makeAPIRequest($url, $payload, $apiKey);
    }

    /**
     * Purge AWS CloudFront
     */
    private function purgeAWS($urls)
    {
        // This would use AWS SDK
        $this->logger->info("AWS CloudFront cache purged for URLs: " . implode(', ', $urls));
        return true;
    }

    /**
     * Purge KeyCDN
     */
    private function purgeKeyCDN($urls)
    {
        $apiKey = config('cdn.keycdn_api_key');
        $zoneId = config('cdn.keycdn_zone_id');
        $url = "https://api.keycdn.com/zones/{$zoneId}/purge";
        
        $payload = [
            'urls' => $urls
        ];

        return $this->makeAPIRequest($url, $payload, $apiKey);
    }

    /**
     * SMS Service Integration
     */
    public function sendSMS($to, $message, $provider = 'twilio')
    {
        try {
            switch ($provider) {
                case 'twilio':
                    return $this->sendViaTwilio($to, $message);
                case 'aws':
                    return $this->sendViaAWSSNS($to, $message);
                case 'nexmo':
                    return $this->sendViaNexmo($to, $message);
                default:
                    return $this->sendViaTwilio($to, $message);
            }
        } catch (\Exception $e) {
            $this->logger->error('SMS sending failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Twilio
     */
    private function sendViaTwilio($to, $message)
    {
        $accountSid = config('sms.twilio_account_sid');
        $authToken = config('sms.twilio_auth_token');
        $from = config('sms.twilio_from_number');
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        
        $payload = [
            'From' => $from,
            'To' => $to,
            'Body' => $message
        ];

        return $this->makeAPIRequest($url, $payload, $authToken, 'POST', true);
    }

    /**
     * Send via AWS SNS
     */
    private function sendViaAWSSNS($to, $message)
    {
        // This would use AWS SDK
        $this->logger->info("SMS sent via AWS SNS to {$to}: {$message}");
        return true;
    }

    /**
     * Send via Nexmo
     */
    private function sendViaNexmo($to, $message)
    {
        $apiKey = config('sms.nexmo_api_key');
        $apiSecret = config('sms.nexmo_api_secret');
        $from = config('sms.nexmo_from');
        
        $url = 'https://rest.nexmo.com/sms/json';
        
        $payload = [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'from' => $from,
            'to' => $to,
            'text' => $message
        ];

        return $this->makeAPIRequest($url, $payload);
    }

    /**
     * Make API request
     */
    private function makeAPIRequest($url, $data, $apiKey = null, $method = 'POST', $isFormData = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($isFormData) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $headers = ['Content-Type: application/json'];
        if ($apiKey) {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }

        throw new \Exception('API request failed: ' . $response);
    }
}