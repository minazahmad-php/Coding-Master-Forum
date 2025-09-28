<?php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Logger;

class ChatbotService
{
    private Database $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Process chatbot message
     */
    public function processMessage(int $userId, string $message): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO chatbot_conversations (user_id, user_message, bot_response, created_at)
                VALUES (?, ?, ?, ?)
            ");
            
            $response = $this->generateResponse($message);
            
            $stmt->execute([
                $userId,
                $message,
                $response,
                date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'response' => $response];
        } catch (\Exception $e) {
            $this->logger->error("Failed to process chatbot message: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate bot response
     */
    private function generateResponse(string $message): string
    {
        $message = strtolower($message);
        
        if (strpos($message, 'hello') !== false || strpos($message, 'hi') !== false) {
            return "Hello! How can I help you today?";
        }
        
        if (strpos($message, 'help') !== false) {
            return "I can help you with forum-related questions, user support, and general information.";
        }
        
        if (strpos($message, 'post') !== false) {
            return "To create a post, go to the 'Create Post' section and fill in the required information.";
        }
        
        if (strpos($message, 'comment') !== false) {
            return "You can comment on posts by clicking the 'Comment' button below any post.";
        }
        
        return "I'm here to help! Please let me know what you need assistance with.";
    }

    /**
     * Get chatbot analytics
     */
    public function getChatbotAnalytics(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_conversations,
                    COUNT(DISTINCT user_id) as unique_users,
                    AVG(LENGTH(user_message)) as avg_message_length,
                    AVG(LENGTH(bot_response)) as avg_response_length
                FROM chatbot_conversations 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->logger->error("Failed to get chatbot analytics: " . $e->getMessage());
            return [];
        }
    }
}