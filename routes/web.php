<?php
declare(strict_types=1);

use Core\Router;

$router = new Router();

// Apply global middleware
$router->group(['middleware' => ['SecurityHeadersMiddleware', 'LoggingMiddleware']], function($router) {
    
    // Public routes
    $router->get('/', 'HomeController@index');
    $router->get('/about', 'HomeController@about');
    $router->get('/contact', 'HomeController@contact');
    $router->post('/contact', 'HomeController@sendContact');
    $router->get('/privacy', 'HomeController@privacy');
    $router->get('/terms', 'HomeController@terms');
    $router->get('/help', 'HomeController@help');
    $router->get('/faq', 'HomeController@faq');
    
    // Forum routes
    $router->get('/forums', 'ForumController@index');
    $router->get('/forum/{slug}', 'ForumController@show');
    $router->get('/forum/{slug}/create-thread', 'ForumController@showCreateThread');
    $router->post('/forum/{slug}/create-thread', 'ForumController@createThread');
    
    // Thread routes
    $router->get('/thread/{id}', 'ThreadController@show');
    $router->get('/thread/{id}/edit', 'ThreadController@showEdit');
    $router->post('/thread/{id}/edit', 'ThreadController@edit');
    $router->delete('/thread/{id}', 'ThreadController@delete');
    $router->post('/thread/{id}/pin', 'ThreadController@pin');
    $router->post('/thread/{id}/unpin', 'ThreadController@unpin');
    $router->post('/thread/{id}/lock', 'ThreadController@lock');
    $router->post('/thread/{id}/unlock', 'ThreadController@unlock');
    $router->post('/thread/{id}/subscribe', 'ThreadController@subscribe');
    $router->post('/thread/{id}/unsubscribe', 'ThreadController@unsubscribe');
    
    // Post routes
    $router->post('/thread/{id}/reply', 'PostController@create');
    $router->get('/post/{id}/edit', 'PostController@showEdit');
    $router->post('/post/{id}/edit', 'PostController@edit');
    $router->delete('/post/{id}', 'PostController@delete');
    $router->post('/post/{id}/like', 'PostController@like');
    $router->post('/post/{id}/unlike', 'PostController@unlike');
    $router->post('/post/{id}/report', 'PostController@report');
    $router->post('/post/{id}/quote', 'PostController@quote');
    
    // Search routes
    $router->get('/search', 'SearchController@index');
    $router->get('/search/advanced', 'SearchController@advanced');
    $router->post('/search', 'SearchController@search');
    $router->get('/search/suggestions', 'SearchController@suggestions');
    $router->post('/search/track-click', 'SearchController@trackClick');
    $router->post('/search/track-duration', 'SearchController@trackDuration');

    // Payment routes (public)
    $router->get('/payments/plans', 'PaymentController@plans');
    $router->post('/payments/webhook', 'PaymentController@webhook');
    
    // User routes
    $router->get('/users', 'UserController@index');
    $router->get('/user/{username}', 'UserController@profile');
    $router->get('/user/{username}/posts', 'UserController@posts');
    $router->get('/user/{username}/threads', 'UserController@threads');
    $router->get('/user/{username}/followers', 'UserController@followers');
    $router->get('/user/{username}/following', 'UserController@following');
    
    // Authentication routes
    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@login');
    $router->get('/register', 'AuthController@showRegister');
    $router->post('/register', 'AuthController@register');
    $router->get('/logout', 'AuthController@logout');
    
    // Social login routes
    $router->get('/auth/google', 'AuthController@googleLogin');
    $router->get('/auth/google/callback', 'AuthController@googleCallback');
    $router->get('/auth/facebook', 'AuthController@facebookLogin');
    $router->get('/auth/facebook/callback', 'AuthController@facebookCallback');
    $router->get('/auth/twitter', 'AuthController@twitterLogin');
    $router->get('/auth/twitter/callback', 'AuthController@twitterCallback');
    
    // Password reset routes
    $router->get('/forgot-password', 'AuthController@showForgotPassword');
    $router->post('/forgot-password', 'AuthController@forgotPassword');
    $router->get('/reset-password/{token}', 'AuthController@showResetPassword');
    $router->post('/reset-password', 'AuthController@resetPassword');
    
    // Email verification
    $router->get('/verify-email/{token}', 'AuthController@verifyEmail');
    $router->post('/resend-verification', 'AuthController@resendVerification');
    
    // Authenticated routes
    $router->group(['auth' => true], function($router) {
        
        // User dashboard and profile
        $router->get('/dashboard', 'UserController@dashboard');
        $router->get('/profile', 'UserController@profile');
        $router->post('/profile/update', 'UserController@updateProfile');
        $router->post('/profile/upload-avatar', 'UserController@uploadAvatar');
        $router->post('/profile/upload-cover', 'UserController@uploadCover');
        
        // User settings
        $router->get('/settings', 'UserController@settings');
        $router->post('/settings/update', 'UserController@updateSettings');
        $router->post('/settings/change-password', 'UserController@changePassword');
        $router->post('/settings/enable-2fa', 'UserController@enable2FA');
        $router->post('/settings/disable-2fa', 'UserController@disable2FA');

        // Payment routes (authenticated)
        $router->get('/payments/dashboard', 'PaymentController@dashboard');
        $router->get('/payments/history', 'PaymentController@paymentHistory');
        $router->post('/payments/subscribe', 'PaymentController@subscribe');
        $router->post('/payments/process', 'PaymentController@processPayment');
        $router->post('/payments/cancel-subscription', 'PaymentController@cancelSubscription');
        $router->post('/payments/update-payment-method', 'PaymentController@updatePaymentMethod');
        $router->get('/payments/invoice/{id}', 'PaymentController@downloadInvoice');
        
        // Following system
        $router->post('/user/{id}/follow', 'UserController@follow');
        $router->post('/user/{id}/unfollow', 'UserController@unfollow');
        $router->get('/following', 'UserController@following');
        $router->get('/followers', 'UserController@followers');
        
        // Private messaging
        $router->get('/messages', 'MessageController@index');
        $router->get('/messages/compose', 'MessageController@compose');
        $router->post('/messages/send', 'MessageController@send');
        $router->get('/messages/conversation/{id}', 'MessageController@conversation');
        $router->post('/messages/conversation/{id}/reply', 'MessageController@reply');
        $router->delete('/messages/{id}', 'MessageController@delete');
        $router->post('/messages/{id}/mark-read', 'MessageController@markRead');
        
        // Notifications
        $router->get('/notifications', 'NotificationController@index');
        $router->post('/notifications/mark-read', 'NotificationController@markRead');
        $router->post('/notifications/mark-all-read', 'NotificationController@markAllRead');
        $router->delete('/notifications/{id}', 'NotificationController@delete');
        
        // Bookmarks
        $router->get('/bookmarks', 'BookmarkController@index');
        $router->post('/thread/{id}/bookmark', 'BookmarkController@add');
        $router->post('/thread/{id}/unbookmark', 'BookmarkController@remove');
        
        // Reports
        $router->post('/report/thread/{id}', 'ReportController@reportThread');
        $router->post('/report/post/{id}', 'ReportController@reportPost');
        $router->post('/report/user/{id}', 'ReportController@reportUser');
        
        // Premium features
        $router->get('/premium', 'PremiumController@index');
        $router->post('/premium/subscribe', 'PremiumController@subscribe');
        $router->post('/premium/cancel', 'PremiumController@cancel');
        
    });
    
    // Moderator routes
    $router->group(['auth' => true, 'moderator' => true], function($router) {
        
        $router->get('/moderator', 'ModeratorController@dashboard');
        $router->get('/moderator/reports', 'ModeratorController@reports');
        $router->post('/moderator/reports/{id}/resolve', 'ModeratorController@resolveReport');
        $router->post('/moderator/reports/{id}/dismiss', 'ModeratorController@dismissReport');
        
        $router->post('/moderator/thread/{id}/pin', 'ModeratorController@pinThread');
        $router->post('/moderator/thread/{id}/unpin', 'ModeratorController@unpinThread');
        $router->post('/moderator/thread/{id}/lock', 'ModeratorController@lockThread');
        $router->post('/moderator/thread/{id}/unlock', 'ModeratorController@unlockThread');
        $router->post('/moderator/thread/{id}/move', 'ModeratorController@moveThread');
        
        $router->post('/moderator/post/{id}/hide', 'ModeratorController@hidePost');
        $router->post('/moderator/post/{id}/unhide', 'ModeratorController@unhidePost');
        $router->post('/moderator/post/{id}/edit', 'ModeratorController@editPost');
        
        $router->post('/moderator/user/{id}/warn', 'ModeratorController@warnUser');
        $router->post('/moderator/user/{id}/suspend', 'ModeratorController@suspendUser');
        
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
        $router->post('/admin/users/{id}/promote', 'AdminController@promoteUser');
        $router->post('/admin/users/{id}/demote', 'AdminController@demoteUser');
        $router->delete('/admin/users/{id}', 'AdminController@deleteUser');
        
        // Forum management
        $router->get('/admin/forums', 'AdminController@forums');
        $router->get('/admin/forums/create', 'AdminController@showCreateForum');
        $router->post('/admin/forums/create', 'AdminController@createForum');
        $router->get('/admin/forums/{id}/edit', 'AdminController@showEditForum');
        $router->post('/admin/forums/{id}/edit', 'AdminController@editForum');
        $router->delete('/admin/forums/{id}', 'AdminController@deleteForum');
        $router->post('/admin/forums/{id}/reorder', 'AdminController@reorderForum');
        
        // Thread management
        $router->get('/admin/threads', 'AdminController@threads');
        $router->delete('/admin/threads/{id}', 'AdminController@deleteThread');
        $router->post('/admin/threads/{id}/restore', 'AdminController@restoreThread');
        
        // Post management
        $router->get('/admin/posts', 'AdminController@posts');
        $router->delete('/admin/posts/{id}', 'AdminController@deletePost');
        $router->post('/admin/posts/{id}/restore', 'AdminController@restorePost');
        
        // Settings
        $router->get('/admin/settings', 'AdminController@settings');
        $router->post('/admin/settings', 'AdminController@updateSettings');
        $router->get('/admin/settings/email', 'AdminController@emailSettings');
        $router->post('/admin/settings/email', 'AdminController@updateEmailSettings');
        $router->get('/admin/settings/social', 'AdminController@socialSettings');
        $router->post('/admin/settings/social', 'AdminController@updateSocialSettings');
        
        // Reports
        $router->get('/admin/reports', 'AdminController@reports');
        $router->post('/admin/reports/{id}/resolve', 'AdminController@resolveReport');
        $router->post('/admin/reports/{id}/dismiss', 'AdminController@dismissReport');
        
        // Statistics
        $router->get('/admin/stats', 'AdminController@stats');
        $router->get('/admin/stats/users', 'AdminController@userStats');
        $router->get('/admin/stats/posts', 'AdminController@postStats');
        $router->get('/admin/stats/forums', 'AdminController@forumStats');
        
        // Analytics Dashboard
        $router->get('/admin/analytics', 'AnalyticsController@dashboard');
        $router->get('/admin/analytics/user', 'AnalyticsController@userAnalytics');
        $router->get('/admin/analytics/content', 'AnalyticsController@contentAnalytics');
        $router->get('/admin/analytics/traffic', 'AnalyticsController@trafficAnalytics');
        $router->get('/admin/analytics/engagement', 'AnalyticsController@engagementAnalytics');
        $router->get('/admin/analytics/conversion', 'AnalyticsController@conversionAnalytics');
        $router->get('/admin/analytics/revenue', 'AnalyticsController@revenueAnalytics');
        $router->get('/admin/analytics/performance', 'AnalyticsController@performanceAnalytics');
        $router->get('/admin/analytics/export', 'AnalyticsController@exportAnalytics');
        
        // Search Analytics
        $router->get('/admin/search-analytics', 'SearchController@analytics');
        $router->get('/admin/search-analytics/export', 'SearchController@export');

        // Advanced analytics routes
        $router->get('/admin/analytics/advanced', 'AdvancedAnalyticsController@dashboard');
        $router->get('/admin/analytics/user', 'AdvancedAnalyticsController@userAnalytics');
        $router->get('/admin/analytics/content', 'AdvancedAnalyticsController@contentAnalytics');
        $router->get('/admin/analytics/traffic', 'AdvancedAnalyticsController@trafficAnalytics');
        $router->get('/admin/analytics/engagement', 'AdvancedAnalyticsController@engagementAnalytics');
        $router->get('/admin/analytics/revenue', 'AdvancedAnalyticsController@revenueAnalytics');
        $router->get('/admin/analytics/performance', 'AdvancedAnalyticsController@performanceAnalytics');
        $router->get('/admin/analytics/export/{type}', 'AdvancedAnalyticsController@exportAnalytics');

        // Admin payment management
        $router->get('/admin/payments', 'PaymentController@adminDashboard');
        $router->get('/admin/payments/list', 'PaymentController@adminPayments');
        $router->get('/admin/payments/subscriptions', 'PaymentController@adminSubscriptions');
        $router->post('/admin/payments/refund', 'PaymentController@adminRefundPayment');
        
        // Integrations Management
        $router->get('/admin/integrations', 'IntegrationController@dashboard');
        $router->get('/admin/integrations/social-media', 'IntegrationController@socialMedia');
        $router->get('/admin/integrations/payment-gateway', 'IntegrationController@paymentGateway');
        $router->get('/admin/integrations/email-service', 'IntegrationController@emailService');
        $router->get('/admin/integrations/sms-service', 'IntegrationController@smsService');
        $router->get('/admin/integrations/api-management', 'IntegrationController@apiManagement');
        $router->get('/admin/integrations/cloud-storage', 'IntegrationController@cloudStorage');
        $router->get('/admin/integrations/cdn-service', 'IntegrationController@cdnService');
        $router->get('/admin/integrations/monitoring-service', 'IntegrationController@monitoringService');
        $router->get('/admin/integrations/backup-service', 'IntegrationController@backupService');
        
        // Theme Management
        $router->get('/admin/themes', 'AdminController@themes');
        $router->get('/admin/themes/create', 'AdminController@showCreateTheme');
        $router->post('/admin/themes/create', 'AdminController@createTheme');
        $router->get('/admin/themes/{id}/edit', 'AdminController@showEditTheme');
        $router->post('/admin/themes/{id}/edit', 'AdminController@editTheme');
        $router->post('/admin/themes/{id}/activate', 'AdminController@activateTheme');
        $router->delete('/admin/themes/{id}', 'AdminController@deleteTheme');
        
        // Plugin Management
        $router->get('/admin/plugins', 'AdminController@plugins');
        $router->get('/admin/plugins/create', 'AdminController@showCreatePlugin');
        $router->post('/admin/plugins/create', 'AdminController@createPlugin');
        $router->get('/admin/plugins/{id}/edit', 'AdminController@showEditPlugin');
        $router->post('/admin/plugins/{id}/edit', 'AdminController@editPlugin');
        $router->post('/admin/plugins/{plugin}/activate', 'AdminController@activatePlugin');
        $router->post('/admin/plugins/{plugin}/deactivate', 'AdminController@deactivatePlugin');
        $router->delete('/admin/plugins/{id}', 'AdminController@deletePlugin');
        
        // Payment Management
        $router->get('/admin/payments', 'AdminController@payments');
        $router->get('/admin/payments/{id}', 'AdminController@showPayment');
        $router->post('/admin/payments/{id}/refund', 'AdminController@refundPayment');
        $router->get('/admin/subscriptions', 'AdminController@subscriptions');
        $router->post('/admin/subscriptions/{id}/cancel', 'AdminController@cancelSubscription');
        
        // Language Management
        $router->get('/admin/languages', 'AdminController@languages');
        $router->get('/admin/languages/create', 'AdminController@showCreateLanguage');
        $router->post('/admin/languages/create', 'AdminController@createLanguage');
        $router->get('/admin/languages/{id}/edit', 'AdminController@showEditLanguage');
        $router->post('/admin/languages/{id}/edit', 'AdminController@editLanguage');
        $router->post('/admin/languages/{id}/activate', 'AdminController@activateLanguage');
        $router->delete('/admin/languages/{id}', 'AdminController@deleteLanguage');
        
        // Translation Management
        $router->get('/admin/translations', 'AdminController@translations');
        $router->get('/admin/translations/{language}', 'AdminController@showTranslations');
        $router->post('/admin/translations/{language}', 'AdminController@updateTranslations');
        $router->post('/admin/translations/import', 'AdminController@importTranslations');
        $router->get('/admin/translations/export/{language}', 'AdminController@exportTranslations');
        
        // Security Management
        $router->get('/admin/security', 'AdminController@security');
        $router->get('/admin/security/logs', 'AdminController@securityLogs');
        $router->get('/admin/security/blocked-ips', 'AdminController@blockedIPs');
        $router->post('/admin/security/block-ip', 'AdminController@blockIP');
        $router->post('/admin/security/unblock-ip', 'AdminController@unblockIP');
        $router->get('/admin/security/alerts', 'AdminController@securityAlerts');
        $router->post('/admin/security/alerts/{id}/resolve', 'AdminController@resolveSecurityAlert');
        
        // API Management
        $router->get('/admin/api', 'AdminController@api');
        $router->get('/admin/api/keys', 'AdminController@apiKeys');
        $router->get('/admin/api/keys/create', 'AdminController@showCreateApiKey');
        $router->post('/admin/api/keys/create', 'AdminController@createApiKey');
        $router->delete('/admin/api/keys/{id}', 'AdminController@deleteApiKey');
        $router->get('/admin/api/usage', 'AdminController@apiUsage');
        $router->get('/admin/api/logs', 'AdminController@apiLogs');
        
        // Maintenance
        $router->get('/admin/maintenance', 'AdminController@maintenance');
        $router->post('/admin/maintenance/enable', 'AdminController@enableMaintenance');
        $router->post('/admin/maintenance/disable', 'AdminController@disableMaintenance');
        $router->post('/admin/maintenance/backup', 'AdminController@createBackup');
        $router->post('/admin/maintenance/optimize', 'AdminController@optimizeDatabase');
        $router->post('/admin/maintenance/clear-cache', 'AdminController@clearCache');
        
        // System Information
        $router->get('/admin/system', 'AdminController@system');
        $router->get('/admin/system/info', 'AdminController@systemInfo');
        $router->get('/admin/system/logs', 'AdminController@systemLogs');
        $router->get('/admin/system/performance', 'AdminController@systemPerformance');
        
    });
    
    // API routes
    $router->group(['prefix' => '/api', 'middleware' => ['CorsMiddleware']], function($router) {
        
        // Public API
        $router->get('/forums', 'ApiController@getForums');
        $router->get('/forum/{slug}/threads', 'ApiController@getForumThreads');
        $router->get('/thread/{id}/posts', 'ApiController@getThreadPosts');
        $router->get('/user/{username}', 'ApiController@getUser');
        $router->get('/search', 'ApiController@search');
        
        // Authenticated API
        $router->group(['auth' => true], function($router) {
            
            $router->post('/thread/{id}/reply', 'ApiController@createPost');
            $router->post('/post/{id}/like', 'ApiController@likePost');
            $router->post('/post/{id}/unlike', 'ApiController@unlikePost');
            $router->post('/user/{id}/follow', 'ApiController@followUser');
            $router->post('/user/{id}/unfollow', 'ApiController@unfollowUser');
            $router->post('/thread/{id}/bookmark', 'ApiController@bookmarkThread');
            $router->post('/thread/{id}/unbookmark', 'ApiController@unbookmarkThread');
            
            $router->get('/notifications', 'ApiController@getNotifications');
            $router->post('/notifications/mark-read', 'ApiController@markNotificationRead');
            
            $router->get('/messages', 'ApiController@getMessages');
            $router->post('/messages/send', 'ApiController@sendMessage');
            
        });
        
        // Real-time API
        $router->group(['auth' => true], function($router) {
            $router->get('/realtime/token', 'ApiController@getRealtimeToken');
            $router->post('/realtime/presence', 'ApiController@updatePresence');
        });
        
    });
    
});

// Run the router
$router->run();
?>