<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class QuizSystemService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create quiz
     */
    public function createQuiz(array $quizData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO quizzes (title, description, course_id, time_limit, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $quizData['title'],
                $quizData['description'],
                $quizData['course_id'],
                $quizData['time_limit'],
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'quiz_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create quiz: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get quizzes
     */
    public function getQuizzes(int $courseId, int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    q.*,
                    COUNT(qa.id) as attempts_count,
                    AVG(qa.score) as avg_score
                FROM quizzes q
                LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id
                WHERE q.course_id = ?
                GROUP BY q.id
                ORDER BY q.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$courseId, $limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get quizzes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Submit quiz attempt
     */
    public function submitQuizAttempt(array $attemptData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO quiz_attempts (quiz_id, user_id, score, answers, completed_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $attemptData['quiz_id'],
                $attemptData['user_id'],
                $attemptData['score'],
                json_encode($attemptData['answers']),
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'attempt_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to submit quiz attempt: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get quiz analytics
     */
    public function getQuizAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    q.title as quiz_title,
                    COUNT(qa.id) as attempts_count,
                    AVG(qa.score) as avg_score,
                    MAX(qa.score) as max_score,
                    MIN(qa.score) as min_score,
                    COUNT(DISTINCT qa.user_id) as unique_students
                FROM quizzes q
                LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id
                WHERE qa.completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY q.id, q.title
                ORDER BY attempts_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get quiz analytics: " . $e->getMessage());
            return [];
        }
    }
}