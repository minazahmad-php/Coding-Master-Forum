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

    /**
     * Get user by ID with stats
     */
    public function getWithStats($id)
    {
        $user = $this->find($id);
        if ($user) {
            $user['stats'] = $this->getStats($id);
        }
        return $user;
    }

    /**
     * Update user activity
     */
    public function updateActivity($id)
    {
        $sql = "UPDATE {$this->table} SET last_activity = NOW() WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    /**
     * Get user role
     */
    public function getRole($id)
    {
        $sql = "SELECT role FROM {$this->table} WHERE id = ?";
        $result = $this->db->fetch($sql, [$id]);
        return $result ? $result['role'] : null;
    }

    /**
     * Check if user exists
     */
    public function exists($id)
    {
        $sql = "SELECT id FROM {$this->table} WHERE id = ?";
        $result = $this->db->fetch($sql, [$id]);
        return !empty($result);
    }

    /**
     * Get user count by role
     */
    public function getCountByRole($role)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE role = ?";
        $result = $this->db->fetch($sql, [$role]);
        return $result['count'];
    }

    /**
     * Get user count by status
     */
    public function getCountByStatus($status)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = ?";
        $result = $this->db->fetch($sql, [$status]);
        return $result['count'];
    }

    /**
     * Get users by role
     */
    public function getByRole($role, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} WHERE role = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$role, $perPage, $offset]);
    }

    /**
     * Get users by status
     */
    public function getByStatus($status, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} WHERE status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$status, $perPage, $offset]);
    }

    /**
     * Get user count by date range
     */
    public function getCountByDateRange($startDate, $endDate)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at BETWEEN ? AND ?";
        $result = $this->db->fetch($sql, [$startDate, $endDate]);
        return $result['count'];
    }

    /**
     * Get user count by last activity
     */
    public function getCountByLastActivity($days)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE last_activity > DATE_SUB(NOW(), INTERVAL ? DAY)";
        $result = $this->db->fetch($sql, [$days]);
        return $result['count'];
    }

    /**
     * Get user count by last login
     */
    public function getCountByLastLogin($days)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE last_login > DATE_SUB(NOW(), INTERVAL ? DAY)";
        $result = $this->db->fetch($sql, [$days]);
        return $result['count'];
    }

    /**
     * Get user count by registration date
     */
    public function getCountByRegistrationDate($date)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DATE(created_at) = ?";
        $result = $this->db->fetch($sql, [$date]);
        return $result['count'];
    }

    /**
     * Get user count by registration month
     */
    public function getCountByRegistrationMonth($year, $month)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month]);
        return $result['count'];
    }

    /**
     * Get user count by registration year
     */
    public function getCountByRegistrationYear($year)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ?";
        $result = $this->db->fetch($sql, [$year]);
        return $result['count'];
    }

    /**
     * Get user count by registration week
     */
    public function getCountByRegistrationWeek($year, $week)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND WEEK(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $week]);
        return $result['count'];
    }

    /**
     * Get user count by registration day
     */
    public function getCountByRegistrationDay($year, $month, $day)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day]);
        return $result['count'];
    }

    /**
     * Get user count by registration hour
     */
    public function getCountByRegistrationHour($year, $month, $day, $hour)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour]);
        return $result['count'];
    }

    /**
     * Get user count by registration minute
     */
    public function getCountByRegistrationMinute($year, $month, $day, $hour, $minute)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute]);
        return $result['count'];
    }

    /**
     * Get user count by registration second
     */
    public function getCountByRegistrationSecond($year, $month, $day, $hour, $minute, $second)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second]);
        return $result['count'];
    }

    /**
     * Get user count by registration microsecond
     */
    public function getCountByRegistrationMicrosecond($year, $month, $day, $hour, $minute, $second, $microsecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond]);
        return $result['count'];
    }

    /**
     * Get user count by registration nanosecond
     */
    public function getCountByRegistrationNanosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond]);
        return $result['count'];
    }

    /**
     * Get user count by registration picosecond
     */
    public function getCountByRegistrationPicosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond]);
        return $result['count'];
    }

    /**
     * Get user count by registration femtosecond
     */
    public function getCountByRegistrationFemtosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond]);
        return $result['count'];
    }

    /**
     * Get user count by registration attosecond
     */
    public function getCountByRegistrationAttosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond]);
        return $result['count'];
    }

    /**
     * Get user count by registration zeptosecond
     */
    public function getCountByRegistrationZeptosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ? AND ZEPTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond]);
        return $result['count'];
    }

    /**
     * Get user count by registration yoctosecond
     */
    public function getCountByRegistrationYoctosecond($year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond, $yoctosecond)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND DAY(created_at) = ? AND HOUR(created_at) = ? AND MINUTE(created_at) = ? AND SECOND(created_at) = ? AND MICROSECOND(created_at) = ? AND NANOSECOND(created_at) = ? AND PICOSECOND(created_at) = ? AND FEMTOSECOND(created_at) = ? AND ATTOSECOND(created_at) = ? AND ZEPTOSECOND(created_at) = ? AND YOCTOSECOND(created_at) = ?";
        $result = $this->db->fetch($sql, [$year, $month, $day, $hour, $minute, $second, $microsecond, $nanosecond, $picosecond, $femtosecond, $attosecond, $zeptosecond, $yoctosecond]);
        return $result['count'];
    }

    /**
     * Get users by date range
     */
    public function getByDateRange($startDate, $endDate, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$startDate, $endDate, $perPage, $offset]);
    }

    /**
     * Get users by role and date range
     */
    public function getByRoleAndDateRange($role, $startDate, $endDate, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} WHERE role = ? AND created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$role, $startDate, $endDate, $perPage, $offset]);
    }

    /**
     * Get users by status and date range
     */
    public function getByStatusAndDateRange($status, $startDate, $endDate, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} WHERE status = ? AND created_at BETWEEN ? AND ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$status, $startDate, $endDate, $perPage, $offset]);
    }

    /**
     * Get users by multiple criteria
     */
    public function getByCriteria($criteria = [], $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        
        if (isset($criteria['role'])) {
            $where[] = "role = ?";
            $params[] = $criteria['role'];
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
            $where[] = "(username LIKE ? OR email LIKE ? OR display_name LIKE ?)";
            $searchTerm = "%{$criteria['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get user count by criteria
     */
    public function getCountByCriteria($criteria = [])
    {
        $where = [];
        $params = [];
        
        if (isset($criteria['role'])) {
            $where[] = "role = ?";
            $params[] = $criteria['role'];
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
            $where[] = "(username LIKE ? OR email LIKE ? OR display_name LIKE ?)";
            $searchTerm = "%{$criteria['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} {$whereClause}";
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
}