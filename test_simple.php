<?php

// Simple test to check if our Route class works
require_once 'src/Core/Http/Router.php';

use CineVerse\Core\Http\Router;

// Create router
$router = new Router();

// Test the route creation and where method
try {
    $route = $router->get('/{path}', function($request, $params) {
        return "Test response for: " . $params['path'];
    });
    
    echo "Route created successfully\n";
    
    // Test the where method
    $route->where('path', '.*');
    
    echo "Where constraint added successfully\n";
    echo "Test passed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
