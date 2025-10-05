<?php

namespace App\Models;

use App\Core\Database;

/**
 * User Model
 * Handles user-related database operations
 */
class User
{
    private $db;
    private $table = 'users';

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
    }

    /**
     * Create new user
     */
    public function create($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert($this->table, $data);
    }

    /**
     * Find user by ID
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->fetch($sql, [$email]);
    }

    /**
     * Find user by username
     */
    public function findByUsername($username)
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        return $this->db->fetch($sql, [$username]);
    }

    /**
     * Update user
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->db->update($this->table, $data, 'id = ?', [$id]);
    }

    /**
     * Delete user
     */
    public function delete($id)
    {
        return $this->db->delete($this->table, 'id = ?', [$id]);
    }

    /**
     * Get all users with pagination
     */
    public function getAll($page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$perPage, $offset]);
    }

    /**
     * Get user count
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['count'];
    }

    /**
     * Verify password
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Update last login
     */
    public function updateLastLogin($id)
    {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    /**
     * Get user statistics
     */
    public function getStats($id)
    {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM posts WHERE user_id = ?) as post_count,
                    (SELECT COUNT(*) FROM threads WHERE user_id = ?) as thread_count,
                    (SELECT COUNT(*) FROM post_reactions WHERE user_id = ?) as reaction_count
                FROM {$this->table} WHERE id = ?";
        
        return $this->db->fetch($sql, [$id, $id, $id, $id]);
    }

    /**
     * Search users
     */
    public function search($query, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE username LIKE ? OR email LIKE ? OR display_name LIKE ?
                ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $perPage, $offset]);
    }

    /**
     * Ban user
     */
    public function ban($id, $reason = '')
    {
        $data = [
            'status' => 'banned',
            'ban_reason' => $reason,
            'banned_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($id, $data);
    }

    /**
     * Unban user
     */
    public function unban($id)
    {
        $data = [
            'status' => 'active',
            'ban_reason' => null,
            'banned_at' => null
        ];
        
        return $this->update($id, $data);
    }

    /**
     * Get online users
     */
    public function getOnlineUsers($limit = 50)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                AND status = 'active'
                ORDER BY last_activity DESC LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
}