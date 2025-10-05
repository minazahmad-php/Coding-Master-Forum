<?php

namespace App\Controllers;

use App\Models\Post;
use App\Models\Thread;

/**
 * Post Controller
 * Handles post-related operations
 */
class PostController extends BaseController
{
    /**
     * Create new post
     */
    public function create()
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $threadId = (int)($_POST['thread_id'] ?? 0);
        $content = $this->sanitize($_POST['content'] ?? '');
        
        $errors = $this->validateRequired(['content'], $_POST);
        
        if (empty($errors)) {
            $postModel = new Post();
            
            $postData = [
                'thread_id' => $threadId,
                'user_id' => $this->getCurrentUser()['id'],
                'content' => $content
            ];
            
            $postId = $postModel->create($postData);
            
            if ($postId) {
                $this->logActivity('Post created', ['post_id' => $postId]);
                $this->success('Post created successfully!');
            } else {
                $this->error('Failed to create post', 500);
            }
        } else {
            $this->error(implode(', ', $errors), 400);
        }
    }

    /**
     * React to post
     */
    public function react($id)
    {
        $this->requireAuth();
        
        $type = $_POST['type'] ?? 'like';
        $postModel = new Post();
        
        $result = $postModel->addReaction($id, $this->getCurrentUser()['id'], $type);
        
        if ($result) {
            $this->success('Reaction added');
        } else {
            $this->error('Failed to add reaction', 500);
        }
    }

    /**
     * Mark post as solution
     */
    public function markSolution($id)
    {
        $this->requireAuth();
        
        $postModel = new Post();
        $post = $postModel->find($id);
        
        if (!$post) {
            $this->error('Post not found', 404);
        }
        
        // Check if user owns the thread
        $threadModel = new Thread();
        $thread = $threadModel->find($post['thread_id']);
        
        if (!$thread || $thread['user_id'] != $this->getCurrentUser()['id']) {
            $this->error('Only thread owner can mark solution', 403);
        }
        
        $result = $postModel->markAsSolution($id);
        
        if ($result) {
            $this->success('Post marked as solution');
        } else {
            $this->error('Failed to mark solution', 500);
        }
    }
}