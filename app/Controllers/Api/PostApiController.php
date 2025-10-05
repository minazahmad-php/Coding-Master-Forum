<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\Post;

/**
 * API Post Controller
 */
class PostApiController extends BaseController
{
    /**
     * Get all posts
     */
    public function index()
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $threadId = (int)($_GET['thread_id'] ?? 0);
        
        $postModel = new Post();
        
        if ($threadId) {
            $posts = $postModel->getByThread($threadId, $page, $perPage);
        } else {
            $posts = $postModel->getRecent($perPage);
        }
        
        $this->success('Posts retrieved', ['posts' => $posts]);
    }

    /**
     * Get specific post
     */
    public function show($id)
    {
        $postModel = new Post();
        $post = $postModel->find($id);
        
        if (!$post) {
            $this->error('Post not found', 404);
        }
        
        $this->success('Post retrieved', ['post' => $post]);
    }

    /**
     * Create new post
     */
    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $threadId = (int)($_POST['thread_id'] ?? 0);
        $content = $this->sanitize($_POST['content'] ?? '');
        
        $errors = $this->validateRequired(['thread_id', 'content'], $_POST);
        
        if (empty($errors)) {
            $postModel = new Post();
            
            $postData = [
                'thread_id' => $threadId,
                'user_id' => $this->getCurrentUser()['id'],
                'content' => $content
            ];
            
            $postId = $postModel->create($postData);
            
            if ($postId) {
                $this->success('Post created successfully', ['post_id' => $postId]);
            } else {
                $this->error('Failed to create post', 500);
            }
        } else {
            $this->error(implode(', ', $errors), 400);
        }
    }

    /**
     * Update post
     */
    public function update($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $postModel = new Post();
        $post = $postModel->find($id);
        
        if (!$post) {
            $this->error('Post not found', 404);
        }
        
        // Check if user owns the post or is admin/moderator
        if ($post['user_id'] != $this->getCurrentUser()['id'] && !$this->isModerator()) {
            $this->error('Unauthorized', 403);
        }
        
        $content = $this->sanitize($_POST['content'] ?? '');
        
        if (empty($content)) {
            $this->error('Content is required', 400);
        }
        
        $result = $postModel->update($id, ['content' => $content]);
        
        if ($result) {
            $this->success('Post updated successfully');
        } else {
            $this->error('Failed to update post', 500);
        }
    }

    /**
     * Delete post
     */
    public function delete($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        
        $postModel = new Post();
        $post = $postModel->find($id);
        
        if (!$post) {
            $this->error('Post not found', 404);
        }
        
        // Check if user owns the post or is admin/moderator
        if ($post['user_id'] != $this->getCurrentUser()['id'] && !$this->isModerator()) {
            $this->error('Unauthorized', 403);
        }
        
        $result = $postModel->delete($id);
        
        if ($result) {
            $this->success('Post deleted successfully');
        } else {
            $this->error('Failed to delete post', 500);
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
            $this->success('Reaction added successfully');
        } else {
            $this->error('Failed to add reaction', 500);
        }
    }

    /**
     * Remove reaction from post
     */
    public function unreact($id)
    {
        $this->requireAuth();
        
        $type = $_POST['type'] ?? 'like';
        $postModel = new Post();
        
        $result = $postModel->removeReaction($id, $this->getCurrentUser()['id']);
        
        if ($result) {
            $this->success('Reaction removed successfully');
        } else {
            $this->error('Failed to remove reaction', 500);
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
        
        // Get thread to check ownership
        $thread = $this->db->fetch("SELECT user_id FROM threads WHERE id = ?", [$post['thread_id']]);
        
        if (!$thread || $thread['user_id'] != $this->getCurrentUser()['id']) {
            $this->error('Only thread owner can mark solution', 403);
        }
        
        $result = $postModel->markAsSolution($id);
        
        if ($result) {
            $this->success('Post marked as solution successfully');
        } else {
            $this->error('Failed to mark solution', 500);
        }
    }
}