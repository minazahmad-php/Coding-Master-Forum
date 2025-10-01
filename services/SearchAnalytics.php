<?php
declare(strict_types=1);

namespace Services;

class SearchAnalytics {
    private Database $db;
    private array $analytics;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->analytics = [];
    }
    
    public function trackSearch(string $query, int $userId = null, array $results = []): void {
        $this->db->insert('search_logs', [
            'query' => $query,
            'user_id' => $userId,
            'results_count' => count($results),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'session_id' => session_id(),
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Track individual result clicks
        foreach ($results as $result) {
            $this->db->insert('search_result_logs', [
                'search_id' => $this->db->lastInsertId(),
                'result_type' => $result['type'] ?? 'unknown',
                'result_id' => $result['id'] ?? 0,
                'position' => $result['position'] ?? 0,
                'clicked' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    public function trackResultClick(int $searchId, int $resultId, string $resultType): void {
        $this->db->update('search_result_logs', 
            ['clicked' => true, 'clicked_at' => date('Y-m-d H:i:s')],
            'search_id = :search_id AND result_id = :result_id AND result_type = :result_type',
            [
                'search_id' => $searchId,
                'result_id' => $resultId,
                'result_type' => $resultType
            ]
        );
    }
    
    public function getPopularSearches(int $days = 30, int $limit = 20): array {
        return $this->db->fetchAll(
            "SELECT query, COUNT(*) as search_count, 
                    AVG(results_count) as avg_results,
                    COUNT(DISTINCT user_id) as unique_users
             FROM search_logs 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY query 
             ORDER BY search_count DESC 
             LIMIT :limit",
            ['days' => $days, 'limit' => $limit]
        );
    }
    
    public function getSearchTrends(int $days = 30): array {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as date, 
                    COUNT(*) as total_searches,
                    COUNT(DISTINCT user_id) as unique_searchers,
                    AVG(results_count) as avg_results
             FROM search_logs 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(created_at) 
             ORDER BY date DESC",
            ['days' => $days]
        );
    }
    
    public function getNoResultSearches(int $days = 30, int $limit = 20): array {
        return $this->db->fetchAll(
            "SELECT query, COUNT(*) as count 
             FROM search_logs 
             WHERE results_count = 0 
             AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY query 
             ORDER BY count DESC 
             LIMIT :limit",
            ['days' => $days, 'limit' => $limit]
        );
    }
    
    public function getSearchPerformance(): array {
        $performance = [];
        
        // Average search time
        $performance['avg_search_time'] = $this->db->fetchColumn(
            "SELECT AVG(search_time) FROM search_logs WHERE search_time IS NOT NULL"
        ) ?? 0;
        
        // Search success rate
        $totalSearches = $this->db->fetchColumn("SELECT COUNT(*) FROM search_logs");
        $successfulSearches = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM search_logs WHERE results_count > 0"
        );
        $performance['success_rate'] = $totalSearches > 0 ? 
            ($successfulSearches / $totalSearches) * 100 : 0;
        
        // Click-through rate
        $totalResults = $this->db->fetchColumn("SELECT COUNT(*) FROM search_result_logs");
        $clickedResults = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM search_result_logs WHERE clicked = 1"
        );
        $performance['click_through_rate'] = $totalResults > 0 ? 
            ($clickedResults / $totalResults) * 100 : 0;
        
        return $performance;
    }
    
    public function getUserSearchHistory(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT query, results_count, created_at 
             FROM search_logs 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC 
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
    
    public function getSearchSuggestions(string $query, int $limit = 10): array {
        $suggestions = [];
        
        // Get suggestions from recent searches
        $recentSearches = $this->db->fetchAll(
            "SELECT DISTINCT query, COUNT(*) as count 
             FROM search_logs 
             WHERE query LIKE :query 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY query 
             ORDER BY count DESC 
             LIMIT :limit",
            ['query' => $query . '%', 'limit' => $limit]
        );
        
        foreach ($recentSearches as $search) {
            $suggestions[] = [
                'type' => 'recent',
                'text' => $search['query'],
                'count' => $search['count']
            ];
        }
        
        // Get suggestions from popular tags
        $popularTags = $this->db->fetchAll(
            "SELECT tag, COUNT(*) as count 
             FROM thread_tags 
             WHERE tag LIKE :query 
             GROUP BY tag 
             ORDER BY count DESC 
             LIMIT :limit",
            ['query' => $query . '%', 'limit' => $limit]
        );
        
        foreach ($popularTags as $tag) {
            $suggestions[] = [
                'type' => 'tag',
                'text' => $tag['tag'],
                'count' => $tag['count']
            ];
        }
        
        // Get suggestions from user names
        $userNames = $this->db->fetchAll(
            "SELECT username, COUNT(*) as count 
             FROM users 
             WHERE username LIKE :query 
             GROUP BY username 
             ORDER BY count DESC 
             LIMIT :limit",
            ['query' => $query . '%', 'limit' => $limit]
        );
        
        foreach ($userNames as $user) {
            $suggestions[] = [
                'type' => 'user',
                'text' => $user['username'],
                'count' => $user['count']
            ];
        }
        
        return $suggestions;
    }
    
    public function getSearchInsights(): array {
        $insights = [];
        
        // Most searched terms
        $insights['top_searches'] = $this->getPopularSearches(7, 10);
        
        // Search trends
        $insights['trends'] = $this->getSearchTrends(7);
        
        // No result searches
        $insights['no_results'] = $this->getNoResultSearches(7, 10);
        
        // Performance metrics
        $insights['performance'] = $this->getSearchPerformance();
        
        // User behavior
        $insights['user_behavior'] = [
            'avg_searches_per_user' => $this->db->fetchColumn(
                "SELECT AVG(search_count) FROM (
                    SELECT COUNT(*) as search_count 
                    FROM search_logs 
                    WHERE user_id IS NOT NULL 
                    GROUP BY user_id
                ) as user_searches"
            ) ?? 0,
            'most_active_searchers' => $this->db->fetchAll(
                "SELECT u.username, COUNT(*) as search_count 
                 FROM search_logs sl 
                 JOIN users u ON sl.user_id = u.id 
                 WHERE sl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY sl.user_id, u.username 
                 ORDER BY search_count DESC 
                 LIMIT 10"
            )
        ];
        
        return $insights;
    }
    
    public function generateSearchReport(int $days = 30): array {
        $report = [
            'period' => $days,
            'generated_at' => date('Y-m-d H:i:s'),
            'summary' => [],
            'details' => []
        ];
        
        // Summary statistics
        $report['summary'] = [
            'total_searches' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM search_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)",
                ['days' => $days]
            ),
            'unique_searchers' => $this->db->fetchColumn(
                "SELECT COUNT(DISTINCT user_id) FROM search_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)",
                ['days' => $days]
            ),
            'avg_results_per_search' => $this->db->fetchColumn(
                "SELECT AVG(results_count) FROM search_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)",
                ['days' => $days]
            ),
            'success_rate' => $this->getSearchPerformance()['success_rate']
        ];
        
        // Detailed data
        $report['details'] = [
            'popular_searches' => $this->getPopularSearches($days, 50),
            'search_trends' => $this->getSearchTrends($days),
            'no_result_searches' => $this->getNoResultSearches($days, 20),
            'performance_metrics' => $this->getSearchPerformance()
        ];
        
        return $report;
    }
    
    public function optimizeSearchIndex(): void {
        // Clean up old search logs
        $this->db->query(
            "DELETE FROM search_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
        
        // Clean up old search result logs
        $this->db->query(
            "DELETE FROM search_result_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
        
        // Optimize tables
        $this->db->query("OPTIMIZE TABLE search_logs");
        $this->db->query("OPTIMIZE TABLE search_result_logs");
    }
    
    public function exportSearchData(string $format = 'json'): string {
        $data = $this->generateSearchReport(30);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCSV($data);
            case 'xml':
                return $this->exportToXML($data);
            default:
                return json_encode($data, JSON_PRETTY_PRINT);
        }
    }
    
    private function exportToCSV(array $data): string {
        $csv = "Search Analytics Report\n";
        $csv .= "Generated: " . $data['generated_at'] . "\n\n";
        
        $csv .= "Summary\n";
        foreach ($data['summary'] as $key => $value) {
            $csv .= $key . "," . $value . "\n";
        }
        
        $csv .= "\nPopular Searches\n";
        $csv .= "Query,Search Count,Avg Results,Unique Users\n";
        foreach ($data['details']['popular_searches'] as $search) {
            $csv .= $search['query'] . "," . $search['search_count'] . "," . 
                   $search['avg_results'] . "," . $search['unique_users'] . "\n";
        }
        
        return $csv;
    }
    
    private function exportToXML(array $data): string {
        $xml = new SimpleXMLElement('<search_analytics></search_analytics>');
        $xml->addAttribute('generated_at', $data['generated_at']);
        $xml->addAttribute('period', $data['period']);
        
        $summary = $xml->addChild('summary');
        foreach ($data['summary'] as $key => $value) {
            $summary->addChild($key, $value);
        }
        
        $details = $xml->addChild('details');
        $popularSearches = $details->addChild('popular_searches');
        foreach ($data['details']['popular_searches'] as $search) {
            $searchNode = $popularSearches->addChild('search');
            $searchNode->addChild('query', htmlspecialchars($search['query']));
            $searchNode->addChild('search_count', $search['search_count']);
            $searchNode->addChild('avg_results', $search['avg_results']);
            $searchNode->addChild('unique_users', $search['unique_users']);
        }
        
        return $xml->asXML();
    }
}