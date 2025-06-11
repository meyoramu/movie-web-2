<?php

/**
 * API Routes
 * 
 * Define all API routes for the CineVerse application
 * These routes are designed for mobile app integration and AJAX requests
 */

use CineVerse\Core\Application;

$app = Application::getInstance();
$router = $app->get('router');

// API routes with CORS middleware
$router->group(['prefix' => '/api/v1', 'middleware' => ['cors']], function($router) {
    
    // Authentication endpoints
    $router->group(['prefix' => '/auth'], function($router) {
        $router->post('/register', 'Api\\AuthController@register');
        $router->post('/login', 'Api\\AuthController@login');
        $router->post('/logout', 'Api\\AuthController@logout', ['auth']);
        $router->post('/refresh', 'Api\\AuthController@refresh', ['auth']);
        $router->post('/forgot-password', 'Api\\AuthController@forgotPassword');
        $router->post('/reset-password', 'Api\\AuthController@resetPassword');
        $router->post('/verify-email', 'Api\\AuthController@verifyEmail');
        $router->get('/me', 'Api\\AuthController@me', ['auth']);
    });
    
    // User endpoints
    $router->group(['prefix' => '/user', 'middleware' => ['auth']], function($router) {
        $router->get('/profile', 'Api\\UserController@profile');
        $router->put('/profile', 'Api\\UserController@updateProfile');
        $router->post('/avatar', 'Api\\UserController@uploadAvatar');
        $router->get('/watchlist', 'Api\\UserController@watchlist');
        $router->get('/activities', 'Api\\UserController@activities');
        $router->get('/statistics', 'Api\\UserController@statistics');
    });
    
    // Movies endpoints
    $router->group(['prefix' => '/movies'], function($router) {
        $router->get('/', 'Api\\MovieController@index');
        $router->get('/search', 'Api\\MovieController@search');
        $router->get('/trending', 'Api\\MovieController@trending');
        $router->get('/popular', 'Api\\MovieController@popular');
        $router->get('/top-rated', 'Api\\MovieController@topRated');
        $router->get('/upcoming', 'Api\\MovieController@upcoming');
        $router->get('/now-playing', 'Api\\MovieController@nowPlaying');
        $router->get('/genres', 'Api\\MovieController@genres');
        $router->get('/genre/{slug}', 'Api\\MovieController@byGenre');
        $router->get('/{id}', 'Api\\MovieController@show');
        $router->get('/{id}/similar', 'Api\\MovieController@similar');
        $router->get('/{id}/recommendations', 'Api\\MovieController@recommendations');
        
        // Authenticated movie actions
        $router->group(['middleware' => ['auth']], function($router) {
            $router->post('/{id}/watchlist', 'Api\\MovieController@addToWatchlist');
            $router->delete('/{id}/watchlist', 'Api\\MovieController@removeFromWatchlist');
            $router->post('/{id}/rating', 'Api\\MovieController@rate');
            $router->get('/{id}/rating', 'Api\\MovieController@getUserRating');
            $router->post('/{id}/review', 'Api\\MovieController@review');
            $router->get('/{id}/reviews', 'Api\\MovieController@reviews');
        });
    });
    
    // Payment endpoints
    $router->group(['prefix' => '/payment', 'middleware' => ['auth']], function($router) {
        $router->get('/plans', 'Api\\PaymentController@plans');
        $router->post('/subscribe', 'Api\\PaymentController@subscribe');
        $router->get('/subscription', 'Api\\PaymentController@subscription');
        $router->post('/cancel-subscription', 'Api\\PaymentController@cancelSubscription');
        $router->get('/transactions', 'Api\\PaymentController@transactions');
        $router->get('/transactions/{id}', 'Api\\PaymentController@transaction');
    });
    
    // Search endpoints
    $router->group(['prefix' => '/search'], function($router) {
        $router->get('/movies', 'Api\\SearchController@movies');
        $router->get('/suggestions', 'Api\\SearchController@suggestions');
        $router->get('/autocomplete', 'Api\\SearchController@autocomplete');
    });
    
    // Analytics endpoints (for tracking user behavior)
    $router->group(['prefix' => '/analytics'], function($router) {
        $router->post('/event', 'Api\\AnalyticsController@trackEvent');
        $router->post('/page-view', 'Api\\AnalyticsController@trackPageView');
        $router->post('/movie-view', 'Api\\AnalyticsController@trackMovieView');
    });
    
    // Admin API endpoints
    $router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function($router) {
        
        // Dashboard stats
        $router->get('/stats', 'Api\\Admin\\DashboardController@stats');
        
        // User management
        $router->get('/users', 'Api\\Admin\\UserController@index');
        $router->get('/users/{id}', 'Api\\Admin\\UserController@show');
        $router->put('/users/{id}', 'Api\\Admin\\UserController@update');
        $router->post('/users/{id}/status', 'Api\\Admin\\UserController@updateStatus');
        $router->delete('/users/{id}', 'Api\\Admin\\UserController@delete');
        $router->get('/users/{id}/activities', 'Api\\Admin\\UserController@activities');
        
        // Movie management
        $router->get('/movies', 'Api\\Admin\\MovieController@index');
        $router->post('/movies', 'Api\\Admin\\MovieController@store');
        $router->get('/movies/{id}', 'Api\\Admin\\MovieController@show');
        $router->put('/movies/{id}', 'Api\\Admin\\MovieController@update');
        $router->delete('/movies/{id}', 'Api\\Admin\\MovieController@delete');
        $router->post('/movies/sync', 'Api\\Admin\\MovieController@syncFromApi');
        $router->post('/movies/bulk-import', 'Api\\Admin\\MovieController@bulkImport');
        
        // Analytics
        $router->get('/analytics/overview', 'Api\\Admin\\AnalyticsController@overview');
        $router->get('/analytics/users', 'Api\\Admin\\AnalyticsController@userAnalytics');
        $router->get('/analytics/movies', 'Api\\Admin\\AnalyticsController@movieAnalytics');
        $router->get('/analytics/payments', 'Api\\Admin\\AnalyticsController@paymentAnalytics');
        $router->get('/analytics/engagement', 'Api\\Admin\\AnalyticsController@engagementAnalytics');
        
        // System settings
        $router->get('/settings', 'Api\\Admin\\SettingsController@index');
        $router->put('/settings', 'Api\\Admin\\SettingsController@update');
        $router->post('/settings/cache/clear', 'Api\\Admin\\SettingsController@clearCache');
        
        // Translations
        $router->get('/translations', 'Api\\Admin\\TranslationController@index');
        $router->post('/translations', 'Api\\Admin\\TranslationController@store');
        $router->put('/translations/{id}', 'Api\\Admin\\TranslationController@update');
        $router->delete('/translations/{id}', 'Api\\Admin\\TranslationController@delete');
        $router->post('/translations/import', 'Api\\Admin\\TranslationController@import');
        $router->get('/translations/export', 'Api\\Admin\\TranslationController@export');
    });
    
    // Public API endpoints (no authentication required)
    $router->group(['prefix' => '/public'], function($router) {
        $router->get('/movies/featured', 'Api\\PublicController@featuredMovies');
        $router->get('/movies/trending', 'Api\\PublicController@trendingMovies');
        $router->get('/genres', 'Api\\PublicController@genres');
        $router->get('/stats', 'Api\\PublicController@publicStats');
    });
    
    // Webhook endpoints
    $router->group(['prefix' => '/webhooks'], function($router) {
        $router->post('/mtn-mobile-money', 'Api\\WebhookController@mtnMobileMoney');
        $router->post('/airtel-money', 'Api\\WebhookController@airtelMoney');
        $router->post('/tmdb-update', 'Api\\WebhookController@tmdbUpdate');
    });
    
    // Health check
    $router->get('/health', function() {
        return \CineVerse\Core\Http\Response::json([
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'production'
        ]);
    });
    
    // API documentation endpoint
    $router->get('/docs', function() {
        return \CineVerse\Core\Http\Response::json([
            'message' => 'CineVerse API v1.0',
            'documentation' => '/api/v1/docs',
            'endpoints' => [
                'auth' => '/api/v1/auth',
                'movies' => '/api/v1/movies',
                'user' => '/api/v1/user',
                'payment' => '/api/v1/payment',
                'search' => '/api/v1/search'
            ]
        ]);
    });
});

// Rate limiting for API routes
$router->group(['prefix' => '/api', 'middleware' => ['throttle']], function($router) {
    // All API routes will have rate limiting applied
});
