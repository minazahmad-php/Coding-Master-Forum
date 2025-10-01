# Middleware Documentation

## Overview
This document describes all middleware classes available in the forum application, their purposes, and how to use them.

## Middleware Classes

### Authentication Middleware

#### AuthMiddleware
**Purpose**: Ensures user is logged in before accessing protected routes.

**Features**:
- Checks if user is logged in via session
- Redirects to login page for web requests
- Returns JSON error for API requests
- Logs unauthorized access attempts

**Usage**:
```php
$router->group(['middleware' => ['AuthMiddleware']], function($router) {
    // Protected routes
});
```

#### AdminMiddleware
**Purpose**: Ensures user has admin privileges.

**Features**:
- Checks if user is logged in
- Verifies user role is 'admin'
- Logs unauthorized access attempts
- Returns appropriate error responses

**Usage**:
```php
$router->group(['middleware' => ['AuthMiddleware', 'AdminMiddleware']], function($router) {
    // Admin-only routes
});
```

#### ModeratorMiddleware
**Purpose**: Ensures user has moderator or admin privileges.

**Features**:
- Checks if user is logged in
- Verifies user role is 'moderator' or 'admin'
- Logs unauthorized access attempts
- Returns appropriate error responses

**Usage**:
```php
$router->group(['middleware' => ['AuthMiddleware', 'ModeratorMiddleware']], function($router) {
    // Moderator-only routes
});
```

### Security Middleware

#### CSRFMiddleware
**Purpose**: Protects against Cross-Site Request Forgery attacks.

**Features**:
- Validates CSRF tokens on POST/PUT/DELETE requests
- Skips validation for GET requests and API requests
- Checks tokens from POST data, headers, or JSON body
- Generates and manages CSRF tokens
- Logs CSRF validation failures

**Usage**:
```php
$router->group(['middleware' => ['CSRFMiddleware']], function($router) {
    // CSRF-protected routes
});
```

**Helper Methods**:
- `CSRFMiddleware::generateToken()` - Generate new CSRF token
- `CSRFMiddleware::getTokenField()` - Get HTML input field for token

#### SecurityHeadersMiddleware
**Purpose**: Sets security headers to protect against various attacks.

**Features**:
- HSTS (HTTP Strict Transport Security)
- X-Frame-Options
- X-Content-Type-Options
- Referrer-Policy
- Permissions-Policy
- Content Security Policy (CSP)
- X-XSS-Protection
- Cache-Control for sensitive pages

**Usage**:
```php
$router->group(['middleware' => ['SecurityHeadersMiddleware']], function($router) {
    // Routes with security headers
});
```

**Helper Methods**:
- `SecurityHeadersMiddleware::generateCSPNonce()` - Generate CSP nonce
- `SecurityHeadersMiddleware::getCSPNonce()` - Get current CSP nonce

#### RateLimitMiddleware
**Purpose**: Implements rate limiting to prevent abuse.

**Features**:
- Configurable request limits and time windows
- Per-user or per-IP rate limiting
- Database-backed rate limit tracking
- Automatic cleanup of old records
- Logs rate limit violations

**Usage**:
```php
$router->group(['middleware' => ['RateLimitMiddleware']], function($router) {
    // Rate-limited routes
});
```

**Configuration**:
- `RATE_LIMIT_ENABLED` - Enable/disable rate limiting
- `RATE_LIMIT_REQUESTS` - Maximum requests per window
- `RATE_LIMIT_WINDOW` - Time window in seconds

### Logging Middleware

#### LoggingMiddleware
**Purpose**: Logs HTTP requests and responses for monitoring and debugging.

**Features**:
- Logs request details (method, URI, IP, user agent, etc.)
- Logs response details (status code, response time, memory usage)
- Tracks user ID for authenticated requests
- Formats memory usage in human-readable format

**Usage**:
```php
$router->group(['middleware' => ['LoggingMiddleware']], function($router) {
    // Logged routes
});
```

### API Middleware

#### CorsMiddleware
**Purpose**: Handles Cross-Origin Resource Sharing for API endpoints.

**Features**:
- Sets CORS headers for API requests
- Handles preflight OPTIONS requests
- Configurable allowed origins
- Supports credentials and custom headers

**Usage**:
```php
$router->group(['middleware' => ['CorsMiddleware']], function($router) {
    // CORS-enabled API routes
});
```

#### ApiMiddleware
**Purpose**: Sets API-specific headers and logging.

**Features**:
- Sets JSON content type
- Adds API version headers
- Logs API requests and responses
- Tracks API key usage

**Usage**:
```php
$router->group(['middleware' => ['ApiMiddleware']], function($router) {
    // API routes
});
```

#### ApiAuthMiddleware
**Purpose**: Authenticates API requests using API keys.

**Features**:
- Validates API keys from headers or query parameters
- Checks API key validity and user status
- Updates API key usage statistics
- Logs API key usage
- Sets user context for requests

**Usage**:
```php
$router->group(['middleware' => ['ApiAuthMiddleware']], function($router) {
    // Authenticated API routes
});
```

#### ApiModeratorMiddleware
**Purpose**: Ensures API user has moderator privileges.

**Features**:
- Checks API user authentication
- Verifies user role is 'moderator' or 'admin'
- Logs unauthorized access attempts

**Usage**:
```php
$router->group(['middleware' => ['ApiAuthMiddleware', 'ApiModeratorMiddleware']], function($router) {
    // Moderator API routes
});
```

#### ApiAdminMiddleware
**Purpose**: Ensures API user has admin privileges.

**Features**:
- Checks API user authentication
- Verifies user role is 'admin'
- Logs unauthorized access attempts

**Usage**:
```php
$router->group(['middleware' => ['ApiAuthMiddleware', 'ApiAdminMiddleware']], function($router) {
    // Admin API routes
});
```

## Middleware Stack

### Global Middleware
Applied to all routes:
- `SecurityHeadersMiddleware` - Security headers
- `LoggingMiddleware` - Request/response logging

### Web Routes Middleware
- `CSRFMiddleware` - CSRF protection
- `RateLimitMiddleware` - Rate limiting
- `AuthMiddleware` - Authentication
- `AdminMiddleware` - Admin access
- `ModeratorMiddleware` - Moderator access

### API Routes Middleware
- `CorsMiddleware` - CORS headers
- `ApiMiddleware` - API headers and logging
- `ApiAuthMiddleware` - API authentication
- `ApiModeratorMiddleware` - API moderator access
- `ApiAdminMiddleware` - API admin access

## Configuration

### Environment Variables
```env
# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=3600

# Security Headers
SECURITY_HEADERS_ENABLED=true
HSTS_ENABLED=true
HSTS_MAX_AGE=31536000
CSP_ENABLED=true
X_FRAME_OPTIONS=DENY
X_CONTENT_TYPE_OPTIONS=nosniff
REFERRER_POLICY=strict-origin-when-cross-origin

# CSRF
CSRF_TOKEN_LIFETIME=3600
CSRF_TOKEN_NAME=_token

# CORS
CORS_ALLOWED_ORIGINS=https://example.com,https://app.example.com
```

## Database Tables

### Required Tables
- `rate_limit_logs` - Rate limiting data
- `api_keys` - API key storage
- `api_usage_logs` - API usage tracking

### Table Creation
```sql
CREATE TABLE IF NOT EXISTS rate_limit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    identifier VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    url TEXT,
    method VARCHAR(10),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name VARCHAR(100) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    permissions TEXT,
    is_active BOOLEAN DEFAULT 1,
    last_used_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS api_usage_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key_hash VARCHAR(255) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    response_time INTEGER,
    status_code INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## Error Handling

### HTTP Status Codes
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient privileges
- `419 Page Expired` - CSRF token mismatch
- `429 Too Many Requests` - Rate limit exceeded

### Error Responses

#### Web Routes
- Redirect to login page for authentication errors
- Show error pages (403, 419, 429) for other errors
- Flash messages for user feedback

#### API Routes
- JSON error responses with consistent format
- Appropriate HTTP status codes
- Error details in response body

## Security Considerations

### CSRF Protection
- Required for all state-changing operations
- Tokens must be included in forms and AJAX requests
- Tokens are validated on server-side

### Rate Limiting
- Prevents brute force attacks
- Protects against DDoS
- Configurable per endpoint

### Security Headers
- Prevents clickjacking (X-Frame-Options)
- Prevents MIME type sniffing (X-Content-Type-Options)
- Enforces HTTPS (HSTS)
- Controls resource loading (CSP)

### API Security
- API key authentication
- Role-based access control
- Usage tracking and monitoring
- CORS protection

## Monitoring and Logging

### Request Logging
- All HTTP requests are logged
- Includes IP, user agent, method, URI
- Tracks authenticated users
- Measures response time and memory usage

### Security Logging
- Failed authentication attempts
- CSRF token mismatches
- Rate limit violations
- Unauthorized access attempts

### API Logging
- API key usage
- Endpoint access
- Response times
- Error rates

## Performance Considerations

### Rate Limiting
- Database queries for each request
- Consider caching for high-traffic sites
- Cleanup old records regularly

### Logging
- Can impact performance on high-traffic sites
- Consider async logging
- Rotate log files regularly

### Security Headers
- Minimal performance impact
- Headers are set once per request
- CSP can be complex but necessary

## Best Practices

### Middleware Order
1. Security headers (first)
2. CORS (for API)
3. Logging
4. Rate limiting
5. CSRF protection
6. Authentication
7. Authorization (Admin/Moderator)

### Error Handling
- Always log security events
- Provide clear error messages
- Use appropriate HTTP status codes
- Don't expose sensitive information

### Configuration
- Use environment variables for sensitive settings
- Enable all security features in production
- Regularly review and update security policies
- Monitor logs for suspicious activity