<?php

//models/Post.php

class Post {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findById($id) {
        return $this->db->fetch(
            "SELECT p.*, u.username, u.avatar, u.role, t.title as thread_title, t.forum_id
             FROM posts p 
             LEFT JOIN users u ON p.user_id = u.id 
             LEFT JOIN threads t ON p.thread_id = t.id 
             WHERE p.id = :id",
            ['id' => $id]
        );
    }
    
    public function findByThread($threadId, $limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT p.*, u.username, u.avatar, u.role, u.reputation
             FROM posts p 
             LEFT JOIN users u ON p.user_id = u.id 
             WHERE p.thread_id = :thread_id 
             ORDER BY p.created_at ASC 
             LIMIT :limit OFFSET :offset",
            ['thread_id' => $threadId, 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function findByUser($userId, $limit = 20, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT p.*, t.title as thread_title, f.name as forum_name, f.slug as forum_slug
             FROM posts p 
             LEFT JOIN threads t ON p.thread_id = t.id 
             LEFT JOIN forums f ON t.forum_id = f.id 
             WHERE p.user_id = :user_id 
             ORDER BY p.created_at DESC 
             LIMIT :limit OFFSET :offset",
            ['user_id' => $userId, 'limit' => $limit, 'offset' => $offset]
        );
    }
    
    public function create($data) {
        $postId = $this->db->insert('posts', $data);
        
        // Update thread reply count
        if ($postId) {
            $threadModel = new Thread();
            $threadModel->incrementReplyCount($data['thread_id']);
            
            // Update forum post count
            $thread = $threadModel->findById($data['thread_id']);
            if ($thread) {
                $forumModel = new Forum();
                $forumModel->incrementPostCount($thread['forum_id']);
            }
            
            // Update user post count
            $userModel = new User();
            $userModel->incrementPostCount($data['user_id']);
        }
        
        return $postId;
    }
    
    public function update($id, $data) {
        $data['is_edited'] = 1;
        return $this->db->update('posts', $data, 'id = :id', ['id' => $id]);
    }
    
    public function delete($id) {
        $post = $this->findById($id);
        
        if ($post) {
            // Update thread reply count
            $threadModel = new Thread();
            $threadModel->decrementReplyCount($post['thread_id']);
            
            // Update forum post count
            $forumModel = new Forum();
            $forumModel->decrementPostCount($post['forum_id']);
            
            // Update user post count
            $userModel = new User();
            $userModel->update($post['user_id'], [
                'posts_count' => max(0, $post['posts_count'] - 1)
            ]);
            
            // Delete the post
            return $this->db->delete('posts', 'id = :id', ['id' => $id]);
        }
        
        return false;
    }
    
    public function countByThread($threadId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM posts WHERE thread_id = :thread_id",
            ['thread_id' => $threadId]
        );
        return $result['count'];
    }
    
    public function countByUser($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM posts WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        return $result['count'];
    }
    
    public function getLatestPosts($limit = 10) {
        return $this->db->fetchAll(
            "SELECT p.*, u.username, u.avatar, t.title as thread_title, f.name as forum_name, f.slug as forum_slug
             FROM posts p 
             LEFT JOIN users u ON p.user_id = u.id 
             LEFT JOIN threads t ON p.thread_id = t.id 
             LEFT JOIN forums f ON t.forum_id = f.id 
             ORDER BY p.created_at DESC 
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
}
?>