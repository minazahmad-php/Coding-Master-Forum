<?php

// API Authentication
$router->post('/api/auth/login', 'Api\AuthApiController@login');
$router->post('/api/auth/register', 'Api\AuthApiController@register');
$router->post('/api/auth/logout', 'Api\AuthApiController@logout', ['auth']);
$router->get('/api/auth/user', 'Api\AuthApiController@user', ['auth']);

// API Forums
$router->get('/api/forums', 'Api\ForumApiController@index');
$router->get('/api/forums/{id}', 'Api\ForumApiController@show');
$router->get('/api/forums/{id}/threads', 'Api\ForumApiController@threads');

// API Threads
$router->get('/api/threads', 'Api\ThreadApiController@index');
$router->get('/api/threads/{id}', 'Api\ThreadApiController@show');
$router->post('/api/threads', 'Api\ThreadApiController@store', ['auth']);
$router->put('/api/threads/{id}', 'Api\ThreadApiController@update', ['auth']);
$router->delete('/api/threads/{id}', 'Api\ThreadApiController@delete', ['auth']);
$router->get('/api/threads/{id}/posts', 'Api\ThreadApiController@posts');
$router->post('/api/threads/{id}/subscribe', 'Api\ThreadApiController@subscribe', ['auth']);
$router->post('/api/threads/{id}/unsubscribe', 'Api\ThreadApiController@unsubscribe', ['auth']);

// API Posts
$router->get('/api/posts', 'Api\PostApiController@index');
$router->get('/api/posts/{id}', 'Api\PostApiController@show');
$router->post('/api/posts', 'Api\PostApiController@store', ['auth']);
$router->put('/api/posts/{id}', 'Api\PostApiController@update', ['auth']);
$router->delete('/api/posts/{id}', 'Api\PostApiController@delete', ['auth']);
$router->post('/api/posts/{id}/react', 'Api\PostApiController@react', ['auth']);
$router->post('/api/posts/{id}/unreact', 'Api\PostApiController@unreact', ['auth']);

// API Users
$router->get('/api/users', 'Api\UserApiController@index');
$router->get('/api/users/{id}', 'Api\UserApiController@show');
$router->put('/api/users/{id}', 'Api\UserApiController@update', ['auth']);
$router->get('/api/users/{id}/posts', 'Api\UserApiController@posts');
$router->get('/api/users/{id}/threads', 'Api\UserApiController@threads');

// API Search
$router->get('/api/search', 'Api\SearchApiController@search');
$router->get('/api/search/threads', 'Api\SearchApiController@threads');
$router->get('/api/search/posts', 'Api\SearchApiController@posts');
$router->get('/api/search/users', 'Api\SearchApiController@users');

// API Notifications
$router->get('/api/notifications', 'Api\NotificationApiController@index', ['auth']);
$router->post('/api/notifications/{id}/read', 'Api\NotificationApiController@markAsRead', ['auth']);
$router->post('/api/notifications/read-all', 'Api\NotificationApiController@markAllAsRead', ['auth']);

// API Messages
$router->get('/api/messages', 'Api\MessageApiController@index', ['auth']);
$router->get('/api/messages/{id}', 'Api\MessageApiController@show', ['auth']);
$router->post('/api/messages', 'Api\MessageApiController@store', ['auth']);
$router->post('/api/messages/{id}/reply', 'Api\MessageApiController@reply', ['auth']);

// API Statistics
$router->get('/api/statistics', 'Api\StatisticsApiController@index');
$router->get('/api/statistics/forums', 'Api\StatisticsApiController@forums');
$router->get('/api/statistics/users', 'Api\StatisticsApiController@users');