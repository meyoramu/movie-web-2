<?php

namespace CineVerse\Core;

use Dotenv\Dotenv;
use CineVerse\Core\Database\DatabaseManager;
use CineVerse\Core\Http\Router;
use CineVerse\Core\Http\Request;
use CineVerse\Core\Http\Response;
use CineVerse\Core\Session\SessionManager;
use CineVerse\Core\Auth\AuthManager;
use CineVerse\Core\Cache\CacheManager;
use CineVerse\Core\Logger\Logger;
use CineVerse\Core\Localization\Translator;
use Exception;

/**
 * Main Application Class
 * 
 * Bootstraps and manages the entire CineVerse application
 */
class Application
{
    private static ?Application $instance = null;
    private array $config = [];
    private DatabaseManager $database;
    private Router $router;
    private SessionManager $session;
    private AuthManager $auth;
    private CacheManager $cache;
    private Logger $logger;
    private Translator $translator;
    private array $services = [];

    private function __construct()
    {
        $this->loadEnvironment();
        $this->loadConfiguration();
        $this->initializeServices();
    }

    /**
     * Get application instance (Singleton)
     */
    public static function getInstance(): Application
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load environment variables
     */
    private function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->safeLoad();
    }

    /**
     * Load application configuration
     */
    private function loadConfiguration(): void
    {
        $configFiles = ['app', 'database'];
        
        foreach ($configFiles as $file) {
            $configPath = dirname(__DIR__, 2) . "/config/{$file}.php";
            if (file_exists($configPath)) {
                $this->config[$file] = require $configPath;
            }
        }
    }

    /**
     * Initialize core services
     */
    private function initializeServices(): void
    {
        // Initialize logger first for error handling
        $this->logger = new Logger($this->config['app']['logging']);
        
        // Initialize database
        $this->database = new DatabaseManager($this->config['database']);
        
        // Initialize cache
        $this->cache = new CacheManager($this->config['app']['cache']);
        
        // Initialize session
        $this->session = new SessionManager($this->config['app']['session']);
        
        // Initialize authentication
        $this->auth = new AuthManager($this->database, $this->session, $this->config['app']['jwt']);
        
        // Initialize translator
        $this->translator = new Translator($this->config['app']['localization']);
        
        // Initialize router
        $this->router = new Router();
        
        // Register error handlers
        $this->registerErrorHandlers();
    }

    /**
     * Register error handlers
     */
    private function registerErrorHandlers(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle PHP errors
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        $this->logger->error("PHP Error: {$message}", [
            'level' => $level,
            'file' => $file,
            'line' => $line
        ]);

        if ($this->config['app']['debug']) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }

        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public function handleException(\Throwable $exception): void
    {
        $this->logger->error("Uncaught Exception: " . $exception->getMessage(), [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString()
        ]);

        if ($this->config['app']['debug']) {
            $this->renderDebugException($exception);
        } else {
            $this->renderErrorPage(500);
        }
    }

    /**
     * Handle fatal errors
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->logger->critical("Fatal Error: {$error['message']}", $error);
            
            if (!$this->config['app']['debug']) {
                $this->renderErrorPage(500);
            }
        }
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            $request = Request::createFromGlobals();
            $response = $this->handleRequest($request);
            $response->send();
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Handle HTTP request
     */
    private function handleRequest(Request $request): Response
    {
        // Start session
        $this->session->start();
        
        // Set language from request or session
        $this->setLanguage($request);
        
        // Route the request
        return $this->router->dispatch($request);
    }

    /**
     * Set application language
     */
    private function setLanguage(Request $request): void
    {
        $language = $request->get('lang') 
                   ?? $this->session->get('language') 
                   ?? $this->config['app']['localization']['default_language'];
        
        if (in_array($language, $this->config['app']['localization']['supported_languages'])) {
            $this->translator->setLanguage($language);
            $this->session->set('language', $language);
        }
    }

    /**
     * Render debug exception page
     */
    private function renderDebugException(\Throwable $exception): void
    {
        http_response_code(500);
        echo "<h1>Application Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    }

    /**
     * Render error page
     */
    private function renderErrorPage(int $code): void
    {
        http_response_code($code);
        $errorPages = [
            404 => 'Page Not Found',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable'
        ];
        
        $title = $errorPages[$code] ?? 'Error';
        echo "<h1>{$title}</h1>";
        echo "<p>We're sorry, but something went wrong.</p>";
    }

    /**
     * Get service instance
     */
    public function get(string $service)
    {
        return match($service) {
            'database', 'db' => $this->database,
            'router' => $this->router,
            'session' => $this->session,
            'auth' => $this->auth,
            'cache' => $this->cache,
            'logger' => $this->logger,
            'translator' => $this->translator,
            default => $this->services[$service] ?? null
        };
    }

    /**
     * Register a service
     */
    public function register(string $name, $service): void
    {
        $this->services[$name] = $service;
    }

    /**
     * Get configuration value
     */
    public function config(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    /**
     * Check if application is in debug mode
     */
    public function isDebug(): bool
    {
        return $this->config['app']['debug'] ?? false;
    }

    /**
     * Get application environment
     */
    public function getEnvironment(): string
    {
        return $this->config['app']['env'] ?? 'production';
    }
}
