<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\Thread;
use App\Models\Post;

/**
 * API Thread Controller
 */
class ThreadApiController extends BaseController
{
    /**
     * Get all threads
     */
    public function index()
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $forumId = (int)($_GET['forum_id'] ?? 0);
        
        $threadModel = new Thread();
        
        if ($forumId) {
            $threads = $threadModel->getByForum($forumId, $page, $perPage);
        } else {
            $threads = $threadModel->getRecent($perPage);
        }
        
        $this->success('Threads retrieved', ['threads' => $threads]);
    }

    /**
     * Get specific thread
     */
    public function show($id)
    {
        $threadModel = new Thread();
        $thread = $threadModel->find($id);
        
        if (!$thread) {
            $this->error('Thread not found', 404);
        }
        
        $this->success('Thread retrieved', ['thread' => $thread]);
    }

    /**
     * Create new thread
     */
    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $forumId = (int)($_POST['forum_id'] ?? 0);
        $title = $this->sanitize($_POST['title'] ?? '');
        $content = $this->sanitize($_POST['content'] ?? '');
        
        $errors = $this->validateRequired(['forum_id', 'title', 'content'], $_POST);
        
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
                $this->success('Thread created successfully', ['thread_id' => $threadId]);
            } else {
                $this->error('Failed to create thread', 500);
            }
        } else {
            $this->error(implode(', ', $errors), 400);
        }
    }

    /**
     * Update thread
     */
    public function update($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $threadModel = new Thread();
        $thread = $threadModel->find($id);
        
        if (!$thread) {
            $this->error('Thread not found', 404);
        }
        
        // Check if user owns the thread or is admin/moderator
        if ($thread['user_id'] != $this->getCurrentUser()['id'] && !$this->isModerator()) {
            $this->error('Unauthorized', 403);
        }
        
        $title = $this->sanitize($_POST['title'] ?? '');
        $content = $this->sanitize($_POST['content'] ?? '');
        
        $data = [];
        if ($title) $data['title'] = $title;
        if ($content) $data['content'] = $content;
        
        $result = $threadModel->update($id, $data);
        
        if ($result) {
            $this->success('Thread updated successfully');
        } else {
            $this->error('Failed to update thread', 500);
        }
    }

    /**
     * Delete thread
     */
    public function delete($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $threadModel = new Thread();
        $thread = $threadModel->find($id);
        
        if (!$thread) {
            $this->error('Thread not found', 404);
        }
        
        // Check if user owns the thread or is admin/moderator
        if ($thread['user_id'] != $this->getCurrentUser()['id'] && !$this->isModerator()) {
            $this->error('Unauthorized', 403);
        }
        
        $result = $threadModel->delete($id);
        
        if ($result) {
            $this->success('Thread deleted successfully');
        } else {
            $this->error('Failed to delete thread', 500);
        }
    }

    /**
     * Get thread posts
     */
    public function posts($id)
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        $threadModel = new Thread();
        $thread = $threadModel->getWithPosts($id, $page, $perPage);
        
        if (!$thread) {
            $this->error('Thread not found', 404);
        }
        
        $this->success('Posts retrieved', [
            'thread' => $thread,
            'posts' => $thread['posts'] ?? []
        ]);
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
        
        $userId = $this->getCurrentUser()['id'];
        
        // Check if already subscribed
        $existing = $this->db->fetch(
            "SELECT id FROM thread_subscriptions WHERE thread_id = ? AND user_id = ?",
            [$id, $userId]
        );
        
        if ($existing) {
            $this->error('Already subscribed to this thread', 400);
        }
        
        $result = $this->db->insert('thread_subscriptions', [
            'thread_id' => $id,
            'user_id' => $userId
        ]);
        
        if ($result) {
            $this->success('Subscribed to thread successfully');
        } else {
            $this->error('Failed to subscribe to thread', 500);
        }
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
        
        $userId = $this->getCurrentUser()['id'];
        
        $result = $this->db->delete(
            'thread_subscriptions',
            'thread_id = ? AND user_id = ?',
            [$id, $userId]
        );
        
        if ($result) {
            $this->success('Unsubscribed from thread successfully');
        } else {
            $this->error('Failed to unsubscribe from thread', 500);
        }
    }
}