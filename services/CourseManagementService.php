<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class CourseManagementService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create course
     */
    public function createCourse(array $courseData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO courses (title, description, instructor_id, category, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $courseData['title'],
                $courseData['description'],
                $courseData['instructor_id'],
                $courseData['category'],
                'draft',
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'course_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create course: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get courses
     */
    public function getCourses(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.*,
                    u.username as instructor_name,
                    COUNT(cl.id) as enrolled_students,
                    AVG(cr.rating) as avg_rating
                FROM courses c
                LEFT JOIN users u ON c.instructor_id = u.id
                LEFT JOIN course_enrollments cl ON c.id = cl.course_id
                LEFT JOIN course_ratings cr ON c.id = cr.course_id
                WHERE c.status = 'published'
                GROUP BY c.id
                ORDER BY c.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get courses: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Enroll in course
     */
    public function enrollInCourse(int $courseId, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO course_enrollments (course_id, user_id, enrolled_at)
                VALUES (?, ?, ?)
            ");
            
            return $stmt->execute([
                $courseId,
                $userId,
                date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to enroll in course: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get course analytics
     */
    public function getCourseAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.title as course_title,
                    COUNT(ce.id) as enrollments,
                    COUNT(DISTINCT ce.user_id) as unique_students,
                    AVG(cr.rating) as avg_rating,
                    COUNT(cr.id) as ratings_count
                FROM courses c
                LEFT JOIN course_enrollments ce ON c.id = ce.course_id
                LEFT JOIN course_ratings cr ON c.id = cr.course_id
                WHERE ce.enrolled_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY c.id, c.title
                ORDER BY enrollments DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get course analytics: " . $e->getMessage());
            return [];
        }
    }
}