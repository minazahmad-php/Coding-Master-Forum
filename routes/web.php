<?php

// Home routes
$router->get('/', 'HomeController@index');
$router->get('/forums', 'HomeController@forums');
$router->get('/forum/{id}', 'HomeController@forum');
$router->get('/thread/{id}', 'HomeController@thread');
$router->get('/search', 'HomeController@search');
$router->get('/advanced-search', 'HomeController@advancedSearch');
$router->get('/members', 'HomeController@members');
$router->get('/online-users', 'HomeController@onlineUsers');
$router->get('/statistics', 'HomeController@statistics');
$router->get('/rules', 'HomeController@rules');
$router->get('/contact', 'HomeController@contact');
$router->post('/contact', 'HomeController@contact');

// Authentication routes
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@handleLogin');
$router->get('/register', 'AuthController@register');
$router->post('/register', 'AuthController@handleRegister');
$router->get('/logout', 'AuthController@logout');
$router->get('/forgot-password', 'AuthController@forgotPassword');
$router->post('/forgot-password', 'AuthController@handleForgotPassword');
$router->get('/reset-password/{token}', 'AuthController@resetPassword');
$router->post('/reset-password', 'AuthController@handleResetPassword');

// User routes
$router->get('/profile/{id}', 'UserController@profile');
$router->get('/settings', 'UserController@settings', ['auth']);
$router->post('/settings', 'UserController@updateSettings', ['auth']);
$router->get('/change-password', 'UserController@changePassword', ['auth']);
$router->post('/change-password', 'UserController@updatePassword', ['auth']);
$router->get('/messages', 'MessageController@index', ['auth']);
$router->get('/messages/{id}', 'MessageController@conversation', ['auth']);
$router->get('/new-message', 'MessageController@newMessage', ['auth']);
$router->post('/new-message', 'MessageController@sendMessage', ['auth']);
$router->get('/notifications', 'NotificationController@index', ['auth']);
$router->get('/subscriptions', 'UserController@subscriptions', ['auth']);
$router->get('/reputation', 'UserController@reputation', ['auth']);
$router->get('/activity', 'UserController@activity', ['auth']);

// Thread routes
$router->get('/create-thread/{forum_id}', 'ThreadController@create', ['auth']);
$router->post('/create-thread', 'ThreadController@store', ['auth']);
$router->get('/edit-thread/{id}', 'ThreadController@edit', ['auth']);
$router->post('/edit-thread/{id}', 'ThreadController@update', ['auth']);
$router->post('/delete-thread/{id}', 'ThreadController@delete', ['auth']);
$router->post('/subscribe-thread/{id}', 'ThreadController@subscribe', ['auth']);
$router->post('/unsubscribe-thread/{id}', 'ThreadController@unsubscribe', ['auth']);
$router->post('/pin-thread/{id}', 'ThreadController@pin', ['moderator']);
$router->post('/unpin-thread/{id}', 'ThreadController@unpin', ['moderator']);
$router->post('/lock-thread/{id}', 'ThreadController@lock', ['moderator']);
$router->post('/unlock-thread/{id}', 'ThreadController@unlock', ['moderator']);

// Post routes
$router->post('/create-post', 'PostController@create', ['auth']);
$router->get('/edit-post/{id}', 'PostController@edit', ['auth']);
$router->post('/edit-post/{id}', 'PostController@update', ['auth']);
$router->post('/delete-post/{id}', 'PostController@delete', ['auth']);
$router->post('/react-post/{id}', 'PostController@react', ['auth']);
$router->post('/mark-solution/{id}', 'PostController@markSolution', ['auth']);

// Search routes
$router->get('/search/threads', 'SearchController@threads');
$router->get('/search/posts', 'SearchController@posts');
$router->get('/search/users', 'SearchController@users');

// Report routes
$router->post('/report', 'ReportController@create', ['auth']);
$router->get('/report/{id}', 'ReportController@view', ['moderator']);

// Language routes
$router->get('/language/{lang}', 'LanguageController@switch');

// API routes
$router->get('/api/threads/{id}/posts', 'Api\ThreadApiController@posts');
$router->post('/api/threads/{id}/subscribe', 'Api\ThreadApiController@subscribe', ['auth']);
$router->post('/api/posts/{id}/react', 'Api\PostApiController@react', ['auth']);
$router->get('/api/search', 'Api\SearchApiController@search');