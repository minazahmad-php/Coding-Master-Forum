<?php

namespace App\Models;

use App\Core\Database;

/**
 * Forum Model
 * Handles forum-related database operations
 */
class Forum
{
    private $db;
    private $table = 'forums';

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
    }

    /**
     * Create new forum
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert($this->table, $data);
    }

    /**
     * Find forum by ID
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Get all forums
     */
    public function getAll()
    {
        $sql = "SELECT f.*, 
                    (SELECT COUNT(*) FROM threads WHERE forum_id = f.id) as thread_count,
                    (SELECT COUNT(*) FROM posts p 
                     JOIN threads t ON p.thread_id = t.id 
                     WHERE t.forum_id = f.id) as post_count
                FROM {$this->table} f 
                WHERE f.status = 'active'
                ORDER BY f.sort_order ASC, f.name ASC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Update forum
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->table, $data, 'id = ?', [$id]);
    }

    /**
     * Delete forum
     */
    public function delete($id)
    {
        return $this->db->delete($this->table, 'id = ?', [$id]);
    }

    /**
     * Get forum with threads
     */
    public function getWithThreads($id, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT f.*, 
                    (SELECT COUNT(*) FROM threads WHERE forum_id = f.id) as thread_count,
                    (SELECT COUNT(*) FROM posts p 
                     JOIN threads t ON p.thread_id = t.id 
                     WHERE t.forum_id = f.id) as post_count
                FROM {$this->table} f 
                WHERE f.id = ?";
        
        $forum = $this->db->fetch($sql, [$id]);
        
        if ($forum) {
            $threadsSql = "SELECT t.*, u.username, u.display_name,
                                (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count,
                                (SELECT p.created_at FROM posts p 
                                 WHERE p.thread_id = t.id 
                                 ORDER BY p.created_at DESC LIMIT 1) as last_post_at
                          FROM threads t
                          LEFT JOIN users u ON t.user_id = u.id
                          WHERE t.forum_id = ?
                          ORDER BY t.is_pinned DESC, t.updated_at DESC
                          LIMIT ? OFFSET ?";
            
            $forum['threads'] = $this->db->fetchAll($threadsSql, [$id, $perPage, $offset]);
        }
        
        return $forum;
    }

    /**
     * Get forum statistics
     */
    public function getStats($id)
    {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM threads WHERE forum_id = ?) as thread_count,
                    (SELECT COUNT(*) FROM posts p 
                     JOIN threads t ON p.thread_id = t.id 
                     WHERE t.forum_id = ?) as post_count,
                    (SELECT COUNT(DISTINCT p.user_id) FROM posts p 
                     JOIN threads t ON p.thread_id = t.id 
                     WHERE t.forum_id = ?) as unique_participants";
        
        return $this->db->fetch($sql, [$id, $id, $id]);
    }

    /**
     * Search forums
     */
    public function search($query, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT f.*, 
                    (SELECT COUNT(*) FROM threads WHERE forum_id = f.id) as thread_count,
                    (SELECT COUNT(*) FROM posts p 
                     JOIN threads t ON p.thread_id = t.id 
                     WHERE t.forum_id = f.id) as post_count
                FROM {$this->table} f 
                WHERE f.name LIKE ? OR f.description LIKE ?
                AND f.status = 'active'
                ORDER BY f.name ASC LIMIT ? OFFSET ?";
        
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $perPage, $offset]);
    }

    /**
     * Update forum order
     */
    public function updateOrder($id, $sortOrder)
    {
        $data = ['sort_order' => $sortOrder];
        return $this->update($id, $data);
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity($id, $limit = 10)
    {
        $sql = "SELECT p.*, t.title as thread_title, u.username, u.display_name
                FROM posts p
                JOIN threads t ON p.thread_id = t.id
                JOIN users u ON p.user_id = u.id
                WHERE t.forum_id = ?
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$id, $limit]);
    }
}