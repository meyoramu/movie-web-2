<?php

/**
 * Web Routes
 * 
 * Define all web routes for the CineVerse application
 */

use CineVerse\Core\Application;

$app = Application::getInstance();
$router = $app->get('router');

// Home page
$router->get('/', 'HomeController@index');

// Authentication routes
$router->group(['prefix' => '/auth'], function($router) {
    // Registration
    $router->get('/register', 'AuthController@showRegister');
    $router->post('/register', 'AuthController@register');
    
    // Login
    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@login');
    
    // Logout
    $router->post('/logout', 'AuthController@logout', ['auth']);
    
    // Password reset
    $router->get('/forgot-password', 'AuthController@showForgotPassword');
    $router->post('/forgot-password', 'AuthController@forgotPassword');
    $router->get('/reset-password/{token}', 'AuthController@showResetPassword');
    $router->post('/reset-password', 'AuthController@resetPassword');
    
    // Email verification
    $router->get('/verify-email/{token}', 'AuthController@verifyEmail');
    $router->post('/resend-verification', 'AuthController@resendVerification', ['auth']);
});

// User dashboard
$router->group(['prefix' => '/dashboard', 'middleware' => ['auth']], function($router) {
    $router->get('/', 'DashboardController@index');
    $router->get('/profile', 'DashboardController@profile');
    $router->post('/profile', 'DashboardController@updateProfile');
    $router->get('/watchlist', 'DashboardController@watchlist');
    $router->get('/settings', 'DashboardController@settings');
    $router->post('/settings', 'DashboardController@updateSettings');
});

// Movies
$router->group(['prefix' => '/movies'], function($router) {
    $router->get('/', 'MovieController@index');
    $router->get('/search', 'MovieController@search');
    $router->get('/genre/{slug}', 'MovieController@byGenre');
    $router->get('/{id}', 'MovieController@show');
    
    // Authenticated movie actions
    $router->group(['middleware' => ['auth']], function($router) {
        $router->post('/{id}/watchlist', 'MovieController@addToWatchlist');
        $router->delete('/{id}/watchlist', 'MovieController@removeFromWatchlist');
        $router->post('/{id}/rating', 'MovieController@rate');
        $router->post('/{id}/review', 'MovieController@review');
    });
});

// Payment routes
$router->group(['prefix' => '/payment', 'middleware' => ['auth']], function($router) {
    $router->get('/plans', 'PaymentController@plans');
    $router->post('/subscribe', 'PaymentController@subscribe');
    $router->get('/success', 'PaymentController@success');
    $router->get('/cancel', 'PaymentController@cancel');
    $router->post('/webhook/mtn', 'PaymentController@mtnWebhook');
    $router->post('/webhook/airtel', 'PaymentController@airtelWebhook');
});

// Admin routes
$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function($router) {
    $router->get('/', 'Admin\\DashboardController@index');
    
    // User management
    $router->get('/users', 'Admin\\UserController@index');
    $router->get('/users/{id}', 'Admin\\UserController@show');
    $router->post('/users/{id}/status', 'Admin\\UserController@updateStatus');
    $router->delete('/users/{id}', 'Admin\\UserController@delete');
    
    // Movie management
    $router->get('/movies', 'Admin\\MovieController@index');
    $router->post('/movies/sync', 'Admin\\MovieController@syncFromApi');
    $router->get('/movies/{id}', 'Admin\\MovieController@show');
    $router->put('/movies/{id}', 'Admin\\MovieController@update');
    $router->delete('/movies/{id}', 'Admin\\MovieController@delete');
    
    // Analytics
    $router->get('/analytics', 'Admin\\AnalyticsController@index');
    $router->get('/analytics/users', 'Admin\\AnalyticsController@users');
    $router->get('/analytics/movies', 'Admin\\AnalyticsController@movies');
    $router->get('/analytics/payments', 'Admin\\AnalyticsController@payments');
    
    // Settings
    $router->get('/settings', 'Admin\\SettingsController@index');
    $router->post('/settings', 'Admin\\SettingsController@update');
    
    // Translations
    $router->get('/translations', 'Admin\\TranslationController@index');
    $router->post('/translations', 'Admin\\TranslationController@store');
    $router->put('/translations/{id}', 'Admin\\TranslationController@update');
    $router->delete('/translations/{id}', 'Admin\\TranslationController@delete');
});

// Language switching
$router->get('/lang/{language}', function($request, $params) use ($app) {
    $language = $params['language'];
    $supportedLanguages = $app->config('app.localization.supported_languages');
    
    if (in_array($language, $supportedLanguages)) {
        $app->get('session')->set('language', $language);
    }
    
    $referrer = $request->getReferrer() ?: '/';
    return \CineVerse\Core\Http\Response::redirect($referrer);
});

// Static pages
$router->get('/about', 'PageController@about');
$router->get('/contact', 'PageController@contact');
$router->post('/contact', 'PageController@submitContact');
$router->get('/privacy', 'PageController@privacy');
$router->get('/terms', 'PageController@terms');
$router->get('/help', 'PageController@help');

// Sitemap and robots
$router->get('/sitemap.xml', 'SeoController@sitemap');
$router->get('/robots.txt', 'SeoController@robots');

// Health check
$router->get('/health', function() {
    return \CineVerse\Core\Http\Response::json([
        'status' => 'ok',
        'timestamp' => date('c'),
        'version' => '1.0.0'
    ]);
});

// Catch-all route for SPA (if needed)
$router->get('/{path}', function($request, $params) {
    $path = $params['path'];
    
    // Check if it's a file request
    if (str_contains($path, '.')) {
        return \CineVerse\Core\Http\Response::notFound();
    }
    
    // Return main app view for SPA routes
    return \CineVerse\Core\Http\Response::view('app');
})->where('path', '.*');
