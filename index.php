<?php
declare(strict_types=1);

// Main entry point for the application
require_once __DIR__ . '/config.php';

// Include core files
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Auth.php';
require_once CORE_PATH . '/Router.php';
require_once CORE_PATH . '/Functions.php';
require_once CORE_PATH . '/Middleware.php';

// Include models
require_once MODELS_PATH . '/User.php';
require_once MODELS_PATH . '/Forum.php';
require_once MODELS_PATH . '/Thread.php';
require_once MODELS_PATH . '/Post.php';
require_once MODELS_PATH . '/Message.php';
require_once MODELS_PATH . '/Notification.php';

// Include controllers
require_once CONTROLLERS_PATH . '/HomeController.php';
require_once CONTROLLERS_PATH . '/AuthController.php';
require_once CONTROLLERS_PATH . '/ForumController.php';
require_once CONTROLLERS_PATH . '/UserController.php';
require_once CONTROLLERS_PATH . '/MessageController.php';
require_once CONTROLLERS_PATH . '/AdminController.php';
require_once CONTROLLERS_PATH . '/ApiController.php';

// Include routes
require_once ROUTES_PATH . '/web.php';
?>