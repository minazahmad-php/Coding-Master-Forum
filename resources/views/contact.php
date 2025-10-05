<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h1 class="h4 mb-0">Contact Us</h1>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Get in Touch</h5>
                        <p>Have a question, suggestion, or need help? We'd love to hear from you!</p>
                        
                        <div class="contact-info">
                            <div class="mb-3">
                                <i class="fas fa-envelope me-2"></i>
                                <strong>Email:</strong> 
                                <a href="mailto:contact@coding-master.infy.uk">contact@coding-master.infy.uk</a>
                            </div>
                            
                            <div class="mb-3">
                                <i class="fas fa-shield-alt me-2"></i>
                                <strong>Moderators:</strong> 
                                <a href="mailto:moderators@coding-master.infy.uk">moderators@coding-master.infy.uk</a>
                            </div>
                            
                            <div class="mb-3">
                                <i class="fas fa-cog me-2"></i>
                                <strong>Technical Support:</strong> 
                                <a href="mailto:support@coding-master.infy.uk">support@coding-master.infy.uk</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Send Message</h5>
                        <form method="POST" action="<?= url('/contact') ?>">
                            <?= csrf_field() ?>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= old('name') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= old('email') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <select class="form-select" id="subject" name="subject" required>
                                    <option value="">Choose a subject</option>
                                    <option value="general" <?= old('subject') === 'general' ? 'selected' : '' ?>>General Question</option>
                                    <option value="technical" <?= old('subject') === 'technical' ? 'selected' : '' ?>>Technical Support</option>
                                    <option value="moderation" <?= old('subject') === 'moderation' ? 'selected' : '' ?>>Moderation Issue</option>
                                    <option value="bug" <?= old('subject') === 'bug' ? 'selected' : '' ?>>Bug Report</option>
                                    <option value="feature" <?= old('subject') === 'feature' ? 'selected' : '' ?>>Feature Request</option>
                                    <option value="other" <?= old('subject') === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" 
                                          placeholder="Please describe your question or issue in detail..." required><?= old('message') ?></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i>Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-12">
                        <h5>Frequently Asked Questions</h5>
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq1">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                        How do I register for an account?
                                    </button>
                                </h2>
                                <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Click the "Register" button in the top navigation, fill out the registration form with your username, email, and password, then click "Create Account".
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                        How do I create a new thread?
                                    </button>
                                </h2>
                                <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Navigate to the forum where you want to post, click "New Thread", enter a title and your message, then click "Create Thread".
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faq3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                        How do I report inappropriate content?
                                    </button>
                                </h2>
                                <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Click the "Report" button on any post or thread, select the reason for reporting, add any additional details, and submit the report.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>