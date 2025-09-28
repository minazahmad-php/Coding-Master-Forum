<?php
declare(strict_types=1);

use Core\Router;

$router = new Router();

// Public routes
$router->get('/', 'HomeController@index');
$router->get('/forum/{slug}', 'HomeController@forum');
$router->get('/thread/{id}', 'HomeController@thread');
$router->get('/search', 'HomeController@search');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');
$router->get('/user/{username}', 'AuthController@profile');

// Password reset routes
$router->get('/forgot-password', 'AuthController@showForgotPassword');
$router->post('/forgot-password', 'AuthController@forgotPassword');
$router->get('/reset-password/{token}', 'AuthController@showResetPassword');
$router->post('/reset-password', 'AuthController@resetPassword');

// Authenticated routes
$router->group(['auth' => true], function($router) {
    // Forum routes
    $router->get('/forum/{slug}/create-thread', 'ForumController@showCreateThread');
    $router->post('/forum/{slug}/create-thread', 'ForumController@createThread');
    $router->post('/thread/{id}/reply', 'ForumController@createPost');
    $router->get('/thread/{id}/edit', 'ForumController@showEditThread');
    $router->post('/thread/{id}/edit', 'ForumController@editThread');
    $router->get('/post/{id}/edit', 'ForumController@showEditPost');
    $router->post('/post/{id}/edit', 'ForumController@editPost');
    $router->delete('/thread/{id}', 'ForumController@deleteThread');
    $router->delete('/post/{id}', 'ForumController@deletePost');
    $router->post('/thread/{id}/toggle-lock', 'ForumController@toggleThreadLock');
    $router->post('/thread/{id}/toggle-pin', 'ForumController@toggleThreadPin');
    
    // User routes
    $router->get('/user/dashboard', 'UserController@dashboard');
    $router->get('/user/profile', 'UserController@profile');
    $router->post('/user/profile/update', 'UserController@updateProfile');
    $router->get('/user/settings', 'UserController@settings');
    $router->post('/user/settings/update', 'UserController@updateSettings');
    $router->post('/user/change-password', 'UserController@changePassword');
    $router->post('/user/upload-avatar', 'UserController@uploadAvatar');
    
    // Message routes
    $router->get('/messages', 'MessageController@index');
    $router->get('/messages/conversation/{id}', 'MessageController@conversation');
    $router->post('/messages/send', 'MessageController@send');
    $router->delete('/messages/{id}', 'MessageController@delete');
    
    // Notification routes
    $router->get('/notifications', 'UserController@notifications');
    $router->post('/notifications/mark-read', 'UserController@markNotificationsRead');
    
    // Like/Unlike routes
    $router->post('/post/{id}/like', 'ForumController@likePost');
    $router->delete('/post/{id}/like', 'ForumController@unlikePost');
    
    // Follow/Unfollow routes
    $router->post('/user/{id}/follow', 'UserController@follow');
    $router->delete('/user/{id}/follow', 'UserController@unfollow');
});

// Admin routes
$router->group(['auth' => true, 'admin' => true], function($router) {
    $router->get('/admin', 'AdminController@dashboard');
    $router->get('/admin/dashboard', 'AdminController@dashboard');
    
    // User management
    $router->get('/admin/users', 'AdminController@users');
    $router->get('/admin/users/{id}/edit', 'AdminController@showEditUser');
    $router->post('/admin/users/{id}/edit', 'AdminController@editUser');
    $router->post('/admin/users/{id}/ban', 'AdminController@banUser');
    $router->post('/admin/users/{id}/unban', 'AdminController@unbanUser');
    $router->delete('/admin/users/{id}', 'AdminController@deleteUser');
    
    // Forum management
    $router->get('/admin/forums', 'AdminController@forums');
    $router->get('/admin/forums/create', 'AdminController@showCreateForum');
    $router->post('/admin/forums/create', 'AdminController@createForum');
    $router->get('/admin/forums/{id}/edit', 'AdminController@showEditForum');
    $router->post('/admin/forums/{id}/edit', 'AdminController@editForum');
    $router->delete('/admin/forums/{id}', 'AdminController@deleteForum');
    
    // Thread management
    $router->get('/admin/threads', 'AdminController@threads');
    $router->post('/admin/threads/{id}/pin', 'AdminController@pinThread');
    $router->post('/admin/threads/{id}/unpin', 'AdminController@unpinThread');
    $router->post('/admin/threads/{id}/lock', 'AdminController@lockThread');
    $router->post('/admin/threads/{id}/unlock', 'AdminController@unlockThread');
    $router->delete('/admin/threads/{id}', 'AdminController@deleteThread');
    
    // Post management
    $router->get('/admin/posts', 'AdminController@posts');
    $router->delete('/admin/posts/{id}', 'AdminController@deletePost');
    
    // Settings
    $router->get('/admin/settings', 'AdminController@settings');
    $router->post('/admin/settings', 'AdminController@updateSettings');
    
    // Reports
    $router->get('/admin/reports', 'AdminController@reports');
    $router->post('/admin/reports/{id}/resolve', 'AdminController@resolveReport');
    
    // Statistics
    $router->get('/admin/stats', 'AdminController@stats');
});

// API routes
$router->group(['prefix' => '/api'], function($router) {
    // Public API
    $router->get('/forums', 'ApiController@getForums');
    $router->get('/forum/{slug}/threads', 'ApiController@getForumThreads');
    $router->get('/thread/{id}/posts', 'ApiController@getThreadPosts');
    $router->get('/user/{username}', 'ApiController@getUser');
    
    // Authenticated API
    $router->group(['auth' => true], function($router) {
        $router->post('/thread/{id}/reply', 'ApiController@createPost');
        $router->post('/post/{id}/like', 'ApiController@likePost');
        $router->delete('/post/{id}/like', 'ApiController@unlikePost');
        $router->post('/user/{id}/follow', 'ApiController@followUser');
        $router->delete('/user/{id}/follow', 'ApiController@unfollowUser');
    });
});

// Run the router
$router->run();
?>