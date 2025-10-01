<?php

//models/Thread.php

class Thread {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findById($id) {
        return $this->db->fetch(
            "SELECT t.*, u.username, u.avatar, f.name as forum_name, f.slug as forum_slug
             FROM threads t 
             LEFT JOIN users u ON t.user_id = u.id 
             LEFT JOIN forums f ON t.forum_id = f.id 
             WHERE t.id = :id",
            ['id' => $id]
        );
    }
    
    public function findAll($limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT t.*, u.username, u.avatar, f.name as forum_name, f.slug as forum_slug
             FROM threads t 
             LEFT JOIN users u ON t.user_id = u.id 
             LEFT JOIN forums f ON t.forum_id = f.id 
             ORDER BY t.is_pinned DESC, t.updated_at DESC 
             LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function findByForum($forumId, $limit = 20, $offset = 0) {
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
    
    public function findByUser($userId, $limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT t.*, f.name as forum_name, f.slug as forum_slug
             FROM threads t 
             LEFT JOIN forums f ON t.forum_id = f.id 
             WHERE t.user_id = :user_id 
             ORDER BY t.created_at DESC 
             LIMIT :limit OFFSET :offset",
            ['user_id' => $userId, 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function create($data) {
        $threadId = $this->db->insert('threads', $data);
        
        // Update forum thread count
        if ($threadId) {
            $forumModel = new Forum();
            $forumModel->incrementThreadCount($data['forum_id']);
            
            // Update user thread count
            $userModel = new User();
            $userModel->incrementThreadCount($data['user_id']);
        }
        
        return $threadId;
    }
    
    public function update($id, $data) {
        return $this->db->update('threads', $data, 'id = :id', ['id' => $id]);
    }
    
    public function delete($id) {
        $thread = $this->findById($id);
        
        if ($thread) {
            // Update forum counts
            $forumModel = new Forum();
            $forumModel->decrementThreadCount($thread['forum_id']);
            $forumModel->decrementPostCount($thread['forum_id'], $thread['replies_count']);
            
            // Update user thread count
            $userModel = new User();
            $userModel->update($thread['user_id'], [
                'threads_count' => max(0, $thread['threads_count'] - 1)
            ]);
            
            // Delete all posts in this thread
            $postModel = new Post();
            $posts = $postModel->findByThread($id);
            foreach ($posts as $post) {
                $postModel->delete($post['id']);
            }
            
            // Delete the thread
            return $this->db->delete('threads', 'id = :id', ['id' => $id]);
        }
        
        return false;
    }
    
    public function incrementViewCount($id) {
        $thread = $this->findById($id);
        $views = $thread['views'] + 1;
        
        return $this->update($id, ['views' => $views]);
    }
    
    public function incrementReplyCount($id) {
        $thread = $this->findById($id);
        $replies = $thread['replies_count'] + 1;
        
        return $this->update($id, ['replies_count' => $replies]);
    }
    
    public function decrementReplyCount($id) {
        $thread = $this->findById($id);
        $replies = max(0, $thread['replies_count'] - 1);
        
        return $this->update($id, ['replies_count' => $replies]);
    }
    
    public function toggleLock($id) {
        $thread = $this->findById($id);
        $isLocked = $thread['is_locked'] ? 0 : 1;
        
        return $this->update($id, ['is_locked' => $isLocked]);
    }
    
    public function togglePin($id) {
        $thread = $this->findById($id);
        $isPinned = $thread['is_pinned'] ? 0 : 1;
        
        return $this->update($id, ['is_pinned' => $isPinned]);
    }
    
    public function countAll() {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM threads");
        return $result['count'];
    }
    
    public function countByForum($forumId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM threads WHERE forum_id = :forum_id",
            ['forum_id' => $forumId]
        );
        return $result['count'];
    }
    
    public function countByUser($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM threads WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        return $result['count'];
    }
    
    public function search($query, $limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT t.*, u.username, u.avatar, f.name as forum_name, f.slug as forum_slug
             FROM threads t 
             LEFT JOIN users u ON t.user_id = u.id 
             LEFT JOIN forums f ON t.forum_id = f.id 
             WHERE t.title LIKE :query OR t.content LIKE :query 
             ORDER BY t.created_at DESC 
             LIMIT :limit OFFSET :offset",
            ['query' => "%$query%", 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function getLatestThreads($limit = 10) {
        return $this->db->fetchAll(
            "SELECT t.*, u.username, u.avatar, f.name as forum_name, f.slug as forum_slug
             FROM threads t 
             LEFT JOIN users u ON t.user_id = u.id 
             LEFT JOIN forums f ON t.forum_id = f.id 
             ORDER BY t.created_at DESC 
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
}
?>