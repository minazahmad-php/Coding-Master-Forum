<?php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Session;
use Core\Mail;
use Core\Logger;
use Services\SocialMediaIntegrationService;
use Services\PaymentGatewayIntegrationService;
use Services\EmailServiceIntegrationService;
use Services\SMSServiceIntegrationService;
use Services\APIManagementService;
use Services\CloudStorageIntegrationService;
use Services\CDNIntegrationService;
use Services\MonitoringServiceIntegrationService;
use Services\BackupServiceIntegrationService;

class IntegrationController
{
    private Database $db;
    private Session $session;
    private Mail $mail;
    private Logger $logger;
    private SocialMediaIntegrationService $socialMedia;
    private PaymentGatewayIntegrationService $paymentGateway;
    private EmailServiceIntegrationService $emailService;
    private SMSServiceIntegrationService $smsService;
    private APIManagementService $apiManagement;
    private CloudStorageIntegrationService $cloudStorage;
    private CDNIntegrationService $cdnService;
    private MonitoringServiceIntegrationService $monitoringService;
    private BackupServiceIntegrationService $backupService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->mail = new Mail();
        $this->logger = Logger::getInstance();
        $this->socialMedia = new SocialMediaIntegrationService();
        $this->paymentGateway = new PaymentGatewayIntegrationService();
        $this->emailService = new EmailServiceIntegrationService();
        $this->smsService = new SMSServiceIntegrationService();
        $this->apiManagement = new APIManagementService();
        $this->cloudStorage = new CloudStorageIntegrationService();
        $this->cdnService = new CDNIntegrationService();
        $this->monitoringService = new MonitoringServiceIntegrationService();
        $this->backupService = new BackupServiceIntegrationService();
    }

    public function dashboard(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        try {
            $data = [
                'social_media_analytics' => $this->socialMedia->getSocialMediaAnalytics(30),
                'payment_analytics' => $this->paymentGateway->getPaymentAnalytics(30),
                'email_analytics' => $this->emailService->getEmailAnalytics(30),
                'sms_analytics' => $this->smsService->getSMSAnalytics(30),
                'api_usage_analytics' => $this->apiManagement->getAPIUsageAnalytics(30),
                'cloud_storage_analytics' => $this->cloudStorage->getCloudStorageAnalytics(30),
                'cdn_analytics' => $this->cdnService->getCDNAnalytics(30),
                'system_health' => $this->monitoringService->getSystemHealthStatus(),
                'backup_history' => $this->backupService->getBackupHistory(30)
            ];

            $this->render('integrations/dashboard', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load integrations dashboard', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load integrations dashboard']);
        }
    }

    public function socialMedia(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'analytics' => $this->socialMedia->getSocialMediaAnalytics($days),
                'trending_content' => $this->socialMedia->getTrendingContentForSocialMedia(20),
                'engagement_metrics' => $this->socialMedia->getSocialMediaEngagementMetrics($days),
                'performance_by_content_type' => $this->socialMedia->getSocialMediaPerformanceByContentType($days),
                'user_behavior' => $this->socialMedia->getSocialMediaUserBehavior($days),
                'platform_preferences' => $this->socialMedia->getSocialMediaPlatformPreferences($days),
                'days' => $days
            ];

            $this->render('integrations/social-media', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load social media analytics', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load social media analytics']);
        }
    }

    public function shareToSocialMedia(): void
    {
        if (!$this->session->isLoggedIn()) {
            $this->unauthorized();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $contentId = (int) ($input['content_id'] ?? 0);
        $contentType = $input['content_type'] ?? '';
        $platform = $input['platform'] ?? '';
        $userId = $this->session->getUserId();

        try {
            $success = $this->socialMedia->shareToSocialMedia($contentId, $contentType, $platform, $userId);
            
            $this->jsonResponse([
                'success' => $success,
                'message' => $success ? 'Content shared successfully' : 'Failed to share content'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to share to social media', [
                'user_id' => $userId,
                'content_id' => $contentId,
                'platform' => $platform,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to share content'
            ], 500);
        }
    }

    public function paymentGateway(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'analytics' => $this->paymentGateway->getPaymentAnalytics($days),
                'days' => $days
            ];

            $this->render('integrations/payment-gateway', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load payment gateway analytics', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load payment gateway analytics']);
        }
    }

    public function processPayment(): void
    {
        if (!$this->session->isLoggedIn()) {
            $this->unauthorized();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $this->session->getUserId();

        try {
            $paymentData = [
                'user_id' => $userId,
                'amount' => (float) ($input['amount'] ?? 0),
                'currency' => $input['currency'] ?? 'USD',
                'payment_method' => $input['payment_method'] ?? '',
                'transaction_id' => $input['transaction_id'] ?? '',
                'metadata' => $input['metadata'] ?? []
            ];

            $result = $this->paymentGateway->processPayment($paymentData);
            
            $this->jsonResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process payment', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to process payment'
            ], 500);
        }
    }

    public function emailService(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'analytics' => $this->emailService->getEmailAnalytics($days),
                'days' => $days
            ];

            $this->render('integrations/email-service', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load email service analytics', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load email service analytics']);
        }
    }

    public function sendEmail(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $to = $input['to'] ?? '';
        $subject = $input['subject'] ?? '';
        $body = $input['body'] ?? '';
        $options = $input['options'] ?? [];

        try {
            $success = $this->emailService->sendEmail($to, $subject, $body, $options);
            
            $this->jsonResponse([
                'success' => $success,
                'message' => $success ? 'Email sent successfully' : 'Failed to send email'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to send email'
            ], 500);
        }
    }

    public function smsService(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'analytics' => $this->smsService->getSMSAnalytics($days),
                'days' => $days
            ];

            $this->render('integrations/sms-service', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load SMS service analytics', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load SMS service analytics']);
        }
    }

    public function sendSMS(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $phoneNumber = $input['phone_number'] ?? '';
        $message = $input['message'] ?? '';
        $options = $input['options'] ?? [];

        try {
            $success = $this->smsService->sendSMS($phoneNumber, $message, $options);
            
            $this->jsonResponse([
                'success' => $success,
                'message' => $success ? 'SMS sent successfully' : 'Failed to send SMS'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send SMS', [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to send SMS'
            ], 500);
        }
    }

    public function apiManagement(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'usage_analytics' => $this->apiManagement->getAPIUsageAnalytics($days),
                'days' => $days
            ];

            $this->render('integrations/api-management', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load API management analytics', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load API management analytics']);
        }
    }

    public function createAPIKey(): void
    {
        if (!$this->session->isLoggedIn()) {
            $this->unauthorized();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $this->session->getUserId();
        $name = $input['name'] ?? '';
        $permissions = $input['permissions'] ?? [];

        try {
            $result = $this->apiManagement->createAPIKey($userId, $name, $permissions);
            
            $this->jsonResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create API key', [
                'user_id' => $userId,
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to create API key'
            ], 500);
        }
    }

    public function cloudStorage(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'analytics' => $this->cloudStorage->getCloudStorageAnalytics($days),
                'days' => $days
            ];

            $this->render('integrations/cloud-storage', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load cloud storage analytics', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load cloud storage analytics']);
        }
    }

    public function uploadToCloudStorage(): void
    {
        if (!$this->session->isLoggedIn()) {
            $this->unauthorized();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $filePath = $input['file_path'] ?? '';
        $cloudPath = $input['cloud_path'] ?? '';
        $metadata = $input['metadata'] ?? [];

        try {
            $result = $this->cloudStorage->uploadFile($filePath, $cloudPath, $metadata);
            
            $this->jsonResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload to cloud storage', [
                'file_path' => $filePath,
                'cloud_path' => $cloudPath,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to upload file'
            ], 500);
        }
    }

    public function cdnService(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'analytics' => $this->cdnService->getCDNAnalytics($days),
                'days' => $days
            ];

            $this->render('integrations/cdn-service', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load CDN service analytics', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load CDN service analytics']);
        }
    }

    public function purgeCDNCache(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $url = $input['url'] ?? '';

        try {
            $success = $this->cdnService->purgeCDNCache($url);
            
            $this->jsonResponse([
                'success' => $success,
                'message' => $success ? 'CDN cache purged successfully' : 'Failed to purge CDN cache'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to purge CDN cache', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to purge CDN cache'
            ], 500);
        }
    }

    public function monitoringService(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        try {
            $data = [
                'system_health' => $this->monitoringService->getSystemHealthStatus()
            ];

            $this->render('integrations/monitoring-service', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load monitoring service data', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load monitoring service data']);
        }
    }

    public function trackSystemMetrics(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $metrics = $input['metrics'] ?? [];

        try {
            $success = $this->monitoringService->trackSystemMetrics($metrics);
            
            $this->jsonResponse([
                'success' => $success,
                'message' => $success ? 'Metrics tracked successfully' : 'Failed to track metrics'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to track system metrics', [
                'metrics' => $metrics,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to track metrics'
            ], 500);
        }
    }

    public function backupService(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        try {
            $data = [
                'backup_history' => $this->backupService->getBackupHistory(30)
            ];

            $this->render('integrations/backup-service', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load backup service data', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load backup service data']);
        }
    }

    public function createBackup(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $backupType = $input['backup_type'] ?? '';
        $options = $input['options'] ?? [];

        try {
            $result = $this->backupService->createBackup($backupType, $options);
            
            $this->jsonResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create backup', [
                'backup_type' => $backupType,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse([
                'success' => false,
                'error' => 'Failed to create backup'
            ], 500);
        }
    }

    private function isAdmin(): bool
    {
        return $this->session->get('role') === 'admin';
    }

    private function unauthorized(): void
    {
        http_response_code(401);
        $this->jsonResponse(['error' => 'Unauthorized']);
    }

    private function forbidden(): void
    {
        http_response_code(403);
        $this->render('error', ['message' => 'Access forbidden']);
    }

    private function methodNotAllowed(): void
    {
        http_response_code(405);
        $this->jsonResponse(['error' => 'Method not allowed']);
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        include VIEWS_PATH . '/' . $view . '.php';
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}