<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Security;

/**
 * Payment Service for Premium Features
 */
class PaymentService
{
    private $db;
    private $logger;
    private $stripeApiKey;
    private $paypalClientId;
    private $paypalClientSecret;

    public function __construct()
    {
        global $app;
        $this->db = $app->get('database');
        $this->logger = $app->get('logger');
        $this->stripeApiKey = config('payment.stripe_secret_key');
        $this->paypalClientId = config('payment.paypal_client_id');
        $this->paypalClientSecret = config('payment.paypal_client_secret');
    }

    /**
     * Get subscription plans
     */
    public function getSubscriptionPlans()
    {
        return [
            'basic' => [
                'name' => 'Basic',
                'price' => 0,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    'Basic forum access',
                    'Create threads and posts',
                    'Basic profile customization',
                    'Email support'
                ],
                'limits' => [
                    'max_threads_per_day' => 5,
                    'max_posts_per_day' => 20,
                    'max_file_uploads' => 10,
                    'max_file_size' => '5MB'
                ]
            ],
            'premium' => [
                'name' => 'Premium',
                'price' => 9.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    'All Basic features',
                    'Unlimited threads and posts',
                    'Advanced profile customization',
                    'Priority support',
                    'Custom themes',
                    'Advanced analytics',
                    'File uploads up to 50MB',
                    'Private messaging',
                    'Ad-free experience'
                ],
                'limits' => [
                    'max_threads_per_day' => -1,
                    'max_posts_per_day' => -1,
                    'max_file_uploads' => 100,
                    'max_file_size' => '50MB'
                ]
            ],
            'pro' => [
                'name' => 'Pro',
                'price' => 19.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    'All Premium features',
                    'Custom domain support',
                    'Advanced moderation tools',
                    'API access',
                    'White-label options',
                    'Priority customer support',
                    'Custom integrations',
                    'Advanced reporting',
                    'File uploads up to 100MB'
                ],
                'limits' => [
                    'max_threads_per_day' => -1,
                    'max_posts_per_day' => -1,
                    'max_file_uploads' => 500,
                    'max_file_size' => '100MB'
                ]
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => 99.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    'All Pro features',
                    'Custom development',
                    'Dedicated support',
                    'SLA guarantee',
                    'Custom branding',
                    'Advanced security',
                    'Unlimited file uploads',
                    'Custom analytics',
                    'Multi-tenant support'
                ],
                'limits' => [
                    'max_threads_per_day' => -1,
                    'max_posts_per_day' => -1,
                    'max_file_uploads' => -1,
                    'max_file_size' => '500MB'
                ]
            ]
        ];
    }

    /**
     * Create Stripe payment intent
     */
    public function createStripePaymentIntent($userId, $planId, $amount, $currency = 'USD')
    {
        try {
            $paymentIntent = $this->callStripeAPI('payment_intents', [
                'amount' => $amount * 100, // Convert to cents
                'currency' => $currency,
                'metadata' => [
                    'user_id' => $userId,
                    'plan_id' => $planId
                ]
            ]);

            // Store payment intent
            $this->db->query(
                "INSERT INTO payment_intents (user_id, plan_id, stripe_payment_intent_id, amount, currency, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, 'pending', NOW())",
                [$userId, $planId, $paymentIntent['id'], $amount, $currency]
            );

            return $paymentIntent;
        } catch (\Exception $e) {
            $this->logger->error('Stripe payment intent creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process Stripe payment
     */
    public function processStripePayment($paymentIntentId, $userId)
    {
        try {
            $paymentIntent = $this->callStripeAPI("payment_intents/{$paymentIntentId}");
            
            if ($paymentIntent['status'] === 'succeeded') {
                $this->updatePaymentStatus($paymentIntentId, 'completed');
                $this->activateSubscription($userId, $paymentIntent['metadata']['plan_id']);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error('Stripe payment processing failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create PayPal order
     */
    public function createPayPalOrder($userId, $planId, $amount, $currency = 'USD')
    {
        try {
            $accessToken = $this->getPayPalAccessToken();
            
            $order = $this->callPayPalAPI('v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2)
                        ]
                    ]
                ],
                'application_context' => [
                    'return_url' => config('app.url') . '/payment/success',
                    'cancel_url' => config('app.url') . '/payment/cancel'
                ]
            ], $accessToken);

            // Store PayPal order
            $this->db->query(
                "INSERT INTO paypal_orders (user_id, plan_id, paypal_order_id, amount, currency, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, 'pending', NOW())",
                [$userId, $planId, $order['id'], $amount, $currency]
            );

            return $order;
        } catch (\Exception $e) {
            $this->logger->error('PayPal order creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Capture PayPal payment
     */
    public function capturePayPalPayment($orderId, $userId)
    {
        try {
            $accessToken = $this->getPayPalAccessToken();
            
            $capture = $this->callPayPalAPI("v2/checkout/orders/{$orderId}/capture", [], $accessToken, 'POST');
            
            if ($capture['status'] === 'COMPLETED') {
                $this->updatePayPalOrderStatus($orderId, 'completed');
                $this->activateSubscription($userId, $capture['purchase_units'][0]['reference_id']);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error('PayPal payment capture failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Activate subscription
     */
    public function activateSubscription($userId, $planId)
    {
        try {
            $plans = $this->getSubscriptionPlans();
            $plan = $plans[$planId] ?? null;

            if (!$plan) {
                throw new \Exception('Invalid plan ID');
            }

            $subscriptionId = Security::generateToken(16);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 month'));

            $this->db->query(
                "INSERT INTO user_subscriptions (id, user_id, plan_id, status, expires_at, created_at) 
                 VALUES (?, ?, ?, 'active', ?, NOW()) 
                 ON DUPLICATE KEY UPDATE 
                 plan_id = VALUES(plan_id), 
                 status = 'active', 
                 expires_at = VALUES(expires_at), 
                 updated_at = NOW()",
                [$subscriptionId, $userId, $planId, $expiresAt]
            );

            $this->logger->info("Subscription activated for user {$userId}: {$planId}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Subscription activation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user subscription
     */
    public function getUserSubscription($userId)
    {
        $result = $this->db->fetch(
            "SELECT s.*, p.name as plan_name, p.price, p.currency 
             FROM user_subscriptions s 
             LEFT JOIN subscription_plans p ON s.plan_id = p.id 
             WHERE s.user_id = ? AND s.status = 'active' AND s.expires_at > NOW()",
            [$userId]
        );

        return $result;
    }

    /**
     * Check if user has premium feature
     */
    public function hasPremiumFeature($userId, $feature)
    {
        $subscription = $this->getUserSubscription($userId);
        
        if (!$subscription) {
            return false;
        }

        $plans = $this->getSubscriptionPlans();
        $plan = $plans[$subscription['plan_id']] ?? null;

        if (!$plan) {
            return false;
        }

        return in_array($feature, $plan['features']);
    }

    /**
     * Get user usage limits
     */
    public function getUserLimits($userId)
    {
        $subscription = $this->getUserSubscription($userId);
        
        if (!$subscription) {
            $plans = $this->getSubscriptionPlans();
            return $plans['basic']['limits'];
        }

        $plans = $this->getSubscriptionPlans();
        $plan = $plans[$subscription['plan_id']] ?? $plans['basic'];
        
        return $plan['limits'];
    }

    /**
     * Check if user can perform action
     */
    public function canPerformAction($userId, $action)
    {
        $limits = $this->getUserLimits($userId);
        
        switch ($action) {
            case 'create_thread':
                return $this->checkDailyLimit($userId, 'threads', $limits['max_threads_per_day']);
            case 'create_post':
                return $this->checkDailyLimit($userId, 'posts', $limits['max_posts_per_day']);
            case 'upload_file':
                return $this->checkDailyLimit($userId, 'file_uploads', $limits['max_file_uploads']);
            default:
                return true;
        }
    }

    /**
     * Check daily limit
     */
    private function checkDailyLimit($userId, $type, $limit)
    {
        if ($limit === -1) {
            return true; // Unlimited
        }

        $count = $this->db->fetch(
            "SELECT COUNT(*) as count FROM user_activity 
             WHERE user_id = ? AND action = ? AND DATE(created_at) = CURDATE()",
            [$userId, $type]
        );

        return $count['count'] < $limit;
    }

    /**
     * Record user activity
     */
    public function recordActivity($userId, $action)
    {
        $this->db->query(
            "INSERT INTO user_activity (user_id, action, created_at) VALUES (?, ?, NOW())",
            [$userId, $action]
        );
    }

    /**
     * Call Stripe API
     */
    private function callStripeAPI($endpoint, $data = [], $method = 'POST')
    {
        $url = "https://api.stripe.com/v1/{$endpoint}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->stripeApiKey . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        throw new \Exception('Stripe API error: ' . $response);
    }

    /**
     * Call PayPal API
     */
    private function callPayPalAPI($endpoint, $data = [], $accessToken = null, $method = 'POST')
    {
        $url = "https://api-m.sandbox.paypal.com/{$endpoint}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);

        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            return json_decode($response, true);
        }

        throw new \Exception('PayPal API error: ' . $response);
    }

    /**
     * Get PayPal access token
     */
    private function getPayPalAccessToken()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api-m.sandbox.paypal.com/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, $this->paypalClientId . ':' . $this->paypalClientSecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['access_token'];
        }

        throw new \Exception('PayPal access token error: ' . $response);
    }

    /**
     * Update payment status
     */
    private function updatePaymentStatus($paymentIntentId, $status)
    {
        $this->db->query(
            "UPDATE payment_intents SET status = ?, updated_at = NOW() WHERE stripe_payment_intent_id = ?",
            [$status, $paymentIntentId]
        );
    }

    /**
     * Update PayPal order status
     */
    private function updatePayPalOrderStatus($orderId, $status)
    {
        $this->db->query(
            "UPDATE paypal_orders SET status = ?, updated_at = NOW() WHERE paypal_order_id = ?",
            [$status, $orderId]
        );
    }
}