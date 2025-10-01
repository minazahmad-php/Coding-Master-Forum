<?php
/**
 * Register Page View
 */

$this->layout('layouts/app', ['title' => 'Register - ' . APP_NAME]);
?>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-title">Join Our Community</h1>
                <p class="auth-subtitle">Create your account to get started</p>
            </div>
            
            <form class="auth-form" method="POST" action="/register">
                <?= View::csrfField() ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" 
                               id="first_name" 
                               name="first_name" 
                               class="form-input" 
                               placeholder="Enter your first name"
                               value="<?= View::escape($_POST['first_name'] ?? '') ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" 
                               id="last_name" 
                               name="last_name" 
                               class="form-input" 
                               placeholder="Enter your last name"
                               value="<?= View::escape($_POST['last_name'] ?? '') ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-input" 
                           placeholder="Choose a username"
                           value="<?= View::escape($_POST['username'] ?? '') ?>"
                           required>
                    <div class="form-help">This will be your public display name</div>
                </div>
                
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
                               placeholder="Create a password"
                               required>
                        <button type="button" class="password-toggle" data-password-toggle>
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill"></div>
                        </div>
                        <div class="strength-text">Password strength</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <div class="password-input-group">
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               class="form-input" 
                               placeholder="Confirm your password"
                               required>
                        <button type="button" class="password-toggle" data-password-toggle>
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" value="1" required>
                        <span class="checkbox-text">
                            I agree to the <a href="/terms" target="_blank" class="auth-link">Terms of Service</a> 
                            and <a href="/privacy" target="_blank" class="auth-link">Privacy Policy</a>
                        </span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="newsletter" value="1">
                        <span class="checkbox-text">Subscribe to our newsletter for updates</span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>
            
            <div class="auth-footer">
                <p class="auth-text">
                    Already have an account? 
                    <a href="/login" class="auth-link">Sign in here</a>
                </p>
            </div>
            
            <!-- Social Login -->
            <div class="social-login">
                <div class="social-divider">
                    <span>Or sign up with</span>
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
                <h2>Why Join Us?</h2>
                <p>Be part of a vibrant community where ideas flourish and connections are made.</p>
                
                <div class="info-features">
                    <div class="feature-item">
                        <i class="fas fa-comments"></i>
                        <div class="feature-content">
                            <h4>Rich Discussions</h4>
                            <p>Engage in meaningful conversations with like-minded people</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <div class="feature-content">
                            <h4>Active Community</h4>
                            <p>Connect with thousands of members worldwide</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <div class="feature-content">
                            <h4>Secure Platform</h4>
                            <p>Your privacy and security are our top priorities</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.password-strength {
    margin-top: 0.5rem;
}

.strength-bar {
    width: 100%;
    height: 4px;
    background: var(--border-color);
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.25rem;
}

.strength-fill {
    height: 100%;
    width: 0%;
    background: var(--error-color);
    transition: all var(--transition-fast);
}

.strength-fill.weak {
    width: 25%;
    background: var(--error-color);
}

.strength-fill.fair {
    width: 50%;
    background: var(--warning-color);
}

.strength-fill.good {
    width: 75%;
    background: var(--info-color);
}

.strength-fill.strong {
    width: 100%;
    background: var(--success-color);
}

.strength-text {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.feature-item i {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 0.25rem;
}

.feature-content h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.feature-content p {
    font-size: 0.9rem;
    opacity: 0.8;
    line-height: 1.4;
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .auth-container {
        grid-template-columns: 1fr;
        max-width: 400px;
    }
    
    .auth-info {
        display: none;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password strength checker
    const passwordInput = document.getElementById('password');
    const strengthBar = document.querySelector('.strength-fill');
    const strengthText = document.querySelector('.strength-text');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        
        strengthBar.className = 'strength-fill ' + strength.level;
        strengthText.textContent = strength.text;
    });
    
    function calculatePasswordStrength(password) {
        let score = 0;
        let feedback = [];
        
        if (password.length >= 8) score += 1;
        else feedback.push('at least 8 characters');
        
        if (/[a-z]/.test(password)) score += 1;
        else feedback.push('lowercase letters');
        
        if (/[A-Z]/.test(password)) score += 1;
        else feedback.push('uppercase letters');
        
        if (/[0-9]/.test(password)) score += 1;
        else feedback.push('numbers');
        
        if (/[^A-Za-z0-9]/.test(password)) score += 1;
        else feedback.push('special characters');
        
        if (score <= 1) {
            return { level: 'weak', text: 'Weak password' };
        } else if (score <= 2) {
            return { level: 'fair', text: 'Fair password' };
        } else if (score <= 3) {
            return { level: 'good', text: 'Good password' };
        } else {
            return { level: 'strong', text: 'Strong password' };
        }
    }
    
    // Password confirmation validation
    const passwordConfirmation = document.getElementById('password_confirmation');
    
    passwordConfirmation.addEventListener('input', function() {
        if (this.value && this.value !== passwordInput.value) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Password toggle functionality
    document.querySelectorAll('[data-password-toggle]').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    });
});
</script>