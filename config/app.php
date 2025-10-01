<?php
declare(strict_types=1);

// Application Configuration
class AppConfig
{
    public static function getSiteConfig(): array
    {
        return [
            'name' => SITE_NAME,
            'url' => SITE_URL,
            'description' => SITE_DESCRIPTION,
            'keywords' => SITE_KEYWORDS,
            'author' => SITE_AUTHOR,
            'version' => SITE_VERSION
        ];
    }

    public static function getEnvironmentConfig(): array
    {
        return [
            'env' => APP_ENV,
            'debug' => APP_DEBUG,
            'timezone' => APP_TIMEZONE
        ];
    }

    public static function getDatabaseConfig(): array
    {
        return [
            'connection' => DB_CONNECTION,
            'host' => DB_HOST,
            'port' => DB_PORT,
            'database' => DB_DATABASE,
            'username' => DB_USERNAME,
            'password' => DB_PASSWORD
        ];
    }

    public static function getAppConfig(): array
    {
        return [
            'key' => APP_KEY,
            'env' => APP_ENV,
            'debug' => APP_DEBUG,
            'timezone' => APP_TIMEZONE
        ];
    }

    public static function getPathConfig(): array
    {
        return [
            'root' => dirname(__DIR__),
            'public' => dirname(__DIR__) . '/public',
            'storage' => dirname(__DIR__) . '/storage',
            'cache' => dirname(__DIR__) . '/cache',
            'logs' => dirname(__DIR__) . '/logs',
            'temp' => dirname(__DIR__) . '/temp',
            'backups' => dirname(__DIR__) . '/backups',
            'uploads' => dirname(__DIR__) . '/uploads'
        ];
    }

    public static function getDirectoryConfig(): array
    {
        return [
            'storage' => STORAGE_PATH,
            'cache' => CACHE_PATH,
            'logs' => LOGS_PATH,
            'temp' => TEMP_PATH,
            'backups' => BACKUPS_PATH,
            'uploads' => UPLOADS_PATH
        ];
    }

    public static function getCoreConfig(): array
    {
        return [
            'core_path' => CORE_PATH,
            'controllers_path' => CONTROLLERS_PATH,
            'models_path' => MODELS_PATH,
            'views_path' => VIEWS_PATH,
            'services_path' => SERVICES_PATH,
            'middleware_path' => MIDDLEWARE_PATH,
            'helpers_path' => HELPERS_PATH
        ];
    }

    public static function getPublicConfig(): array
    {
        return [
            'assets_path' => ASSETS_PATH,
            'css_path' => CSS_PATH,
            'js_path' => JS_PATH,
            'images_path' => IMAGES_PATH,
            'fonts_path' => FONTS_PATH
        ];
    }

    public static function getAutoloaderConfig(): array
    {
        return [
            'namespaces' => [
                'Core' => CORE_PATH,
                'Controllers' => CONTROLLERS_PATH,
                'Models' => MODELS_PATH,
                'Services' => SERVICES_PATH,
                'Middleware' => MIDDLEWARE_PATH,
                'Helpers' => HELPERS_PATH
            ]
        ];
    }

    public static function getErrorConfig(): array
    {
        return [
            'reporting' => APP_DEBUG ? E_ALL : 0,
            'display_errors' => APP_DEBUG,
            'log_errors' => true,
            'error_log' => LOGS_PATH . '/error.log'
        ];
    }

    public static function getTimezoneConfig(): array
    {
        return [
            'default' => APP_TIMEZONE,
            'supported' => [
                'UTC', 'America/New_York', 'America/Chicago', 'America/Denver',
                'America/Los_Angeles', 'Europe/London', 'Europe/Paris',
                'Europe/Berlin', 'Asia/Tokyo', 'Asia/Shanghai', 'Asia/Kolkata',
                'Asia/Dhaka', 'Australia/Sydney'
            ]
        ];
    }

    public static function getLocaleConfig(): array
    {
        return [
            'default' => DEFAULT_LANGUAGE,
            'supported' => explode(',', SUPPORTED_LANGUAGES),
            'rtl' => explode(',', RTL_LANGUAGES)
        ];
    }

    public static function getThemeConfig(): array
    {
        return [
            'default' => DEFAULT_THEME,
            'customization_enabled' => THEME_CUSTOMIZATION_ENABLED,
            'available_themes' => [
                'default', 'dark', 'light', 'blue', 'green', 'red', 'purple'
            ]
        ];
    }

    public static function getGamificationConfig(): array
    {
        return [
            'enabled' => GAMIFICATION_ENABLED,
            'points_per_post' => POINTS_PER_POST,
            'points_per_comment' => POINTS_PER_COMMENT,
            'points_per_like' => POINTS_PER_LIKE,
            'points_per_share' => POINTS_PER_SHARE
        ];
    }

    public static function getNotificationConfig(): array
    {
        return [
            'email_enabled' => NOTIFICATION_EMAIL_ENABLED,
            'sms_enabled' => NOTIFICATION_SMS_ENABLED,
            'push_enabled' => NOTIFICATION_PUSH_ENABLED,
            'browser_enabled' => NOTIFICATION_BROWSER_ENABLED
        ];
    }

    public static function getMaintenanceConfig(): array
    {
        return [
            'enabled' => MAINTENANCE_MODE,
            'message' => MAINTENANCE_MESSAGE
        ];
    }

    public static function getDebugConfig(): array
    {
        return [
            'toolbar_enabled' => DEBUG_TOOLBAR_ENABLED,
            'queries_enabled' => DEBUG_QUERIES_ENABLED,
            'performance_enabled' => DEBUG_PERFORMANCE_ENABLED
        ];
    }

    public static function getTestingConfig(): array
    {
        return [
            'enabled' => TESTING_ENABLED,
            'database' => TEST_DATABASE
        ];
    }

    public static function getProductionConfig(): array
    {
        return [
            'optimize' => PRODUCTION_OPTIMIZE,
            'compress' => PRODUCTION_COMPRESS,
            'minify' => PRODUCTION_MINIFY,
            'cache' => PRODUCTION_CACHE
        ];
    }
}