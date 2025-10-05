<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\Thread;
use App\Models\Post;
use App\Models\User;

/**
 * API Search Controller
 */
class SearchApiController extends BaseController
{
    /**
     * Search all content
     */
    public function search()
    {
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'all';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        if (empty($query)) {
            $this->error('Search query is required', 400);
        }
        
        $results = [];
        $total = 0;
        
        if ($type === 'all' || $type === 'threads') {
            $threadModel = new Thread();
            $threadResults = $threadModel->search($query, $page, $perPage);
            $results['threads'] = $threadResults;
            $total += count($threadResults);
        }
        
        if ($type === 'all' || $type === 'posts') {
            $postModel = new Post();
            $postResults = $postModel->search($query, $page, $perPage);
            $results['posts'] = $postResults;
            $total += count($postResults);
        }
        
        if ($type === 'all' || $type === 'users') {
            $userModel = new User();
            $userResults = $userModel->search($query, $page, $perPage);
            // Remove sensitive data
            foreach ($userResults as &$user) {
                unset($user['password']);
            }
            $results['users'] = $userResults;
            $total += count($userResults);
        }
        
        $this->success('Search completed', [
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'total' => $total
        ]);
    }

    /**
     * Search threads only
     */
    public function threads()
    {
        $query = $_GET['q'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        if (empty($query)) {
            $this->error('Search query is required', 400);
        }
        
        $threadModel = new Thread();
        $results = $threadModel->search($query, $page, $perPage);
        
        $this->success('Thread search completed', [
            'query' => $query,
            'results' => $results,
            'total' => count($results)
        ]);
    }

    /**
     * Search posts only
     */
    public function posts()
    {
        $query = $_GET['q'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        if (empty($query)) {
            $this->error('Search query is required', 400);
        }
        
        $postModel = new Post();
        $results = $postModel->search($query, $page, $perPage);
        
        $this->success('Post search completed', [
            'query' => $query,
            'results' => $results,
            'total' => count($results)
        ]);
    }

    /**
     * Search users only
     */
    public function users()
    {
        $query = $_GET['q'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        if (empty($query)) {
            $this->error('Search query is required', 400);
        }
        
        $userModel = new User();
        $results = $userModel->search($query, $page, $perPage);
        
        // Remove sensitive data
        foreach ($results as &$user) {
            unset($user['password']);
        }
        
        $this->success('User search completed', [
            'query' => $query,
            'results' => $results,
            'total' => count($results)
        ]);
    }
}