<?php

require_once 'vendor/autoload.php';

use CineVerse\Core\Http\Router;
use CineVerse\Core\Http\Request;

// Create a simple test
$router = new Router();

// Test the route with where constraint
$route = $router->get('/{path}', function($request, $params) {
    echo "Path: " . $params['path'] . "\n";
    return "SPA Route";
});

$route->where('path', '.*');

// Test route compilation
$routes = $router->getRoutes();
foreach ($routes as $route) {
    echo "Route pattern: " . $route['pattern'] . "\n";
    echo "Constraints: " . json_encode($route['constraints']) . "\n";
}

// Test matching
$request = new Request('GET', '/test-route', [], []);
try {
    $response = $router->dispatch($request);
    echo "Response: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
