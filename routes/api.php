<?php
declare(strict_types=1);

use Core\Router;

$router = new Router();

// API Routes
$router->group(['prefix' => '/api', 'middleware' => ['CorsMiddleware', 'ApiMiddleware']], function($router) {
    
    // API Version
    $router->get('/version', 'ApiController@version');
    $router->get('/status', 'ApiController@status');
    
    // Public API Routes
    $router->group(['prefix' => '/v1'], function($router) {
        
        // Public endpoints
        $router->get('/forums', 'ApiController@getForums');
        $router->get('/forum/{slug}', 'ApiController@getForum');
        $router->get('/forum/{slug}/threads', 'ApiController@getForumThreads');
        $router->get('/thread/{id}', 'ApiController@getThread');
        $router->get('/thread/{id}/posts', 'ApiController@getThreadPosts');
        $router->get('/user/{username}', 'ApiController@getUser');
        $router->get('/search', 'ApiController@search');
        $router->get('/categories', 'ApiController@getCategories');
        $router->get('/stats', 'ApiController@getStats');
        
        // Authentication endpoints
        $router->post('/auth/login', 'ApiController@login');
        $router->post('/auth/register', 'ApiController@register');
        $router->post('/auth/logout', 'ApiController@logout');
        $router->post('/auth/refresh', 'ApiController@refreshToken');
        $router->post('/auth/forgot-password', 'ApiController@forgotPassword');
        $router->post('/auth/reset-password', 'ApiController@resetPassword');
        
        // Authenticated API Routes
        $router->group(['middleware' => ['ApiAuthMiddleware']], function($router) {
            
            // User profile
            $router->get('/user/profile', 'ApiController@getProfile');
            $router->put('/user/profile', 'ApiController@updateProfile');
            $router->get('/user/settings', 'ApiController@getSettings');
            $router->put('/user/settings', 'ApiController@updateSettings');
            
            // Thread operations
            $router->post('/thread', 'ApiController@createThread');
            $router->put('/thread/{id}', 'ApiController@updateThread');
            $router->delete('/thread/{id}', 'ApiController@deleteThread');
            
            // Post operations
            $router->post('/thread/{id}/post', 'ApiController@createPost');
            $router->put('/post/{id}', 'ApiController@updatePost');
            $router->delete('/post/{id}', 'ApiController@deletePost');
            $router->post('/post/{id}/like', 'ApiController@likePost');
            $router->post('/post/{id}/unlike', 'ApiController@unlikePost');
            
            // Notifications
            $router->get('/notifications', 'ApiController@getNotifications');
            $router->post('/notifications/mark-read', 'ApiController@markNotificationRead');
            
            // Messages
            $router->get('/messages', 'ApiController@getMessages');
            $router->post('/messages', 'ApiController@sendMessage');
            
        });
        
        // Admin API Routes
        $router->group(['middleware' => ['ApiAuthMiddleware', 'ApiAdminMiddleware']], function($router) {
            
            // Admin dashboard
            $router->get('/admin/dashboard', 'ApiController@getAdminDashboard');
            $router->get('/admin/stats', 'ApiController@getAdminStats');
            
            // User management
            $router->get('/admin/users', 'ApiController@getAdminUsers');
            $router->put('/admin/users/{id}', 'ApiController@updateAdminUser');
            $router->delete('/admin/users/{id}', 'ApiController@deleteUser');
            
            // Analytics
            $router->get('/admin/analytics/user', 'ApiController@getUserAnalytics');
            $router->get('/admin/analytics/content', 'ApiController@getContentAnalytics');
            $router->get('/admin/analytics/traffic', 'ApiController@getTrafficAnalytics');
            
        });
        
    });
    
});

// Run the router
$router->run();