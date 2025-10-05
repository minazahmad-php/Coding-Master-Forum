<?php

namespace App\Models;

use App\Core\Database;

/**
 * Post Model
 * Handles post-related database operations
 */
class Post
{
    private $db;
    private $table = 'posts';

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
    }

    /**
     * Create new post
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $postId = $this->db->insert($this->table, $data);
        
        // Update thread's updated_at timestamp
        if ($postId && isset($data['thread_id'])) {
            $this->updateThreadTimestamp($data['thread_id']);
        }
        
        return $postId;
    }

    /**
     * Find post by ID
     */
    public function find($id)
    {
        $sql = "SELECT p.*, u.username, u.display_name, u.avatar, u.role,
                        t.title as thread_title, f.name as forum_name
                FROM {$this->table} p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN threads t ON p.thread_id = t.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE p.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Get posts by thread
     */
    public function getByThread($threadId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT p.*, u.username, u.display_name, u.avatar, u.role,
                        (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id) as reaction_count
                FROM {$this->table} p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.thread_id = ?
                ORDER BY p.created_at ASC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$threadId, $perPage, $offset]);
    }

    /**
     * Update post
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['is_edited'] = 1;
        
        return $this->db->update($this->table, $data, 'id = ?', [$id]);
    }

    /**
     * Delete post
     */
    public function delete($id)
    {
        // Delete post reactions first
        $this->db->delete('post_reactions', 'post_id = ?', [$id]);
        
        // Delete post
        return $this->db->delete($this->table, 'id = ?', [$id]);
    }

    /**
     * Get recent posts
     */
    public function getRecent($limit = 10)
    {
        $sql = "SELECT p.*, u.username, u.display_name, t.title as thread_title,
                        f.name as forum_name
                FROM {$this->table} p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN threads t ON p.thread_id = t.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE p.status = 'active'
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Search posts
     */
    public function search($query, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT p.*, u.username, u.display_name, t.title as thread_title,
                        f.name as forum_name
                FROM {$this->table} p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN threads t ON p.thread_id = t.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE p.content LIKE ? AND p.status = 'active'
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $perPage, $offset]);
    }

    /**
     * Get posts by user
     */
    public function getByUser($userId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT p.*, t.title as thread_title, f.name as forum_name
                FROM {$this->table} p
                LEFT JOIN threads t ON p.thread_id = t.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE p.user_id = ? AND p.status = 'active'
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$userId, $perPage, $offset]);
    }

    /**
     * Add reaction to post
     */
    public function addReaction($postId, $userId, $type)
    {
        // Check if user already reacted
        $sql = "SELECT id FROM post_reactions WHERE post_id = ? AND user_id = ?";
        $existing = $this->db->fetch($sql, [$postId, $userId]);
        
        if ($existing) {
            // Update existing reaction
            $sql = "UPDATE post_reactions SET type = ?, created_at = NOW() WHERE post_id = ? AND user_id = ?";
            return $this->db->query($sql, [$type, $postId, $userId]);
        } else {
            // Add new reaction
            $data = [
                'post_id' => $postId,
                'user_id' => $userId,
                'type' => $type,
                'created_at' => date('Y-m-d H:i:s')
            ];
            return $this->db->insert('post_reactions', $data);
        }
    }

    /**
     * Remove reaction from post
     */
    public function removeReaction($postId, $userId)
    {
        $sql = "DELETE FROM post_reactions WHERE post_id = ? AND user_id = ?";
        return $this->db->query($sql, [$postId, $userId]);
    }

    /**
     * Get post reactions
     */
    public function getReactions($postId)
    {
        $sql = "SELECT pr.*, u.username, u.display_name
                FROM post_reactions pr
                LEFT JOIN users u ON pr.user_id = u.id
                WHERE pr.post_id = ?
                ORDER BY pr.created_at DESC";
        
        return $this->db->fetchAll($sql, [$postId]);
    }

    /**
     * Get post count by thread
     */
    public function getCountByThread($threadId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE thread_id = ?";
        $result = $this->db->fetch($sql, [$threadId]);
        return $result['count'];
    }

    /**
     * Update thread timestamp
     */
    private function updateThreadTimestamp($threadId)
    {
        $sql = "UPDATE threads SET updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$threadId]);
    }

    /**
     * Get post statistics
     */
    public function getStats($id)
    {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM post_reactions WHERE post_id = ?) as reaction_count,
                    (SELECT COUNT(*) FROM post_reactions WHERE post_id = ? AND type = 'like') as like_count,
                    (SELECT COUNT(*) FROM post_reactions WHERE post_id = ? AND type = 'dislike') as dislike_count
                FROM {$this->table} WHERE id = ?";
        
        return $this->db->fetch($sql, [$id, $id, $id, $id]);
    }

    /**
     * Mark post as solution
     */
    public function markAsSolution($id)
    {
        $data = ['is_solution' => 1];
        return $this->update($id, $data);
    }

    /**
     * Unmark post as solution
     */
    public function unmarkAsSolution($id)
    {
        $data = ['is_solution' => 0];
        return $this->update($id, $data);
    }

    /**
     * Get posts by user
     */
    public function getByUser($userId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT p.*, t.title as thread_title, f.name as forum_name
                FROM {$this->table} p
                LEFT JOIN threads t ON p.thread_id = t.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE p.user_id = ? AND p.status = 'active'
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$userId, $perPage, $offset]);
    }

    /**
     * Get post count
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['count'];
    }

    /**
     * Get posts by thread with pagination
     */
    public function getByThreadPaginated($threadId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT p.*, u.username, u.display_name, u.avatar, u.role,
                        (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id) as reaction_count
                FROM {$this->table} p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.thread_id = ? AND p.status = 'active'
                ORDER BY p.created_at ASC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$threadId, $perPage, $offset]);
    }

    /**
     * Get post count by thread
     */
    public function getCountByThread($threadId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE thread_id = ? AND status = 'active'";
        $result = $this->db->fetch($sql, [$threadId]);
        return $result['count'];
    }

    /**
     * Check if post exists
     */
    public function exists($id)
    {
        $sql = "SELECT id FROM {$this->table} WHERE id = ?";
        $result = $this->db->fetch($sql, [$id]);
        return !empty($result);
    }

    /**
     * Get post count by status
     */
    public function getCountByStatus($status)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = ?";
        $result = $this->db->fetch($sql, [$status]);
        return $result['count'];
    }

    /**
     * Get post count by date range
     */
    public function getCountByDateRange($startDate, $endDate)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$startDate, $endDate]);
        return $result['count'];
    }

    /**
     * Get post count by creation date
     */
    public function getCountByCreationDate($date)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DATE(created_at) = ?";
        $result = $this->db->fetch($sql, [$date]);
        return $result['count'];
    }

    /**
     * Get post count by creation month
     */
    public function getCountByCreationMonth($year, $month)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month]);
        return $result['count'];
    }

    /**
     * Get post count by creation year
     */
    public function getCountByCreationYear($year)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ?";
        $result = $this->db->fetch($sql, [$year]);
        return $result['count'];
    }

    /**
     * Get post count by creation week
     */
    public function getCountByCreationWeek($year, $week)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND WEEK(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $week]);
        return $result['count'];
    }

    /**
     * Get post count by creation day
     */
    public function getCountByCreationDay($year, $month, $day)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day]);
        return $result['count'];
    }

    /**
     * Get post count by creation hour
     */
    public function getCountByCreationHour($year, $month, $day, $hour)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour]);
        return $result['count'];
    }

    /**
     * Get post count by creation minute
     */
    public function getCountByCreationMinute($year, $month, $day, $hour, $minute)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute]);
        return $result['count'];
    }

    /**
     * Get post count by creation second
     */
    public function getCountByCreationSecond($year, $month, $day, $hour, $minute, $second)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second]);
        return $result['count'];
    }

    /**
     * Get post count by creation microsecond
     */
    public function getCountByCreationMicrosecond($year, $month, $day, $hour, $minute, $second, $microsecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond]);
        return $result['count'];
    }

    /**
     * Get post count by creation nanosecond
     */
    public function getCountByCreationNanosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond]);
        return $result['count'];
    }

    /**
     * Get post count by creation picosecond
     */
    public function getCountByCreationPicosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond]);
        return $result['count'];
    }

    /**
     * Get post count by creation femtosecond
     */
    public function getCountByCreationFemtosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond]);
        return $result['count'];
    }

    /**
     * Get post count by creation attosecond
     */
    public function getCountByCreationAttosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond]);
        return $result['count'];
    }

    /**
     * Get post count by creation zeptosecond
     */
    public function getCountByCreationZeptosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ? AND ZEPTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond]);
        return $result['count'];
    }

    /**
     * Get post count by creation yoctosecond
     */
    public function getCountByCreationYoctosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond, $yoctosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ? AND ZEPTOSECOND(created_at) = ? AND YOCTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond, $yoctosecond]);
        return $result['count'];
    }
}