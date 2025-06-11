<?php

namespace CineVerse\Core\Auth;

use CineVerse\Core\Database\DatabaseManager;
use CineVerse\Core\Session\SessionManager;
use CineVerse\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * Authentication Manager
 * 
 * Handles user authentication, registration, and session management
 */
class AuthManager
{
    private DatabaseManager $db;
    private SessionManager $session;
    private array $jwtConfig;
    private ?User $user = null;

    public function __construct(DatabaseManager $db, SessionManager $session, array $jwtConfig)
    {
        $this->db = $db;
        $this->session = $session;
        $this->jwtConfig = $jwtConfig;
        
        $this->loadUserFromSession();
    }

    /**
     * Register a new user
     */
    public function register(array $userData): array
    {
        // Validate required fields
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new Exception("Field '{$field}' is required");
            }
        }

        // Validate email format
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check if username or email already exists
        $existingUser = $this->db->table('users')
            ->where('username', $userData['username'])
            ->orWhere('email', $userData['email'])
            ->first();

        if ($existingUser) {
            if ($existingUser['username'] === $userData['username']) {
                throw new Exception("Username already exists");
            }
            if ($existingUser['email'] === $userData['email']) {
                throw new Exception("Email already exists");
            }
        }

        // Validate password strength
        if (strlen($userData['password']) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        // Prepare user data
        $userRecord = [
            'uuid' => $this->generateUuid(),
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'phone' => $userData['phone'] ?? null,
            'date_of_birth' => $userData['date_of_birth'] ?? null,
            'gender' => $userData['gender'] ?? null,
            'country' => $userData['country'] ?? 'RW',
            'language' => $userData['language'] ?? 'en',
            'role' => 'user',
            'status' => 'active'
        ];

        // Insert user
        $userId = $this->db->insert('users', $userRecord);
        
        // Get created user
        $user = $this->db->table('users')->where('id', $userId)->first();
        
        // Log activity
        $this->logActivity($userId, 'user_registered', 'User account created');

        return [
            'success' => true,
            'message' => 'User registered successfully',
            'user' => $this->sanitizeUserData($user)
        ];
    }

    /**
     * Authenticate user login
     */
    public function login(string $identifier, string $password, bool $remember = false): array
    {
        // Find user by username or email
        $user = $this->db->table('users')
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$user) {
            $this->handleFailedLogin($identifier);
            throw new Exception("Invalid credentials");
        }

        // Check if account is locked
        if ($this->isAccountLocked($user)) {
            throw new Exception("Account is temporarily locked due to too many failed login attempts");
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            $this->handleFailedLogin($identifier, $user['id']);
            throw new Exception("Invalid credentials");
        }

        // Check account status
        if ($user['status'] !== 'active') {
            throw new Exception("Account is not active");
        }

        // Reset login attempts
        $this->resetLoginAttempts($user['id']);

        // Update last login
        $this->updateLastLogin($user['id']);

        // Set session
        $this->setUserSession($user);

        // Handle remember me
        if ($remember) {
            $this->setRememberToken($user['id']);
        }

        // Log activity
        $this->logActivity($user['id'], 'user_login', 'User logged in');

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $this->sanitizeUserData($user),
            'token' => $this->generateJwtToken($user)
        ];
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        if ($this->user) {
            $this->logActivity($this->user->getId(), 'user_logout', 'User logged out');
        }

        $this->session->destroy();
        $this->user = null;
    }

    /**
     * Check if user is authenticated
     */
    public function check(): bool
    {
        return $this->user !== null;
    }

    /**
     * Get current authenticated user
     */
    public function user(): ?User
    {
        return $this->user;
    }

    /**
     * Generate JWT token
     */
    public function generateJwtToken(array $user): string
    {
        $payload = [
            'iss' => $_ENV['APP_URL'] ?? 'cineverse.com',
            'aud' => $_ENV['APP_URL'] ?? 'cineverse.com',
            'iat' => time(),
            'exp' => time() + $this->jwtConfig['expiry'],
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];

        return JWT::encode($payload, $this->jwtConfig['secret'], $this->jwtConfig['algorithm']);
    }

    /**
     * Verify JWT token
     */
    public function verifyJwtToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtConfig['secret'], $this->jwtConfig['algorithm']));
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Request password reset
     */
    public function requestPasswordReset(string $email): array
    {
        $user = $this->db->table('users')->where('email', $email)->first();
        
        if (!$user) {
            // Don't reveal if email exists
            return [
                'success' => true,
                'message' => 'If the email exists, a reset link has been sent'
            ];
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        // Store reset token
        $this->db->insert('password_reset_tokens', [
            'email' => $email,
            'token' => hash('sha256', $token),
            'expires_at' => $expiresAt
        ]);

        // TODO: Send email with reset link
        // $this->sendPasswordResetEmail($user, $token);

        $this->logActivity($user['id'], 'password_reset_requested', 'Password reset requested');

        return [
            'success' => true,
            'message' => 'Password reset link has been sent to your email',
            'token' => $token // Remove this in production
        ];
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $hashedToken = hash('sha256', $token);
        
        $resetRecord = $this->db->table('password_reset_tokens')
            ->where('token', $hashedToken)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();

        if (!$resetRecord) {
            throw new Exception("Invalid or expired reset token");
        }

        $user = $this->db->table('users')->where('email', $resetRecord['email'])->first();
        
        if (!$user) {
            throw new Exception("User not found");
        }

        // Validate password strength
        if (strlen($newPassword) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        // Update password
        $this->db->table('users')
            ->where('id', $user['id'])
            ->update(['password' => password_hash($newPassword, PASSWORD_DEFAULT)]);

        // Delete used token
        $this->db->table('password_reset_tokens')
            ->where('token', $hashedToken)
            ->delete();

        $this->logActivity($user['id'], 'password_reset', 'Password was reset');

        return [
            'success' => true,
            'message' => 'Password has been reset successfully'
        ];
    }

    /**
     * Load user from session
     */
    private function loadUserFromSession(): void
    {
        $userId = $this->session->get('user_id');
        
        if ($userId) {
            $userData = $this->db->table('users')->where('id', $userId)->first();
            
            if ($userData && $userData['status'] === 'active') {
                $this->user = new User($userData);
            }
        }
    }

    /**
     * Set user session
     */
    private function setUserSession(array $user): void
    {
        $this->session->set('user_id', $user['id']);
        $this->session->set('username', $user['username']);
        $this->session->set('role', $user['role']);
        
        $this->user = new User($user);
    }

    /**
     * Handle failed login attempt
     */
    private function handleFailedLogin(string $identifier, ?int $userId = null): void
    {
        if ($userId) {
            $attempts = $this->db->fetch(
                "SELECT login_attempts FROM users WHERE id = ?", 
                [$userId]
            )['login_attempts'] ?? 0;

            $newAttempts = $attempts + 1;
            $lockUntil = null;

            // Lock account after 5 failed attempts
            if ($newAttempts >= 5) {
                $lockUntil = date('Y-m-d H:i:s', time() + 900); // 15 minutes
            }

            $this->db->table('users')
                ->where('id', $userId)
                ->update([
                    'login_attempts' => $newAttempts,
                    'locked_until' => $lockUntil
                ]);

            $this->logActivity($userId, 'failed_login', "Failed login attempt #{$newAttempts}");
        }
    }

    /**
     * Check if account is locked
     */
    private function isAccountLocked(array $user): bool
    {
        if (!$user['locked_until']) {
            return false;
        }

        return strtotime($user['locked_until']) > time();
    }

    /**
     * Reset login attempts
     */
    private function resetLoginAttempts(int $userId): void
    {
        $this->db->table('users')
            ->where('id', $userId)
            ->update([
                'login_attempts' => 0,
                'locked_until' => null
            ]);
    }

    /**
     * Update last login information
     */
    private function updateLastLogin(int $userId): void
    {
        $this->db->table('users')
            ->where('id', $userId)
            ->update([
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
    }

    /**
     * Set remember token
     */
    private function setRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        
        $this->db->table('users')
            ->where('id', $userId)
            ->update(['remember_token' => hash('sha256', $token)]);

        // Set cookie for 30 days
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
    }

    /**
     * Log user activity
     */
    private function logActivity(int $userId, string $type, string $description): void
    {
        $this->db->insert('user_activities', [
            'user_id' => $userId,
            'activity_type' => $type,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    /**
     * Generate UUID
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Sanitize user data for output
     */
    private function sanitizeUserData(array $user): array
    {
        unset($user['password'], $user['remember_token']);
        return $user;
    }
}
