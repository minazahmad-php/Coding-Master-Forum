<?php
declare(strict_types=1);

/**
 * Modern Forum - Email Template Service
 * Manages email templates and rendering
 */

namespace Services;

use Core\Database;
use Core\Logger;

class EmailTemplateService
{
    private Database $db;
    private Logger $logger;
    private string $templatePath;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->templatePath = VIEWS_PATH . '/emails';
    }

    public function render(string $template, array $data = []): string
    {
        try {
            $templateFile = $this->templatePath . '/' . $template . '.php';
            
            if (!file_exists($templateFile)) {
                throw new \Exception("Email template not found: $template");
            }

            // Extract variables for template
            extract($data);
            
            // Start output buffering
            ob_start();
            
            // Include template
            include $templateFile;
            
            // Get content
            $content = ob_get_clean();
            
            return $content;
        } catch (\Exception $e) {
            $this->logger->error('Email template rendering error', [
                'template' => $template,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getWelcomeTemplate(array $userData): string
    {
        return $this->render('welcome', $userData);
    }

    public function getPasswordResetTemplate(array $data): string
    {
        return $this->render('password_reset', $data);
    }

    public function getEmailVerificationTemplate(array $data): string
    {
        return $this->render('email_verification', $data);
    }

    public function getNotificationTemplate(array $data): string
    {
        return $this->render('notification', $data);
    }

    public function getThreadReplyTemplate(array $data): string
    {
        return $this->render('thread_reply', $data);
    }

    public function getMentionTemplate(array $data): string
    {
        return $this->render('mention', $data);
    }

    public function getNewsletterTemplate(array $data): string
    {
        return $this->render('newsletter', $data);
    }

    public function getMaintenanceTemplate(array $data): string
    {
        return $this->render('maintenance', $data);
    }

    public function getSecurityAlertTemplate(array $data): string
    {
        return $this->render('security_alert', $data);
    }

    public function getAccountSuspendedTemplate(array $data): string
    {
        return $this->render('account_suspended', $data);
    }

    public function getAccountDeletedTemplate(array $data): string
    {
        return $this->render('account_deleted', $data);
    }

    public function getTwoFactorCodeTemplate(array $data): string
    {
        return $this->render('two_factor_code', $data);
    }

    public function getPasswordChangedTemplate(array $data): string
    {
        return $this->render('password_changed', $data);
    }

    public function getProfileUpdatedTemplate(array $data): string
    {
        return $this->render('profile_updated', $data);
    }

    public function getNewMessageTemplate(array $data): string
    {
        return $this->render('new_message', $data);
    }

    public function getFriendRequestTemplate(array $data): string
    {
        return $this->render('friend_request', $data);
    }

    public function getEventInvitationTemplate(array $data): string
    {
        return $this->render('event_invitation', $data);
    }

    public function getReportTemplate(array $data): string
    {
        return $this->render('report', $data);
    }

    public function getModerationActionTemplate(array $data): string
    {
        return $this->render('moderation_action', $data);
    }

    public function getAdminNotificationTemplate(array $data): string
    {
        return $this->render('admin_notification', $data);
    }

    public function getSystemAlertTemplate(array $data): string
    {
        return $this->render('system_alert', $data);
    }

    public function getBackupCompleteTemplate(array $data): string
    {
        return $this->render('backup_complete', $data);
    }

    public function getUpdateAvailableTemplate(array $data): string
    {
        return $this->render('update_available', $data);
    }

    public function getCustomTemplate(string $templateName, array $data): string
    {
        return $this->render($templateName, $data);
    }

    public function getTemplateList(): array
    {
        $templates = [];
        $files = glob($this->templatePath . '/*.php');
        
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $templates[] = [
                'name' => $name,
                'file' => $file,
                'modified' => filemtime($file)
            ];
        }
        
        return $templates;
    }

    public function createTemplate(string $name, string $content): bool
    {
        try {
            $file = $this->templatePath . '/' . $name . '.php';
            
            if (file_exists($file)) {
                throw new \Exception("Template already exists: $name");
            }
            
            $result = file_put_contents($file, $content);
            
            if ($result === false) {
                throw new \Exception("Failed to create template file");
            }
            
            $this->logger->info('Email template created', [
                'template' => $name,
                'file' => $file
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create email template', [
                'template' => $name,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function updateTemplate(string $name, string $content): bool
    {
        try {
            $file = $this->templatePath . '/' . $name . '.php';
            
            if (!file_exists($file)) {
                throw new \Exception("Template not found: $name");
            }
            
            $result = file_put_contents($file, $content);
            
            if ($result === false) {
                throw new \Exception("Failed to update template file");
            }
            
            $this->logger->info('Email template updated', [
                'template' => $name,
                'file' => $file
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update email template', [
                'template' => $name,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function deleteTemplate(string $name): bool
    {
        try {
            $file = $this->templatePath . '/' . $name . '.php';
            
            if (!file_exists($file)) {
                throw new \Exception("Template not found: $name");
            }
            
            $result = unlink($file);
            
            if (!$result) {
                throw new \Exception("Failed to delete template file");
            }
            
            $this->logger->info('Email template deleted', [
                'template' => $name,
                'file' => $file
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete email template', [
                'template' => $name,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getTemplateContent(string $name): ?string
    {
        try {
            $file = $this->templatePath . '/' . $name . '.php';
            
            if (!file_exists($file)) {
                return null;
            }
            
            return file_get_contents($file);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get template content', [
                'template' => $name,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function validateTemplate(string $name): array
    {
        $errors = [];
        
        try {
            $file = $this->templatePath . '/' . $name . '.php';
            
            if (!file_exists($file)) {
                $errors[] = "Template file not found: $name";
                return $errors;
            }
            
            $content = file_get_contents($file);
            
            // Basic PHP syntax check
            $tempFile = tempnam(sys_get_temp_dir(), 'email_template_');
            file_put_contents($tempFile, $content);
            
            $output = [];
            $returnCode = 0;
            exec("php -l $tempFile 2>&1", $output, $returnCode);
            
            unlink($tempFile);
            
            if ($returnCode !== 0) {
                $errors[] = "PHP syntax error: " . implode(' ', $output);
            }
            
            // Check for required variables
            $requiredVars = $this->getRequiredVariables($name);
            foreach ($requiredVars as $var) {
                if (strpos($content, '$' . $var) === false) {
                    $errors[] = "Missing required variable: $var";
                }
            }
            
        } catch (\Exception $e) {
            $errors[] = "Validation error: " . $e->getMessage();
        }
        
        return $errors;
    }

    private function getRequiredVariables(string $templateName): array
    {
        $requiredVars = [
            'welcome' => ['user_name', 'site_name', 'login_url'],
            'password_reset' => ['user_name', 'reset_url', 'expires_at'],
            'email_verification' => ['user_name', 'verification_url', 'expires_at'],
            'notification' => ['user_name', 'notification_title', 'notification_message'],
            'thread_reply' => ['user_name', 'thread_title', 'reply_author', 'reply_content', 'thread_url'],
            'mention' => ['user_name', 'mentioned_by', 'post_content', 'post_url'],
            'newsletter' => ['user_name', 'newsletter_title', 'newsletter_content'],
            'maintenance' => ['user_name', 'maintenance_message', 'estimated_duration'],
            'security_alert' => ['user_name', 'alert_type', 'alert_message', 'action_required'],
            'account_suspended' => ['user_name', 'suspension_reason', 'suspension_duration', 'appeal_url'],
            'account_deleted' => ['user_name', 'deletion_reason', 'data_retention_info'],
            'two_factor_code' => ['user_name', 'verification_code', 'expires_at'],
            'password_changed' => ['user_name', 'change_time', 'ip_address'],
            'profile_updated' => ['user_name', 'updated_fields', 'update_time'],
            'new_message' => ['user_name', 'sender_name', 'message_preview', 'message_url'],
            'friend_request' => ['user_name', 'requester_name', 'requester_profile', 'accept_url', 'decline_url'],
            'event_invitation' => ['user_name', 'event_title', 'event_date', 'event_location', 'rsvp_url'],
            'report' => ['user_name', 'report_type', 'reported_content', 'report_reason'],
            'moderation_action' => ['user_name', 'action_type', 'action_reason', 'appeal_url'],
            'admin_notification' => ['admin_name', 'notification_type', 'notification_details'],
            'system_alert' => ['alert_type', 'alert_message', 'alert_time', 'action_required'],
            'backup_complete' => ['backup_type', 'backup_size', 'backup_location', 'backup_time'],
            'update_available' => ['current_version', 'new_version', 'update_notes', 'update_url']
        ];
        
        return $requiredVars[$templateName] ?? [];
    }

    public function getTemplatePreview(string $name, array $sampleData = []): string
    {
        try {
            $defaultData = $this->getDefaultTemplateData($name);
            $data = array_merge($defaultData, $sampleData);
            
            return $this->render($name, $data);
        } catch (\Exception $e) {
            $this->logger->error('Template preview error', [
                'template' => $name,
                'error' => $e->getMessage()
            ]);
            return "Error generating preview: " . $e->getMessage();
        }
    }

    private function getDefaultTemplateData(string $templateName): array
    {
        $defaultData = [
            'site_name' => APP_NAME,
            'site_url' => APP_URL,
            'site_logo' => APP_URL . '/assets/images/logo.png',
            'current_year' => date('Y'),
            'support_email' => MAIL_FROM_ADDRESS,
            'admin_email' => ADMIN_EMAIL ?? MAIL_FROM_ADDRESS
        ];
        
        $templateSpecificData = [
            'welcome' => [
                'user_name' => 'John Doe',
                'login_url' => APP_URL . '/login'
            ],
            'password_reset' => [
                'user_name' => 'John Doe',
                'reset_url' => APP_URL . '/reset-password/token123',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ],
            'email_verification' => [
                'user_name' => 'John Doe',
                'verification_url' => APP_URL . '/verify-email/token123',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
            ],
            'notification' => [
                'user_name' => 'John Doe',
                'notification_title' => 'New Message',
                'notification_message' => 'You have received a new message from Jane Smith.'
            ],
            'thread_reply' => [
                'user_name' => 'John Doe',
                'thread_title' => 'Welcome to Our Community',
                'reply_author' => 'Jane Smith',
                'reply_content' => 'Thank you for the warm welcome!',
                'thread_url' => APP_URL . '/thread/123'
            ],
            'mention' => [
                'user_name' => 'John Doe',
                'mentioned_by' => 'Jane Smith',
                'post_content' => 'Hey @johndoe, check this out!',
                'post_url' => APP_URL . '/post/456'
            ]
        ];
        
        return array_merge($defaultData, $templateSpecificData[$templateName] ?? []);
    }
}