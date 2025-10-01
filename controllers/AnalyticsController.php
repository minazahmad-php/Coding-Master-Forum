<?php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Session;
use Core\Mail;
use Core\Logger;
use Services\UserAnalyticsService;
use Services\ContentAnalyticsService;
use Services\TrafficAnalyticsService;
use Services\EngagementAnalyticsService;
use Services\ConversionAnalyticsService;
use Services\RevenueAnalyticsService;
use Services\PerformanceAnalyticsService;

class AnalyticsController
{
    private Database $db;
    private Session $session;
    private Logger $logger;
    private UserAnalyticsService $userAnalytics;
    private ContentAnalyticsService $contentAnalytics;
    private TrafficAnalyticsService $trafficAnalytics;
    private EngagementAnalyticsService $engagementAnalytics;
    private ConversionAnalyticsService $conversionAnalytics;
    private RevenueAnalyticsService $revenueAnalytics;
    private PerformanceAnalyticsService $performanceAnalytics;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->logger = Logger::getInstance();
        $this->userAnalytics = new UserAnalyticsService();
        $this->contentAnalytics = new ContentAnalyticsService();
        $this->trafficAnalytics = new TrafficAnalyticsService();
        $this->engagementAnalytics = new EngagementAnalyticsService();
        $this->conversionAnalytics = new ConversionAnalyticsService();
        $this->revenueAnalytics = new RevenueAnalyticsService();
        $this->performanceAnalytics = new PerformanceAnalyticsService();
    }

    public function dashboard(): void
    {
        if (!$this->session->isLoggedIn()) {
            $this->redirectToLogin();
            return;
        }

        $userId = $this->session->getUserId();
        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'user_analytics' => $this->userAnalytics->getUserEngagementMetrics($userId),
                'content_analytics' => $this->contentAnalytics->getContentEngagementMetrics($days),
                'traffic_analytics' => $this->trafficAnalytics->getTrafficOverview($days),
                'engagement_analytics' => $this->engagementAnalytics->getEngagementMetrics($days),
                'conversion_analytics' => $this->conversionAnalytics->getConversionFunnelMetrics(),
                'revenue_analytics' => $this->revenueAnalytics->getRevenueOverview($days),
                'performance_analytics' => $this->performanceAnalytics->getPerformanceOverview($days),
                'days' => $days
            ];

            $this->render('analytics/dashboard', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load analytics dashboard', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load analytics dashboard']);
        }
    }

    public function userAnalytics(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);
        $userId = (int) ($_GET['user_id'] ?? 0);

        try {
            if ($userId > 0) {
                $data = [
                    'user_activity' => $this->userAnalytics->getUserActivitySummary($userId, $days),
                    'user_engagement' => $this->userAnalytics->getUserEngagementMetrics($userId),
                    'user_behavior' => $this->userAnalytics->getUserBehaviorPatterns($userId),
                    'user_id' => $userId,
                    'days' => $days
                ];
            } else {
                $data = [
                    'retention_metrics' => $this->userAnalytics->getUserRetentionMetrics(),
                    'cohort_analysis' => $this->userAnalytics->getUserCohortAnalysis(),
                    'user_segmentation' => $this->userAnalytics->getUserSegmentation(),
                    'conversion_funnel' => $this->userAnalytics->getUserConversionFunnel(),
                    'lifetime_value' => $this->userAnalytics->getUserLifetimeValue(),
                    'days' => $days
                ];
            }

            $this->render('analytics/user', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load user analytics', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load user analytics']);
        }
    }

    public function contentAnalytics(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);
        $contentType = $_GET['content_type'] ?? 'all';

        try {
            $data = [
                'content_performance' => $this->contentAnalytics->getContentPerformanceMetrics(0, $contentType),
                'trending_content' => $this->contentAnalytics->getTrendingContent($contentType, 20, $days),
                'engagement_metrics' => $this->contentAnalytics->getContentEngagementMetrics($days),
                'quality_metrics' => $this->contentAnalytics->getContentQualityMetrics(),
                'creation_trends' => $this->contentAnalytics->getContentCreationTrends($days),
                'category_performance' => $this->contentAnalytics->getContentPerformanceByCategory(),
                'moderation_metrics' => $this->contentAnalytics->getContentModerationMetrics($days),
                'recommendation_effectiveness' => $this->contentAnalytics->getContentRecommendationEffectiveness(),
                'ab_testing_results' => $this->contentAnalytics->getContentABTestingResults(),
                'content_type' => $contentType,
                'days' => $days
            ];

            $this->render('analytics/content', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load content analytics', [
                'content_type' => $contentType,
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load content analytics']);
        }
    }

    public function trafficAnalytics(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'traffic_overview' => $this->trafficAnalytics->getTrafficOverview($days),
                'page_performance' => $this->trafficAnalytics->getPagePerformanceMetrics($days),
                'traffic_sources' => $this->trafficAnalytics->getTrafficSources($days),
                'geographic_data' => $this->trafficAnalytics->getGeographicTrafficData($days),
                'device_browser_analytics' => $this->trafficAnalytics->getDeviceBrowserAnalytics($days),
                'traffic_trends' => $this->trafficAnalytics->getTrafficTrends($days),
                'bounce_rate_analysis' => $this->trafficAnalytics->getBounceRateAnalysis($days),
                'conversion_funnel' => $this->trafficAnalytics->getConversionFunnel(),
                'real_time_data' => $this->trafficAnalytics->getRealTimeTrafficData(),
                'days' => $days
            ];

            $this->render('analytics/traffic', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load traffic analytics', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load traffic analytics']);
        }
    }

    public function engagementAnalytics(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'engagement_metrics' => $this->engagementAnalytics->getEngagementMetrics($days),
                'top_engaged_users' => $this->engagementAnalytics->getTopEngagedUsers(20, $days),
                'engagement_trends' => $this->engagementAnalytics->getEngagementTrends($days),
                'content_engagement_analysis' => $this->engagementAnalytics->getContentEngagementAnalysis($days),
                'engagement_by_time' => $this->engagementAnalytics->getEngagementByTimeOfDay($days),
                'engagement_by_day' => $this->engagementAnalytics->getEngagementByDayOfWeek($days),
                'engagement_heatmap' => $this->engagementAnalytics->getEngagementHeatmap($days),
                'correlation_analysis' => $this->engagementAnalytics->getEngagementCorrelationAnalysis(),
                'days' => $days
            ];

            $this->render('analytics/engagement', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load engagement analytics', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load engagement analytics']);
        }
    }

    public function conversionAnalytics(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'conversion_funnel' => $this->conversionAnalytics->getConversionFunnelMetrics(),
                'conversion_by_source' => $this->conversionAnalytics->getConversionRatesBySource($days),
                'conversion_trends' => $this->conversionAnalytics->getConversionTrends($days),
                'attribution_analysis' => $this->conversionAnalytics->getConversionAttributionAnalysis(),
                'cohort_analysis' => $this->conversionAnalytics->getConversionCohortAnalysis(),
                'conversion_by_device' => $this->conversionAnalytics->getConversionByDeviceType($days),
                'conversion_by_location' => $this->conversionAnalytics->getConversionByGeographicLocation($days),
                'optimization_insights' => $this->conversionAnalytics->getConversionOptimizationInsights(),
                'value_analysis' => $this->conversionAnalytics->getConversionValueAnalysis(),
                'days' => $days
            ];

            $this->render('analytics/conversion', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load conversion analytics', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load conversion analytics']);
        }
    }

    public function revenueAnalytics(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = [
                'revenue_overview' => $this->revenueAnalytics->getRevenueOverview($days),
                'revenue_by_type' => $this->revenueAnalytics->getRevenueByType($days),
                'revenue_trends' => $this->revenueAnalytics->getRevenueTrends($days),
                'customer_lifetime_value' => $this->revenueAnalytics->getCustomerLifetimeValue(),
                'customer_segments' => $this->revenueAnalytics->getRevenueByCustomerSegment(),
                'revenue_by_location' => $this->revenueAnalytics->getRevenueByGeographicLocation($days),
                'revenue_by_device' => $this->revenueAnalytics->getRevenueByDeviceType($days),
                'revenue_by_source' => $this->revenueAnalytics->getRevenueByTrafficSource($days),
                'revenue_forecasting' => $this->revenueAnalytics->getRevenueForecasting($days),
                'optimization_insights' => $this->revenueAnalytics->getRevenueOptimizationInsights(),
                'subscription_plans' => $this->revenueAnalytics->getRevenueBySubscriptionPlan($days),
                'days' => $days
            ];

            $this->render('analytics/revenue', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load revenue analytics', [
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load revenue analytics']);
        }
    }

    public function performanceAnalytics(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $days = (int) ($_GET['days'] ?? 30);
        $metricName = $_GET['metric'] ?? '';

        try {
            $data = [
                'performance_overview' => $this->performanceAnalytics->getPerformanceOverview($days),
                'page_load_times' => $this->performanceAnalytics->getPageLoadTimes($days),
                'database_performance' => $this->performanceAnalytics->getDatabasePerformanceMetrics($days),
                'server_performance' => $this->performanceAnalytics->getServerPerformanceMetrics($days),
                'performance_alerts' => $this->performanceAnalytics->getPerformanceAlerts(7),
                'performance_benchmarks' => $this->performanceAnalytics->getPerformanceBenchmarks(),
                'optimization_recommendations' => $this->performanceAnalytics->getPerformanceOptimizationRecommendations(),
                'days' => $days
            ];

            if ($metricName) {
                $data['performance_trends'] = $this->performanceAnalytics->getPerformanceTrends($metricName, $days);
                $data['performance_by_time'] = $this->performanceAnalytics->getPerformanceByTimeOfDay($metricName, $days);
                $data['performance_by_day'] = $this->performanceAnalytics->getPerformanceByDayOfWeek($metricName, $days);
                $data['performance_heatmap'] = $this->performanceAnalytics->getPerformanceHeatmap($metricName, $days);
                $data['metric_name'] = $metricName;
            }

            $this->render('analytics/performance', $data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load performance analytics', [
                'metric' => $metricName,
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to load performance analytics']);
        }
    }

    public function exportAnalytics(): void
    {
        if (!$this->isAdmin()) {
            $this->forbidden();
            return;
        }

        $type = $_GET['type'] ?? '';
        $format = $_GET['format'] ?? 'csv';
        $days = (int) ($_GET['days'] ?? 30);

        try {
            $data = '';
            $filename = '';

            switch ($type) {
                case 'user':
                    $data = $this->userAnalytics->exportUserAnalytics(['date_from' => date('Y-m-d', strtotime("-{$days} days"))]);
                    $filename = "user_analytics_{$days}_days.csv";
                    break;
                case 'content':
                    $data = $this->contentAnalytics->exportContentAnalytics(['date_from' => date('Y-m-d', strtotime("-{$days} days"))]);
                    $filename = "content_analytics_{$days}_days.csv";
                    break;
                case 'traffic':
                    $data = $this->trafficAnalytics->exportTrafficAnalytics(['date_from' => date('Y-m-d', strtotime("-{$days} days"))]);
                    $filename = "traffic_analytics_{$days}_days.csv";
                    break;
                case 'engagement':
                    $data = $this->engagementAnalytics->exportEngagementAnalytics(['date_from' => date('Y-m-d', strtotime("-{$days} days"))]);
                    $filename = "engagement_analytics_{$days}_days.csv";
                    break;
                case 'conversion':
                    $data = $this->conversionAnalytics->exportConversionAnalytics(['date_from' => date('Y-m-d', strtotime("-{$days} days"))]);
                    $filename = "conversion_analytics_{$days}_days.csv";
                    break;
                case 'revenue':
                    $data = $this->revenueAnalytics->exportRevenueAnalytics(['date_from' => date('Y-m-d', strtotime("-{$days} days"))]);
                    $filename = "revenue_analytics_{$days}_days.csv";
                    break;
                case 'performance':
                    $data = $this->performanceAnalytics->exportPerformanceAnalytics(['date_from' => date('Y-m-d', strtotime("-{$days} days"))]);
                    $filename = "performance_analytics_{$days}_days.csv";
                    break;
                default:
                    $this->badRequest('Invalid analytics type');
                    return;
            }

            $this->downloadFile($data, $filename, 'text/csv');
        } catch (\Exception $e) {
            $this->logger->error('Failed to export analytics', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            $this->render('error', ['message' => 'Failed to export analytics']);
        }
    }

    private function isAdmin(): bool
    {
        return $this->session->get('role') === 'admin';
    }

    private function redirectToLogin(): void
    {
        header('Location: /login');
        exit;
    }

    private function forbidden(): void
    {
        http_response_code(403);
        $this->render('error', ['message' => 'Access forbidden']);
    }

    private function badRequest(string $message): void
    {
        http_response_code(400);
        $this->render('error', ['message' => $message]);
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        include VIEWS_PATH . '/' . $view . '.php';
    }

    private function downloadFile(string $content, string $filename, string $contentType): void
    {
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }
}