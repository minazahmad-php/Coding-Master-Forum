<?php $this->extend('layouts/app') ?>

<?php $this->section('title', 'Payment Dashboard') ?>

<?php $this->section('content') ?>
<div class="payment-dashboard">
    <div class="dashboard-header">
        <h1>Payment Dashboard</h1>
        <div class="user-balance">
            <span class="balance-label">Total Spent:</span>
            <span class="balance-amount">$<?= number_format(array_sum(array_column($payment_history, 'amount')), 2) ?></span>
        </div>
    </div>

    <!-- Current Subscription -->
    <div class="subscription-section">
        <h2>Current Subscription</h2>
        <?php if ($subscription && $subscription['status'] === 'active'): ?>
            <div class="subscription-card active">
                <div class="subscription-info">
                    <h3><?= htmlspecialchars($subscription['plan_name']) ?></h3>
                    <p class="subscription-status">Status: <span class="status-active">Active</span></p>
                    <p class="subscription-expires">Next billing: <?= date('F j, Y', strtotime($subscription['next_billing_at'])) ?></p>
                    <p class="subscription-price">$<?= number_format($subscription['price'], 2) ?>/<?= $subscription['billing_interval'] ?></p>
                </div>
                <div class="subscription-actions">
                    <button class="btn btn-secondary" onclick="showCancelModal()">Cancel Subscription</button>
                    <button class="btn btn-primary" onclick="showUpdatePaymentModal()">Update Payment Method</button>
                </div>
            </div>
        <?php else: ?>
            <div class="subscription-card inactive">
                <div class="subscription-info">
                    <h3>No Active Subscription</h3>
                    <p>Upgrade to premium to unlock all features</p>
                </div>
                <div class="subscription-actions">
                    <a href="/payments/plans" class="btn btn-primary">View Plans</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Premium Features -->
    <div class="features-section">
        <h2>Premium Features</h2>
        <div class="features-grid">
            <?php foreach ($premium_features as $feature): ?>
                <div class="feature-card <?= $feature['enabled'] ? 'enabled' : 'disabled' ?>">
                    <div class="feature-icon">
                        <i class="<?= $feature['icon'] ?>"></i>
                    </div>
                    <div class="feature-info">
                        <h4><?= htmlspecialchars($feature['name']) ?></h4>
                        <p><?= htmlspecialchars($feature['description']) ?></p>
                    </div>
                    <div class="feature-status">
                        <?php if ($feature['enabled']): ?>
                            <span class="status-enabled">Enabled</span>
                        <?php else: ?>
                            <span class="status-disabled">Requires Premium</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="payment-methods-section">
        <h2>Payment Methods</h2>
        <div class="payment-methods-list">
            <?php if (!empty($payment_methods)): ?>
                <?php foreach ($payment_methods as $method): ?>
                    <div class="payment-method-card <?= $method['is_default'] ? 'default' : '' ?>">
                        <div class="method-info">
                            <div class="method-type">
                                <i class="fas fa-credit-card"></i>
                                <span><?= ucfirst($method['brand']) ?> **** <?= $method['last_four'] ?></span>
                            </div>
                            <div class="method-details">
                                <span class="expires">Expires <?= $method['expires_at'] ?></span>
                                <?php if ($method['is_default']): ?>
                                    <span class="default-badge">Default</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="method-actions">
                            <?php if (!$method['is_default']): ?>
                                <button class="btn btn-sm btn-outline" onclick="setDefaultPaymentMethod(<?= $method['id'] ?>)">
                                    Set as Default
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-danger" onclick="removePaymentMethod(<?= $method['id'] ?>)">
                                Remove
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No payment methods added yet</p>
                </div>
            <?php endif; ?>
        </div>
        <button class="btn btn-primary" onclick="showAddPaymentMethodModal()">
            <i class="fas fa-plus"></i> Add Payment Method
        </button>
    </div>

    <!-- Recent Payments -->
    <div class="payments-section">
        <h2>Recent Payments</h2>
        <div class="payments-table-container">
            <?php if (!empty($payment_history)): ?>
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($payment_history, 0, 10) as $payment): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($payment['created_at'])) ?></td>
                                <td>
                                    <?= htmlspecialchars($payment['description'] ?: 'Payment') ?>
                                    <?php if ($payment['plan_name']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($payment['plan_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($payment['amount'], 2) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $payment['status'] ?>">
                                        <?= ucfirst($payment['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($payment['status'] === 'completed'): ?>
                                        <button class="btn btn-sm btn-outline" onclick="downloadInvoice(<?= $payment['id'] ?>)">
                                            <i class="fas fa-download"></i> Invoice
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="table-footer">
                    <a href="/payments/history" class="btn btn-outline">View All Payments</a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No payment history yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="cancelSubscriptionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Cancel Subscription</h3>
            <button class="modal-close" onclick="hideCancelModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to cancel your subscription?</p>
            <p><strong>Your subscription will remain active until the next billing date.</strong></p>
            <div class="form-group">
                <label for="cancelReason">Reason for cancellation (optional):</label>
                <textarea id="cancelReason" rows="3" placeholder="Help us improve..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="hideCancelModal()">Keep Subscription</button>
            <button class="btn btn-danger" onclick="cancelSubscription()">Cancel Subscription</button>
        </div>
    </div>
</div>

<div id="updatePaymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Payment Method</h3>
            <button class="modal-close" onclick="hideUpdatePaymentModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="paymentMethodForm">
                <div class="form-group">
                    <label for="cardNumber">Card Number</label>
                    <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiryDate">Expiry Date</label>
                        <input type="text" id="expiryDate" placeholder="MM/YY" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" placeholder="123" maxlength="4">
                    </div>
                </div>
                <div class="form-group">
                    <label for="cardName">Name on Card</label>
                    <input type="text" id="cardName" placeholder="John Doe">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="hideUpdatePaymentModal()">Cancel</button>
            <button class="btn btn-primary" onclick="updatePaymentMethod()">Update Payment Method</button>
        </div>
    </div>
</div>

<div id="addPaymentMethodModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Payment Method</h3>
            <button class="modal-close" onclick="hideAddPaymentMethodModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addPaymentMethodForm">
                <div class="form-group">
                    <label for="newCardNumber">Card Number</label>
                    <input type="text" id="newCardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="newExpiryDate">Expiry Date</label>
                        <input type="text" id="newExpiryDate" placeholder="MM/YY" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label for="newCvv">CVV</label>
                        <input type="text" id="newCvv" placeholder="123" maxlength="4">
                    </div>
                </div>
                <div class="form-group">
                    <label for="newCardName">Name on Card</label>
                    <input type="text" id="newCardName" placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="setAsDefault"> Set as default payment method
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="hideAddPaymentMethodModal()">Cancel</button>
            <button class="btn btn-primary" onclick="addPaymentMethod()">Add Payment Method</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/assets/css/payments.css">
<script src="/assets/js/payments.js"></script>
<?php $this->endSection() ?>