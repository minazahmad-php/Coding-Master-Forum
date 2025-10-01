<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;
use Core\Cache;

class AdvancedAnalyticsService
{
    private Database $db;
    private Logger $logger;
    private Cache $cache;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
        $this->cache = new Cache();
    }

    public function getUserAnalytics(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-d');

        return [
            'total_users' => $this->getTotalUsers(),
            'new_users' => $this->getNewUsers($startDate, $endDate),
            'active_users' => $this->getActiveUsers($startDate, $endDate),
        ];
    }

    public function getContentAnalytics(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-d');

        return [
            'total_posts' => $this->getTotalPosts(),
            'new_posts' => $this->getNewPosts($startDate, $endDate),
            'popular_posts' => $this->getPopularPosts($startDate, $endDate),
        ];
    }

    public function getTrafficAnalytics(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-d');

        return [
            'page_views' => $this->getPageViews($startDate, $endDate),
            'unique_visitors' => $this->getUniqueVisitors($startDate, $endDate),
        ];
    }

    public function exportAnalytics(string $type, array $filters, string $format = 'json'): string
    {
        $data = [];
        
        switch ($type) {
            case 'user':
                $data = $this->getUserAnalytics($filters);
                break;
            case 'content':
                $data = $this->getContentAnalytics($filters);
                break;
            case 'traffic':
                $data = $this->getTrafficAnalytics($filters);
                break;
            default:
                throw new \InvalidArgumentException('Invalid analytics type');
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function getTotalUsers(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
        return (int)$stmt->fetchColumn();
    }

    private function getNewUsers(string $startDate, string $endDate): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM users WHERE created_at BETWEEN :start_date AND :end_date",
            [':start_date' => $startDate, ':end_date' => $endDate]
        );
        return (int)$stmt->fetchColumn();
    }

    private function getActiveUsers(string $startDate, string $endDate): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(DISTINCT user_id) FROM user_activities WHERE created_at BETWEEN :start_date AND :end_date",
            [':start_date' => $startDate, ':end_date' => $endDate]
        );
        return (int)$stmt->fetchColumn();
    }

    private function getTotalPosts(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM posts");
        return (int)$stmt->fetchColumn();
    }

    private function getNewPosts(string $startDate, string $endDate): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM posts WHERE created_at BETWEEN :start_date AND :end_date",
            [':start_date' => $startDate, ':end_date' => $endDate]
        );
        return (int)$stmt->fetchColumn();
    }

    private function getPopularPosts(string $startDate, string $endDate): array
    {
        $stmt = $this->db->query(
            "SELECT p.id, p.title, p.likes_count, p.comments_count, p.views_count, u.username
             FROM posts p
             JOIN users u ON p.user_id = u.id
             WHERE p.created_at BETWEEN :start_date AND :end_date
             ORDER BY (p.likes_count + p.comments_count + p.views_count) DESC
             LIMIT 10",
            [':start_date' => $startDate, ':end_date' => $endDate]
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getPageViews(string $startDate, string $endDate): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM page_views WHERE created_at BETWEEN :start_date AND :end_date",
            [':start_date' => $startDate, ':end_date' => $endDate]
        );
        return (int)$stmt->fetchColumn();
    }

    private function getUniqueVisitors(string $startDate, string $endDate): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(DISTINCT ip_address) FROM page_views WHERE created_at BETWEEN :start_date AND :end_date",
            [':start_date' => $startDate, ':end_date' => $endDate]
        );
        return (int)$stmt->fetchColumn();
    }
}