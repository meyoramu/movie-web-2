<?php

/**
 * CineVerse Application Entry Point
 * 
 * This is the main entry point for the CineVerse web application.
 * All HTTP requests are routed through this file.
 */

// Define application constants
define('APP_START_TIME', microtime(true));
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

// Enable error reporting for development
if (file_exists(APP_ROOT . '/.env') && strpos(file_get_contents(APP_ROOT . '/.env'), 'APP_DEBUG=true') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Autoload dependencies
require_once APP_ROOT . '/vendor/autoload.php';

// Bootstrap the application
try {
    $app = \CineVerse\Core\Application::getInstance();
    
    // Register middleware
    $router = $app->get('router');
    
    // CORS middleware
    $router->middleware('cors', function($request, $next) {
        $response = $next();
        
        if ($response instanceof \CineVerse\Core\Http\Response) {
            $response->setHeaders([
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'
            ]);
        }
        
        return $response;
    });
    
    // Authentication middleware
    $router->middleware('auth', function($request, $next) use ($app) {
        $auth = $app->get('auth');
        
        if (!$auth->check()) {
            if ($request->expectsJson()) {
                return \CineVerse\Core\Http\Response::unauthorized();
            }
            return \CineVerse\Core\Http\Response::redirect('/login');
        }
        
        return $next();
    });
    
    // Admin middleware
    $router->middleware('admin', function($request, $next) use ($app) {
        $auth = $app->get('auth');
        $user = $auth->user();
        
        if (!$user || $user->getRole() !== 'admin') {
            if ($request->expectsJson()) {
                return \CineVerse\Core\Http\Response::forbidden();
            }
            return \CineVerse\Core\Http\Response::redirect('/');
        }
        
        return $next();
    });
    
    // Rate limiting middleware
    $router->middleware('throttle', function($request, $next) {
        // TODO: Implement rate limiting
        return $next();
    });
    
    // Load routes
    require_once APP_ROOT . '/routes/web.php';
    require_once APP_ROOT . '/routes/api.php';
    
    // Run the application
    $app->run();
    
} catch (Exception $e) {
    // Handle fatal errors
    http_response_code(500);
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<h1>Application Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        echo "<h1>Service Unavailable</h1>";
        echo "<p>We're sorry, but the service is temporarily unavailable. Please try again later.</p>";
    }
    
    // Log the error
    error_log("Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
}
