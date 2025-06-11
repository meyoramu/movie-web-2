<?php

namespace CineVerse\Core\Session;

use Exception;

/**
 * Session Manager
 * 
 * Handles PHP session management for CineVerse application
 */
class SessionManager
{
    private array $config;
    private bool $started = false;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->configureSession();
    }

    /**
     * Configure session settings
     */
    private function configureSession(): void
    {
        // Only configure if session hasn't started and we're not in CLI mode
        if (session_status() === PHP_SESSION_ACTIVE || php_sapi_name() === 'cli') {
            return;
        }

        // Set session configuration
        @ini_set('session.cookie_lifetime', $this->config['lifetime'] * 60);
        @ini_set('session.cookie_path', $this->config['path'] ?? '/');
        @ini_set('session.cookie_httponly', $this->config['http_only'] ?? true);
        @ini_set('session.cookie_secure', $this->config['secure'] ?? false);
        @ini_set('session.cookie_samesite', $this->config['same_site'] ?? 'Lax');

        if (isset($this->config['domain'])) {
            @ini_set('session.cookie_domain', $this->config['domain']);
        }

        // Set session save path for file driver
        if (($this->config['driver'] ?? 'file') === 'file') {
            $sessionPath = __DIR__ . '/../../../storage/sessions';
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0755, true);
            }
            @session_save_path($sessionPath);
        }
    }

    /**
     * Start the session
     */
    public function start(): bool
    {
        // Don't start sessions in CLI mode
        if (php_sapi_name() === 'cli') {
            $this->started = true;
            return true;
        }

        if ($this->started) {
            return true;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return true;
        }

        try {
            $result = @session_start();
            $this->started = $result;

            // Regenerate session ID periodically for security (only if session actually started)
            if ($result && !isset($_SESSION['_session_started'])) {
                $this->regenerateId();
                $_SESSION['_session_started'] = time();
            }

            return $result;
        } catch (Exception $e) {
            error_log("Session start failed: " . $e->getMessage());
            $this->started = false;
            return false;
        }
    }

    /**
     * Get a session value
     */
    public function get(string $key, $default = null)
    {
        if (php_sapi_name() === 'cli') {
            return $default;
        }
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value
     */
    public function set(string $key, $value): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if session has a key
     */
    public function has(string $key): bool
    {
        if (php_sapi_name() === 'cli') {
            return false;
        }
        $this->ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value
     */
    public function remove(string $key): void
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data
     */
    public function all(): array
    {
        $this->ensureStarted();
        return $_SESSION ?? [];
    }

    /**
     * Clear all session data
     */
    public function clear(): void
    {
        $this->ensureStarted();
        $_SESSION = [];
    }

    /**
     * Destroy the session
     */
    public function destroy(): bool
    {
        if (!$this->started) {
            return true;
        }

        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        $result = session_destroy();
        $this->started = false;
        
        return $result;
    }

    /**
     * Regenerate session ID
     */
    public function regenerateId(bool $deleteOldSession = true): bool
    {
        $this->ensureStarted();
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Get session ID
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * Set session ID
     */
    public function setId(string $id): void
    {
        session_id($id);
    }

    /**
     * Get session name
     */
    public function getName(): string
    {
        return session_name();
    }

    /**
     * Set session name
     */
    public function setName(string $name): void
    {
        session_name($name);
    }

    /**
     * Check if session is started
     */
    public function isStarted(): bool
    {
        return $this->started && session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Flash data - set data that will be available for the next request only
     */
    public function flash(string $key, $value): void
    {
        $this->set('_flash_' . $key, $value);
    }

    /**
     * Get flash data
     */
    public function getFlash(string $key, $default = null)
    {
        $flashKey = '_flash_' . $key;
        $value = $this->get($flashKey, $default);
        $this->remove($flashKey);
        return $value;
    }

    /**
     * Check if flash data exists
     */
    public function hasFlash(string $key): bool
    {
        return $this->has('_flash_' . $key);
    }

    /**
     * Get CSRF token
     */
    public function getCsrfToken(): string
    {
        if (!$this->has('_csrf_token')) {
            $this->set('_csrf_token', bin2hex(random_bytes(32)));
        }
        
        return $this->get('_csrf_token');
    }

    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken(string $token): bool
    {
        $sessionToken = $this->get('_csrf_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }

    /**
     * Ensure session is started
     */
    private function ensureStarted(): void
    {
        // In CLI mode, just return without starting session
        if (php_sapi_name() === 'cli') {
            return;
        }

        if (!$this->started && session_status() !== PHP_SESSION_ACTIVE) {
            $this->start();
        }
    }

    /**
     * Clean up expired sessions (for file driver)
     */
    public function gc(): void
    {
        if (($this->config['driver'] ?? 'file') === 'file') {
            $sessionPath = session_save_path();
            $lifetime = $this->config['lifetime'] * 60;
            
            if (is_dir($sessionPath)) {
                $files = glob($sessionPath . '/sess_*');
                foreach ($files as $file) {
                    if (filemtime($file) + $lifetime < time()) {
                        unlink($file);
                    }
                }
            }
        }
    }
}
