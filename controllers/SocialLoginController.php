<?php
declare(strict_types=1);

/**
 * Modern Forum - Social Login Controller
 * Handles OAuth authentication flows
 */

namespace Controllers;

use Core\Controller;
use Core\View;
use Services\SocialLoginService;
use Core\Auth;
use Core\Session;

class SocialLoginController extends Controller
{
    private SocialLoginService $socialLoginService;
    private Auth $auth;

    public function __construct()
    {
        parent::__construct();
        $this->socialLoginService = new SocialLoginService();
        $this->auth = new Auth();
    }

    public function redirect(string $provider): void
    {
        try {
            $authUrl = $this->socialLoginService->getAuthUrl($provider);
            header("Location: $authUrl");
            exit;
        } catch (\Exception $e) {
            $this->logger->error('Social login redirect error', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            View::redirect('/login', [
                'error' => 'Social login is currently unavailable. Please try again later.'
            ]);
        }
    }

    public function callback(string $provider): void
    {
        try {
            $code = $_GET['code'] ?? '';
            $state = $_GET['state'] ?? '';
            $error = $_GET['error'] ?? '';

            if ($error) {
                $errorDescription = $_GET['error_description'] ?? 'Unknown error';
                throw new \Exception("OAuth error: $errorDescription");
            }

            if (empty($code)) {
                throw new \Exception('Authorization code not provided');
            }

            $result = $this->socialLoginService->handleCallback($provider, $code, $state);
            
            if ($result['success']) {
                // Log the user in
                $this->auth->loginUser($result['user']['id']);
                
                // Set session data
                Session::set('user_id', $result['user']['id']);
                Session::set('username', $result['user']['username']);
                Session::set('email', $result['user']['email']);
                Session::set('role', $result['user']['role'] ?? 'user');
                Session::set('avatar', $result['user']['avatar']);
                
                // Flash message
                if ($result['is_new_user']) {
                    Session::flash('success', 'Welcome to ' . APP_NAME . '! Your account has been created successfully.');
                } else {
                    Session::flash('success', 'Welcome back! You have been logged in successfully.');
                }
                
                // Redirect to dashboard or intended page
                $redirectTo = Session::get('intended_url', '/dashboard');
                Session::remove('intended_url');
                
                View::redirect($redirectTo);
            } else {
                throw new \Exception('Social login failed');
            }

        } catch (\Exception $e) {
            $this->logger->error('Social login callback error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'code' => $_GET['code'] ?? null,
                'state' => $_GET['state'] ?? null
            ]);
            
            View::redirect('/login', [
                'error' => 'Social login failed: ' . $e->getMessage()
            ]);
        }
    }

    public function unlink(string $provider): void
    {
        if (!$this->isLoggedIn()) {
            View::redirect('/login');
            return;
        }

        $userId = Session::get('user_id');
        
        try {
            $success = $this->socialLoginService->unlinkSocialAccount($userId, $provider);
            
            if ($success) {
                Session::flash('success', ucfirst($provider) . ' account has been unlinked successfully.');
            } else {
                Session::flash('error', 'Failed to unlink ' . $provider . ' account.');
            }
        } catch (\Exception $e) {
            $this->logger->error('Social account unlink error', [
                'user_id' => $userId,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            Session::flash('error', 'An error occurred while unlinking your account.');
        }

        View::redirect('/settings/social');
    }

    public function getLinkedAccounts(): void
    {
        if (!$this->isLoggedIn()) {
            View::redirect('/login');
            return;
        }

        $userId = Session::get('user_id');
        $linkedAccounts = $this->socialLoginService->getLinkedAccounts($userId);
        
        View::json([
            'success' => true,
            'accounts' => $linkedAccounts
        ]);
    }
}