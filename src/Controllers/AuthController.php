<?php

namespace CineVerse\Controllers;

use CineVerse\Core\Application;
use CineVerse\Core\Http\Request;
use CineVerse\Core\Http\Response;
use Exception;

/**
 * Authentication Controller
 * 
 * Handles user authentication, registration, and password management
 */
class AuthController
{
    private Application $app;

    public function __construct()
    {
        $this->app = Application::getInstance();
    }

    /**
     * Show registration form
     */
    public function showRegister(Request $request): Response
    {
        if ($this->app->get('auth')->check()) {
            return Response::redirect('/dashboard');
        }

        return Response::view('auth/register');
    }

    /**
     * Handle user registration
     */
    public function register(Request $request): Response
    {
        try {
            // Validate input
            $data = $request->validate([
                'username' => 'required|min:3|max:50|unique:users',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8|confirmed',
                'first_name' => 'required|max:100',
                'last_name' => 'required|max:100',
                'phone' => 'nullable|phone',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'country' => 'nullable|string|max:2',
                'language' => 'nullable|string|max:5'
            ]);

            $auth = $this->app->get('auth');
            $result = $auth->register($data);

            if ($request->expectsJson()) {
                return Response::success($result, 'Registration successful');
            }

            // Redirect to login with success message
            $this->app->get('session')->flash('success', 'Registration successful! Please log in.');
            return Response::redirect('/auth/login');

        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return Response::error($e->getMessage(), 400);
            }

            $this->app->get('session')->flash('error', $e->getMessage());
            return Response::redirect('/auth/register');
        }
    }

    /**
     * Show login form
     */
    public function showLogin(Request $request): Response
    {
        if ($this->app->get('auth')->check()) {
            return Response::redirect('/dashboard');
        }

        return Response::view('auth/login');
    }

    /**
     * Handle user login
     */
    public function login(Request $request): Response
    {
        try {
            // Validate input
            $data = $request->validate([
                'identifier' => 'required', // username or email
                'password' => 'required',
                'remember' => 'nullable|boolean'
            ]);

            $auth = $this->app->get('auth');
            $result = $auth->login(
                $data['identifier'],
                $data['password'],
                $data['remember'] ?? false
            );

            if ($request->expectsJson()) {
                return Response::success($result, 'Login successful');
            }

            // Redirect to intended page or dashboard
            $intended = $this->app->get('session')->get('intended_url', '/dashboard');
            $this->app->get('session')->forget('intended_url');
            
            return Response::redirect($intended);

        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return Response::error($e->getMessage(), 401);
            }

            $this->app->get('session')->flash('error', $e->getMessage());
            return Response::redirect('/auth/login');
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request): Response
    {
        $this->app->get('auth')->logout();

        if ($request->expectsJson()) {
            return Response::success(null, 'Logged out successfully');
        }

        return Response::redirect('/');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword(Request $request): Response
    {
        return Response::view('auth/forgot-password');
    }

    /**
     * Handle forgot password request
     */
    public function forgotPassword(Request $request): Response
    {
        try {
            $data = $request->validate([
                'email' => 'required|email'
            ]);

            $auth = $this->app->get('auth');
            $result = $auth->requestPasswordReset($data['email']);

            if ($request->expectsJson()) {
                return Response::success($result);
            }

            $this->app->get('session')->flash('success', $result['message']);
            return Response::redirect('/auth/forgot-password');

        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return Response::error($e->getMessage(), 400);
            }

            $this->app->get('session')->flash('error', $e->getMessage());
            return Response::redirect('/auth/forgot-password');
        }
    }

    /**
     * Show reset password form
     */
    public function showResetPassword(Request $request, array $params): Response
    {
        $token = $params['token'] ?? '';
        
        if (empty($token)) {
            return Response::redirect('/auth/forgot-password');
        }

        return Response::view('auth/reset-password', ['token' => $token]);
    }

    /**
     * Handle password reset
     */
    public function resetPassword(Request $request): Response
    {
        try {
            $data = $request->validate([
                'token' => 'required',
                'password' => 'required|min:8|confirmed'
            ]);

            $auth = $this->app->get('auth');
            $result = $auth->resetPassword($data['token'], $data['password']);

            if ($request->expectsJson()) {
                return Response::success($result);
            }

            $this->app->get('session')->flash('success', $result['message']);
            return Response::redirect('/auth/login');

        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return Response::error($e->getMessage(), 400);
            }

            $this->app->get('session')->flash('error', $e->getMessage());
            return Response::redirect('/auth/forgot-password');
        }
    }

    /**
     * Verify email address
     */
    public function verifyEmail(Request $request, array $params): Response
    {
        try {
            $token = $params['token'] ?? '';
            
            if (empty($token)) {
                throw new Exception('Invalid verification token');
            }

            // TODO: Implement email verification logic
            $db = $this->app->get('database');
            
            $verification = $db->table('email_verification_tokens')
                ->where('token', hash('sha256', $token))
                ->where('expires_at', '>', date('Y-m-d H:i:s'))
                ->first();

            if (!$verification) {
                throw new Exception('Invalid or expired verification token');
            }

            // Update user email verification
            $db->table('users')
                ->where('id', $verification['user_id'])
                ->update(['email_verified_at' => date('Y-m-d H:i:s')]);

            // Delete verification token
            $db->table('email_verification_tokens')
                ->where('id', $verification['id'])
                ->delete();

            if ($request->expectsJson()) {
                return Response::success(null, 'Email verified successfully');
            }

            $this->app->get('session')->flash('success', 'Email verified successfully!');
            return Response::redirect('/dashboard');

        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return Response::error($e->getMessage(), 400);
            }

            $this->app->get('session')->flash('error', $e->getMessage());
            return Response::redirect('/');
        }
    }

    /**
     * Resend email verification
     */
    public function resendVerification(Request $request): Response
    {
        try {
            $user = $this->app->get('auth')->user();
            
            if (!$user) {
                throw new Exception('User not authenticated');
            }

            if ($user->isEmailVerified()) {
                throw new Exception('Email is already verified');
            }

            // TODO: Send verification email
            // $this->sendVerificationEmail($user);

            if ($request->expectsJson()) {
                return Response::success(null, 'Verification email sent');
            }

            $this->app->get('session')->flash('success', 'Verification email sent!');
            return Response::redirect('/dashboard');

        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return Response::error($e->getMessage(), 400);
            }

            $this->app->get('session')->flash('error', $e->getMessage());
            return Response::redirect('/dashboard');
        }
    }
}
