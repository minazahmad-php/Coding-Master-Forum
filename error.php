<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$errorCode = $_GET['code'] ?? 404;
$errorMessages = [
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Page Not Found',
    405 => 'Method Not Allowed',
    429 => 'Too Many Requests',
    500 => 'Internal Server Error',
    503 => 'Service Unavailable'
];

$errorTitle = $errorMessages[$errorCode] ?? 'Error';
$errorDescription = '';

switch ($errorCode) {
    case 400:
        $errorDescription = 'The request was invalid or cannot be served.';
        break;
    case 401:
        $errorDescription = 'You need to be logged in to access this page.';
        break;
    case 403:
        $errorDescription = 'You do not have permission to access this page.';
        break;
    case 404:
        $errorDescription = 'The page you are looking for does not exist.';
        break;
    case 405:
        $errorDescription = 'The request method is not allowed for this resource.';
        break;
    case 429:
        $errorDescription = 'You have made too many requests. Please try again later.';
        break;
    case 500:
        $errorDescription = 'An internal server error occurred. Please try again later.';
        break;
    case 503:
        $errorDescription = 'The service is temporarily unavailable due to maintenance.';
        break;
    default:
        $errorDescription = 'An unexpected error occurred.';
}

http_response_code($errorCode);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $errorCode ?> - <?= $errorTitle ?> | <?= SITE_NAME ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            line-height: 1;
        }
        
        .error-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #2d3748;
        }
        
        .error-description {
            font-size: 1.1rem;
            color: #718096;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f7fafc;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: #edf2f7;
            border-color: #cbd5e0;
        }
        
        .home-icon {
            width: 24px;
            height: 24px;
            margin-right: 0.5rem;
            vertical-align: middle;
        }
        
        @media (max-width: 480px) {
            .error-container {
                padding: 2rem;
            }
            
            .error-code {
                font-size: 6rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?= $errorCode ?></div>
        <h1 class="error-title"><?= $errorTitle ?></h1>
        <p class="error-description"><?= $errorDescription ?></p>
        
        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                <svg class="home-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                </svg>
                Go Home
            </a>
            
            <?php if ($errorCode === 404): ?>
                <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            <?php endif; ?>
            
            <?php if ($errorCode === 401): ?>
                <a href="/login" class="btn btn-secondary">Login</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-redirect for certain error codes
        <?php if ($errorCode === 401): ?>
            setTimeout(() => {
                window.location.href = '/login';
            }, 5000);
        <?php endif; ?>
        
        // Log error for analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'error', {
                'error_code': <?= $errorCode ?>,
                'error_message': '<?= $errorTitle ?>'
            });
        }
    </script>
</body>
</html>