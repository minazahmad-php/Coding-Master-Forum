<?php

namespace App\Models;

use App\Core\Database;

/**
 * Thread Model
 * Handles thread-related database operations
 */
class Thread
{
    private $db;
    private $table = 'threads';

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
    }

    /**
     * Create new thread
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['slug'] = $this->generateSlug($data['title']);
        
        return $this->db->insert($this->table, $data);
    }

    /**
     * Find thread by ID
     */
    public function find($id)
    {
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Find thread by slug
     */
    public function findBySlug($slug)
    {
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.slug = ?";
        
        return $this->db->fetch($sql, [$slug]);
    }

    /**
     * Get thread with posts
     */
    public function getWithPosts($id, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $thread = $this->find($id);
        
        if ($thread) {
            $postsSql = "SELECT p.*, u.username, u.display_name, u.avatar, u.role,
                                (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id) as reaction_count
                         FROM posts p
                         LEFT JOIN users u ON p.user_id = u.id
                         WHERE p.thread_id = ?
                         ORDER BY p.created_at ASC
                         LIMIT ? OFFSET ?";
            
            $thread['posts'] = $this->db->fetchAll($postsSql, [$id, $perPage, $offset]);
            
            // Get total post count
            $countSql = "SELECT COUNT(*) as count FROM posts WHERE thread_id = ?";
            $countResult = $this->db->fetch($countSql, [$id]);
            $thread['total_posts'] = $countResult['count'];
        }
        
        return $thread;
    }

    /**
     * Update thread
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if (isset($data['title'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }
        
        return $this->db->update($this->table, $data, 'id = ?', [$id]);
    }

    /**
     * Delete thread
     */
    public function delete($id)
    {
        // Delete all posts in this thread first
        $this->db->delete('posts', 'thread_id = ?', [$id]);
        
        // Delete thread
        return $this->db->delete($this->table, 'id = ?', [$id]);
    }

    /**
     * Get threads by forum
     */
    public function getByForum($forumId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT t.*, u.username, u.display_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count,
                        (SELECT p.created_at FROM posts p 
                         WHERE p.thread_id = t.id 
                         ORDER BY p.created_at DESC LIMIT 1) as last_post_at,
                        (SELECT u2.username FROM posts p2
                         JOIN users u2 ON p2.user_id = u2.id
                         WHERE p2.thread_id = t.id 
                         ORDER BY p2.created_at DESC LIMIT 1) as last_post_username
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.forum_id = ?
                ORDER BY t.is_pinned DESC, t.updated_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$forumId, $perPage, $offset]);
    }

    /**
     * Get recent threads
     */
    public function getRecent($limit = 10)
    {
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.status = 'active'
                ORDER BY t.updated_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Search threads
     */
    public function search($query, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.title LIKE ? OR t.content LIKE ?
                AND t.status = 'active'
                ORDER BY t.updated_at DESC
                LIMIT ? OFFSET ?";
        
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $perPage, $offset]);
    }

    /**
     * Pin thread
     */
    public function pin($id)
    {
        $data = ['is_pinned' => 1];
        return $this->update($id, $data);
    }

    /**
     * Unpin thread
     */
    public function unpin($id)
    {
        $data = ['is_pinned' => 0];
        return $this->update($id, $data);
    }

    /**
     * Lock thread
     */
    public function lock($id)
    {
        $data = ['is_locked' => 1];
        return $this->update($id, $data);
    }

    /**
     * Unlock thread
     */
    public function unlock($id)
    {
        $data = ['is_locked' => 0];
        return $this->update($id, $data);
    }

    /**
     * Increment view count
     */
    public function incrementViews($id)
    {
        $sql = "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    /**
     * Generate unique slug
     */
    private function generateSlug($title)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Check if slug exists
     */
    private function slugExists($slug)
    {
        $sql = "SELECT id FROM {$this->table} WHERE slug = ?";
        $result = $this->db->fetch($sql, [$slug]);
        return !empty($result);
    }

    /**
     * Get thread statistics
     */
    public function getStats($id)
    {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM posts WHERE thread_id = ?) as post_count,
                    (SELECT COUNT(DISTINCT user_id) FROM posts WHERE thread_id = ?) as unique_participants,
                    view_count
                FROM {$this->table} WHERE id = ?";
        
        return $this->db->fetch($sql, [$id, $id, $id]);
    }
}