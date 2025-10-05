<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\Forum;

/**
 * API Forum Controller
 */
class ForumApiController extends BaseController
{
    /**
     * Get all forums
     */
    public function index()
    {
        $forumModel = new Forum();
        $forums = $forumModel->getAll();
        
        $this->success('Forums retrieved', ['forums' => $forums]);
    }

    /**
     * Get specific forum
     */
    public function show($id)
    {
        $forumModel = new Forum();
        $forum = $forumModel->find($id);
        
        if (!$forum) {
            $this->error('Forum not found', 404);
        }
        
        $this->success('Forum retrieved', ['forum' => $forum]);
    }

    /**
     * Get forum threads
     */
    public function threads($id)
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        $forumModel = new Forum();
        $forum = $forumModel->getWithThreads($id, $page, $perPage);
        
        if (!$forum) {
            $this->error('Forum not found', 404);
        }
        
        $this->success('Threads retrieved', [
            'forum' => $forum,
            'threads' => $forum['threads'] ?? []
        ]);
    }
}