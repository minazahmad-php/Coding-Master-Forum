<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class SurveyToolsService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Create survey
     */
    public function createSurvey(array $surveyData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO surveys (title, description, creator_id, questions, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $surveyData['title'],
                $surveyData['description'],
                $surveyData['creator_id'],
                json_encode($surveyData['questions']),
                'draft',
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'survey_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to create survey: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get surveys
     */
    public function getSurveys(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    s.*,
                    u.username as creator_name,
                    COUNT(sr.id) as responses_count
                FROM surveys s
                LEFT JOIN users u ON s.creator_id = u.id
                LEFT JOIN survey_responses sr ON s.id = sr.survey_id
                GROUP BY s.id
                ORDER BY s.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get surveys: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Submit survey response
     */
    public function submitSurveyResponse(array $responseData): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO survey_responses (survey_id, user_id, answers, submitted_at)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $responseData['survey_id'],
                $responseData['user_id'],
                json_encode($responseData['answers']),
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'response_id' => $this->db->lastInsertId()];
        } catch (\Exception $e) {
            $this->logger->error("Failed to submit survey response: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get survey analytics
     */
    public function getSurveyAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    s.title as survey_title,
                    COUNT(sr.id) as responses_count,
                    COUNT(DISTINCT sr.user_id) as unique_respondents,
                    AVG(response_time) as avg_response_time,
                    AVG(answer_count) as avg_answers_per_response
                FROM surveys s
                LEFT JOIN survey_responses sr ON s.id = sr.survey_id
                WHERE sr.submitted_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY s.id, s.title
                ORDER BY responses_count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get survey analytics: " . $e->getMessage());
            return [];
        }
    }
}