# Forum API Documentation

## Overview

The Forum API provides comprehensive endpoints for managing forums, threads, posts, users, and more. All API endpoints return JSON responses.

## Base URL

```
https://yourdomain.com/api/v1
```

## Authentication

### API Key Authentication
Include your API key in the request header:
```
Authorization: Bearer YOUR_API_KEY
```

### Session Authentication
Include the session cookie for web-based requests.

## Rate Limiting

- **General API**: 1000 requests per hour
- **Authentication**: 10 requests per minute
- **File Upload**: 100 requests per hour

## Response Format

### Success Response
```json
{
    "success": true,
    "data": {
        // Response data
    },
    "message": "Operation successful"
}
```

### Error Response
```json
{
    "success": false,
    "error": "Error message",
    "code": "ERROR_CODE"
}
```

## Endpoints

### Authentication

#### POST /auth/login
Login with email/username and password.

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123",
    "remember": true
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "username": "johndoe",
            "email": "user@example.com",
            "display_name": "John Doe",
            "role": "user"
        },
        "token": "jwt_token_here"
    }
}
```

#### POST /auth/register
Register a new user account.

**Request Body:**
```json
{
    "username": "johndoe",
    "email": "user@example.com",
    "display_name": "John Doe",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### POST /auth/logout
Logout the current user.

### Users

#### GET /users
Get list of users with pagination.

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 20)
- `search` (string): Search term
- `role` (string): Filter by role
- `status` (string): Filter by status

#### GET /users/{id}
Get user by ID.

#### PUT /users/{id}
Update user information.

#### DELETE /users/{id}
Delete user account.

### Forums

#### GET /forums
Get list of forums.

**Query Parameters:**
- `page` (int): Page number
- `per_page` (int): Items per page
- `search` (string): Search term
- `status` (string): Filter by status

#### GET /forums/{id}
Get forum by ID with threads.

#### POST /forums
Create new forum (Admin only).

**Request Body:**
```json
{
    "name": "General Discussion",
    "description": "General forum for discussions",
    "status": "active"
}
```

#### PUT /forums/{id}
Update forum (Admin only).

#### DELETE /forums/{id}
Delete forum (Admin only).

### Threads

#### GET /threads
Get list of threads.

**Query Parameters:**
- `page` (int): Page number
- `per_page` (int): Items per page
- `forum_id` (int): Filter by forum
- `user_id` (int): Filter by user
- `search` (string): Search term
- `status` (string): Filter by status

#### GET /threads/{id}
Get thread by ID with posts.

#### POST /threads
Create new thread.

**Request Body:**
```json
{
    "forum_id": 1,
    "title": "Thread Title",
    "content": "Thread content here",
    "status": "active"
}
```

#### PUT /threads/{id}
Update thread.

#### DELETE /threads/{id}
Delete thread.

#### GET /threads/{id}/posts
Get posts in a thread.

#### POST /threads/{id}/subscribe
Subscribe to thread.

#### DELETE /threads/{id}/subscribe
Unsubscribe from thread.

### Posts

#### GET /posts
Get list of posts.

**Query Parameters:**
- `page` (int): Page number
- `per_page` (int): Items per page
- `thread_id` (int): Filter by thread
- `user_id` (int): Filter by user
- `search` (string): Search term

#### GET /posts/{id}
Get post by ID.

#### POST /posts
Create new post.

**Request Body:**
```json
{
    "thread_id": 1,
    "content": "Post content here",
    "status": "active"
}
```

#### PUT /posts/{id}
Update post.

#### DELETE /posts/{id}
Delete post.

#### POST /posts/{id}/react
Add reaction to post.

**Request Body:**
```json
{
    "reaction_type": "like"
}
```

#### DELETE /posts/{id}/react
Remove reaction from post.

#### POST /posts/{id}/solution
Mark post as solution.

### Search

#### GET /search
Search across threads, posts, and users.

**Query Parameters:**
- `q` (string): Search query
- `type` (string): Content type (threads, posts, users)
- `forum_id` (int): Filter by forum
- `user_id` (int): Filter by user
- `date_from` (date): Start date
- `date_to` (date): End date

### Notifications

#### GET /notifications
Get user notifications.

#### PUT /notifications/{id}/read
Mark notification as read.

#### DELETE /notifications/{id}
Delete notification.

#### POST /notifications/read-all
Mark all notifications as read.

### Messages

#### GET /messages
Get user messages.

#### GET /messages/{id}
Get message by ID.

#### POST /messages
Send new message.

**Request Body:**
```json
{
    "to_user_id": 2,
    "subject": "Message Subject",
    "content": "Message content here"
}
```

#### POST /messages/{id}/reply
Reply to message.

### Analytics

#### GET /analytics
Get analytics data (Admin only).

#### GET /analytics/realtime
Get real-time analytics (Admin only).

### Admin

#### GET /admin/dashboard
Get admin dashboard data.

#### GET /admin/users
Get users list for admin.

#### GET /admin/forums
Get forums list for admin.

#### GET /admin/reports
Get reports list for admin.

#### POST /admin/reports/{id}/handle
Handle report.

**Request Body:**
```json
{
    "action": "dismiss",
    "reason": "Not a valid report"
}
```

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Rate Limit Exceeded |
| 500 | Internal Server Error |

## Pagination

All list endpoints support pagination with the following response format:

```json
{
    "success": true,
    "data": [...],
    "pagination": {
        "current_page": 1,
        "per_page": 20,
        "total": 100,
        "last_page": 5,
        "from": 1,
        "to": 20
    }
}
```

## Webhooks

### Available Webhooks

- `user.created` - User account created
- `user.updated` - User account updated
- `thread.created` - New thread created
- `post.created` - New post created
- `reaction.added` - Reaction added to post
- `message.sent` - Private message sent

### Webhook Payload

```json
{
    "event": "user.created",
    "data": {
        "user": {
            "id": 1,
            "username": "johndoe",
            "email": "user@example.com"
        }
    },
    "timestamp": "2023-01-01T00:00:00Z"
}
```

## SDKs

### JavaScript
```javascript
import ForumAPI from '@forum/api-client';

const api = new ForumAPI({
    baseURL: 'https://yourdomain.com/api/v1',
    apiKey: 'your-api-key'
});

// Get forums
const forums = await api.forums.list();

// Create thread
const thread = await api.threads.create({
    forum_id: 1,
    title: 'New Thread',
    content: 'Thread content'
});
```

### PHP
```php
use Forum\API\Client;

$api = new Client([
    'base_url' => 'https://yourdomain.com/api/v1',
    'api_key' => 'your-api-key'
]);

// Get forums
$forums = $api->forums()->list();

// Create thread
$thread = $api->threads()->create([
    'forum_id' => 1,
    'title' => 'New Thread',
    'content' => 'Thread content'
]);
```

## Support

For API support, please contact:
- Email: api-support@yourdomain.com
- Documentation: https://yourdomain.com/docs/api
- Status Page: https://status.yourdomain.com