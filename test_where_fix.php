<?php

// Test to verify the where() method fix
echo "Testing the where() method fix...\n";

try {
    // Include the routes file to see if it loads without error
    require_once 'vendor/autoload.php';
    
    use CineVerse\Core\Application;
    
    // Get the application instance
    $app = Application::getInstance();
    $router = $app->get('router');
    
    // Test creating a route with where constraint (similar to the problematic line)
    $route = $router->get('/{path}', function($request, $params) {
        return "Test response";
    });
    
    // This should not throw an error anymore
    $route->where('path', '.*');
    
    echo "SUCCESS: The where() method is working correctly!\n";
    echo "The original error 'Call to a member function where() on null' has been fixed.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "The fix did not work.\n";
}
