<?php

namespace App\Controllers;

use App\Models\Forum;
use App\Models\Thread;

/**
 * Forum Controller
 * Handles forum-related operations
 */
class ForumController extends BaseController
{
    /**
     * Show forum list
     */
    public function index()
    {
        $forumModel = new Forum();
        $forums = $forumModel->getAll();
        
        $data = [
            'title' => 'Forums',
            'forums' => $forums
        ];
        
        echo $this->view->render('forum_list', $data);
    }

    /**
     * Show specific forum
     */
    public function show($id)
    {
        $forumModel = new Forum();
        $forum = $forumModel->getWithThreads($id);
        
        if (!$forum) {
            $this->view->error(404, 'Forum not found');
        }
        
        $data = [
            'title' => $forum['name'],
            'forum' => $forum
        ];
        
        echo $this->view->render('forum_view', $data);
    }
}