<?php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Services\AdvancedSearch;
use Services\ElasticsearchService;
use Services\SearchAnalytics;

class SearchController {
    private AdvancedSearch $searchService;
    private ElasticsearchService $elasticsearchService;
    private SearchAnalytics $analyticsService;
    private Database $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->searchService = new AdvancedSearch();
        $this->elasticsearchService = new ElasticsearchService();
        $this->analyticsService = new SearchAnalytics();
    }
    
    public function index(): void {
        $query = $_GET['q'] ?? '';
        $filters = $this->getFilters();
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 25;
        
        $results = [];
        $totalResults = 0;
        
        if (!empty($query)) {
            if (ELASTICSEARCH_ENABLED) {
                $results = $this->elasticsearchSearch($query, $filters, $page, $perPage);
            } else {
                $results = $this->databaseSearch($query, $filters, $page, $perPage);
            }
            
            $totalResults = count($results);
            
            // Track search
            $this->analyticsService->trackSearch($query, Auth::getUserId(), $results);
        }
        
        $this->render('search/index', [
            'query' => $query,
            'results' => $results,
            'totalResults' => $totalResults,
            'filters' => $filters,
            'page' => $page,
            'perPage' => $perPage,
            'suggestions' => $this->analyticsService->getSearchSuggestions($query)
        ]);
    }
    
    public function ajaxSearch(): void {
        $query = $_GET['q'] ?? '';
        $filters = $this->getFilters();
        
        if (empty($query)) {
            $this->jsonResponse(['results' => []]);
            return;
        }
        
        $results = [];
        if (ELASTICSEARCH_ENABLED) {
            $results = $this->elasticsearchSearch($query, $filters, 1, 10);
        } else {
            $results = $this->databaseSearch($query, $filters, 1, 10);
        }
        
        $this->analyticsService->trackSearch($query, Auth::getUserId(), $results);
        
        $this->jsonResponse([
            'results' => $results,
            'suggestions' => $this->analyticsService->getSearchSuggestions($query)
        ]);
    }
    
    public function suggestions(): void {
        $query = $_GET['q'] ?? '';
        $suggestions = $this->analyticsService->getSearchSuggestions($query);
        
        $this->jsonResponse(['suggestions' => $suggestions]);
    }
    
    public function analytics(): void {
        if (!Auth::isAdmin()) {
            $this->redirect('/');
            return;
        }
        
        $days = (int)($_GET['days'] ?? 30);
        $insights = $this->analyticsService->getSearchInsights();
        
        $this->render('search/analytics', [
            'insights' => $insights,
            'days' => $days
        ]);
    }
    
    public function export(): void {
        if (!Auth::isAdmin()) {
            $this->redirect('/');
            return;
        }
        
        $format = $_GET['format'] ?? 'json';
        $days = (int)($_GET['days'] ?? 30);
        
        $data = $this->analyticsService->exportSearchData($format);
        
        $filename = 'search_analytics_' . date('Y-m-d') . '.' . $format;
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($data));
        
        echo $data;
        exit;
    }
    
    public function trackClick(): void {
        $searchId = (int)($_POST['search_id'] ?? 0);
        $resultId = (int)($_POST['result_id'] ?? 0);
        $resultType = $_POST['result_type'] ?? '';
        
        if ($searchId && $resultId && $resultType) {
            $this->analyticsService->trackResultClick($searchId, $resultId, $resultType);
        }
        
        $this->jsonResponse(['success' => true]);
    }
    
    private function elasticsearchSearch(string $query, array $filters, int $page, int $perPage): array {
        $searchQuery = [
            'bool' => [
                'must' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['title^2', 'content'],
                        'type' => 'best_fields',
                        'fuzziness' => 'AUTO'
                    ]
                ],
                'filter' => []
            ],
            'size' => $perPage,
            'from' => ($page - 1) * $perPage
        ];
        
        // Add filters
        if (!empty($filters['type'])) {
            $searchQuery['bool']['filter'][] = [
                'term' => ['type' => $filters['type']]
            ];
        }
        
        if (!empty($filters['forum_id'])) {
            $searchQuery['bool']['filter'][] = [
                'term' => ['forum_id' => $filters['forum_id']]
            ];
        }
        
        if (!empty($filters['date_from'])) {
            $searchQuery['bool']['filter'][] = [
                'range' => [
                    'created_at' => [
                        'gte' => $filters['date_from']
                    ]
                ]
            ];
        }
        
        $response = $this->elasticsearchService->searchDocuments('_doc', $searchQuery);
        
        return $this->formatElasticsearchResults($response['hits']);
    }
    
    private function databaseSearch(string $query, array $filters, int $page, int $perPage): array {
        $results = $this->searchService->fullTextSearch($query, $filters);
        
        // Paginate results
        $offset = ($page - 1) * $perPage;
        return array_slice($results, $offset, $perPage);
    }
    
    private function formatElasticsearchResults(array $hits): array {
        $results = [];
        
        foreach ($hits as $hit) {
            $source = $hit['_source'];
            $result = [
                'id' => $source['id'],
                'type' => $source['type'],
                'title' => $source['title'],
                'content' => $source['content'],
                'score' => $hit['_score'],
                'highlight' => $hit['highlight'] ?? []
            ];
            
            // Add type-specific fields
            switch ($source['type']) {
                case 'post':
                    $result['thread_id'] = $source['thread_id'];
                    $result['user_id'] = $source['user_id'];
                    break;
                case 'thread':
                    $result['forum_id'] = $source['forum_id'];
                    $result['user_id'] = $source['user_id'];
                    break;
                case 'user':
                    $result['username'] = $source['title'];
                    break;
            }
            
            $results[] = $result;
        }
        
        return $results;
    }
    
    private function getFilters(): array {
        return [
            'type' => $_GET['type'] ?? '',
            'forum_id' => (int)($_GET['forum_id'] ?? 0),
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'user_id' => (int)($_GET['user_id'] ?? 0),
            'tags' => $_GET['tags'] ?? ''
        ];
    }
    
    private function render(string $view, array $data = []): void {
        extract($data);
        include VIEWS_PATH . '/' . $view . '.php';
    }
    
    private function jsonResponse(array $data): void {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    private function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }
}