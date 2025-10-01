<?php
declare(strict_types=1);

// Environment Configuration Loader
class Environment
{
    private static array $config = [];
    private static bool $loaded = false;

    public static function load(string $file = '.env'): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = dirname(__DIR__) . '/' . $file;
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    continue; // Skip comments
                }
                
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                        (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                        $value = substr($value, 1, -1);
                    }
                    
                    self::$config[$key] = $value;
                    
                    // Set environment variable if not already set
                    if (!getenv($key)) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                    }
                }
            }
        }
        
        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        self::load();
        
        return self::$config[$key] ?? getenv($key) ?: $default;
    }

    public static function has(string $key): bool
    {
        self::load();
        
        return isset(self::$config[$key]) || getenv($key) !== false;
    }

    public static function all(): array
    {
        self::load();
        
        return self::$config;
    }

    public static function set(string $key, $value): void
    {
        self::$config[$key] = $value;
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }

    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'production') === 'production';
    }

    public static function isDevelopment(): bool
    {
        return self::get('APP_ENV', 'production') === 'development';
    }

    public static function isTesting(): bool
    {
        return self::get('APP_ENV', 'production') === 'testing';
    }

    public static function isDebug(): bool
    {
        return self::get('APP_DEBUG', 'false') === 'true';
    }
}

// Load environment configuration
Environment::load();

// Define constants from environment variables
define('SITE_NAME', Environment::get('SITE_NAME', 'Modern Forum'));
define('SITE_URL', Environment::get('SITE_URL', 'http://localhost'));
define('SITE_DESCRIPTION', Environment::get('SITE_DESCRIPTION', 'A modern forum platform'));
define('SITE_KEYWORDS', Environment::get('SITE_KEYWORDS', 'forum, community, discussion'));
define('SITE_AUTHOR', Environment::get('SITE_AUTHOR', 'Forum Team'));
define('SITE_VERSION', Environment::get('SITE_VERSION', '1.0.0'));

define('APP_ENV', Environment::get('APP_ENV', 'production'));
define('APP_DEBUG', Environment::isDebug());
define('APP_TIMEZONE', Environment::get('APP_TIMEZONE', 'UTC'));

define('DB_CONNECTION', Environment::get('DB_CONNECTION', 'sqlite'));
define('DB_HOST', Environment::get('DB_HOST', 'localhost'));
define('DB_PORT', Environment::get('DB_PORT', '3306'));
define('DB_DATABASE', Environment::get('DB_DATABASE', 'storage/forum.sqlite'));
define('DB_USERNAME', Environment::get('DB_USERNAME', ''));
define('DB_PASSWORD', Environment::get('DB_PASSWORD', ''));

define('APP_KEY', Environment::get('APP_KEY', 'your-secret-key-here'));
define('SESSION_LIFETIME', (int) Environment::get('SESSION_LIFETIME', 120));
define('SESSION_ENCRYPT', Environment::get('SESSION_ENCRYPT', 'true') === 'true');
define('SESSION_SECURE', Environment::get('SESSION_SECURE', 'true') === 'true');
define('SESSION_HTTP_ONLY', Environment::get('SESSION_HTTP_ONLY', 'true') === 'true');
define('SESSION_SAME_SITE', Environment::get('SESSION_SAME_SITE', 'Strict'));

define('CSRF_TOKEN_LIFETIME', (int) Environment::get('CSRF_TOKEN_LIFETIME', 3600));
define('CSRF_TOKEN_NAME', Environment::get('CSRF_TOKEN_NAME', '_token'));

define('RATE_LIMIT_ENABLED', Environment::get('RATE_LIMIT_ENABLED', 'true') === 'true');
define('RATE_LIMIT_REQUESTS', (int) Environment::get('RATE_LIMIT_REQUESTS', 100));
define('RATE_LIMIT_WINDOW', (int) Environment::get('RATE_LIMIT_WINDOW', 3600));

define('PASSWORD_MIN_LENGTH', (int) Environment::get('PASSWORD_MIN_LENGTH', 8));
define('PASSWORD_REQUIRE_UPPERCASE', Environment::get('PASSWORD_REQUIRE_UPPERCASE', 'true') === 'true');
define('PASSWORD_REQUIRE_LOWERCASE', Environment::get('PASSWORD_REQUIRE_LOWERCASE', 'true') === 'true');
define('PASSWORD_REQUIRE_NUMBERS', Environment::get('PASSWORD_REQUIRE_NUMBERS', 'true') === 'true');
define('PASSWORD_REQUIRE_SYMBOLS', Environment::get('PASSWORD_REQUIRE_SYMBOLS', 'true') === 'true');
define('PASSWORD_HASH_ALGORITHM', Environment::get('PASSWORD_HASH_ALGORITHM', 'PASSWORD_ARGON2ID'));

define('TWO_FACTOR_ENABLED', Environment::get('TWO_FACTOR_ENABLED', 'true') === 'true');
define('TWO_FACTOR_ISSUER', Environment::get('TWO_FACTOR_ISSUER', 'Modern Forum'));
define('TWO_FACTOR_ALGORITHM', Environment::get('TWO_FACTOR_ALGORITHM', 'sha1'));
define('TWO_FACTOR_DIGITS', (int) Environment::get('TWO_FACTOR_DIGITS', 6));
define('TWO_FACTOR_PERIOD', (int) Environment::get('TWO_FACTOR_PERIOD', 30));

define('MAIL_DRIVER', Environment::get('MAIL_DRIVER', 'smtp'));
define('MAIL_HOST', Environment::get('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int) Environment::get('MAIL_PORT', 587));
define('MAIL_USERNAME', Environment::get('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', Environment::get('MAIL_PASSWORD', ''));
define('MAIL_ENCRYPTION', Environment::get('MAIL_ENCRYPTION', 'tls'));
define('MAIL_FROM_ADDRESS', Environment::get('MAIL_FROM_ADDRESS', 'noreply@forum.com'));
define('MAIL_FROM_NAME', Environment::get('MAIL_FROM_NAME', 'Modern Forum'));

define('SMS_DRIVER', Environment::get('SMS_DRIVER', 'twilio'));
define('SMS_ACCOUNT_SID', Environment::get('SMS_ACCOUNT_SID', ''));
define('SMS_AUTH_TOKEN', Environment::get('SMS_AUTH_TOKEN', ''));
define('SMS_FROM_NUMBER', Environment::get('SMS_FROM_NUMBER', ''));

define('UPLOAD_MAX_SIZE', (int) Environment::get('UPLOAD_MAX_SIZE', 10485760));
define('UPLOAD_ALLOWED_TYPES', Environment::get('UPLOAD_ALLOWED_TYPES', 'jpg,jpeg,png,gif,pdf,doc,docx,txt'));
define('UPLOAD_PATH', Environment::get('UPLOAD_PATH', 'uploads'));
define('UPLOAD_THUMBNAIL_SIZE', (int) Environment::get('UPLOAD_THUMBNAIL_SIZE', 300));

define('CACHE_DRIVER', Environment::get('CACHE_DRIVER', 'file'));
define('CACHE_LIFETIME', (int) Environment::get('CACHE_LIFETIME', 3600));
define('CACHE_PREFIX', Environment::get('CACHE_PREFIX', 'forum_'));

define('LOG_LEVEL', Environment::get('LOG_LEVEL', 'info'));
define('LOG_CHANNEL', Environment::get('LOG_CHANNEL', 'file'));
define('LOG_MAX_FILES', (int) Environment::get('LOG_MAX_FILES', 5));
define('LOG_MAX_SIZE', (int) Environment::get('LOG_MAX_SIZE', 10485760));

define('FACEBOOK_APP_ID', Environment::get('FACEBOOK_APP_ID', ''));
define('FACEBOOK_APP_SECRET', Environment::get('FACEBOOK_APP_SECRET', ''));
define('TWITTER_API_KEY', Environment::get('TWITTER_API_KEY', ''));
define('TWITTER_API_SECRET', Environment::get('TWITTER_API_SECRET', ''));
define('LINKEDIN_CLIENT_ID', Environment::get('LINKEDIN_CLIENT_ID', ''));
define('LINKEDIN_CLIENT_SECRET', Environment::get('LINKEDIN_CLIENT_SECRET', ''));
define('GOOGLE_CLIENT_ID', Environment::get('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', Environment::get('GOOGLE_CLIENT_SECRET', ''));

define('STRIPE_PUBLIC_KEY', Environment::get('STRIPE_PUBLIC_KEY', ''));
define('STRIPE_SECRET_KEY', Environment::get('STRIPE_SECRET_KEY', ''));
define('PAYPAL_CLIENT_ID', Environment::get('PAYPAL_CLIENT_ID', ''));
define('PAYPAL_CLIENT_SECRET', Environment::get('PAYPAL_CLIENT_SECRET', ''));
define('PAYPAL_MODE', Environment::get('PAYPAL_MODE', 'sandbox'));

define('CDN_ENABLED', Environment::get('CDN_ENABLED', 'false') === 'true');
define('CDN_URL', Environment::get('CDN_URL', ''));
define('CDN_CACHE_TTL', (int) Environment::get('CDN_CACHE_TTL', 86400));

define('CLOUD_STORAGE_DRIVER', Environment::get('CLOUD_STORAGE_DRIVER', 'local'));
define('AWS_ACCESS_KEY_ID', Environment::get('AWS_ACCESS_KEY_ID', ''));
define('AWS_SECRET_ACCESS_KEY', Environment::get('AWS_SECRET_ACCESS_KEY', ''));
define('AWS_DEFAULT_REGION', Environment::get('AWS_DEFAULT_REGION', 'us-east-1'));
define('AWS_BUCKET', Environment::get('AWS_BUCKET', ''));

define('MONITORING_ENABLED', Environment::get('MONITORING_ENABLED', 'true') === 'true');
define('MONITORING_INTERVAL', (int) Environment::get('MONITORING_INTERVAL', 60));
define('MONITORING_ALERT_EMAIL', Environment::get('MONITORING_ALERT_EMAIL', 'admin@forum.com'));

define('BACKUP_ENABLED', Environment::get('BACKUP_ENABLED', 'true') === 'true');
define('BACKUP_SCHEDULE', Environment::get('BACKUP_SCHEDULE', '0 2 * * *'));
define('BACKUP_RETENTION_DAYS', (int) Environment::get('BACKUP_RETENTION_DAYS', 30));
define('BACKUP_STORAGE', Environment::get('BACKUP_STORAGE', 'local'));

define('SEARCH_DRIVER', Environment::get('SEARCH_DRIVER', 'database'));
define('ELASTICSEARCH_HOST', Environment::get('ELASTICSEARCH_HOST', 'localhost'));
define('ELASTICSEARCH_PORT', (int) Environment::get('ELASTICSEARCH_PORT', 9200));
define('ELASTICSEARCH_INDEX', Environment::get('ELASTICSEARCH_INDEX', 'forum'));
define('ELASTICSEARCH_USERNAME', Environment::get('ELASTICSEARCH_USERNAME', ''));
define('ELASTICSEARCH_PASSWORD', Environment::get('ELASTICSEARCH_PASSWORD', ''));

define('ANALYTICS_ENABLED', Environment::get('ANALYTICS_ENABLED', 'true') === 'true');
define('GOOGLE_ANALYTICS_ID', Environment::get('GOOGLE_ANALYTICS_ID', ''));
define('FACEBOOK_PIXEL_ID', Environment::get('FACEBOOK_PIXEL_ID', ''));

define('SECURITY_HEADERS_ENABLED', Environment::get('SECURITY_HEADERS_ENABLED', 'true') === 'true');
define('HSTS_ENABLED', Environment::get('HSTS_ENABLED', 'true') === 'true');
define('HSTS_MAX_AGE', (int) Environment::get('HSTS_MAX_AGE', 31536000));
define('CSP_ENABLED', Environment::get('CSP_ENABLED', 'true') === 'true');
define('X_FRAME_OPTIONS', Environment::get('X_FRAME_OPTIONS', 'DENY'));
define('X_CONTENT_TYPE_OPTIONS', Environment::get('X_CONTENT_TYPE_OPTIONS', 'nosniff'));
define('REFERRER_POLICY', Environment::get('REFERRER_POLICY', 'strict-origin-when-cross-origin'));

define('API_RATE_LIMIT', (int) Environment::get('API_RATE_LIMIT', 1000));
define('API_RATE_LIMIT_WINDOW', (int) Environment::get('API_RATE_LIMIT_WINDOW', 3600));
define('API_VERSION', Environment::get('API_VERSION', 'v1'));

define('WEBSOCKET_ENABLED', Environment::get('WEBSOCKET_ENABLED', 'false') === 'true');
define('WEBSOCKET_HOST', Environment::get('WEBSOCKET_HOST', 'localhost'));
define('WEBSOCKET_PORT', (int) Environment::get('WEBSOCKET_PORT', 8080));
define('WEBSOCKET_SSL', Environment::get('WEBSOCKET_SSL', 'false') === 'true');

define('QUEUE_DRIVER', Environment::get('QUEUE_DRIVER', 'sync'));
define('QUEUE_CONNECTION', Environment::get('QUEUE_CONNECTION', 'default'));

define('REDIS_HOST', Environment::get('REDIS_HOST', 'localhost'));
define('REDIS_PORT', (int) Environment::get('REDIS_PORT', 6379));
define('REDIS_PASSWORD', Environment::get('REDIS_PASSWORD', ''));
define('REDIS_DATABASE', (int) Environment::get('REDIS_DATABASE', 0));

define('DEFAULT_LANGUAGE', Environment::get('DEFAULT_LANGUAGE', 'en'));
define('SUPPORTED_LANGUAGES', Environment::get('SUPPORTED_LANGUAGES', 'en,bn,ar,hi'));
define('RTL_LANGUAGES', Environment::get('RTL_LANGUAGES', 'ar,he'));

define('DEFAULT_THEME', Environment::get('DEFAULT_THEME', 'default'));
define('THEME_CUSTOMIZATION_ENABLED', Environment::get('THEME_CUSTOMIZATION_ENABLED', 'true') === 'true');

define('GAMIFICATION_ENABLED', Environment::get('GAMIFICATION_ENABLED', 'true') === 'true');
define('POINTS_PER_POST', (int) Environment::get('POINTS_PER_POST', 10));
define('POINTS_PER_COMMENT', (int) Environment::get('POINTS_PER_COMMENT', 5));
define('POINTS_PER_LIKE', (int) Environment::get('POINTS_PER_LIKE', 1));
define('POINTS_PER_SHARE', (int) Environment::get('POINTS_PER_SHARE', 3));

define('NOTIFICATION_EMAIL_ENABLED', Environment::get('NOTIFICATION_EMAIL_ENABLED', 'true') === 'true');
define('NOTIFICATION_SMS_ENABLED', Environment::get('NOTIFICATION_SMS_ENABLED', 'false') === 'true');
define('NOTIFICATION_PUSH_ENABLED', Environment::get('NOTIFICATION_PUSH_ENABLED', 'true') === 'true');
define('NOTIFICATION_BROWSER_ENABLED', Environment::get('NOTIFICATION_BROWSER_ENABLED', 'true') === 'true');

define('MAINTENANCE_MODE', Environment::get('MAINTENANCE_MODE', 'false') === 'true');
define('MAINTENANCE_MESSAGE', Environment::get('MAINTENANCE_MESSAGE', 'We are currently performing maintenance. Please check back later.'));

define('DEBUG_TOOLBAR_ENABLED', Environment::get('DEBUG_TOOLBAR_ENABLED', 'false') === 'true');
define('DEBUG_QUERIES_ENABLED', Environment::get('DEBUG_QUERIES_ENABLED', 'false') === 'true');
define('DEBUG_PERFORMANCE_ENABLED', Environment::get('DEBUG_PERFORMANCE_ENABLED', 'false') === 'true');

define('TESTING_ENABLED', Environment::get('TESTING_ENABLED', 'false') === 'true');
define('TEST_DATABASE', Environment::get('TEST_DATABASE', 'storage/test.sqlite'));

define('PRODUCTION_OPTIMIZE', Environment::get('PRODUCTION_OPTIMIZE', 'true') === 'true');
define('PRODUCTION_COMPRESS', Environment::get('PRODUCTION_COMPRESS', 'true') === 'true');
define('PRODUCTION_MINIFY', Environment::get('PRODUCTION_MINIFY', 'true') === 'true');
define('PRODUCTION_CACHE', Environment::get('PRODUCTION_CACHE', 'true') === 'true');