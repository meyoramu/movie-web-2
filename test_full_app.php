<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing full application...\n";

require_once 'vendor/autoload.php';

try {
    // Load environment
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
    echo "✓ Environment loaded\n";
    
    // Test application creation
    $app = \CineVerse\Core\Application::getInstance();
    echo "✓ Application created successfully\n";
    
    // Test services
    $logger = $app->get('logger');
    echo "✓ Logger service available\n";
    
    $session = $app->get('session');
    echo "✓ Session service available\n";
    
    $cache = $app->get('cache');
    echo "✓ Cache service available\n";
    
    $translator = $app->get('translator');
    echo "✓ Translator service available\n";
    
    echo "All services working!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "Test completed.\n";
