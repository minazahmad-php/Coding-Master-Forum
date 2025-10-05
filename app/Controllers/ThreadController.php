<?php

namespace App\Controllers;

use App\Models\Thread;
use App\Models\Forum;

/**
 * Thread Controller
 * Handles thread-related operations
 */
class ThreadController extends BaseController
{
    /**
     * Show create thread form
     */
    public function create($forumId)
    {
        $this->requireAuth();
        
        $forumModel = new Forum();
        $forum = $forumModel->find($forumId);
        
        if (!$forum) {
            $this->view->error(404, 'Forum not found');
        }
        
        $data = [
            'title' => 'Create New Thread',
            'forum' => $forum
        ];
        
        echo $this->view->render('thread_create', $data);
    }

    /**
     * Store new thread
     */
    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $forumId = (int)($_POST['forum_id'] ?? 0);
        $title = $this->sanitize($_POST['title'] ?? '');
        $content = $this->sanitize($_POST['content'] ?? '');
        
        $errors = $this->validateRequired(['title', 'content'], $_POST);
        
        if (empty($errors)) {
            $threadModel = new Thread();
            
            $threadData = [
                'forum_id' => $forumId,
                'user_id' => $this->getCurrentUser()['id'],
                'title' => $title,
                'content' => $content
            ];
            
            $threadId = $threadModel->create($threadData);
            
            if ($threadId) {
                $this->logActivity('Thread created', ['thread_id' => $threadId]);
                $this->redirectWithMessage('/thread/' . $threadId, 'success', 'Thread created successfully!');
            } else {
                $this->redirectWithMessage('/create-thread/' . $forumId, 'error', 'Failed to create thread.');
            }
        } else {
            $this->redirectWithMessage('/create-thread/' . $forumId, 'error', implode('<br>', $errors));
        }
    }

    /**
     * Subscribe to thread
     */
    public function subscribe($id)
    {
        $this->requireAuth();
        
        $threadModel = new Thread();
        $thread = $threadModel->find($id);
        
        if (!$thread) {
            $this->error('Thread not found', 404);
        }
        
        // Add subscription logic here
        $this->success('Subscribed to thread');
    }

    /**
     * Unsubscribe from thread
     */
    public function unsubscribe($id)
    {
        $this->requireAuth();
        
        $threadModel = new Thread();
        $thread = $threadModel->find($id);
        
        if (!$thread) {
            $this->error('Thread not found', 404);
        }
        
        // Remove subscription logic here
        $this->success('Unsubscribed from thread');
    }
}