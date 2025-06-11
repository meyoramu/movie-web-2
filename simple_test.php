<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Simple test...\n";

require_once 'vendor/autoload.php';

// Test individual components
try {
    echo "Testing Logger...\n";
    $config = ['level' => 'info', 'channel' => 'daily', 'path' => __DIR__ . '/storage/logs'];
    $logger = new \CineVerse\Core\Logger\Logger($config);
    echo "Logger OK\n";
    
    echo "Testing SessionManager...\n";
    $sessionConfig = ['driver' => 'file', 'lifetime' => 120, 'path' => '/', 'http_only' => true, 'secure' => false, 'same_site' => 'lax'];
    $session = new \CineVerse\Core\Session\SessionManager($sessionConfig);
    echo "SessionManager OK\n";
    
    echo "Testing CacheManager...\n";
    $cacheConfig = ['driver' => 'file'];
    $cache = new \CineVerse\Core\Cache\CacheManager($cacheConfig);
    echo "CacheManager OK\n";
    
    echo "Testing Translator...\n";
    $translatorConfig = ['default_language' => 'en', 'supported_languages' => ['en', 'fr', 'rw']];
    $translator = new \CineVerse\Core\Localization\Translator($translatorConfig);
    echo "Translator OK\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "Test completed.\n";
