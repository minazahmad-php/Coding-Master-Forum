<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

/**
 * API Message Controller
 */
class MessageApiController extends BaseController
{
    /**
     * Get user messages
     */
    public function index()
    {
        $this->requireAuth();
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $userId = $this->getCurrentUser()['id'];
        
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT m.*, u.username as sender_name, u.display_name as sender_display_name
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.recipient_id = ?
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?";
        
        $messages = $this->db->fetchAll($sql, [$userId, $perPage, $offset]);
        
        $this->success('Messages retrieved', ['messages' => $messages]);
    }

    /**
     * Get specific message
     */
    public function show($id)
    {
        $this->requireAuth();
        
        $userId = $this->getCurrentUser()['id'];
        
        $sql = "SELECT m.*, u.username as sender_name, u.display_name as sender_display_name
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.id = ? AND (m.sender_id = ? OR m.recipient_id = ?)";
        
        $message = $this->db->fetch($sql, [$id, $userId, $userId]);
        
        if (!$message) {
            $this->error('Message not found', 404);
        }
        
        // Mark as read if recipient
        if ($message['recipient_id'] == $userId && !$message['is_read']) {
            $this->db->update('messages', ['is_read' => 1], 'id = ?', [$id]);
        }
        
        $this->success('Message retrieved', ['message' => $message]);
    }

    /**
     * Send new message
     */
    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $recipientId = (int)($_POST['recipient_id'] ?? 0);
        $subject = $this->sanitize($_POST['subject'] ?? '');
        $content = $this->sanitize($_POST['content'] ?? '');
        
        $errors = $this->validateRequired(['recipient_id', 'subject', 'content'], $_POST);
        
        if (empty($errors)) {
            $messageData = [
                'sender_id' => $this->getCurrentUser()['id'],
                'recipient_id' => $recipientId,
                'subject' => $subject,
                'content' => $content
            ];
            
            $result = $this->db->insert('messages', $messageData);
            
            if ($result) {
                $this->success('Message sent successfully', ['message_id' => $result]);
            } else {
                $this->error('Failed to send message', 500);
            }
        } else {
            $this->error(implode(', ', $errors), 400);
        }
    }

    /**
     * Reply to message
     */
    public function reply($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $userId = $this->getCurrentUser()['id'];
        
        // Get original message
        $originalMessage = $this->db->fetch(
            "SELECT * FROM messages WHERE id = ? AND (sender_id = ? OR recipient_id = ?)",
            [$id, $userId, $userId]
        );
        
        if (!$originalMessage) {
            $this->error('Message not found', 404);
        }
        
        $content = $this->sanitize($_POST['content'] ?? '');
        
        if (empty($content)) {
            $this->error('Content is required', 400);
        }
        
        // Determine recipient (opposite of original sender)
        $recipientId = $originalMessage['sender_id'] == $userId ? 
                      $originalMessage['recipient_id'] : 
                      $originalMessage['sender_id'];
        
        $messageData = [
            'sender_id' => $userId,
            'recipient_id' => $recipientId,
            'subject' => 'Re: ' . $originalMessage['subject'],
            'content' => $content
        ];
        
        $result = $this->db->insert('messages', $messageData);
        
        if ($result) {
            $this->success('Reply sent successfully', ['message_id' => $result]);
        } else {
            $this->error('Failed to send reply', 500);
        }
    }
}