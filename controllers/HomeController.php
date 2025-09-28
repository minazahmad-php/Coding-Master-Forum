<?php

//controllers/HomeController.php

class HomeController {
    private $forumModel;
    private $threadModel;
    private $postModel;
    private $userModel;
    
    public function __construct() {
        $this->forumModel = new Forum();
        $this->threadModel = new Thread();
        $this->postModel = new Post();
        $this->userModel = new User();
    }
    
    public function index() {
        $forums = $this->forumModel->findAll();
        $latestThreads = $this->threadModel->getLatestThreads(10);
        $latestPosts = $this->postModel->getLatestPosts(10);
        $topUsers = $this->userModel->getTopUsers(10);
        
        include VIEWS_PATH . '/home.php';
    }
    
    public function forum($slug) {
        $forum = $this->forumModel->findBySlug($slug);
        
        if (!$forum) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $threads = $this->threadModel->findByForum($forum['id'], $limit, $offset);
        $totalThreads = $this->threadModel->countByForum($forum['id']);
        $pagination = paginate($page, $totalThreads, $limit, "/forum/{$forum['slug']}?page={page}");
        
        include VIEWS_PATH . '/forum_list.php';
    }
    
    public function thread($id) {
        $thread = $this->threadModel->findById($id);
        
        if (!$thread) {
            include VIEWS_PATH . '/error.php';
            return;
        }
        
        // Increment view count
        $this->threadModel->incrementViewCount($id);
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $posts = $this->postModel->findByThread($id, $limit, $offset);
        $totalPosts = $this->postModel->countByThread($id);
        $pagination = paginate($page, $totalPosts, $limit, "/thread/{$id}?page={page}");
        
        include VIEWS_PATH . '/thread_view.php';
    }
    
    public function search() {
        $query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
        
        if (empty($query)) {
            header('Location: /');
            exit;
        }
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $threads = $this->threadModel->search($query, $limit, $offset);
        $users = $this->userModel->search($query, $limit, $offset);
        
        include VIEWS_PATH . '/search.php';
    }
}
?>