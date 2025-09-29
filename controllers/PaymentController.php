<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\View;
use Core\Auth;
use Services\PaymentSystemService;
use Services\PaymentGatewayIntegrationService;
use Services\SubscriptionManagementService;
use Services\PremiumFeaturesService;

class PaymentController extends Controller
{
    private PaymentSystemService $paymentService;
    private PaymentGatewayIntegrationService $gatewayService;
    private SubscriptionManagementService $subscriptionService;
    private PremiumFeaturesService $premiumService;

    public function __construct()
    {
        parent::__construct();
        $this->paymentService = new PaymentSystemService();
        $this->gatewayService = new PaymentGatewayIntegrationService();
        $this->subscriptionService = new SubscriptionManagementService();
        $this->premiumService = new PremiumFeaturesService();
    }

    public function dashboard()
    {
        $user = Auth::user();
        
        $data = [
            'user' => $user,
            'subscription' => $this->subscriptionService->getUserSubscription($user['id']),
            'payment_history' => $this->paymentService->getUserPayments($user['id']),
            'premium_features' => $this->premiumService->getAvailableFeatures(),
            'subscription_plans' => $this->subscriptionService->getAvailablePlans(),
            'payment_methods' => $this->paymentService->getUserPaymentMethods($user['id'])
        ];

        return View::render('payments/dashboard', $data);
    }

    public function plans()
    {
        $data = [
            'plans' => $this->subscriptionService->getAvailablePlans(),
            'features' => $this->premiumService->getFeatureComparison(),
            'current_plan' => null
        ];

        if (Auth::check()) {
            $user = Auth::user();
            $data['current_plan'] = $this->subscriptionService->getUserSubscription($user['id']);
        }

        return View::render('payments/plans', $data);
    }

    public function subscribe()
    {
        if (!Auth::check()) {
            return $this->redirectToLogin();
        }

        $planId = $this->request->input('plan_id');
        $paymentMethod = $this->request->input('payment_method');
        
        if (!$planId) {
            return $this->jsonError('Plan ID is required', 400);
        }

        try {
            $user = Auth::user();
            $plan = $this->subscriptionService->getPlan($planId);
            
            if (!$plan) {
                return $this->jsonError('Invalid plan selected', 400);
            }

            // Create payment intent
            $paymentIntent = $this->paymentService->createPaymentIntent([
                'amount' => $plan['price'],
                'currency' => 'usd',
                'user_id' => $user['id'],
                'plan_id' => $planId,
                'description' => "Subscription to {$plan['name']} plan"
            ]);

            return $this->jsonResponse([
                'success' => true,
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent['client_secret']
            ]);

        } catch (\Exception $e) {
            return $this->jsonError('Failed to create subscription: ' . $e->getMessage(), 500);
        }
    }

    public function processPayment()
    {
        if (!Auth::check()) {
            return $this->jsonError('Authentication required', 401);
        }

        $paymentIntentId = $this->request->input('payment_intent_id');
        $planId = $this->request->input('plan_id');

        if (!$paymentIntentId || !$planId) {
            return $this->jsonError('Missing required parameters', 400);
        }

        try {
            $user = Auth::user();
            
            // Verify payment with gateway
            $paymentStatus = $this->gatewayService->verifyPayment($paymentIntentId);
            
            if ($paymentStatus['status'] === 'succeeded') {
                // Create subscription
                $subscription = $this->subscriptionService->createSubscription([
                    'user_id' => $user['id'],
                    'plan_id' => $planId,
                    'payment_intent_id' => $paymentIntentId,
                    'status' => 'active',
                    'starts_at' => date('Y-m-d H:i:s'),
                    'expires_at' => $this->calculateExpirationDate($planId)
                ]);

                // Record payment
                $this->paymentService->recordPayment([
                    'user_id' => $user['id'],
                    'subscription_id' => $subscription['id'],
                    'amount' => $paymentStatus['amount'],
                    'currency' => $paymentStatus['currency'],
                    'payment_method' => $paymentStatus['payment_method'],
                    'gateway_transaction_id' => $paymentIntentId,
                    'status' => 'completed'
                ]);

                // Enable premium features
                $this->premiumService->enableFeaturesForUser($user['id'], $planId);

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'subscription' => $subscription
                ]);
            } else {
                return $this->jsonError('Payment failed: ' . $paymentStatus['failure_reason'], 400);
            }

        } catch (\Exception $e) {
            return $this->jsonError('Payment processing failed: ' . $e->getMessage(), 500);
        }
    }

    public function cancelSubscription()
    {
        if (!Auth::check()) {
            return $this->jsonError('Authentication required', 401);
        }

        try {
            $user = Auth::user();
            $subscription = $this->subscriptionService->getUserSubscription($user['id']);

            if (!$subscription || $subscription['status'] !== 'active') {
                return $this->jsonError('No active subscription found', 400);
            }

            // Cancel subscription
            $this->subscriptionService->cancelSubscription($subscription['id']);

            // Disable premium features at the end of billing period
            $this->premiumService->scheduleFeatureDisable($user['id'], $subscription['expires_at']);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Subscription cancelled successfully',
                'expires_at' => $subscription['expires_at']
            ]);

        } catch (\Exception $e) {
            return $this->jsonError('Failed to cancel subscription: ' . $e->getMessage(), 500);
        }
    }

    public function updatePaymentMethod()
    {
        if (!Auth::check()) {
            return $this->jsonError('Authentication required', 401);
        }

        $paymentMethodId = $this->request->input('payment_method_id');
        
        if (!$paymentMethodId) {
            return $this->jsonError('Payment method ID is required', 400);
        }

        try {
            $user = Auth::user();
            
            // Update payment method
            $this->paymentService->updateUserPaymentMethod($user['id'], $paymentMethodId);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Payment method updated successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonError('Failed to update payment method: ' . $e->getMessage(), 500);
        }
    }

    public function paymentHistory()
    {
        if (!Auth::check()) {
            return $this->redirectToLogin();
        }

        $user = Auth::user();
        $page = (int)($this->request->input('page') ?? 1);
        $limit = 20;

        $data = [
            'payments' => $this->paymentService->getUserPayments($user['id'], $page, $limit),
            'total_spent' => $this->paymentService->getUserTotalSpent($user['id']),
            'current_page' => $page
        ];

        return View::render('payments/history', $data);
    }

    public function downloadInvoice()
    {
        if (!Auth::check()) {
            return $this->jsonError('Authentication required', 401);
        }

        $paymentId = $this->request->input('payment_id');
        
        if (!$paymentId) {
            return $this->jsonError('Payment ID is required', 400);
        }

        try {
            $user = Auth::user();
            $payment = $this->paymentService->getPayment($paymentId);

            if (!$payment || $payment['user_id'] !== $user['id']) {
                return $this->jsonError('Payment not found', 404);
            }

            // Generate PDF invoice
            $invoicePdf = $this->paymentService->generateInvoice($payment);

            return response($invoicePdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice-' . $payment['id'] . '.pdf"'
            ]);

        } catch (\Exception $e) {
            return $this->jsonError('Failed to generate invoice: ' . $e->getMessage(), 500);
        }
    }

    public function webhook()
    {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = $this->gatewayService->verifyWebhookSignature($payload, $signature);

            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSuccess($event['data']['object']);
                    break;
                
                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event['data']['object']);
                    break;
                
                case 'invoice.payment_succeeded':
                    $this->handleSubscriptionRenewal($event['data']['object']);
                    break;
                
                case 'customer.subscription.deleted':
                    $this->handleSubscriptionCancelled($event['data']['object']);
                    break;
                
                default:
                    // Log unhandled webhook event
                    error_log("Unhandled webhook event: " . $event['type']);
            }

            return $this->jsonResponse(['success' => true]);

        } catch (\Exception $e) {
            error_log("Webhook error: " . $e->getMessage());
            return $this->jsonError('Webhook processing failed', 400);
        }
    }

    // Admin methods
    public function adminDashboard()
    {
        $this->requireAdmin();

        $data = [
            'total_revenue' => $this->paymentService->getTotalRevenue(),
            'monthly_revenue' => $this->paymentService->getMonthlyRevenue(),
            'active_subscriptions' => $this->subscriptionService->getActiveSubscriptionsCount(),
            'recent_payments' => $this->paymentService->getRecentPayments(10),
            'subscription_stats' => $this->subscriptionService->getSubscriptionStats(),
            'revenue_chart' => $this->paymentService->getRevenueChartData(),
            'failed_payments' => $this->paymentService->getFailedPayments()
        ];

        return View::render('admin/payments/dashboard', $data);
    }

    public function adminPayments()
    {
        $this->requireAdmin();

        $page = (int)($this->request->input('page') ?? 1);
        $status = $this->request->input('status');
        $search = $this->request->input('search');

        $data = [
            'payments' => $this->paymentService->getAllPayments($page, 50, $status, $search),
            'current_page' => $page,
            'filters' => compact('status', 'search')
        ];

        return View::render('admin/payments/list', $data);
    }

    public function adminSubscriptions()
    {
        $this->requireAdmin();

        $page = (int)($this->request->input('page') ?? 1);
        $status = $this->request->input('status');

        $data = [
            'subscriptions' => $this->subscriptionService->getAllSubscriptions($page, 50, $status),
            'current_page' => $page,
            'filters' => compact('status')
        ];

        return View::render('admin/payments/subscriptions', $data);
    }

    public function adminRefundPayment()
    {
        $this->requireAdmin();

        $paymentId = $this->request->input('payment_id');
        $amount = $this->request->input('amount');
        $reason = $this->request->input('reason');

        if (!$paymentId) {
            return $this->jsonError('Payment ID is required', 400);
        }

        try {
            $payment = $this->paymentService->getPayment($paymentId);
            
            if (!$payment) {
                return $this->jsonError('Payment not found', 404);
            }

            // Process refund through gateway
            $refund = $this->gatewayService->processRefund(
                $payment['gateway_transaction_id'],
                $amount,
                $reason
            );

            // Record refund
            $this->paymentService->recordRefund([
                'payment_id' => $paymentId,
                'amount' => $refund['amount'],
                'reason' => $reason,
                'gateway_refund_id' => $refund['id'],
                'processed_by' => Auth::user()['id']
            ]);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Refund processed successfully',
                'refund' => $refund
            ]);

        } catch (\Exception $e) {
            return $this->jsonError('Refund failed: ' . $e->getMessage(), 500);
        }
    }

    // Private helper methods
    private function calculateExpirationDate(int $planId): string
    {
        $plan = $this->subscriptionService->getPlan($planId);
        $interval = $plan['billing_interval'] ?? 'monthly';
        
        $date = new \DateTime();
        
        switch ($interval) {
            case 'weekly':
                $date->add(new \DateInterval('P1W'));
                break;
            case 'monthly':
                $date->add(new \DateInterval('P1M'));
                break;
            case 'yearly':
                $date->add(new \DateInterval('P1Y'));
                break;
            default:
                $date->add(new \DateInterval('P1M'));
        }
        
        return $date->format('Y-m-d H:i:s');
    }

    private function handlePaymentSuccess($paymentIntent)
    {
        // Update payment status
        $this->paymentService->updatePaymentStatus($paymentIntent['id'], 'completed');
        
        // Send confirmation email
        // Implementation depends on your email service
    }

    private function handlePaymentFailed($paymentIntent)
    {
        // Update payment status
        $this->paymentService->updatePaymentStatus($paymentIntent['id'], 'failed');
        
        // Send failure notification
        // Implementation depends on your email service
    }

    private function handleSubscriptionRenewal($invoice)
    {
        // Extend subscription
        $this->subscriptionService->renewSubscription($invoice['subscription']);
        
        // Send renewal confirmation
        // Implementation depends on your email service
    }

    private function handleSubscriptionCancelled($subscription)
    {
        // Update subscription status
        $this->subscriptionService->cancelSubscription($subscription['id']);
        
        // Disable premium features
        $userId = $this->subscriptionService->getUserIdBySubscription($subscription['id']);
        $this->premiumService->disableFeaturesForUser($userId);
    }
}