<?php
declare(strict_types=1);

// Service Configuration
class ServiceConfig
{
    public static function getEmailConfig(): array
    {
        return [
            'driver' => MAIL_DRIVER,
            'host' => MAIL_HOST,
            'port' => MAIL_PORT,
            'username' => MAIL_USERNAME,
            'password' => MAIL_PASSWORD,
            'encryption' => MAIL_ENCRYPTION,
            'from' => [
                'address' => MAIL_FROM_ADDRESS,
                'name' => MAIL_FROM_NAME
            ]
        ];
    }

    public static function getSMSConfig(): array
    {
        return [
            'driver' => SMS_DRIVER,
            'account_sid' => SMS_ACCOUNT_SID,
            'auth_token' => SMS_AUTH_TOKEN,
            'from_number' => SMS_FROM_NUMBER
        ];
    }

    public static function getSocialMediaConfig(): array
    {
        return [
            'facebook' => [
                'app_id' => FACEBOOK_APP_ID,
                'app_secret' => FACEBOOK_APP_SECRET
            ],
            'twitter' => [
                'api_key' => TWITTER_API_KEY,
                'api_secret' => TWITTER_API_SECRET
            ],
            'linkedin' => [
                'client_id' => LINKEDIN_CLIENT_ID,
                'client_secret' => LINKEDIN_CLIENT_SECRET
            ],
            'google' => [
                'client_id' => GOOGLE_CLIENT_ID,
                'client_secret' => GOOGLE_CLIENT_SECRET
            ]
        ];
    }

    public static function getPaymentConfig(): array
    {
        return [
            'stripe' => [
                'public_key' => STRIPE_PUBLIC_KEY,
                'secret_key' => STRIPE_SECRET_KEY
            ],
            'paypal' => [
                'client_id' => PAYPAL_CLIENT_ID,
                'client_secret' => PAYPAL_CLIENT_SECRET,
                'mode' => PAYPAL_MODE
            ]
        ];
    }

    public static function getCDNConfig(): array
    {
        return [
            'enabled' => CDN_ENABLED,
            'url' => CDN_URL,
            'cache_ttl' => CDN_CACHE_TTL
        ];
    }

    public static function getCloudStorageConfig(): array
    {
        return [
            'driver' => CLOUD_STORAGE_DRIVER,
            'aws' => [
                'access_key_id' => AWS_ACCESS_KEY_ID,
                'secret_access_key' => AWS_SECRET_ACCESS_KEY,
                'default_region' => AWS_DEFAULT_REGION,
                'bucket' => AWS_BUCKET
            ]
        ];
    }

    public static function getMonitoringConfig(): array
    {
        return [
            'enabled' => MONITORING_ENABLED,
            'interval' => MONITORING_INTERVAL,
            'alert_email' => MONITORING_ALERT_EMAIL
        ];
    }

    public static function getBackupConfig(): array
    {
        return [
            'enabled' => BACKUP_ENABLED,
            'schedule' => BACKUP_SCHEDULE,
            'retention_days' => BACKUP_RETENTION_DAYS,
            'storage' => BACKUP_STORAGE
        ];
    }

    public static function getSearchConfig(): array
    {
        return [
            'driver' => SEARCH_DRIVER,
            'elasticsearch' => [
                'host' => ELASTICSEARCH_HOST,
                'port' => ELASTICSEARCH_PORT,
                'index' => ELASTICSEARCH_INDEX,
                'username' => ELASTICSEARCH_USERNAME,
                'password' => ELASTICSEARCH_PASSWORD
            ]
        ];
    }

    public static function getAnalyticsConfig(): array
    {
        return [
            'enabled' => ANALYTICS_ENABLED,
            'google_analytics_id' => GOOGLE_ANALYTICS_ID,
            'facebook_pixel_id' => FACEBOOK_PIXEL_ID
        ];
    }

    public static function getSecurityConfig(): array
    {
        return [
            'headers_enabled' => SECURITY_HEADERS_ENABLED,
            'hsts_enabled' => HSTS_ENABLED,
            'hsts_max_age' => HSTS_MAX_AGE,
            'csp_enabled' => CSP_ENABLED,
            'x_frame_options' => X_FRAME_OPTIONS,
            'x_content_type_options' => X_CONTENT_TYPE_OPTIONS,
            'referrer_policy' => REFERRER_POLICY
        ];
    }

    public static function getAPIConfig(): array
    {
        return [
            'rate_limit' => API_RATE_LIMIT,
            'rate_limit_window' => API_RATE_LIMIT_WINDOW,
            'version' => API_VERSION
        ];
    }

    public static function getWebSocketConfig(): array
    {
        return [
            'enabled' => WEBSOCKET_ENABLED,
            'host' => WEBSOCKET_HOST,
            'port' => WEBSOCKET_PORT,
            'ssl' => WEBSOCKET_SSL
        ];
    }

    public static function getQueueConfig(): array
    {
        return [
            'driver' => QUEUE_DRIVER,
            'connection' => QUEUE_CONNECTION
        ];
    }

    public static function getRedisConfig(): array
    {
        return [
            'host' => REDIS_HOST,
            'port' => REDIS_PORT,
            'password' => REDIS_PASSWORD,
            'database' => REDIS_DATABASE
        ];
    }

    public static function getLanguageConfig(): array
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
            'customization_enabled' => THEME_CUSTOMIZATION_ENABLED
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