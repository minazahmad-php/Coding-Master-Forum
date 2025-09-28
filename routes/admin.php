<?php

//routes/admin.php

$router = new Router();

// Admin routes
$router->add('/admin', 'AdminController@dashboard', 'GET', true, true);
$router->add('/admin/users', 'AdminController@users', 'GET', true, true);
$router->add('/admin/users/edit/{id}', 'AdminController@editUser', ['GET', 'POST'], true, true);
$router->add('/admin/users/delete/{id}', 'AdminController@deleteUser', 'GET', true, true);
$router->add('/admin/forums', 'AdminController@forums', 'GET', true, true);
$router->add('/admin/forums/create', 'AdminController@createForum', ['GET', 'POST'], true, true);
$router->add('/admin/forums/edit/{id}', 'AdminController@editForum', ['GET', 'POST'], true, true);
$router->add('/admin/forums/delete/{id}', 'AdminController@deleteForum', 'GET', true, true);
$router->add('/admin/threads', 'AdminController@threads', 'GET', true, true);
$router->add('/admin/threads/delete/{id}', 'AdminController@deleteThread', 'GET', true, true);
$router->add('/admin/posts', 'AdminController@posts', 'GET', true, true);
$router->add('/admin/posts/delete/{id}', 'AdminController@deletePost', 'GET', true, true);
$router->add('/admin/settings', 'AdminController@settings', ['GET', 'POST'], true, true);
?>