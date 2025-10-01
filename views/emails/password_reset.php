<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - <?= htmlspecialchars($site_name ?? APP_NAME) ?></title>
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
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
        .reset-message {
            font-size: 18px;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .security-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        .security-notice h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        .security-notice p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .expiry-info h4 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .expiry-info p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }
        .alternative-method {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .alternative-method h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        .alternative-method p {
            margin: 0;
            color: #1565c0;
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
            .reset-message {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🔒 Password Reset Request</h1>
            <p>Secure your account</p>
        </div>
        
        <div class="email-body">
            <div class="reset-message">
                <p>Hi <?= htmlspecialchars($user_name ?? 'there') ?>,</p>
                <p>We received a request to reset your password for your <?= htmlspecialchars($site_name ?? APP_NAME) ?> account.</p>
            </div>
            
            <div style="text-align: center;">
                <a href="<?= htmlspecialchars($reset_url ?? '#') ?>" class="cta-button">
                    Reset My Password
                </a>
            </div>
            
            <div class="expiry-info">
                <h4>⏰ Important: Link Expires Soon</h4>
                <p>This password reset link will expire on <strong><?= htmlspecialchars($expires_at ?? '1 hour from now') ?></strong>. Please use it before then to reset your password.</p>
            </div>
            
            <div class="security-notice">
                <h4>🛡️ Security Notice</h4>
                <p>If you didn't request this password reset, please ignore this email. Your password will remain unchanged. For additional security, consider enabling two-factor authentication in your account settings.</p>
            </div>
            
            <div class="alternative-method">
                <h4>💡 Alternative Method</h4>
                <p>If the button above doesn't work, you can copy and paste this link into your browser:</p>
                <p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;">
                    <?= htmlspecialchars($reset_url ?? '#') ?>
                </p>
            </div>
            
            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 30px 0;">
                <h4 style="margin: 0 0 15px 0; color: #2c3e50;">🔐 Password Security Tips</h4>
                <ul style="margin: 0; padding-left: 20px; color: #555;">
                    <li>Use a unique password for this account</li>
                    <li>Include a mix of uppercase, lowercase, numbers, and symbols</li>
                    <li>Make it at least 12 characters long</li>
                    <li>Don't reuse passwords from other accounts</li>
                    <li>Consider using a password manager</li>
                </ul>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <p style="color: #6c757d; font-size: 14px;">
                    Still having trouble? Contact our 
                    <a href="mailto:<?= htmlspecialchars($support_email ?? MAIL_FROM_ADDRESS) ?>" style="color: #dc3545;">Support Team</a> 
                    for assistance.
                </p>
            </div>
        </div>
        
        <div class="email-footer">
            <p><strong><?= htmlspecialchars($site_name ?? APP_NAME) ?></strong></p>
            <p>Connect, Discuss, Share</p>
            
            <div class="unsubscribe">
                <p>
                    This is a security-related email for your <?= htmlspecialchars($site_name ?? APP_NAME) ?> account.
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