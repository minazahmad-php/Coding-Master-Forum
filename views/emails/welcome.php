<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to <?= htmlspecialchars($site_name ?? APP_NAME) ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .email-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .email-body {
            padding: 40px 30px;
        }
        .welcome-message {
            font-size: 18px;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .features-list {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
        }
        .features-list h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 20px;
        }
        .features-list ul {
            margin: 0;
            padding-left: 20px;
        }
        .features-list li {
            margin-bottom: 10px;
            color: #555;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            opacity: 0.9;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .email-footer p {
            margin: 0 0 10px 0;
            color: #6c757d;
            font-size: 14px;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
        }
        .social-links a:hover {
            color: #667eea;
        }
        .unsubscribe {
            font-size: 12px;
            color: #adb5bd;
            margin-top: 20px;
        }
        .unsubscribe a {
            color: #adb5bd;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .email-container {
                margin: 0;
                box-shadow: none;
            }
            .email-header, .email-body, .email-footer {
                padding: 20px;
            }
            .email-header h1 {
                font-size: 24px;
            }
            .welcome-message {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Welcome to <?= htmlspecialchars($site_name ?? APP_NAME) ?>!</h1>
            <p>Your journey starts here</p>
        </div>
        
        <div class="email-body">
            <div class="welcome-message">
                <p>Hi <?= htmlspecialchars($user_name ?? 'there') ?>,</p>
                <p>Welcome to <?= htmlspecialchars($site_name ?? APP_NAME) ?>! We're thrilled to have you join our amazing community of thinkers, creators, and innovators.</p>
            </div>
            
            <div class="features-list">
                <h3>🚀 What you can do now:</h3>
                <ul>
                    <li><strong>Start Discussions:</strong> Create posts and share your thoughts with the community</li>
                    <li><strong>Connect:</strong> Follow interesting people and build your network</li>
                    <li><strong>Discover:</strong> Explore trending topics and popular discussions</li>
                    <li><strong>Learn:</strong> Ask questions and get answers from experts</li>
                    <li><strong>Share:</strong> Upload images, videos, and documents</li>
                    <li><strong>Earn:</strong> Build your reputation and unlock achievements</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="<?= htmlspecialchars($login_url ?? APP_URL . '/login') ?>" class="cta-button">
                    Get Started Now
                </a>
            </div>
            
            <div style="background-color: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin: 30px 0; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0; color: #1976d2;">💡 Pro Tip</h4>
                <p style="margin: 0; color: #1565c0;">Complete your profile and add a profile picture to get more engagement from other members!</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <p style="color: #6c757d; font-size: 14px;">
                    Need help getting started? Check out our 
                    <a href="<?= htmlspecialchars($site_url ?? APP_URL) ?>/help" style="color: #667eea;">Help Center</a> 
                    or contact our 
                    <a href="mailto:<?= htmlspecialchars($support_email ?? MAIL_FROM_ADDRESS) ?>" style="color: #667eea;">Support Team</a>.
                </p>
            </div>
        </div>
        
        <div class="email-footer">
            <p><strong><?= htmlspecialchars($site_name ?? APP_NAME) ?></strong></p>
            <p>Connect, Discuss, Share</p>
            
            <div class="social-links">
                <a href="<?= htmlspecialchars($site_url ?? APP_URL) ?>/about">About</a>
                <a href="<?= htmlspecialchars($site_url ?? APP_URL) ?>/contact">Contact</a>
                <a href="<?= htmlspecialchars($site_url ?? APP_URL) ?>/privacy">Privacy</a>
                <a href="<?= htmlspecialchars($site_url ?? APP_URL) ?>/terms">Terms</a>
            </div>
            
            <div class="unsubscribe">
                <p>
                    You received this email because you signed up for <?= htmlspecialchars($site_name ?? APP_NAME) ?>.
                    <br>
                    <a href="<?= htmlspecialchars($site_url ?? APP_URL) ?>/unsubscribe">Unsubscribe</a> | 
                    <a href="<?= htmlspecialchars($site_url ?? APP_URL) ?>/preferences">Email Preferences</a>
                </p>
                <p>&copy; <?= $current_year ?? date('Y') ?> <?= htmlspecialchars($site_name ?? APP_NAME) ?>. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>