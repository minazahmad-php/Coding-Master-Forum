<?php

namespace App\Controllers;

use App\Models\Thread;
use App\Models\Post;
use App\Models\User;

/**
 * Search Controller
 * Handles search functionality
 */
class SearchController extends BaseController
{
    /**
     * Search threads
     */
    public function threads()
    {
        $query = $_GET['q'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        
        if (empty($query)) {
            $this->json(['results' => [], 'total' => 0]);
        }
        
        $threadModel = new Thread();
        $results = $threadModel->search($query, $page, 20);
        
        $this->json(['results' => $results, 'total' => count($results)]);
    }

    /**
     * Search posts
     */
    public function posts()
    {
        $query = $_GET['q'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        
        if (empty($query)) {
            $this->json(['results' => [], 'total' => 0]);
        }
        
        $postModel = new Post();
        $results = $postModel->search($query, $page, 20);
        
        $this->json(['results' => $results, 'total' => count($results)]);
    }

    /**
     * Search users
     */
    public function users()
    {
        $query = $_GET['q'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        
        if (empty($query)) {
            $this->json(['results' => [], 'total' => 0]);
        }
        
        $userModel = new User();
        $results = $userModel->search($query, $page, 20);
        
        $this->json(['results' => $results, 'total' => count($results)]);
    }
}