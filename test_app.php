<?php

// Simple test script to debug the application
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing CineVerse Application...\n";

// Test autoloader
require_once 'vendor/autoload.php';
echo "✓ Autoloader loaded\n";

// Test environment loading
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
echo "✓ Environment loaded\n";

// Test configuration loading
$appConfig = require 'config/app.php';
$dbConfig = require 'config/database.php';
echo "✓ Configuration loaded\n";

// Test Logger
try {
    $logger = new \CineVerse\Core\Logger\Logger($appConfig['logging']);
    $logger->info('Test log message');
    echo "✓ Logger working\n";
} catch (Exception $e) {
    echo "✗ Logger failed: " . $e->getMessage() . "\n";
}

// Test SessionManager
try {
    $session = new \CineVerse\Core\Session\SessionManager($appConfig['session']);
    echo "✓ SessionManager working\n";
} catch (Exception $e) {
    echo "✗ SessionManager failed: " . $e->getMessage() . "\n";
}

// Test CacheManager
try {
    $cache = new \CineVerse\Core\Cache\CacheManager($appConfig['cache']);
    echo "✓ CacheManager working\n";
} catch (Exception $e) {
    echo "✗ CacheManager failed: " . $e->getMessage() . "\n";
}

// Test Translator
try {
    $translator = new \CineVerse\Core\Localization\Translator($appConfig['localization']);
    echo "✓ Translator working\n";
} catch (Exception $e) {
    echo "✗ Translator failed: " . $e->getMessage() . "\n";
}

// Test Application
try {
    $app = \CineVerse\Core\Application::getInstance();
    echo "✓ Application instance created\n";
} catch (Exception $e) {
    echo "✗ Application failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "Test completed.\n";
