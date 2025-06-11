<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Minimal test...\n";

require_once 'vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
echo "✓ Environment loaded\n";

// Load configs
$appConfig = require 'config/app.php';
$dbConfig = require 'config/database.php';
echo "✓ Configs loaded\n";

// Test each service individually
try {
    echo "Testing Logger...\n";
    $logger = new \CineVerse\Core\Logger\Logger($appConfig['logging']);
    echo "✓ Logger created\n";
    
    echo "Testing CacheManager...\n";
    $cache = new \CineVerse\Core\Cache\CacheManager($appConfig['cache']);
    echo "✓ CacheManager created\n";
    
    echo "Testing SessionManager...\n";
    $session = new \CineVerse\Core\Session\SessionManager($appConfig['session']);
    echo "✓ SessionManager created\n";
    
    echo "Testing Translator...\n";
    $translator = new \CineVerse\Core\Localization\Translator($appConfig['localization']);
    echo "✓ Translator created\n";
    
    echo "All services created successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "Test completed.\n";
