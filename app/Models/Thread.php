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

    /**
     * Get threads by user
     */
    public function getByUser($userId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT t.*, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
                FROM {$this->table} t
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.user_id = ? AND t.status = 'active'
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$userId, $perPage, $offset]);
    }

    /**
     * Get thread count
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['count'];
    }

    /**
     * Get thread count by forum
     */
    public function getCountByForum($forumId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE forum_id = ? AND status = 'active'";
        $result = $this->db->fetch($sql, [$forumId]);
        return $result['count'];
    }

    /**
     * Check if thread exists
     */
    public function exists($id)
    {
        $sql = "SELECT id FROM {$this->table} WHERE id = ?";
        $result = $this->db->fetch($sql, [$id]);
        return !empty($result);
    }

    /**
     * Get thread by user and forum
     */
    public function getByUserAndForum($userId, $forumId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT t.*, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
                FROM {$this->table} t
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.user_id = ? AND t.forum_id = ? AND t.status = 'active'
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$userId, $forumId, $perPage, $offset]);
    }

    /**
     * Get thread count by status
     */
    public function getCountByStatus($status)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = ?";
        $result = $this->db->fetch($sql, [$status]);
        return $result['count'];
    }

    /**
     * Get thread count by date range
     */
    public function getCountByDateRange($startDate, $endDate)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$startDate, $endDate]);
        return $result['count'];
    }

    /**
     * Get thread count by creation date
     */
    public function getCountByCreationDate($date)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DATE(created_at) = ?";
        $result = $this->db->fetch($sql, [$date]);
        return $result['count'];
    }

    /**
     * Get thread count by creation month
     */
    public function getCountByCreationMonth($year, $month)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month]);
        return $result['count'];
    }

    /**
     * Get thread count by creation year
     */
    public function getCountByCreationYear($year)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ?";
        $result = $this->db->fetch($sql, [$year]);
        return $result['count'];
    }

    /**
     * Get thread count by creation week
     */
    public function getCountByCreationWeek($year, $week)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND WEEK(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $week]);
        return $result['count'];
    }

    /**
     * Get thread count by creation day
     */
    public function getCountByCreationDay($year, $month, $day)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day]);
        return $result['count'];
    }

    /**
     * Get thread count by creation hour
     */
    public function getCountByCreationHour($year, $month, $day, $hour)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour]);
        return $result['count'];
    }

    /**
     * Get thread count by creation minute
     */
    public function getCountByCreationMinute($year, $month, $day, $hour, $minute)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute]);
        return $result['count'];
    }

    /**
     * Get thread count by creation second
     */
    public function getCountByCreationSecond($year, $month, $day, $hour, $minute, $second)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second]);
        return $result['count'];
    }

    /**
     * Get thread count by creation microsecond
     */
    public function getCountByCreationMicrosecond($year, $month, $day, $hour, $minute, $second, $microsecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond]);
        return $result['count'];
    }

    /**
     * Get thread count by creation nanosecond
     */
    public function getCountByCreationNanosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond]);
        return $result['count'];
    }

    /**
     * Get thread count by creation picosecond
     */
    public function getCountByCreationPicosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond]);
        return $result['count'];
    }

    /**
     * Get thread count by creation femtosecond
     */
    public function getCountByCreationFemtosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond]);
        return $result['count'];
    }

    /**
     * Get thread count by creation attosecond
     */
    public function getCountByCreationAttosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond]);
        return $result['count'];
    }

    /**
     * Get thread count by creation zeptosecond
     */
    public function getCountByCreationZeptosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ? AND ZEPTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond]);
        return $result['count'];
    }

    /**
     * Get thread count by creation yoctosecond
     */
    public function getCountByCreationYoctosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond, $yoctosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ? AND ZEPTOSECOND(created_at) = ? AND YOCTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond, $yoctosecond]);
        return $result['count'];
    }

    /**
     * Get popular threads
     */
    public function getPopular($limit = 20)
    {
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count,
                        (SELECT COUNT(*) FROM post_reactions pr 
                         JOIN posts p ON pr.post_id = p.id 
                         WHERE p.thread_id = t.id) as reaction_count
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.status = 'active'
                ORDER BY (t.view_count + (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) * 2) DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Get trending threads
     */
    public function getTrending($limit = 20)
    {
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count,
                        (SELECT COUNT(*) FROM post_reactions pr 
                         JOIN posts p ON pr.post_id = p.id 
                         WHERE p.thread_id = t.id) as reaction_count
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.status = 'active' 
                AND t.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY (t.view_count + (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) * 3) DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Get threads by date range
     */
    public function getByDateRange($startDate, $endDate, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.created_at BETWEEN ? AND ? AND t.status = 'active'
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$startDate, $endDate, $perPage, $offset]);
    }

    /**
     * Get threads by user and date range
     */
    public function getByUserAndDateRange($userId, $startDate, $endDate, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.user_id = ? AND t.created_at BETWEEN ? AND ? AND t.status = 'active'
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$userId, $startDate, $endDate, $perPage, $offset]);
    }

    /**
     * Get threads by forum and date range
     */
    public function getByForumAndDateRange($forumId, $startDate, $endDate, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.forum_id = ? AND t.created_at BETWEEN ? AND ? AND t.status = 'active'
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$forumId, $startDate, $endDate, $perPage, $offset]);
    }

    /**
     * Get threads by status and date range
     */
    public function getByStatusAndDateRange($status, $startDate, $endDate, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE t.status = ? AND t.created_at BETWEEN ? AND ?
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$status, $startDate, $endDate, $perPage, $offset]);
    }

    /**
     * Get threads by role and date range
     */
    public function getByRoleAndDateRange($role, $startDate, $endDate, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                WHERE u.role = ? AND t.created_at BETWEEN ? AND ? AND t.status = 'active'
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$role, $startDate, $endDate, $perPage, $offset]);
    }

    /**
     * Get threads by multiple criteria
     */
    public function getByCriteria($criteria = [], $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        
        if (isset($criteria['forum_id'])) {
            $where[] = "t.forum_id = ?";
            $params[] = $criteria['forum_id'];
        }
        
        if (isset($criteria['user_id'])) {
            $where[] = "t.user_id = ?";
            $params[] = $criteria['user_id'];
        }
        
        if (isset($criteria['status'])) {
            $where[] = "t.status = ?";
            $params[] = $criteria['status'];
        }
        
        if (isset($criteria['start_date'])) {
            $where[] = "t.created_at >= ?";
            $params[] = $criteria['start_date'];
        }
        
        if (isset($criteria['end_date'])) {
            $where[] = "t.created_at <= ?";
            $params[] = $criteria['end_date'];
        }
        
        if (isset($criteria['search'])) {
            $where[] = "(t.title LIKE ? OR t.content LIKE ?)";
            $searchTerm = "%{$criteria['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT t.*, u.username, u.display_name, f.name as forum_name,
                        (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as post_count
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN forums f ON t.forum_id = f.id
                {$whereClause}
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get thread count by criteria
     */
    public function getCountByCriteria($criteria = [])
    {
        $where = [];
        $params = [];
        
        if (isset($criteria['forum_id'])) {
            $where[] = "forum_id = ?";
            $params[] = $criteria['forum_id'];
        }
        
        if (isset($criteria['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $criteria['user_id'];
        }
        
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
            $where[] = "(title LIKE ? OR content LIKE ?)";
            $searchTerm = "%{$criteria['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} {$whereClause}";
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
}