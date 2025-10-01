<?php
declare(strict_types=1);

namespace Services;

class ElasticsearchService {
    private string $host;
    private int $port;
    private string $index;
    private array $config;
    
    public function __construct() {
        $this->host = ELASTICSEARCH_HOST ?? 'localhost';
        $this->port = ELASTICSEARCH_PORT ?? 9200;
        $this->index = ELASTICSEARCH_INDEX ?? 'forum';
        $this->config = [
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => false
        ];
    }
    
    public function indexDocument(string $type, array $document): bool {
        $url = "http://{$this->host}:{$this->port}/{$this->index}/{$type}/{$document['id']}";
        
        $data = [
            'doc' => $document,
            'doc_as_upsert' => true
        ];
        
        $response = $this->makeRequest('POST', $url, $data);
        return $response['result'] === 'created' || $response['result'] === 'updated';
    }
    
    public function searchDocuments(string $type, array $query): array {
        $url = "http://{$this->host}:{$this->port}/{$this->index}/{$type}/_search";
        
        $searchBody = [
            'query' => $query,
            'size' => $query['size'] ?? 10,
            'from' => $query['from'] ?? 0,
            'sort' => $query['sort'] ?? ['_score' => ['order' => 'desc']],
            'highlight' => [
                'fields' => [
                    'content' => ['fragment_size' => 150],
                    'title' => ['fragment_size' => 100]
                ]
            ]
        ];
        
        $response = $this->makeRequest('POST', $url, $searchBody);
        
        return [
            'hits' => $response['hits']['hits'] ?? [],
            'total' => $response['hits']['total']['value'] ?? 0,
            'max_score' => $response['hits']['max_score'] ?? 0
        ];
    }
    
    public function deleteDocument(string $type, string $id): bool {
        $url = "http://{$this->host}:{$this->port}/{$this->index}/{$type}/{$id}";
        
        $response = $this->makeRequest('DELETE', $url);
        return $response['result'] === 'deleted';
    }
    
    public function bulkIndex(array $documents): bool {
        $url = "http://{$this->host}:{$this->port}/{$this->index}/_bulk";
        
        $body = '';
        foreach ($documents as $doc) {
            $action = [
                'index' => [
                    '_index' => $this->index,
                    '_type' => $doc['type'],
                    '_id' => $doc['id']
                ]
            ];
            $body .= json_encode($action) . "\n";
            $body .= json_encode($doc['document']) . "\n";
        }
        
        $response = $this->makeRequest('POST', $url, $body, 'application/x-ndjson');
        return !isset($response['errors']) || !$response['errors'];
    }
    
    public function createIndex(): bool {
        $url = "http://{$this->host}:{$this->port}/{$this->index}";
        
        $mapping = [
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'title' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                        'fields' => [
                            'keyword' => ['type' => 'keyword']
                        ]
                    ],
                    'content' => [
                        'type' => 'text',
                        'analyzer' => 'standard'
                    ],
                    'user_id' => ['type' => 'keyword'],
                    'forum_id' => ['type' => 'keyword'],
                    'thread_id' => ['type' => 'keyword'],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date'],
                    'tags' => ['type' => 'keyword'],
                    'status' => ['type' => 'keyword'],
                    'type' => ['type' => 'keyword']
                ]
            ],
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'analysis' => [
                    'analyzer' => [
                        'custom_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'stop', 'snowball']
                        ]
                    ]
                ]
            ]
        ];
        
        $response = $this->makeRequest('PUT', $url, $mapping);
        return $response['acknowledged'] ?? false;
    }
    
    public function deleteIndex(): bool {
        $url = "http://{$this->host}:{$this->port}/{$this->index}";
        
        $response = $this->makeRequest('DELETE', $url);
        return $response['acknowledged'] ?? false;
    }
    
    public function getIndexStats(): array {
        $url = "http://{$this->host}:{$this->port}/{$this->index}/_stats";
        
        $response = $this->makeRequest('GET', $url);
        return $response['indices'][$this->index] ?? [];
    }
    
    public function reindexFromDatabase(): bool {
        $db = Database::getInstance();
        
        // Delete existing index
        $this->deleteIndex();
        
        // Create new index
        if (!$this->createIndex()) {
            return false;
        }
        
        // Index posts
        $posts = $db->fetchAll("SELECT * FROM posts");
        foreach ($posts as $post) {
            $this->indexDocument('post', [
                'id' => $post['id'],
                'title' => $post['title'] ?? '',
                'content' => $post['content'],
                'user_id' => $post['user_id'],
                'thread_id' => $post['thread_id'],
                'created_at' => $post['created_at'],
                'updated_at' => $post['updated_at'],
                'type' => 'post'
            ]);
        }
        
        // Index threads
        $threads = $db->fetchAll("SELECT * FROM threads");
        foreach ($threads as $thread) {
            $this->indexDocument('thread', [
                'id' => $thread['id'],
                'title' => $thread['title'],
                'content' => $thread['description'] ?? '',
                'user_id' => $thread['user_id'],
                'forum_id' => $thread['forum_id'],
                'created_at' => $thread['created_at'],
                'updated_at' => $thread['updated_at'],
                'type' => 'thread'
            ]);
        }
        
        // Index users
        $users = $db->fetchAll("SELECT * FROM users");
        foreach ($users as $user) {
            $this->indexDocument('user', [
                'id' => $user['id'],
                'title' => $user['username'],
                'content' => $user['bio'] ?? '',
                'user_id' => $user['id'],
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at'],
                'type' => 'user'
            ]);
        }
        
        return true;
    }
    
    private function makeRequest(string $method, string $url, $data = null, string $contentType = 'application/json') {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_CONNECTTIMEOUT => $this->config['connect_timeout'],
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: ' . $contentType
            ],
            CURLOPT_SSL_VERIFYPEER => $this->config['verify'],
            CURLOPT_SSL_VERIFYHOST => $this->config['verify'] ? 2 : 0
        ]);
        
        if ($data !== null) {
            if ($contentType === 'application/json') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Elasticsearch request failed: {$error}");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("Elasticsearch HTTP error: {$httpCode}");
        }
        
        return json_decode($response, true);
    }
}