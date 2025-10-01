<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?= htmlspecialchars($site_name ?? APP_NAME) ?></title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        .verification-message {
            font-size: 18px;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .verification-notice {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        .verification-notice h4 {
            margin: 0 0 10px 0;
            color: #155724;
        }
        .verification-notice p {
            margin: 0;
            color: #155724;
            font-size: 14px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        .expiry-info {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .expiry-info h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        .expiry-info p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        .benefits-list {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .benefits-list h4 {
            margin: 0 0 15px 0;
            color: #1976d2;
        }
        .benefits-list ul {
            margin: 0;
            padding-left: 20px;
            color: #1565c0;
        }
        .benefits-list li {
            margin-bottom: 8px;
        }
        .alternative-method {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .alternative-method h4 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .alternative-method p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
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
            .verification-message {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>✅ Verify Your Email</h1>
            <p>Complete your account setup</p>
        </div>
        
        <div class="email-body">
            <div class="verification-message">
                <p>Hi <?= htmlspecialchars($user_name ?? 'there') ?>,</p>
                <p>Welcome to <?= htmlspecialchars($site_name ?? APP_NAME) ?>! To complete your account setup and start using all features, please verify your email address.</p>
            </div>
            
            <div style="text-align: center;">
                <a href="<?= htmlspecialchars($verification_url ?? '#') ?>" class="cta-button">
                    Verify My Email
                </a>
            </div>
            
            <div class="expiry-info">
                <h4>⏰ Verification Link Expires</h4>
                <p>This verification link will expire on <strong><?= htmlspecialchars($expires_at ?? '24 hours from now') ?></strong>. Please verify your email before then to activate your account.</p>
            </div>
            
            <div class="verification-notice">
                <h4>🔒 Why Verify Your Email?</h4>
                <p>Email verification helps us ensure account security and enables important features like password recovery and notifications.</p>
            </div>
            
            <div class="benefits-list">
                <h4>🎉 After Verification, You'll Get:</h4>
                <ul>
                    <li>Full access to all community features</li>
                    <li>Ability to create posts and comments</li>
                    <li>Email notifications for replies and mentions</li>
                    <li>Password recovery options</li>
                    <li>Account security features</li>
                    <li>Access to private messages</li>
                </ul>
            </div>
            
            <div class="alternative-method">
                <h4>💡 Alternative Method</h4>
                <p>If the button above doesn't work, you can copy and paste this link into your browser:</p>
                <p style="word-break: break-all; background-color: #ffffff; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; border: 1px solid #dee2e6;">
                    <?= htmlspecialchars($verification_url ?? '#') ?>
                </p>
            </div>
            
            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 30px 0;">
                <h4 style="margin: 0 0 15px 0; color: #2c3e50;">❓ Didn't Request This?</h4>
                <p style="margin: 0; color: #6c757d; font-size: 14px;">
                    If you didn't create an account with <?= htmlspecialchars($site_name ?? APP_NAME) ?>, please ignore this email. 
                    No further action is required, and your email address will not be used for any communications.
                </p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <p style="color: #6c757d; font-size: 14px;">
                    Need help? Contact our 
                    <a href="mailto:<?= htmlspecialchars($support_email ?? MAIL_FROM_ADDRESS) ?>" style="color: #28a745;">Support Team</a> 
                    for assistance.
                </p>
            </div>
        </div>
        
        <div class="email-footer">
            <p><strong><?= htmlspecialchars($site_name ?? APP_NAME) ?></strong></p>
            <p>Connect, Discuss, Share</p>
            
            <div class="unsubscribe">
                <p>
                    This is a verification email for your <?= htmlspecialchars($site_name ?? APP_NAME) ?> account.
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