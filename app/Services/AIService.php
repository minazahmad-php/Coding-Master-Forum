<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;

/**
 * AI Service for Content Moderation and Recommendations
 */
class AIService
{
    private $db;
    private $logger;
    private $openaiApiKey;
    private $moderationApiKey;

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
        $this->logger = $app->get('logger');
        $this->openaiApiKey = config('ai.openai_api_key');
        $this->moderationApiKey = config('ai.moderation_api_key');
    }

    /**
     * AI Content Moderation
     */
    public function moderateContent($content, $type = 'post')
    {
        try {
            $moderationResult = $this->callModerationAPI($content);
            
            // Store moderation result
            $this->db->query(
                "INSERT INTO content_moderation (content, type, result, created_at) VALUES (?, ?, ?, NOW())",
                [$content, $type, json_encode($moderationResult)]
            );

            return $moderationResult;
        } catch (\Exception $e) {
            $this->logger->error('Content moderation failed: ' . $e->getMessage());
            return ['approved' => false, 'reason' => 'Moderation error'];
        }
    }

    /**
     * Generate AI Chatbot Response
     */
    public function generateChatbotResponse($message, $context = [])
    {
        try {
            $response = $this->callOpenAIAPI($message, $context);
            
            // Store conversation
            $this->db->query(
                "INSERT INTO chatbot_conversations (user_message, bot_response, created_at) VALUES (?, ?, NOW())",
                [$message, $response]
            );

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Chatbot response generation failed: ' . $e->getMessage());
            return "I'm sorry, I'm having trouble processing your request right now. Please try again later.";
        }
    }

    /**
     * Generate Content Recommendations
     */
    public function generateRecommendations($userId, $limit = 10)
    {
        try {
            $userInterests = $this->getUserInterests($userId);
            $userHistory = $this->getUserHistory($userId);
            
            $recommendations = $this->callRecommendationAPI($userInterests, $userHistory, $limit);
            
            return $recommendations;
        } catch (\Exception $e) {
            $this->logger->error('Recommendation generation failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Auto-translate Content
     */
    public function translateContent($content, $targetLanguage = 'en')
    {
        try {
            $translation = $this->callTranslationAPI($content, $targetLanguage);
            
            return $translation;
        } catch (\Exception $e) {
            $this->logger->error('Translation failed: ' . $e->getMessage());
            return $content; // Return original content if translation fails
        }
    }

    /**
     * Sentiment Analysis
     */
    public function analyzeSentiment($content)
    {
        try {
            $sentiment = $this->callSentimentAPI($content);
            
            // Store sentiment analysis
            $this->db->query(
                "INSERT INTO sentiment_analysis (content, sentiment, confidence, created_at) VALUES (?, ?, ?, NOW())",
                [$content, $sentiment['sentiment'], $sentiment['confidence']]
            );

            return $sentiment;
        } catch (\Exception $e) {
            $this->logger->error('Sentiment analysis failed: ' . $e->getMessage());
            return ['sentiment' => 'neutral', 'confidence' => 0.5];
        }
    }

    /**
     * Generate Smart Tags
     */
    public function generateTags($content)
    {
        try {
            $tags = $this->callTaggingAPI($content);
            
            return $tags;
        } catch (\Exception $e) {
            $this->logger->error('Tag generation failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Content Summarization
     */
    public function summarizeContent($content, $maxLength = 200)
    {
        try {
            $summary = $this->callSummarizationAPI($content, $maxLength);
            
            return $summary;
        } catch (\Exception $e) {
            $this->logger->error('Content summarization failed: ' . $e->getMessage());
            return substr($content, 0, $maxLength) . '...';
        }
    }

    /**
     * Call Moderation API
     */
    private function callModerationAPI($content)
    {
        $url = 'https://api.openai.com/v1/moderations';
        $data = [
            'input' => $content
        ];

        $response = $this->makeAPIRequest($url, $data, $this->openaiApiKey);
        
        if ($response && isset($response['results'][0])) {
            $result = $response['results'][0];
            return [
                'approved' => !$result['flagged'],
                'categories' => $result['categories'],
                'scores' => $result['category_scores']
            ];
        }

        return ['approved' => true, 'reason' => 'API error'];
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAIAPI($message, $context = [])
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful forum assistant. Provide helpful, accurate, and friendly responses.'
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ],
            'max_tokens' => 150,
            'temperature' => 0.7
        ];

        $response = $this->makeAPIRequest($url, $data, $this->openaiApiKey);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }

        return "I'm sorry, I couldn't process your request.";
    }

    /**
     * Call Recommendation API
     */
    private function callRecommendationAPI($interests, $history, $limit)
    {
        // This would integrate with a recommendation service
        // For now, return basic recommendations based on user activity
        $sql = "SELECT t.*, f.name as forum_name 
                FROM threads t 
                LEFT JOIN forums f ON t.forum_id = f.id 
                WHERE t.status = 'active' 
                ORDER BY t.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Call Translation API
     */
    private function callTranslationAPI($content, $targetLanguage)
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Translate the following text to {$targetLanguage}. Only return the translation, no explanations."
                ],
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ],
            'max_tokens' => 500,
            'temperature' => 0.3
        ];

        $response = $this->makeAPIRequest($url, $data, $this->openaiApiKey);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }

        return $content;
    }

    /**
     * Call Sentiment API
     */
    private function callSentimentAPI($content)
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Analyze the sentiment of the following text. Respond with JSON format: {"sentiment": "positive/negative/neutral", "confidence": 0.0-1.0}'
                ],
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ],
            'max_tokens' => 50,
            'temperature' => 0.1
        ];

        $response = $this->makeAPIRequest($url, $data, $this->openaiApiKey);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            $result = json_decode($response['choices'][0]['message']['content'], true);
            return $result ?: ['sentiment' => 'neutral', 'confidence' => 0.5];
        }

        return ['sentiment' => 'neutral', 'confidence' => 0.5];
    }

    /**
     * Call Tagging API
     */
    private function callTaggingAPI($content)
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Extract relevant tags from the following text. Respond with a comma-separated list of tags (max 5).'
                ],
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ],
            'max_tokens' => 50,
            'temperature' => 0.3
        ];

        $response = $this->makeAPIRequest($url, $data, $this->openaiApiKey);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            $tags = explode(',', $response['choices'][0]['message']['content']);
            return array_map('trim', $tags);
        }

        return [];
    }

    /**
     * Call Summarization API
     */
    private function callSummarizationAPI($content, $maxLength)
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Summarize the following text in maximum {$maxLength} characters. Keep it concise and informative."
                ],
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ],
            'max_tokens' => $maxLength,
            'temperature' => 0.3
        ];

        $response = $this->makeAPIRequest($url, $data, $this->openaiApiKey);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }

        return substr($content, 0, $maxLength) . '...';
    }

    /**
     * Make API Request
     */
    private function makeAPIRequest($url, $data, $apiKey)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        return null;
    }

    /**
     * Get User Interests
     */
    private function getUserInterests($userId)
    {
        $sql = "SELECT f.name, COUNT(*) as activity 
                FROM posts p 
                LEFT JOIN threads t ON p.thread_id = t.id 
                LEFT JOIN forums f ON t.forum_id = f.id 
                WHERE p.user_id = ? 
                GROUP BY f.id, f.name 
                ORDER BY activity DESC 
                LIMIT 10";
        
        return $this->db->fetchAll($sql, [$userId]);
    }

    /**
     * Get User History
     */
    private function getUserHistory($userId)
    {
        $sql = "SELECT t.title, t.content, t.created_at 
                FROM threads t 
                WHERE t.user_id = ? 
                ORDER BY t.created_at DESC 
                LIMIT 20";
        
        return $this->db->fetchAll($sql, [$userId]);
    }
}