<?php $this->extend('layouts/app') ?>

<?php $this->section('title', 'Subscription Plans') ?>

<?php $this->section('content') ?>
<div class="plans-page">
    <div class="plans-header">
        <h1>Choose Your Plan</h1>
        <p>Unlock premium features and take your forum experience to the next level</p>
        
        <div class="billing-toggle">
            <label class="toggle-label">
                <span class="billing-option">Monthly</span>
                <input type="checkbox" id="billingToggle">
                <span class="toggle-slider"></span>
                <span class="billing-option">
                    Yearly 
                    <span class="discount-badge">Save 20%</span>
                </span>
            </label>
        </div>
    </div>

    <div class="plans-grid">
        <?php foreach ($plans as $plan): ?>
            <div class="plan-card <?= $plan['is_popular'] ? 'popular' : '' ?> <?= $current_plan && $current_plan['plan_id'] == $plan['id'] ? 'current' : '' ?>">
                <?php if ($plan['is_popular']): ?>
                    <div class="popular-badge">Most Popular</div>
                <?php endif; ?>
                
                <?php if ($current_plan && $current_plan['plan_id'] == $plan['id']): ?>
                    <div class="current-badge">Current Plan</div>
                <?php endif; ?>

                <div class="plan-header">
                    <h3 class="plan-name"><?= htmlspecialchars($plan['name']) ?></h3>
                    <div class="plan-price">
                        <span class="price-amount" data-monthly="<?= $plan['monthly_price'] ?>" data-yearly="<?= $plan['yearly_price'] ?>">
                            $<?= number_format($plan['monthly_price'], 0) ?>
                        </span>
                        <span class="price-period">/month</span>
                    </div>
                    <p class="plan-description"><?= htmlspecialchars($plan['description']) ?></p>
                </div>

                <div class="plan-features">
                    <ul class="features-list">
                        <?php foreach ($plan['features'] as $feature): ?>
                            <li class="feature-item <?= $feature['included'] ? 'included' : 'not-included' ?>">
                                <i class="fas <?= $feature['included'] ? 'fa-check' : 'fa-times' ?>"></i>
                                <span><?= htmlspecialchars($feature['name']) ?></span>
                                <?php if ($feature['limit']): ?>
                                    <small class="feature-limit"><?= htmlspecialchars($feature['limit']) ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="plan-footer">
                    <?php if ($current_plan && $current_plan['plan_id'] == $plan['id']): ?>
                        <button class="btn btn-outline btn-full-width" disabled>Current Plan</button>
                    <?php elseif ($plan['id'] == 1): // Free plan ?>
                        <?php if ($current_plan): ?>
                            <button class="btn btn-secondary btn-full-width" onclick="downgradePlan(<?= $plan['id'] ?>)">
                                Downgrade to Free
                            </button>
                        <?php else: ?>
                            <button class="btn btn-outline btn-full-width" disabled>Free Plan</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-primary btn-full-width" onclick="selectPlan(<?= $plan['id'] ?>, '<?= htmlspecialchars($plan['name']) ?>')">
                            <?= $current_plan ? 'Upgrade' : 'Get Started' ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Feature Comparison Table -->
    <div class="features-comparison">
        <h2>Feature Comparison</h2>
        <div class="comparison-table-container">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Features</th>
                        <?php foreach ($plans as $plan): ?>
                            <th><?= htmlspecialchars($plan['name']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($features as $featureName => $featureData): ?>
                        <tr>
                            <td class="feature-name">
                                <?= htmlspecialchars($featureName) ?>
                                <?php if ($featureData['description']): ?>
                                    <div class="feature-tooltip">
                                        <i class="fas fa-info-circle"></i>
                                        <span class="tooltip-text"><?= htmlspecialchars($featureData['description']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <?php foreach ($plans as $plan): ?>
                                <td class="feature-value">
                                    <?php 
                                    $planFeature = $featureData['plans'][$plan['id']] ?? false;
                                    if ($planFeature === true): ?>
                                        <i class="fas fa-check text-success"></i>
                                    <?php elseif ($planFeature === false): ?>
                                        <i class="fas fa-times text-muted"></i>
                                    <?php else: ?>
                                        <?= htmlspecialchars($planFeature) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-list">
            <div class="faq-item">
                <div class="faq-question">
                    <h4>Can I change my plan at any time?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Yes, you can upgrade or downgrade your plan at any time. Changes will be reflected in your next billing cycle.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>What payment methods do you accept?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>We accept all major credit cards (Visa, MasterCard, American Express), PayPal, and bank transfers.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>Is there a money-back guarantee?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Yes, we offer a 30-day money-back guarantee. If you're not satisfied, we'll refund your payment in full.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>What happens to my data if I cancel?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Your account and data remain intact. You'll simply lose access to premium features but can reactivate anytime.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Complete Your Subscription</h3>
            <button class="modal-close" onclick="hidePaymentModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="payment-summary">
                <h4>Plan Summary</h4>
                <div class="summary-item">
                    <span class="summary-label">Plan:</span>
                    <span class="summary-value" id="selectedPlanName"></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Billing:</span>
                    <span class="summary-value" id="selectedBilling"></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total:</span>
                    <span class="summary-value" id="selectedPrice"></span>
                </div>
            </div>

            <div class="payment-form">
                <h4>Payment Information</h4>
                <form id="subscriptionForm">
                    <div class="form-group">
                        <label for="cardNumber">Card Number</label>
                        <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiryDate">Expiry Date</label>
                            <input type="text" id="expiryDate" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" placeholder="123" maxlength="4" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="cardName">Name on Card</label>
                        <input type="text" id="cardName" placeholder="John Doe" required>
                    </div>
                    <div class="form-group">
                        <label for="billingAddress">Billing Address</label>
                        <input type="text" id="billingAddress" placeholder="123 Main St" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="billingCity">City</label>
                            <input type="text" id="billingCity" placeholder="New York" required>
                        </div>
                        <div class="form-group">
                            <label for="billingZip">ZIP Code</label>
                            <input type="text" id="billingZip" placeholder="10001" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="savePaymentMethod" checked>
                            <span class="checkmark"></span>
                            Save this payment method for future use
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="agreeTerms" required>
                            <span class="checkmark"></span>
                            I agree to the <a href="/terms" target="_blank">Terms of Service</a> and <a href="/privacy" target="_blank">Privacy Policy</a>
                        </label>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="hidePaymentModal()">Cancel</button>
            <button class="btn btn-primary" onclick="processSubscription()" id="subscribeBtn">
                <i class="fas fa-lock"></i> Subscribe Now
            </button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/assets/css/plans.css">
<script src="/assets/js/plans.js"></script>
<?php $this->endSection() ?>