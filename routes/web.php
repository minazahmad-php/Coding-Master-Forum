<?php

//routes/web.php

$router = new Router();

// Public routes
$router->add('/', 'HomeController@index', 'GET');
$router->add('/forum/{slug}', 'HomeController@forum', 'GET');
$router->add('/thread/{id}', 'HomeController@thread', 'GET');
$router->add('/search', 'HomeController@search', 'GET');
$router->add('/login', 'AuthController@login', ['GET', 'POST']);
$router->add('/register', 'AuthController@register', ['GET', 'POST']);
$router->add('/logout', 'AuthController@logout', 'GET');
$router->add('/user/{username}', 'AuthController@profile', 'GET');

// Authenticated routes
$router->add('/forum/{slug}/create-thread', 'ForumController@createThread', ['GET', 'POST'], true);
$router->add('/thread/{id}/reply', 'ForumController@createPost', 'POST', true);
$router->add('/thread/{id}/edit', 'ForumController@editThread', ['GET', 'POST'], true);
$router->add('/post/{id}/edit', 'ForumController@editPost', ['GET', 'POST'], true);
$router->add('/thread/{id}/delete', 'ForumController@deleteThread', 'GET', true);
$router->add('/post/{id}/delete', 'ForumController@deletePost', 'GET', true);
$router->add('/thread/{id}/toggle-lock', 'ForumController@toggleThreadLock', 'GET', true);
$router->add('/thread/{id}/toggle-pin', 'ForumController@toggleThreadPin', 'GET', true);

// User routes
$router->add('/user/dashboard', 'UserController@dashboard', 'GET', true);
$router->add('/user/profile', 'UserController@profile', 'GET', true);
$router->add('/user/profile/update', 'UserController@updateProfile', 'POST', true);
$router->add('/user/settings', 'UserController@settings', 'GET', true);
$router->add('/user/settings/update', 'UserController@updateSettings', 'POST', true);

// Message routes
$router->add('/messages', 'MessageController@index', 'GET', true);
$router->add('/messages/conversation/{id}', 'MessageController@conversation', 'GET', true);
$router->add('/messages/send', 'MessageController@send', 'POST', true);
$router->add('/messages/delete/{id}', 'MessageController@delete', 'GET', true);

// Run the router
$router->run();
?>