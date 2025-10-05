<?php

namespace App\Controllers;

/**
 * Message Controller
 * Handles private messaging
 */
class MessageController extends BaseController
{
    /**
     * Show messages index
     */
    public function index()
    {
        $this->requireAuth();
        
        $data = [
            'title' => 'Messages'
        ];
        
        echo $this->view->render('user/messages', $data);
    }

    /**
     * Show conversation
     */
    public function conversation($id)
    {
        $this->requireAuth();
        
        $data = [
            'title' => 'Conversation',
            'conversation_id' => $id
        ];
        
        echo $this->view->render('user/conversation', $data);
    }

    /**
     * Show new message form
     */
    public function newMessage()
    {
        $this->requireAuth();
        
        $data = [
            'title' => 'Send New Message'
        ];
        
        echo $this->view->render('user/new_message', $data);
    }

    /**
     * Send message
     */
    public function sendMessage()
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
                $this->redirectWithMessage('/messages', 'success', 'Message sent successfully!');
            } else {
                $this->redirectWithMessage('/new-message', 'error', 'Failed to send message.');
            }
        } else {
            $this->redirectWithMessage('/new-message', 'error', implode('<br>', $errors));
        }
    }
}