<?php
$content = ob_get_clean();
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h1 class="h4 mb-0">Forum Rules</h1>
            </div>
            <div class="card-body">
                <div class="rules-content">
                    <h5>1. Respect and Courtesy</h5>
                    <p>Treat all members with respect and courtesy. Personal attacks, harassment, or abusive language will not be tolerated.</p>
                    
                    <h5>2. Stay On Topic</h5>
                    <p>Keep discussions relevant to the forum category and thread topic. Off-topic posts may be moved or removed.</p>
                    
                    <h5>3. No Spam or Advertising</h5>
                    <p>Do not post spam, advertisements, or promotional content without permission. Self-promotion should be relevant and helpful.</p>
                    
                    <h5>4. Use Appropriate Language</h5>
                    <p>Keep language appropriate for all audiences. Excessive profanity or offensive content is not allowed.</p>
                    
                    <h5>5. No Duplicate Posts</h5>
                    <p>Do not create multiple threads on the same topic. Search for existing discussions before posting.</p>
                    
                    <h5>6. Respect Privacy</h5>
                    <p>Do not share personal information of other members or post private messages publicly.</p>
                    
                    <h5>7. Follow Copyright Laws</h5>
                    <p>Do not post copyrighted material without permission. Give credit to original sources when appropriate.</p>
                    
                    <h5>8. Report Violations</h5>
                    <p>If you see a rule violation, report it to moderators rather than responding publicly.</p>
                    
                    <h5>9. Moderator Decisions</h5>
                    <p>Moderator decisions are final. If you disagree, contact them privately rather than arguing publicly.</p>
                    
                    <h5>10. Account Responsibility</h5>
                    <p>You are responsible for all activity on your account. Keep your login credentials secure.</p>
                </div>
                
                <div class="mt-4">
                    <h5>Consequences of Rule Violations</h5>
                    <ul>
                        <li><strong>Warning:</strong> First-time minor violations may result in a warning.</li>
                        <li><strong>Post Removal:</strong> Inappropriate posts may be removed without notice.</li>
                        <li><strong>Temporary Ban:</strong> Repeated violations may result in a temporary suspension.</li>
                        <li><strong>Permanent Ban:</strong> Severe or repeated violations may result in permanent removal.</li>
                    </ul>
                </div>
                
                <div class="mt-4">
                    <h5>Contact Information</h5>
                    <p>If you have questions about these rules or need to report violations, please contact:</p>
                    <ul>
                        <li>Email: <a href="mailto:moderators@coding-master.infy.uk">moderators@coding-master.infy.uk</a></li>
                        <li>Private Message: Contact any moderator directly</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rules-content h5 {
    color: #007bff;
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
}

.rules-content h5:first-child {
    margin-top: 0;
}

.rules-content p {
    margin-bottom: 1rem;
    line-height: 1.6;
}

.rules-content ul {
    margin-bottom: 1rem;
}

.rules-content li {
    margin-bottom: 0.5rem;
}
</style>

<?php
$content = ob_get_clean();
include 'layouts/app.php';
?>