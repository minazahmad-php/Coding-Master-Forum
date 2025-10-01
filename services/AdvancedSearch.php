<?php
declare(strict_types=1);

namespace Services;

class AdvancedSearch {
    private Database $db;
    private array $searchConfig;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->searchConfig = [
            'min_word_length' => 3,
            'max_results' => 100,
            'highlight' => true,
            'fuzzy_search' => true,
            'weighted_search' => true
        ];
    }
    
    public function fullTextSearch(string $query, array $filters = []): array {
        $searchTerms = $this->parseSearchQuery($query);
        $results = [];
        
        // Search in posts
        $postResults = $this->searchPosts($searchTerms, $filters);
        $results['posts'] = $postResults;
        
        // Search in threads
        $threadResults = $this->searchThreads($searchTerms, $filters);
        $results['threads'] = $threadResults;
        
        // Search in users
        $userResults = $this->searchUsers($searchTerms, $filters);
        $results['users'] = $userResults;
        
        // Search in attachments
        $attachmentResults = $this->searchAttachments($searchTerms, $filters);
        $results['attachments'] = $attachmentResults;
        
        return $this->rankResults($results, $searchTerms);
    }
    
    private function parseSearchQuery(string $query): array {
        $terms = [];
        
        // Handle quoted phrases
        preg_match_all('/"([^"]+)"/', $query, $quotedMatches);
        foreach ($quotedMatches[1] as $phrase) {
            $terms[] = ['type' => 'phrase', 'value' => $phrase];
        }
        
        // Handle individual words
        $cleanQuery = preg_replace('/"[^"]+"/', '', $query);
        $words = preg_split('/\s+/', trim($cleanQuery));
        
        foreach ($words as $word) {
            if (strlen($word) >= $this->searchConfig['min_word_length']) {
                $terms[] = ['type' => 'word', 'value' => $word];
            }
        }
        
        return $terms;
    }
    
    private function searchPosts(array $terms, array $filters): array {
        $sql = "SELECT p.*, t.title as thread_title, u.username, u.avatar,
                       MATCH(p.content) AGAINST(:query IN BOOLEAN MODE) as relevance
                FROM posts p
                JOIN threads t ON p.thread_id = t.id
                JOIN users u ON p.user_id = u.id
                WHERE MATCH(p.content) AGAINST(:query IN BOOLEAN MODE)";
        
        $params = ['query' => $this->buildSearchQuery($terms)];
        
        // Add filters
        if (!empty($filters['forum_id'])) {
            $sql .= " AND t.forum_id = :forum_id";
            $params['forum_id'] = $filters['forum_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND p.created_at >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND p.created_at <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY relevance DESC LIMIT :limit";
        $params['limit'] = $this->searchConfig['max_results'];
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function searchThreads(array $terms, array $filters): array {
        $sql = "SELECT t.*, f.name as forum_name, u.username, u.avatar,
                       MATCH(t.title, t.description) AGAINST(:query IN BOOLEAN MODE) as relevance
                FROM threads t
                JOIN forums f ON t.forum_id = f.id
                JOIN users u ON t.user_id = u.id
                WHERE MATCH(t.title, t.description) AGAINST(:query IN BOOLEAN MODE)";
        
        $params = ['query' => $this->buildSearchQuery($terms)];
        
        // Add filters
        if (!empty($filters['forum_id'])) {
            $sql .= " AND t.forum_id = :forum_id";
            $params['forum_id'] = $filters['forum_id'];
        }
        
        $sql .= " ORDER BY relevance DESC LIMIT :limit";
        $params['limit'] = $this->searchConfig['max_results'];
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function searchUsers(array $terms, array $filters): array {
        $sql = "SELECT u.*, 
                       MATCH(u.username, u.email, u.bio) AGAINST(:query IN BOOLEAN MODE) as relevance
                FROM users u
                WHERE MATCH(u.username, u.email, u.bio) AGAINST(:query IN BOOLEAN MODE)";
        
        $params = ['query' => $this->buildSearchQuery($terms)];
        
        $sql .= " ORDER BY relevance DESC LIMIT :limit";
        $params['limit'] = $this->searchConfig['max_results'];
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function searchAttachments(array $terms, array $filters): array {
        $sql = "SELECT a.*, p.content, t.title as thread_title, u.username,
                       MATCH(a.filename, a.description) AGAINST(:query IN BOOLEAN MODE) as relevance
                FROM attachments a
                JOIN posts p ON a.post_id = p.id
                JOIN threads t ON p.thread_id = t.id
                JOIN users u ON a.user_id = u.id
                WHERE MATCH(a.filename, a.description) AGAINST(:query IN BOOLEAN MODE)";
        
        $params = ['query' => $this->buildSearchQuery($terms)];
        
        $sql .= " ORDER BY relevance DESC LIMIT :limit";
        $params['limit'] = $this->searchConfig['max_results'];
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function buildSearchQuery(array $terms): string {
        $queryParts = [];
        
        foreach ($terms as $term) {
            if ($term['type'] === 'phrase') {
                $queryParts[] = '"' . $term['value'] . '"';
            } else {
                $queryParts[] = '+' . $term['value'] . '*';
            }
        }
        
        return implode(' ', $queryParts);
    }
    
    private function rankResults(array $results, array $terms): array {
        $rankedResults = [];
        
        foreach ($results as $type => $items) {
            foreach ($items as $item) {
                $score = $this->calculateScore($item, $terms);
                $item['search_score'] = $score;
                $rankedResults[] = $item;
            }
        }
        
        // Sort by score
        usort($rankedResults, function($a, $b) {
            return $b['search_score'] <=> $a['search_score'];
        });
        
        return $rankedResults;
    }
    
    private function calculateScore(array $item, array $terms): float {
        $score = 0;
        
        // Base relevance score
        if (isset($item['relevance'])) {
            $score += $item['relevance'];
        }
        
        // Boost for exact matches
        foreach ($terms as $term) {
            $text = strtolower($item['content'] ?? $item['title'] ?? '');
            $termValue = strtolower($term['value']);
            
            if (strpos($text, $termValue) !== false) {
                $score += 10;
            }
        }
        
        // Boost for recent content
        if (isset($item['created_at'])) {
            $daysOld = (time() - strtotime($item['created_at'])) / 86400;
            $score += max(0, 10 - $daysOld);
        }
        
        return $score;
    }
    
    public function getSearchSuggestions(string $query): array {
        $suggestions = [];
        
        // Get suggestions from recent searches
        $recentSearches = $this->db->fetchAll(
            "SELECT query, COUNT(*) as count 
             FROM search_logs 
             WHERE query LIKE :query 
             GROUP BY query 
             ORDER BY count DESC 
             LIMIT 5",
            ['query' => $query . '%']
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
             LIMIT 5",
            ['query' => $query . '%']
        );
        
        foreach ($popularTags as $tag) {
            $suggestions[] = [
                'type' => 'tag',
                'text' => $tag['tag'],
                'count' => $tag['count']
            ];
        }
        
        return $suggestions;
    }
    
    public function logSearch(string $query, int $userId = null, array $results = []): void {
        $this->db->insert('search_logs', [
            'query' => $query,
            'user_id' => $userId,
            'results_count' => count($results),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getSearchAnalytics(): array {
        $analytics = [];
        
        // Popular searches
        $analytics['popular_searches'] = $this->db->fetchAll(
            "SELECT query, COUNT(*) as count 
             FROM search_logs 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY query 
             ORDER BY count DESC 
             LIMIT 10"
        );
        
        // Search trends
        $analytics['search_trends'] = $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM search_logs 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at) 
             ORDER BY date DESC"
        );
        
        // No results searches
        $analytics['no_results'] = $this->db->fetchAll(
            "SELECT query, COUNT(*) as count 
             FROM search_logs 
             WHERE results_count = 0 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY query 
             ORDER BY count DESC 
             LIMIT 10"
        );
        
        return $analytics;
    }
}