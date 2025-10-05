<?php

// Admin dashboard
$router->get('/admin', 'AdminController@dashboard', ['admin']);
$router->get('/admin/dashboard', 'AdminController@dashboard', ['admin']);

// User management
$router->get('/admin/users', 'AdminController@users', ['admin']);
$router->get('/admin/users/{id}', 'AdminController@editUser', ['admin']);
$router->post('/admin/users/{id}', 'AdminController@updateUser', ['admin']);
$router->post('/admin/users/{id}/ban', 'AdminController@banUser', ['admin']);
$router->post('/admin/users/{id}/unban', 'AdminController@unbanUser', ['admin']);
$router->post('/admin/users/{id}/delete', 'AdminController@deleteUser', ['admin']);

// Forum management
$router->get('/admin/forums', 'AdminController@forums', ['admin']);
$router->get('/admin/forums/create', 'AdminController@createForum', ['admin']);
$router->post('/admin/forums/create', 'AdminController@storeForum', ['admin']);
$router->get('/admin/forums/{id}/edit', 'AdminController@editForum', ['admin']);
$router->post('/admin/forums/{id}/edit', 'AdminController@updateForum', ['admin']);
$router->post('/admin/forums/{id}/delete', 'AdminController@deleteForum', ['admin']);

// Category management
$router->get('/admin/categories', 'AdminController@categories', ['admin']);
$router->get('/admin/categories/create', 'AdminController@createCategory', ['admin']);
$router->post('/admin/categories/create', 'AdminController@storeCategory', ['admin']);
$router->get('/admin/categories/{id}/edit', 'AdminController@editCategory', ['admin']);
$router->post('/admin/categories/{id}/edit', 'AdminController@updateCategory', ['admin']);
$router->post('/admin/categories/{id}/delete', 'AdminController@deleteCategory', ['admin']);

// Content management
$router->get('/admin/posts', 'AdminController@posts', ['admin']);
$router->get('/admin/threads', 'AdminController@threads', ['admin']);
$router->post('/admin/posts/{id}/delete', 'AdminController@deletePost', ['admin']);
$router->post('/admin/threads/{id}/delete', 'AdminController@deleteThread', ['admin']);

// Noticeboard management
$router->get('/admin/noticeboard', 'AdminController@noticeboard', ['admin']);
$router->get('/admin/noticeboard/create', 'AdminController@createNotice', ['admin']);
$router->post('/admin/noticeboard/create', 'AdminController@storeNotice', ['admin']);
$router->get('/admin/noticeboard/{id}/edit', 'AdminController@editNotice', ['admin']);
$router->post('/admin/noticeboard/{id}/edit', 'AdminController@updateNotice', ['admin']);
$router->post('/admin/noticeboard/{id}/delete', 'AdminController@deleteNotice', ['admin']);

// Settings
$router->get('/admin/settings', 'AdminController@settings', ['admin']);
$router->post('/admin/settings', 'AdminController@updateSettings', ['admin']);
$router->get('/admin/settings/security', 'AdminController@securitySettings', ['admin']);
$router->post('/admin/settings/security', 'AdminController@updateSecuritySettings', ['admin']);
$router->get('/admin/settings/mail', 'AdminController@mailSettings', ['admin']);
$router->post('/admin/settings/mail', 'AdminController@updateMailSettings', ['admin']);
$router->get('/admin/settings/social', 'AdminController@socialSettings', ['admin']);
$router->post('/admin/settings/social', 'AdminController@updateSocialSettings', ['admin']);

// Moderation
$router->get('/admin/moderation', 'AdminController@moderation', ['moderator']);
$router->get('/admin/reports', 'AdminController@reports', ['moderator']);
$router->get('/admin/reports/{id}', 'AdminController@reviewReport', ['moderator']);
$router->post('/admin/reports/{id}/resolve', 'AdminController@resolveReport', ['moderator']);

// Roles and permissions
$router->get('/admin/roles', 'AdminController@roles', ['admin']);
$router->get('/admin/roles/create', 'AdminController@createRole', ['admin']);
$router->post('/admin/roles/create', 'AdminController@storeRole', ['admin']);
$router->get('/admin/roles/{id}/edit', 'AdminController@editRole', ['admin']);
$router->post('/admin/roles/{id}/edit', 'AdminController@updateRole', ['admin']);
$router->post('/admin/roles/{id}/delete', 'AdminController@deleteRole', ['admin']);

// Plugins
$router->get('/admin/plugins', 'AdminController@plugins', ['admin']);
$router->post('/admin/plugins/{plugin}/activate', 'AdminController@activatePlugin', ['admin']);
$router->post('/admin/plugins/{plugin}/deactivate', 'AdminController@deactivatePlugin', ['admin']);

// Themes
$router->get('/admin/themes', 'AdminController@themes', ['admin']);
$router->post('/admin/themes/{theme}/activate', 'AdminController@activateTheme', ['admin']);

// Backup
$router->get('/admin/backup', 'AdminController@backup', ['admin']);
$router->post('/admin/backup/create', 'AdminController@createBackup', ['admin']);
$router->get('/admin/backup/download/{file}', 'AdminController@downloadBackup', ['admin']);

// Logs
$router->get('/admin/logs', 'AdminController@logs', ['admin']);
$router->get('/admin/logs/error', 'AdminController@errorLogs', ['admin']);
$router->post('/admin/logs/clear', 'AdminController@clearLogs', ['admin']);

// Statistics
$router->get('/admin/statistics', 'AdminController@statistics', ['admin']);

// Maintenance
$router->get('/admin/maintenance', 'AdminController@maintenance', ['admin']);
$router->post('/admin/maintenance/toggle', 'AdminController@toggleMaintenance', ['admin']);

// Email templates
$router->get('/admin/email-templates', 'AdminController@emailTemplates', ['admin']);
$router->get('/admin/email-templates/{template}/edit', 'AdminController@editEmailTemplate', ['admin']);
$router->post('/admin/email-templates/{template}/edit', 'AdminController@updateEmailTemplate', ['admin']);