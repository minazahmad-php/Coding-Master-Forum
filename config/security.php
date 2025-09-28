<?php
declare(strict_types=1);

// Security Configuration
class SecurityConfig
{
    public static function getPasswordConfig(): array
    {
        return [
            'min_length' => PASSWORD_MIN_LENGTH,
            'require_uppercase' => PASSWORD_REQUIRE_UPPERCASE,
            'require_lowercase' => PASSWORD_REQUIRE_LOWERCASE,
            'require_numbers' => PASSWORD_REQUIRE_NUMBERS,
            'require_symbols' => PASSWORD_REQUIRE_SYMBOLS,
            'hash_algorithm' => PASSWORD_HASH_ALGORITHM
        ];
    }

    public static function getTwoFactorConfig(): array
    {
        return [
            'enabled' => TWO_FACTOR_ENABLED,
            'issuer' => TWO_FACTOR_ISSUER,
            'algorithm' => TWO_FACTOR_ALGORITHM,
            'digits' => TWO_FACTOR_DIGITS,
            'period' => TWO_FACTOR_PERIOD
        ];
    }

    public static function getSessionConfig(): array
    {
        return [
            'lifetime' => SESSION_LIFETIME,
            'encrypt' => SESSION_ENCRYPT,
            'secure' => SESSION_SECURE,
            'http_only' => SESSION_HTTP_ONLY,
            'same_site' => SESSION_SAME_SITE
        ];
    }

    public static function getCSRFConfig(): array
    {
        return [
            'token_lifetime' => CSRF_TOKEN_LIFETIME,
            'token_name' => CSRF_TOKEN_NAME
        ];
    }

    public static function getRateLimitConfig(): array
    {
        return [
            'enabled' => RATE_LIMIT_ENABLED,
            'requests' => RATE_LIMIT_REQUESTS,
            'window' => RATE_LIMIT_WINDOW
        ];
    }

    public static function getSecurityHeaders(): array
    {
        return [
            'enabled' => SECURITY_HEADERS_ENABLED,
            'hsts_enabled' => HSTS_ENABLED,
            'hsts_max_age' => HSTS_MAX_AGE,
            'csp_enabled' => CSP_ENABLED,
            'x_frame_options' => X_FRAME_OPTIONS,
            'x_content_type_options' => X_CONTENT_TYPE_OPTIONS,
            'referrer_policy' => REFERRER_POLICY
        ];
    }

    public static function getFileUploadConfig(): array
    {
        return [
            'max_size' => UPLOAD_MAX_SIZE,
            'allowed_types' => explode(',', UPLOAD_ALLOWED_TYPES),
            'upload_path' => UPLOAD_PATH,
            'thumbnail_size' => UPLOAD_THUMBNAIL_SIZE
        ];
    }

    public static function getCacheConfig(): array
    {
        return [
            'driver' => CACHE_DRIVER,
            'lifetime' => CACHE_LIFETIME,
            'prefix' => CACHE_PREFIX
        ];
    }

    public static function getLoggingConfig(): array
    {
        return [
            'level' => LOG_LEVEL,
            'channel' => LOG_CHANNEL,
            'max_files' => LOG_MAX_FILES,
            'max_size' => LOG_MAX_SIZE
        ];
    }
}