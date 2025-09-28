<?php

//models/Forum.php

class Forum {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findById($id) {
        return $this->db->fetch("SELECT * FROM forums WHERE id = :id", ['id' => $id]);
    }
    
    public function findBySlug($slug) {
        return $this->db->fetch("SELECT * FROM forums WHERE slug = :slug", ['slug' => $slug]);
    }
    
    public function findAll() {
        return $this->db->fetchAll("SELECT * FROM forums ORDER BY created_at ASC");
    }
    
    public function create($data) {
        if (!isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = slugify($data['name']);
        }
        
        return $this->db->insert('forums', $data);
    }
    
    public function update($id, $data) {
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = slugify($data['name']);
        }
        
        return $this->db->update('forums', $data, 'id = :id', ['id' => $id]);
    }
    
    public function delete($id) {
        return $this->db->delete('forums', 'id = :id', ['id' => $id]);
    }
    
    public function incrementThreadCount($id) {
        $forum = $this->findById($id);
        $threadCount = $forum['threads_count'] + 1;
        
        return $this->update($id, ['threads_count' => $threadCount]);
    }
    
    public function incrementPostCount($id) {
        $forum = $this->findById($id);
        $postCount = $forum['posts_count'] + 1;
        
        return $this->update($id, ['posts_count' => $postCount]);
    }
    
    public function decrementThreadCount($id) {
        $forum = $this->findById($id);
        $threadCount = max(0, $forum['threads_count'] - 1);
        
        return $this->update($id, ['threads_count' => $threadCount]);
    }
    
    public function decrementPostCount($id) {
        $forum = $this->findById($id);
        $postCount = max(0, $forum['posts_count'] - 1);
        
        return $this->update($id, ['posts_count' => $postCount]);
    }
    
    public function getThreads($forumId, $limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT t.*, u.username, u.avatar, 
             (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
             FROM threads t 
             LEFT JOIN users u ON t.user_id = u.id 
             WHERE t.forum_id = :forum_id 
             ORDER BY t.is_pinned DESC, t.updated_at DESC 
             LIMIT :limit OFFSET :offset",
            ['forum_id' => $forumId, 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function countThreads($forumId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM threads WHERE forum_id = :forum_id",
            ['forum_id' => $forumId]
        );
        return $result['count'];
    }
}
?>