<?php

//public/index.php

require_once '../config.php';

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

// Check if API request
if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    require_once ROUTES_PATH . '/api.php';
} else {
    require_once ROUTES_PATH . '/web.php';
}
?>