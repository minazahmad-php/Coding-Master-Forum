<?php

//routes/api.php

$router = new Router();

// API routes
$router->add('/api/search', 'ApiController@search', 'GET');
$router->add('/api/notifications', 'ApiController@notifications', 'GET', true);
$router->add('/api/notifications/{id}/read', 'ApiController@markNotificationRead', 'POST', true);
$router->add('/api/notifications/unread-count', 'ApiController@unreadNotificationsCount', 'GET', true);
$router->add('/api/users/autocomplete', 'ApiController@userAutocomplete', 'GET');
$router->add('/api/posts/{id}/like', 'ApiController@likePost', 'POST', true);
$router->add('/api/threads/{id}/replies', 'ApiController@getThreadReplies', 'GET');
$router->add('/api/upload', 'ApiController@uploadFile', 'POST', true);
$router->add('/api/online-users', 'ApiController@getOnlineUsers', 'GET');
$router->add('/api/stats', 'ApiController@getForumStats', 'GET');
?>