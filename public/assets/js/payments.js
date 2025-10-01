// Payment System JavaScript
class PaymentSystem {
    constructor() {
        this.currentPlan = null;
        this.billingMode = 'monthly';
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.formatCardInputs();
    }

    setupEventListeners() {
        // Card input formatting
        document.addEventListener('input', (e) => {
            if (e.target.id.includes('cardNumber') || e.target.id.includes('CardNumber')) {
                this.formatCardNumber(e.target);
            } else if (e.target.id.includes('expiryDate') || e.target.id.includes('ExpiryDate')) {
                this.formatExpiryDate(e.target);
            } else if (e.target.id.includes('cvv') || e.target.id.includes('Cvv')) {
                this.formatCvv(e.target);
            }
        });

        // Billing toggle
        const billingToggle = document.getElementById('billingToggle');
        if (billingToggle) {
            billingToggle.addEventListener('change', (e) => {
                this.toggleBilling(e.target.checked);
            });
        }

        // FAQ toggles
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const faqItem = question.parentElement;
                const answer = faqItem.querySelector('.faq-answer');
                const icon = question.querySelector('i');
                
                faqItem.classList.toggle('active');
                
                if (faqItem.classList.contains('active')) {
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    answer.style.maxHeight = '0';
                    icon.style.transform = 'rotate(0deg)';
                }
            });
        });
    }

    formatCardInputs() {
        // Format existing card number inputs
        document.querySelectorAll('input[id*="cardNumber"], input[id*="CardNumber"]').forEach(input => {
            this.formatCardNumber(input);
        });
    }

    formatCardNumber(input) {
        let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || '';
        
        if (formattedValue !== input.value) {
            input.value = formattedValue;
        }
    }

    formatExpiryDate(input) {
        let value = input.value.replace(/\D/g, '');
        
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        
        input.value = value;
    }

    formatCvv(input) {
        input.value = input.value.replace(/\D/g, '').substring(0, 4);
    }

    toggleBilling(isYearly) {
        this.billingMode = isYearly ? 'yearly' : 'monthly';
        
        // Update prices on plans page
        document.querySelectorAll('.price-amount').forEach(priceElement => {
            const monthlyPrice = parseFloat(priceElement.dataset.monthly);
            const yearlyPrice = parseFloat(priceElement.dataset.yearly);
            
            if (isYearly) {
                priceElement.textContent = '$' + Math.floor(yearlyPrice / 12);
            } else {
                priceElement.textContent = '$' + Math.floor(monthlyPrice);
            }
        });

        // Update period text
        document.querySelectorAll('.price-period').forEach(periodElement => {
            periodElement.textContent = isYearly ? '/month (billed yearly)' : '/month';
        });
    }

    selectPlan(planId, planName) {
        if (!this.isLoggedIn()) {
            window.location.href = '/login?redirect=' + encodeURIComponent(window.location.href);
            return;
        }

        this.currentPlan = {
            id: planId,
            name: planName,
            billing: this.billingMode
        };

        this.showPaymentModal(planId, planName);
    }

    showPaymentModal(planId, planName) {
        const modal = document.getElementById('paymentModal');
        const planNameElement = document.getElementById('selectedPlanName');
        const billingElement = document.getElementById('selectedBilling');
        const priceElement = document.getElementById('selectedPrice');

        if (modal && planNameElement && billingElement && priceElement) {
            planNameElement.textContent = planName;
            billingElement.textContent = this.billingMode === 'yearly' ? 'Yearly' : 'Monthly';
            
            // Get price from the plan card
            const planCard = document.querySelector(`[onclick*="selectPlan(${planId}"]`).closest('.plan-card');
            const priceAmount = planCard.querySelector('.price-amount').textContent;
            priceElement.textContent = priceAmount + (this.billingMode === 'yearly' ? '/month (billed yearly)' : '/month');

            modal.style.display = 'block';
        }
    }

    hidePaymentModal() {
        const modal = document.getElementById('paymentModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    async processSubscription() {
        const form = document.getElementById('subscriptionForm');
        const subscribeBtn = document.getElementById('subscribeBtn');
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Disable button and show loading
        subscribeBtn.disabled = true;
        subscribeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        try {
            // Create payment intent
            const response = await fetch('/payments/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify({
                    plan_id: this.currentPlan.id,
                    billing_interval: this.billingMode,
                    payment_method: this.getPaymentMethodData()
                })
            });

            const result = await response.json();

            if (result.success) {
                // Process payment
                const paymentResult = await this.processPaymentIntent(result.payment_intent);
                
                if (paymentResult.success) {
                    this.showSuccessMessage('Subscription activated successfully!');
                    setTimeout(() => {
                        window.location.href = '/payments/dashboard';
                    }, 2000);
                } else {
                    throw new Error(paymentResult.error || 'Payment failed');
                }
            } else {
                throw new Error(result.error || 'Subscription creation failed');
            }

        } catch (error) {
            this.showErrorMessage(error.message);
        } finally {
            // Re-enable button
            subscribeBtn.disabled = false;
            subscribeBtn.innerHTML = '<i class="fas fa-lock"></i> Subscribe Now';
        }
    }

    async processPaymentIntent(paymentIntent) {
        // This would integrate with a real payment processor like Stripe
        // For now, simulate a successful payment
        await new Promise(resolve => setTimeout(resolve, 2000));

        return {
            success: true,
            payment_intent_id: paymentIntent.id
        };
    }

    getPaymentMethodData() {
        return {
            card_number: document.getElementById('cardNumber').value.replace(/\s/g, ''),
            expiry_date: document.getElementById('expiryDate').value,
            cvv: document.getElementById('cvv').value,
            card_name: document.getElementById('cardName').value,
            billing_address: {
                address: document.getElementById('billingAddress').value,
                city: document.getElementById('billingCity').value,
                zip: document.getElementById('billingZip').value
            },
            save_method: document.getElementById('savePaymentMethod').checked
        };
    }

    async cancelSubscription() {
        const reason = document.getElementById('cancelReason').value;

        try {
            const response = await fetch('/payments/cancel-subscription', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify({ reason })
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccessMessage('Subscription cancelled successfully');
                this.hideCancelModal();
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                throw new Error(result.error || 'Cancellation failed');
            }

        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    async updatePaymentMethod() {
        const form = document.getElementById('paymentMethodForm');
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        try {
            const response = await fetch('/payments/update-payment-method', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify(this.getUpdatePaymentMethodData())
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccessMessage('Payment method updated successfully');
                this.hideUpdatePaymentModal();
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                throw new Error(result.error || 'Update failed');
            }

        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    getUpdatePaymentMethodData() {
        return {
            card_number: document.getElementById('cardNumber').value.replace(/\s/g, ''),
            expiry_date: document.getElementById('expiryDate').value,
            cvv: document.getElementById('cvv').value,
            card_name: document.getElementById('cardName').value
        };
    }

    async addPaymentMethod() {
        const form = document.getElementById('addPaymentMethodForm');
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        try {
            const response = await fetch('/payments/add-payment-method', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify(this.getAddPaymentMethodData())
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccessMessage('Payment method added successfully');
                this.hideAddPaymentMethodModal();
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                throw new Error(result.error || 'Failed to add payment method');
            }

        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    getAddPaymentMethodData() {
        return {
            card_number: document.getElementById('newCardNumber').value.replace(/\s/g, ''),
            expiry_date: document.getElementById('newExpiryDate').value,
            cvv: document.getElementById('newCvv').value,
            card_name: document.getElementById('newCardName').value,
            is_default: document.getElementById('setAsDefault').checked
        };
    }

    async setDefaultPaymentMethod(methodId) {
        try {
            const response = await fetch('/payments/set-default-payment-method', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify({ method_id: methodId })
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccessMessage('Default payment method updated');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error(result.error || 'Failed to update default payment method');
            }

        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    async removePaymentMethod(methodId) {
        if (!confirm('Are you sure you want to remove this payment method?')) {
            return;
        }

        try {
            const response = await fetch('/payments/remove-payment-method', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify({ method_id: methodId })
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccessMessage('Payment method removed');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error(result.error || 'Failed to remove payment method');
            }

        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    async downloadInvoice(paymentId) {
        try {
            const response = await fetch(`/payments/invoice/${paymentId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-Token': this.getCsrfToken()
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `invoice-${paymentId}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                throw new Error('Failed to download invoice');
            }

        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }

    // Modal management
    showCancelModal() {
        document.getElementById('cancelSubscriptionModal').style.display = 'block';
    }

    hideCancelModal() {
        document.getElementById('cancelSubscriptionModal').style.display = 'none';
    }

    showUpdatePaymentModal() {
        document.getElementById('updatePaymentModal').style.display = 'block';
    }

    hideUpdatePaymentModal() {
        document.getElementById('updatePaymentModal').style.display = 'none';
    }

    showAddPaymentMethodModal() {
        document.getElementById('addPaymentMethodModal').style.display = 'block';
    }

    hideAddPaymentMethodModal() {
        document.getElementById('addPaymentMethodModal').style.display = 'none';
    }

    // Utility methods
    isLoggedIn() {
        // This would check if user is logged in
        // For now, assume they are if we're on the dashboard
        return document.body.classList.contains('authenticated') || 
               window.location.pathname.includes('/payments/dashboard');
    }

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    showSuccessMessage(message) {
        this.showMessage(message, 'success');
    }

    showErrorMessage(message) {
        this.showMessage(message, 'error');
    }

    showMessage(message, type) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        // Add to page
        document.body.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);

        // Add animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
    }
}

// Global functions for onclick handlers
function selectPlan(planId, planName) {
    window.paymentSystem.selectPlan(planId, planName);
}

function processSubscription() {
    window.paymentSystem.processSubscription();
}

function hidePaymentModal() {
    window.paymentSystem.hidePaymentModal();
}

function cancelSubscription() {
    window.paymentSystem.cancelSubscription();
}

function showCancelModal() {
    window.paymentSystem.showCancelModal();
}

function hideCancelModal() {
    window.paymentSystem.hideCancelModal();
}

function updatePaymentMethod() {
    window.paymentSystem.updatePaymentMethod();
}

function showUpdatePaymentModal() {
    window.paymentSystem.showUpdatePaymentModal();
}

function hideUpdatePaymentModal() {
    window.paymentSystem.hideUpdatePaymentModal();
}

function addPaymentMethod() {
    window.paymentSystem.addPaymentMethod();
}

function showAddPaymentMethodModal() {
    window.paymentSystem.showAddPaymentMethodModal();
}

function hideAddPaymentMethodModal() {
    window.paymentSystem.hideAddPaymentMethodModal();
}

function setDefaultPaymentMethod(methodId) {
    window.paymentSystem.setDefaultPaymentMethod(methodId);
}

function removePaymentMethod(methodId) {
    window.paymentSystem.removePaymentMethod(methodId);
}

function downloadInvoice(paymentId) {
    window.paymentSystem.downloadInvoice(paymentId);
}

function downgradePlan(planId) {
    if (confirm('Are you sure you want to downgrade to the free plan? You will lose access to premium features.')) {
        window.paymentSystem.selectPlan(planId, 'Free');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.paymentSystem = new PaymentSystem();
});

// Close modals when clicking outside
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

// Add toast styles
const toastStyles = `
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 10000;
    transform: translateX(400px);
    opacity: 0;
    transition: all 0.3s ease;
    max-width: 400px;
}

.toast.show {
    transform: translateX(0);
    opacity: 1;
}

.toast-success {
    border-left: 4px solid #28a745;
}

.toast-error {
    border-left: 4px solid #dc3545;
}

.toast-content {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.toast-success .fa-check-circle {
    color: #28a745;
}

.toast-error .fa-exclamation-circle {
    color: #dc3545;
}

.toast-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toast-close:hover {
    color: #333;
}
`;

// Add toast styles to page
const styleSheet = document.createElement('style');
styleSheet.textContent = toastStyles;
document.head.appendChild(styleSheet);