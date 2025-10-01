<?php
declare(strict_types=1);

/**
 * Modern Forum - Advanced Analytics Controller
 * Handles analytics requests and data visualization
 */

namespace Controllers;

use Core\Controller;
use Core\View;
use Services\AdvancedAnalyticsService;

class AdvancedAnalyticsController extends Controller
{
    private AdvancedAnalyticsService $analyticsService;

    public function __construct()
    {
        parent::__construct();
        $this->analyticsService = new AdvancedAnalyticsService();
    }

    public function dashboard(): void
    {
        $filters = $this->getFilters();
        
        $data = [
            'title' => 'Analytics Dashboard',
            'user_analytics' => $this->analyticsService->getUserAnalytics($filters),
            'content_analytics' => $this->analyticsService->getContentAnalytics($filters),
            'traffic_analytics' => $this->analyticsService->getTrafficAnalytics($filters),
            'engagement_analytics' => $this->analyticsService->getEngagementAnalytics($filters),
            'revenue_analytics' => $this->analyticsService->getRevenueAnalytics($filters),
            'performance_analytics' => $this->analyticsService->getPerformanceAnalytics(),
            'real_time_analytics' => $this->analyticsService->getRealTimeAnalytics(),
            'filters' => $filters
        ];

        View::render('analytics/dashboard', $data);
    }

    public function users(): void
    {
        $filters = $this->getFilters();
        $analytics = $this->analyticsService->getUserAnalytics($filters);
        
        $data = [
            'title' => 'User Analytics',
            'analytics' => $analytics,
            'filters' => $filters
        ];

        View::render('analytics/users', $data);
    }

    public function content(): void
    {
        $filters = $this->getFilters();
        $analytics = $this->analyticsService->getContentAnalytics($filters);
        
        $data = [
            'title' => 'Content Analytics',
            'analytics' => $analytics,
            'filters' => $filters
        ];

        View::render('analytics/content', $data);
    }

    public function traffic(): void
    {
        $filters = $this->getFilters();
        $analytics = $this->analyticsService->getTrafficAnalytics($filters);
        
        $data = [
            'title' => 'Traffic Analytics',
            'analytics' => $analytics,
            'filters' => $filters
        ];

        View::render('analytics/traffic', $data);
    }

    public function engagement(): void
    {
        $filters = $this->getFilters();
        $analytics = $this->analyticsService->getEngagementAnalytics($filters);
        
        $data = [
            'title' => 'Engagement Analytics',
            'analytics' => $analytics,
            'filters' => $filters
        ];

        View::render('analytics/engagement', $data);
    }

    public function revenue(): void
    {
        $filters = $this->getFilters();
        $analytics = $this->analyticsService->getRevenueAnalytics($filters);
        
        $data = [
            'title' => 'Revenue Analytics',
            'analytics' => $analytics,
            'filters' => $filters
        ];

        View::render('analytics/revenue', $data);
    }

    public function performance(): void
    {
        $analytics = $this->analyticsService->getPerformanceAnalytics();
        
        $data = [
            'title' => 'Performance Analytics',
            'analytics' => $analytics
        ];

        View::render('analytics/performance', $data);
    }

    public function realTime(): void
    {
        $analytics = $this->analyticsService->getRealTimeAnalytics();
        
        View::json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    public function export(): void
    {
        $type = $_GET['type'] ?? 'user';
        $format = $_GET['format'] ?? 'json';
        $filters = $this->getFilters();

        $data = $this->analyticsService->exportAnalytics($type, $filters, $format);
        
        if (empty($data)) {
            View::json(['success' => false, 'error' => 'No data to export'], 400);
            return;
        }

        $filename = $type . '_analytics_' . date('Y-m-d') . '.' . $format;
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        } else {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        
        echo $data;
    }

    public function api(): void
    {
        $type = $_GET['type'] ?? 'dashboard';
        $filters = $this->getFilters();

        try {
            switch ($type) {
                case 'user':
                    $data = $this->analyticsService->getUserAnalytics($filters);
                    break;
                case 'content':
                    $data = $this->analyticsService->getContentAnalytics($filters);
                    break;
                case 'traffic':
                    $data = $this->analyticsService->getTrafficAnalytics($filters);
                    break;
                case 'engagement':
                    $data = $this->analyticsService->getEngagementAnalytics($filters);
                    break;
                case 'revenue':
                    $data = $this->analyticsService->getRevenueAnalytics($filters);
                    break;
                case 'performance':
                    $data = $this->analyticsService->getPerformanceAnalytics();
                    break;
                case 'realtime':
                    $data = $this->analyticsService->getRealTimeAnalytics();
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid analytics type');
            }

            View::json([
                'success' => true,
                'data' => $data,
                'type' => $type,
                'filters' => $filters,
                'generated_at' => date('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            View::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function clearCache(): void
    {
        $this->analyticsService->clearCache();
        
        View::json([
            'success' => true,
            'message' => 'Analytics cache cleared successfully'
        ]);
    }

    public function cacheStats(): void
    {
        $stats = $this->analyticsService->getCacheStats();
        
        View::json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    private function getFilters(): array
    {
        return [
            'start_date' => $_GET['start_date'] ?? date('Y-m-01'), // First day of current month
            'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
            'user_id' => $_GET['user_id'] ?? null,
            'category_id' => $_GET['category_id'] ?? null,
            'time_range' => $_GET['time_range'] ?? '30_days' // 7_days, 30_days, 90_days, 1_year
        ];
    }

    public function customReport(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::json(['success' => false, 'error' => 'Only POST method allowed'], 405);
            return;
        }

        $reportConfig = $_POST['report_config'] ?? [];
        $filters = $this->getFilters();

        try {
            // Generate custom report based on configuration
            $report = $this->generateCustomReport($reportConfig, $filters);
            
            View::json([
                'success' => true,
                'report' => $report,
                'config' => $reportConfig
            ]);

        } catch (\Exception $e) {
            View::json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    private function generateCustomReport(array $config, array $filters): array
    {
        $report = [
            'title' => $config['title'] ?? 'Custom Report',
            'description' => $config['description'] ?? '',
            'data' => [],
            'charts' => [],
            'generated_at' => date('Y-m-d H:i:s')
        ];

        // Include specified analytics types
        $types = $config['types'] ?? ['user', 'content', 'traffic'];
        
        foreach ($types as $type) {
            switch ($type) {
                case 'user':
                    $report['data']['user'] = $this->analyticsService->getUserAnalytics($filters);
                    break;
                case 'content':
                    $report['data']['content'] = $this->analyticsService->getContentAnalytics($filters);
                    break;
                case 'traffic':
                    $report['data']['traffic'] = $this->analyticsService->getTrafficAnalytics($filters);
                    break;
                case 'engagement':
                    $report['data']['engagement'] = $this->analyticsService->getEngagementAnalytics($filters);
                    break;
                case 'revenue':
                    $report['data']['revenue'] = $this->analyticsService->getRevenueAnalytics($filters);
                    break;
            }
        }

        // Generate charts based on configuration
        if (isset($config['charts'])) {
            foreach ($config['charts'] as $chartConfig) {
                $report['charts'][] = $this->generateChart($chartConfig, $report['data']);
            }
        }

        return $report;
    }

    private function generateChart(array $config, array $data): array
    {
        $chart = [
            'type' => $config['type'] ?? 'line',
            'title' => $config['title'] ?? '',
            'data' => [],
            'options' => $config['options'] ?? []
        ];

        // Extract data based on chart configuration
        $dataSource = $config['data_source'] ?? '';
        $dataPath = $config['data_path'] ?? '';

        if ($dataSource && $dataPath && isset($data[$dataSource])) {
            $sourceData = $data[$dataSource];
            $chart['data'] = $this->extractDataByPath($sourceData, $dataPath);
        }

        return $chart;
    }

    private function extractDataByPath(array $data, string $path): array
    {
        $keys = explode('.', $path);
        $result = $data;

        foreach ($keys as $key) {
            if (isset($result[$key])) {
                $result = $result[$key];
            } else {
                return [];
            }
        }

        return is_array($result) ? $result : [$result];
    }
}