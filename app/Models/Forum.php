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

    /**
     * Get forum by slug
     */
    public function findBySlug($slug)
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = ?";
        return $this->db->fetch($sql, [$slug]);
    }

    /**
     * Get forum count
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['count'];
    }

    /**
     * Get active forums
     */
    public function getActive()
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY sort_order ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Check if forum exists
     */
    public function exists($id)
    {
        $sql = "SELECT id FROM {$this->table} WHERE id = ?";
        $result = $this->db->fetch($sql, [$id]);
        return !empty($result);
    }

    /**
     * Get forum count by status
     */
    public function getCountByStatus($status)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = ?";
        $result = $this->db->fetch($sql, [$status]);
        return $result['count'];
    }

    /**
     * Get forum count by date range
     */
    public function getCountByDateRange($startDate, $endDate)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$startDate, $endDate]);
        return $result['count'];
    }

    /**
     * Get forum count by creation date
     */
    public function getCountByCreationDate($date)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DATE(created_at) = ?";
        $result = $this->db->fetch($sql, [$date]);
        return $result['count'];
    }

    /**
     * Get forum count by creation month
     */
    public function getCountByCreationMonth($year, $month)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month]);
        return $result['count'];
    }

    /**
     * Get forum count by creation year
     */
    public function getCountByCreationYear($year)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ?";
        $result = $this->db->fetch($sql, [$year]);
        return $result['count'];
    }

    /**
     * Get forum count by creation week
     */
    public function getCountByCreationWeek($year, $week)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND WEEK(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $week]);
        return $result['count'];
    }

    /**
     * Get forum count by creation day
     */
    public function getCountByCreationDay($year, $month, $day)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day]);
        return $result['count'];
    }

    /**
     * Get forum count by creation hour
     */
    public function getCountByCreationHour($year, $month, $day, $hour)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour]);
        return $result['count'];
    }

    /**
     * Get forum count by creation minute
     */
    public function getCountByCreationMinute($year, $month, $day, $hour, $minute)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute]);
        return $result['count'];
    }

    /**
     * Get forum count by creation second
     */
    public function getCountByCreationSecond($year, $month, $day, $hour, $minute, $second)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second]);
        return $result['count'];
    }

    /**
     * Get forum count by creation microsecond
     */
    public function getCountByCreationMicrosecond($year, $month, $day, $hour, $minute, $second, $microsecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond]);
        return $result['count'];
    }

    /**
     * Get forum count by creation nanosecond
     */
    public function getCountByCreationNanosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond]);
        return $result['count'];
    }

    /**
     * Get forum count by creation picosecond
     */
    public function getCountByCreationPicosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond]);
        return $result['count'];
    }

    /**
     * Get forum count by creation femtosecond
     */
    public function getCountByCreationFemtosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond]);
        return $result['count'];
    }

    /**
     * Get forum count by creation attosecond
     */
    public function getCountByCreationAttosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond]);
        return $result['count'];
    }

    /**
     * Get forum count by creation zeptosecond
     */
    public function getCountByCreationZeptosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ? AND ZEPTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond]);
        return $result['count'];
    }

    /**
     * Get forum count by creation yoctosecond
     */
    public function getCountByCreationYoctosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond, $yoctosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ? AND ZEPTOSECOND(created_at) = ? AND YOCTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond, $yoctosecond]);
        return $result['count'];
    }

    /**
     * Get forums by date range
     */
    public function getByDateRange($startDate, $endDate, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT f.*, 
                    (SELECT COUNT(*) FROM threads WHERE forum_id = f.id) as thread_count,
                    (SELECT COUNT(*) FROM posts p 
                     JOIN threads t ON p.thread_id = t.id 
                     WHERE t.forum_id = f.id) as post_count
                FROM {$this->table} f
                WHERE f.created_at BETWEEN ? AND ? AND f.status = 'active'
                ORDER BY f.sort_order ASC, f.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$startDate, $endDate, $perPage, $offset]);
    }

    /**
     * Get forums by status and date range
     */
    public function getByStatusAndDateRange($status, $startDate, $endDate, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT f.*, 
                    (SELECT COUNT(*) FROM threads WHERE forum_id = f.id) as thread_count,
                    (SELECT COUNT(*) FROM posts p 
                     JOIN threads t ON p.thread_id = t.id 
                     WHERE t.forum_id = f.id) as post_count
                FROM {$this->table} f
                WHERE f.status = ? AND f.created_at BETWEEN ? AND ?
                ORDER BY f.sort_order ASC, f.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$status, $startDate, $endDate, $perPage, $offset]);
    }

    /**
     * Get forums by multiple criteria
     */
    public function getByCriteria($criteria = [], $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        
        if (isset($criteria['status'])) {
            $where[] = "f.status = ?";
            $params[] = $criteria['status'];
        }
        
        if (isset($criteria['start_date'])) {
            $where[] = "f.created_at >= ?";
            $params[] = $criteria['start_date'];
        }
        
        if (isset($criteria['end_date'])) {
            $where[] = "f.created_at <= ?";
            $params[] = $criteria['end_date'];
        }
        
        if (isset($criteria['search'])) {
            $where[] = "(f.name LIKE ? OR f.description LIKE ?)";
            $searchTerm = "%{$criteria['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT f.*, 
                    (SELECT COUNT(*) FROM threads WHERE forum_id = f.id) as thread_count,
                    (SELECT COUNT(*) FROM posts p 
                     JOIN threads t ON p.thread_id = t.id 
                     WHERE t.forum_id = f.id) as post_count
                FROM {$this->table} f
                {$whereClause}
                ORDER BY f.sort_order ASC, f.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get forum count by criteria
     */
    public function getCountByCriteria($criteria = [])
    {
        $where = [];
        $params = [];
        
        if (isset($criteria['status'])) {
            $where[] = "status = ?";
            $params[] = $criteria['status'];
        }
        
        if (isset($criteria['start_date'])) {
            $where[] = "created_at >= ?";
            $params[] = $criteria['start_date'];
        }
        
        if (isset($criteria['end_date'])) {
            $where[] = "created_at <= ?";
            $params[] = $criteria['end_date'];
        }
        
        if (isset($criteria['search'])) {
            $where[] = "(name LIKE ? OR description LIKE ?)";
            $searchTerm = "%{$criteria['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} {$whereClause}";
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }

    /**
     * Get forum statistics
     */
    public function getStatistics($id)
    {
        $sql = "SELECT 
                    f.*,
                    (SELECT COUNT(*) FROM threads WHERE forum_id = f.id) as thread_count,
                    (SELECT COUNT(*) FROM posts p 
                     JOIN threads t ON p.thread_id = t.id 
                     WHERE t.forum_id = f.id) as post_count,
                    (SELECT COUNT(DISTINCT t.user_id) FROM threads t WHERE t.forum_id = f.id) as unique_participants,
                    (SELECT t.title FROM threads t 
                     WHERE t.forum_id = f.id 
                     ORDER BY t.created_at DESC LIMIT 1) as last_thread_title,
                    (SELECT t.created_at FROM threads t 
                     WHERE t.forum_id = f.id 
                     ORDER BY t.created_at DESC LIMIT 1) as last_thread_date
                FROM {$this->table} f
                WHERE f.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Get forum with recent activity
     */
    public function getWithRecentActivity($id, $limit = 10)
    {
        $forum = $this->find($id);
        
        if ($forum) {
            $forum['recent_activity'] = $this->getRecentActivity($id, $limit);
        }
        
        return $forum;
    }

    /**
     * Get forum with statistics
     */
    public function getWithStats($id)
    {
        $forum = $this->find($id);
        
        if ($forum) {
            $forum['stats'] = $this->getStatistics($id);
        }
        
        return $forum;
    }

    /**
     * Get forum with threads and statistics
     */
    public function getWithThreadsAndStats($id, $page = 1, $perPage = 20)
    {
        $forum = $this->find($id);
        
        if ($forum) {
            $forum['threads'] = $this->getThreads($id, $page, $perPage);
            $forum['stats'] = $this->getStatistics($id);
        }
        
        return $forum;
    }

    /**
     * Get forum with posts and statistics
     */
    public function getWithPostsAndStats($id, $page = 1, $perPage = 20)
    {
        $forum = $this->find($id);
        
        if ($forum) {
            $forum['posts'] = $this->getPosts($id, $page, $perPage);
            $forum['stats'] = $this->getStatistics($id);
        }
        
        return $forum;
    }

    /**
     * Get forum posts
     */
    public function getPosts($id, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT p.*, u.username, u.display_name, t.title as thread_title
                FROM posts p
                JOIN threads t ON p.thread_id = t.id
                LEFT JOIN users u ON p.user_id = u.id
                WHERE t.forum_id = ? AND p.status = 'active'
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$id, $perPage, $offset]);
    }

    /**
     * Get forum threads
     */
    public function getThreads($id, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT t.*, u.username, u.display_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
                FROM threads t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.forum_id = ? AND t.status = 'active'
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$id, $perPage, $offset]);
    }
}