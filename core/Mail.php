<?php
declare(strict_types=1);

namespace Core;

use Core\Database;

class Mail
{
    private Database $db;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = $this->getMailConfig();
    }

    private function getMailConfig(): array
    {
        return [
            'smtp_host' => SMTP_HOST ?? 'localhost',
            'smtp_port' => SMTP_PORT ?? 587,
            'smtp_username' => SMTP_USERNAME ?? '',
            'smtp_password' => SMTP_PASSWORD ?? '',
            'smtp_encryption' => SMTP_ENCRYPTION ?? 'tls',
            'from_email' => MAIL_FROM_EMAIL ?? 'noreply@forum.com',
            'from_name' => MAIL_FROM_NAME ?? 'Forum',
            'reply_to' => MAIL_REPLY_TO ?? 'support@forum.com'
        ];
    }

    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            // Log email attempt
            $this->logEmail($to, $subject, $body, 'pending');

            // Send email using PHP's mail function or SMTP
            $success = $this->sendViaSMTP($to, $subject, $body, $options);

            // Update log with result
            $this->updateEmailLog($to, $subject, $success ? 'sent' : 'failed');

            return $success;
        } catch (\Exception $e) {
            error_log("Failed to send email: " . $e->getMessage());
            $this->updateEmailLog($to, $subject, 'failed', $e->getMessage());
            return false;
        }
    }

    private function sendViaSMTP(string $to, string $subject, string $body, array $options = []): bool
    {
        // Simple SMTP implementation using PHP's mail function
        $headers = $this->buildHeaders($options);
        
        return mail($to, $subject, $body, $headers);
    }

    private function buildHeaders(array $options = []): string
    {
        $headers = [];
        
        $headers[] = 'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>';
        $headers[] = 'Reply-To: ' . ($options['reply_to'] ?? $this->config['reply_to']);
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'MIME-Version: 1.0';
        
        if (!empty($options['cc'])) {
            $headers[] = 'Cc: ' . $options['cc'];
        }
        
        if (!empty($options['bcc'])) {
            $headers[] = 'Bcc: ' . $options['bcc'];
        }
        
        return implode("\r\n", $headers);
    }

    public function sendWelcomeEmail(string $to, string $username): bool
    {
        $subject = 'Welcome to ' . SITE_NAME;
        $body = $this->getWelcomeEmailTemplate($username);
        
        return $this->send($to, $subject, $body);
    }

    public function sendPasswordResetEmail(string $to, string $resetToken): bool
    {
        $subject = 'Password Reset Request';
        $resetUrl = SITE_URL . '/reset-password?token=' . $resetToken;
        $body = $this->getPasswordResetEmailTemplate($resetUrl);
        
        return $this->send($to, $subject, $body);
    }

    public function sendEmailVerificationEmail(string $to, string $verificationToken): bool
    {
        $subject = 'Verify Your Email Address';
        $verificationUrl = SITE_URL . '/verify-email?token=' . $verificationToken;
        $body = $this->getEmailVerificationTemplate($verificationUrl);
        
        return $this->send($to, $subject, $body);
    }

    public function sendNotificationEmail(string $to, string $title, string $message): bool
    {
        $subject = 'Notification: ' . $title;
        $body = $this->getNotificationEmailTemplate($title, $message);
        
        return $this->send($to, $subject, $body);
    }

    private function getWelcomeEmailTemplate(string $username): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Welcome</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to " . SITE_NAME . "</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($username) . "!</h2>
                    <p>Thank you for joining our community. We're excited to have you on board!</p>
                    <p>You can now:</p>
                    <ul>
                        <li>Create posts and share your thoughts</li>
                        <li>Comment on other users' posts</li>
                        <li>Connect with like-minded people</li>
                        <li>Explore our various categories</li>
                    </ul>
                    <p>If you have any questions, feel free to contact our support team.</p>
                </div>
                <div class='footer'>
                    <p>Best regards,<br>The " . SITE_NAME . " Team</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getPasswordResetEmailTemplate(string $resetUrl): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Password Reset</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                .footer { padding: 20px; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset Request</h1>
                </div>
                <div class='content'>
                    <p>You have requested to reset your password for your " . SITE_NAME . " account.</p>
                    <p>Click the button below to reset your password:</p>
                    <p style='text-align: center;'>
                        <a href='" . $resetUrl . "' class='button'>Reset Password</a>
                    </p>
                    <p>If you didn't request this password reset, please ignore this email.</p>
                    <p><strong>Note:</strong> This link will expire in 1 hour for security reasons.</p>
                </div>
                <div class='footer'>
                    <p>Best regards,<br>The " . SITE_NAME . " Team</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getEmailVerificationTemplate(string $verificationUrl): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Email Verification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                .footer { padding: 20px; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Verify Your Email Address</h1>
                </div>
                <div class='content'>
                    <p>Thank you for registering with " . SITE_NAME . "!</p>
                    <p>To complete your registration, please verify your email address by clicking the button below:</p>
                    <p style='text-align: center;'>
                        <a href='" . $verificationUrl . "' class='button'>Verify Email</a>
                    </p>
                    <p>If you didn't create an account with us, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>Best regards,<br>The " . SITE_NAME . " Team</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getNotificationEmailTemplate(string $title, string $message): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Notification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #6c757d; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . htmlspecialchars($title) . "</h1>
                </div>
                <div class='content'>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
                <div class='footer'>
                    <p>Best regards,<br>The " . SITE_NAME . " Team</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function logEmail(string $to, string $subject, string $body, string $status): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_logs (to_email, subject, body, status, sent_at)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([$to, $subject, $body, $status]);
        } catch (\Exception $e) {
            error_log("Failed to log email: " . $e->getMessage());
        }
    }

    private function updateEmailLog(string $to, string $subject, string $status, string $errorMessage = null): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE email_logs 
                SET status = ?, error_message = ?
                WHERE to_email = ? AND subject = ? AND status = 'pending'
                ORDER BY sent_at DESC LIMIT 1
            ");
            
            $stmt->execute([$status, $errorMessage, $to, $subject]);
        } catch (\Exception $e) {
            error_log("Failed to update email log: " . $e->getMessage());
        }
    }

    public function getEmailStats(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(sent_at) as date,
                    COUNT(*) as total_emails,
                    COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_emails,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_emails,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_emails
                FROM email_logs 
                WHERE sent_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(sent_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log("Failed to get email stats: " . $e->getMessage());
            return [];
        }
    }

    public function cleanupOldLogs(int $days = 90): bool
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM email_logs 
                WHERE sent_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            
            $stmt->execute([$days]);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to cleanup old email logs: " . $e->getMessage());
            return false;
        }
    }
}