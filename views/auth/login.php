<?php
/**
 * Login Page View
 */

$this->layout('layouts/app', ['title' => 'Login - ' . APP_NAME]);
?>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to your account</p>
            </div>
            
            <form class="auth-form" method="POST" action="/login">
                <?= View::csrfField() ?>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-input" 
                           placeholder="Enter your email"
                           value="<?= View::escape($_POST['email'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input-group">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-input" 
                               placeholder="Enter your password"
                               required>
                        <button type="button" class="password-toggle" data-password-toggle>
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span class="checkbox-text">Remember me</span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            
            <div class="auth-footer">
                <a href="/forgot-password" class="auth-link">Forgot your password?</a>
                <p class="auth-text">
                    Don't have an account? 
                    <a href="/register" class="auth-link">Sign up here</a>
                </p>
            </div>
            
            <!-- Social Login -->
            <div class="social-login">
                <div class="social-divider">
                    <span>Or continue with</span>
                </div>
                
                <div class="social-buttons">
                    <a href="/auth/google" class="btn btn-social btn-google">
                        <i class="fab fa-google"></i>
                        Google
                    </a>
                    <a href="/auth/facebook" class="btn btn-social btn-facebook">
                        <i class="fab fa-facebook"></i>
                        Facebook
                    </a>
                    <a href="/auth/twitter" class="btn btn-social btn-twitter">
                        <i class="fab fa-twitter"></i>
                        Twitter
                    </a>
                </div>
            </div>
        </div>
        
        <div class="auth-info">
            <div class="info-content">
                <h2>Join Our Community</h2>
                <p>Connect with thousands of members, share your thoughts, and discover new ideas.</p>
                
                <div class="info-features">
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <span>Active Community</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-comments"></i>
                        <span>Rich Discussions</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure Platform</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
}

.auth-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    max-width: 1000px;
    width: 100%;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}

.auth-card {
    padding: 3rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.auth-subtitle {
    color: var(--text-secondary);
    font-size: 1rem;
}

.auth-form {
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-primary);
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: 1rem;
    transition: border-color var(--transition-fast);
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.password-input-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0.25rem;
}

.password-toggle:hover {
    color: var(--text-primary);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-color);
}

.checkbox-text {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.btn-block {
    width: 100%;
    padding: 0.875rem 1rem;
    font-size: 1rem;
    font-weight: 600;
}

.btn-block i {
    margin-right: 0.5rem;
}

.auth-footer {
    text-align: center;
}

.auth-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.auth-link:hover {
    text-decoration: underline;
}

.auth-text {
    color: var(--text-secondary);
    margin-top: 1rem;
}

.social-login {
    margin-top: 2rem;
}

.social-divider {
    text-align: center;
    margin-bottom: 1.5rem;
    position: relative;
}

.social-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--border-color);
}

.social-divider span {
    background: white;
    padding: 0 1rem;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.social-buttons {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.btn-social {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border: 2px solid var(--border-color);
    background: white;
    color: var(--text-primary);
    text-decoration: none;
    border-radius: var(--radius-md);
    font-weight: 500;
    transition: all var(--transition-fast);
}

.btn-social:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.btn-google:hover {
    border-color: #db4437;
    color: #db4437;
}

.btn-facebook:hover {
    border-color: #4267B2;
    color: #4267B2;
}

.btn-twitter:hover {
    border-color: #1DA1F2;
    color: #1DA1F2;
}

.auth-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem;
    display: flex;
    align-items: center;
}

.info-content h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.info-content p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.info-features {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1rem;
}

.feature-item i {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
}

/* Responsive Design */
@media (max-width: 768px) {
    .auth-container {
        grid-template-columns: 1fr;
        max-width: 400px;
    }
    
    .auth-info {
        display: none;
    }
    
    .auth-card {
        padding: 2rem;
    }
    
    .auth-title {
        font-size: 1.5rem;
    }
    
    .social-buttons {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .auth-page {
        padding: 1rem;
    }
    
    .auth-card {
        padding: 1.5rem;
    }
}