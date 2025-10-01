# Forum Routes Documentation

## Overview
This document describes all available routes in the forum application, organized by functionality and access level.

## Route Structure
- **Web Routes**: Traditional web pages and forms
- **API Routes**: RESTful API endpoints for programmatic access
- **Admin Routes**: Administrative functions (admin access required)
- **Moderator Routes**: Moderation functions (moderator access required)

## Web Routes

### Public Routes
- `GET /` - Home page
- `GET /about` - About page
- `GET /contact` - Contact page
- `POST /contact` - Send contact message
- `GET /privacy` - Privacy policy
- `GET /terms` - Terms of service
- `GET /help` - Help page
- `GET /faq` - FAQ page

### Forum Routes
- `GET /forums` - List all forums
- `GET /forum/{slug}` - View specific forum
- `GET /forum/{slug}/create-thread` - Create thread form
- `POST /forum/{slug}/create-thread` - Create new thread

### Thread Routes
- `GET /thread/{id}` - View thread
- `GET /thread/{id}/edit` - Edit thread form
- `POST /thread/{id}/edit` - Update thread
- `DELETE /thread/{id}` - Delete thread
- `POST /thread/{id}/pin` - Pin thread
- `POST /thread/{id}/unpin` - Unpin thread
- `POST /thread/{id}/lock` - Lock thread
- `POST /thread/{id}/unlock` - Unlock thread
- `POST /thread/{id}/subscribe` - Subscribe to thread
- `POST /thread/{id}/unsubscribe` - Unsubscribe from thread

### Post Routes
- `POST /thread/{id}/reply` - Reply to thread
- `GET /post/{id}/edit` - Edit post form
- `POST /post/{id}/edit` - Update post
- `DELETE /post/{id}` - Delete post
- `POST /post/{id}/like` - Like post
- `POST /post/{id}/unlike` - Unlike post
- `POST /post/{id}/report` - Report post
- `POST /post/{id}/quote` - Quote post

### Search Routes
- `GET /search` - Search page
- `GET /search/advanced` - Advanced search
- `POST /search` - Perform search
- `GET /search/suggestions` - Get search suggestions
- `POST /search/track-click` - Track search click
- `POST /search/track-duration` - Track search duration

### User Routes
- `GET /users` - List users
- `GET /user/{username}` - User profile
- `GET /user/{username}/posts` - User posts
- `GET /user/{username}/threads` - User threads
- `GET /user/{username}/followers` - User followers
- `GET /user/{username}/following` - User following

### Authentication Routes
- `GET /login` - Login form
- `POST /login` - Process login
- `GET /register` - Registration form
- `POST /register` - Process registration
- `GET /logout` - Logout user

#### Social Login
- `GET /auth/google` - Google login
- `GET /auth/google/callback` - Google callback
- `GET /auth/facebook` - Facebook login
- `GET /auth/facebook/callback` - Facebook callback
- `GET /auth/twitter` - Twitter login
- `GET /auth/twitter/callback` - Twitter callback

#### Password Reset
- `GET /forgot-password` - Forgot password form
- `POST /forgot-password` - Send reset email
- `GET /reset-password/{token}` - Reset password form
- `POST /reset-password` - Process password reset

#### Email Verification
- `GET /verify-email/{token}` - Verify email
- `POST /resend-verification` - Resend verification email

### Authenticated Routes (User Required)

#### User Dashboard & Profile
- `GET /dashboard` - User dashboard
- `GET /profile` - User profile
- `POST /profile/update` - Update profile
- `POST /profile/upload-avatar` - Upload avatar
- `POST /profile/upload-cover` - Upload cover image

#### User Settings
- `GET /settings` - User settings
- `POST /settings/update` - Update settings
- `POST /settings/change-password` - Change password
- `POST /settings/enable-2fa` - Enable 2FA
- `POST /settings/disable-2fa` - Disable 2FA

#### Following System
- `POST /user/{id}/follow` - Follow user
- `POST /user/{id}/unfollow` - Unfollow user
- `GET /following` - Following list
- `GET /followers` - Followers list

#### Private Messaging
- `GET /messages` - Messages list
- `GET /messages/compose` - Compose message
- `POST /messages/send` - Send message
- `GET /messages/conversation/{id}` - View conversation
- `POST /messages/conversation/{id}/reply` - Reply to conversation
- `DELETE /messages/{id}` - Delete message
- `POST /messages/{id}/mark-read` - Mark message as read

#### Notifications
- `GET /notifications` - Notifications list
- `POST /notifications/mark-read` - Mark notification as read
- `POST /notifications/mark-all-read` - Mark all notifications as read
- `DELETE /notifications/{id}` - Delete notification

#### Bookmarks
- `GET /bookmarks` - Bookmarks list
- `POST /thread/{id}/bookmark` - Bookmark thread
- `POST /thread/{id}/unbookmark` - Remove bookmark

#### Reports
- `POST /report/thread/{id}` - Report thread
- `POST /report/post/{id}` - Report post
- `POST /report/user/{id}` - Report user

#### Premium Features
- `GET /premium` - Premium features
- `POST /premium/subscribe` - Subscribe to premium
- `POST /premium/cancel` - Cancel premium

### Moderator Routes (Moderator Required)

#### Moderator Dashboard
- `GET /moderator` - Moderator dashboard
- `GET /moderator/reports` - Reports list
- `POST /moderator/reports/{id}/resolve` - Resolve report
- `POST /moderator/reports/{id}/dismiss` - Dismiss report

#### Thread Moderation
- `POST /moderator/thread/{id}/pin` - Pin thread
- `POST /moderator/thread/{id}/unpin` - Unpin thread
- `POST /moderator/thread/{id}/lock` - Lock thread
- `POST /moderator/thread/{id}/unlock` - Unlock thread
- `POST /moderator/thread/{id}/move` - Move thread

#### Post Moderation
- `POST /moderator/post/{id}/hide` - Hide post
- `POST /moderator/post/{id}/unhide` - Unhide post
- `POST /moderator/post/{id}/edit` - Edit post

#### User Moderation
- `POST /moderator/user/{id}/warn` - Warn user
- `POST /moderator/user/{id}/suspend` - Suspend user

### Admin Routes (Admin Required)

#### Admin Dashboard
- `GET /admin` - Admin dashboard
- `GET /admin/dashboard` - Admin dashboard

#### User Management
- `GET /admin/users` - Users list
- `GET /admin/users/{id}/edit` - Edit user form
- `POST /admin/users/{id}/edit` - Update user
- `POST /admin/users/{id}/ban` - Ban user
- `POST /admin/users/{id}/unban` - Unban user
- `POST /admin/users/{id}/promote` - Promote user
- `POST /admin/users/{id}/demote` - Demote user
- `DELETE /admin/users/{id}` - Delete user

#### Forum Management
- `GET /admin/forums` - Forums list
- `GET /admin/forums/create` - Create forum form
- `POST /admin/forums/create` - Create forum
- `GET /admin/forums/{id}/edit` - Edit forum form
- `POST /admin/forums/{id}/edit` - Update forum
- `DELETE /admin/forums/{id}` - Delete forum
- `POST /admin/forums/{id}/reorder` - Reorder forum

#### Content Management
- `GET /admin/threads` - Threads list
- `DELETE /admin/threads/{id}` - Delete thread
- `POST /admin/threads/{id}/restore` - Restore thread

- `GET /admin/posts` - Posts list
- `DELETE /admin/posts/{id}` - Delete post
- `POST /admin/posts/{id}/restore` - Restore post

#### Settings
- `GET /admin/settings` - Admin settings
- `POST /admin/settings` - Update settings
- `GET /admin/settings/email` - Email settings
- `POST /admin/settings/email` - Update email settings
- `GET /admin/settings/social` - Social settings
- `POST /admin/settings/social` - Update social settings

#### Reports
- `GET /admin/reports` - Reports list
- `POST /admin/reports/{id}/resolve` - Resolve report
- `POST /admin/reports/{id}/dismiss` - Dismiss report

#### Statistics
- `GET /admin/stats` - Statistics
- `GET /admin/stats/users` - User statistics
- `GET /admin/stats/posts` - Post statistics
- `GET /admin/stats/forums` - Forum statistics

#### Analytics Dashboard
- `GET /admin/analytics` - Analytics dashboard
- `GET /admin/analytics/user` - User analytics
- `GET /admin/analytics/content` - Content analytics
- `GET /admin/analytics/traffic` - Traffic analytics
- `GET /admin/analytics/engagement` - Engagement analytics
- `GET /admin/analytics/conversion` - Conversion analytics
- `GET /admin/analytics/revenue` - Revenue analytics
- `GET /admin/analytics/performance` - Performance analytics
- `GET /admin/analytics/export` - Export analytics

#### Search Analytics
- `GET /admin/search-analytics` - Search analytics
- `GET /admin/search-analytics/export` - Export search analytics

#### Integrations Management
- `GET /admin/integrations` - Integrations dashboard
- `GET /admin/integrations/social-media` - Social media integration
- `GET /admin/integrations/payment-gateway` - Payment gateway
- `GET /admin/integrations/email-service` - Email service
- `GET /admin/integrations/sms-service` - SMS service
- `GET /admin/integrations/api-management` - API management
- `GET /admin/integrations/cloud-storage` - Cloud storage
- `GET /admin/integrations/cdn-service` - CDN service
- `GET /admin/integrations/monitoring-service` - Monitoring service
- `GET /admin/integrations/backup-service` - Backup service

#### Theme Management
- `GET /admin/themes` - Themes list
- `GET /admin/themes/create` - Create theme form
- `POST /admin/themes/create` - Create theme
- `GET /admin/themes/{id}/edit` - Edit theme form
- `POST /admin/themes/{id}/edit` - Update theme
- `POST /admin/themes/{id}/activate` - Activate theme
- `DELETE /admin/themes/{id}` - Delete theme

#### Plugin Management
- `GET /admin/plugins` - Plugins list
- `GET /admin/plugins/create` - Create plugin form
- `POST /admin/plugins/create` - Create plugin
- `GET /admin/plugins/{id}/edit` - Edit plugin form
- `POST /admin/plugins/{id}/edit` - Update plugin
- `POST /admin/plugins/{plugin}/activate` - Activate plugin
- `POST /admin/plugins/{plugin}/deactivate` - Deactivate plugin
- `DELETE /admin/plugins/{id}` - Delete plugin

#### Payment Management
- `GET /admin/payments` - Payments list
- `GET /admin/payments/{id}` - Payment details
- `POST /admin/payments/{id}/refund` - Refund payment
- `GET /admin/subscriptions` - Subscriptions list
- `POST /admin/subscriptions/{id}/cancel` - Cancel subscription

#### Language Management
- `GET /admin/languages` - Languages list
- `GET /admin/languages/create` - Create language form
- `POST /admin/languages/create` - Create language
- `GET /admin/languages/{id}/edit` - Edit language form
- `POST /admin/languages/{id}/edit` - Update language
- `POST /admin/languages/{id}/activate` - Activate language
- `DELETE /admin/languages/{id}` - Delete language

#### Translation Management
- `GET /admin/translations` - Translations list
- `GET /admin/translations/{language}` - Language translations
- `POST /admin/translations/{language}` - Update translations
- `POST /admin/translations/import` - Import translations
- `GET /admin/translations/export/{language}` - Export translations

#### Security Management
- `GET /admin/security` - Security dashboard
- `GET /admin/security/logs` - Security logs
- `GET /admin/security/blocked-ips` - Blocked IPs
- `POST /admin/security/block-ip` - Block IP
- `POST /admin/security/unblock-ip` - Unblock IP
- `GET /admin/security/alerts` - Security alerts
- `POST /admin/security/alerts/{id}/resolve` - Resolve security alert

#### API Management
- `GET /admin/api` - API dashboard
- `GET /admin/api/keys` - API keys list
- `GET /admin/api/keys/create` - Create API key form
- `POST /admin/api/keys/create` - Create API key
- `DELETE /admin/api/keys/{id}` - Delete API key
- `GET /admin/api/usage` - API usage statistics
- `GET /admin/api/logs` - API logs

#### Maintenance
- `GET /admin/maintenance` - Maintenance dashboard
- `POST /admin/maintenance/enable` - Enable maintenance mode
- `POST /admin/maintenance/disable` - Disable maintenance mode
- `POST /admin/maintenance/backup` - Create backup
- `POST /admin/maintenance/optimize` - Optimize database
- `POST /admin/maintenance/clear-cache` - Clear cache

#### System Information
- `GET /admin/system` - System dashboard
- `GET /admin/system/info` - System information
- `GET /admin/system/logs` - System logs
- `GET /admin/system/performance` - System performance

## API Routes

### Public API Routes
- `GET /api/v1/forums` - Get forums
- `GET /api/v1/forum/{slug}` - Get forum
- `GET /api/v1/forum/{slug}/threads` - Get forum threads
- `GET /api/v1/thread/{id}` - Get thread
- `GET /api/v1/thread/{id}/posts` - Get thread posts
- `GET /api/v1/user/{username}` - Get user
- `GET /api/v1/search` - Search
- `GET /api/v1/categories` - Get categories
- `GET /api/v1/stats` - Get statistics

### Authentication API Routes
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/register` - Register
- `POST /api/v1/auth/logout` - Logout
- `POST /api/v1/auth/refresh` - Refresh token
- `POST /api/v1/auth/forgot-password` - Forgot password
- `POST /api/v1/auth/reset-password` - Reset password

### Authenticated API Routes
- `GET /api/v1/user/profile` - Get profile
- `PUT /api/v1/user/profile` - Update profile
- `GET /api/v1/user/settings` - Get settings
- `PUT /api/v1/user/settings` - Update settings
- `POST /api/v1/thread` - Create thread
- `PUT /api/v1/thread/{id}` - Update thread
- `DELETE /api/v1/thread/{id}` - Delete thread
- `POST /api/v1/thread/{id}/post` - Create post
- `PUT /api/v1/post/{id}` - Update post
- `DELETE /api/v1/post/{id}` - Delete post
- `POST /api/v1/post/{id}/like` - Like post
- `POST /api/v1/post/{id}/unlike` - Unlike post
- `GET /api/v1/notifications` - Get notifications
- `POST /api/v1/notifications/mark-read` - Mark notification as read
- `GET /api/v1/messages` - Get messages
- `POST /api/v1/messages` - Send message

### Admin API Routes
- `GET /api/v1/admin/dashboard` - Admin dashboard
- `GET /api/v1/admin/stats` - Admin statistics
- `GET /api/v1/admin/users` - Admin users
- `PUT /api/v1/admin/users/{id}` - Update admin user
- `DELETE /api/v1/admin/users/{id}` - Delete user
- `GET /api/v1/admin/analytics/user` - User analytics
- `GET /api/v1/admin/analytics/content` - Content analytics
- `GET /api/v1/admin/analytics/traffic` - Traffic analytics

## Middleware

### Global Middleware
- `SecurityHeadersMiddleware` - Security headers
- `LoggingMiddleware` - Request logging

### Route-Specific Middleware
- `CorsMiddleware` - CORS headers for API
- `ApiMiddleware` - API-specific middleware
- `ApiAuthMiddleware` - API authentication
- `ApiModeratorMiddleware` - API moderator access
- `ApiAdminMiddleware` - API admin access

## Route Parameters

### Dynamic Parameters
- `{id}` - Numeric ID
- `{slug}` - URL-friendly slug
- `{username}` - Username
- `{token}` - Security token
- `{language}` - Language code

### Query Parameters
- `page` - Page number for pagination
- `limit` - Items per page
- `sort` - Sort field
- `order` - Sort order (asc/desc)
- `search` - Search query
- `filter` - Filter criteria

## Response Formats

### Web Routes
- HTML responses for web pages
- JSON responses for AJAX requests
- Redirect responses for form submissions

### API Routes
- JSON responses with consistent structure
- HTTP status codes for success/error indication
- Pagination metadata for list endpoints
- Error messages with details

## Security Considerations

### Authentication
- Session-based authentication for web routes
- Token-based authentication for API routes
- Two-factor authentication support
- Social login integration

### Authorization
- Role-based access control (User, Moderator, Admin)
- Permission-based access for specific actions
- API key authentication for external access

### Rate Limiting
- Per-IP rate limiting
- Per-user rate limiting
- API endpoint rate limiting
- Configurable rate limits

### CSRF Protection
- CSRF tokens for web forms
- SameSite cookie attributes
- Origin header validation

## Error Handling

### Web Routes
- Custom error pages (404, 500, etc.)
- Flash messages for user feedback
- Form validation with error display

### API Routes
- Consistent error response format
- HTTP status codes
- Error codes and messages
- Stack traces in development mode

## Caching

### Route Caching
- Static route caching
- Dynamic route caching
- Cache invalidation strategies

### Response Caching
- Page caching
- API response caching
- Cache headers for client-side caching

## Monitoring

### Route Monitoring
- Request/response logging
- Performance metrics
- Error tracking
- Usage analytics

### API Monitoring
- API usage statistics
- Rate limit monitoring
- Error rate tracking
- Performance monitoring