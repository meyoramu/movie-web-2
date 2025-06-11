<?php

namespace CineVerse\Controllers;

use CineVerse\Core\Application;
use CineVerse\Core\Http\Request;
use CineVerse\Core\Http\Response;

/**
 * Home Controller
 * 
 * Handles the main homepage and landing page functionality
 */
class HomeController
{
    private Application $app;

    public function __construct()
    {
        $this->app = Application::getInstance();
    }

    /**
     * Display the homepage
     */
    public function index(Request $request): Response
    {
        try {
            // Get featured movies (if database is available)
            $featuredMovies = [];
            $trendingMovies = [];
            $genres = [];
            
            $database = $this->app->get('database');
            if ($database) {
                try {
                    // Get some featured movies
                    $featuredMovies = $database->fetchAll(
                        "SELECT * FROM movies WHERE vote_average >= 7.0 ORDER BY popularity DESC LIMIT 6"
                    );
                    
                    // Get trending movies
                    $trendingMovies = $database->fetchAll(
                        "SELECT * FROM movies ORDER BY popularity DESC LIMIT 12"
                    );
                    
                    // Get genres
                    $genres = $database->fetchAll("SELECT * FROM genres ORDER BY name ASC");
                    
                } catch (\Exception $e) {
                    // Log error but continue without database data
                    $this->app->get('logger')?->error('Database query failed: ' . $e->getMessage());
                }
            }

            // If this is an API request, return JSON
            if ($request->expectsJson()) {
                return Response::json([
                    'success' => true,
                    'data' => [
                        'featured_movies' => $featuredMovies,
                        'trending_movies' => $trendingMovies,
                        'genres' => $genres,
                        'app_name' => $this->app->config('app.name', 'CineVerse'),
                        'app_description' => 'The Future of Movie Discovery'
                    ]
                ]);
            }

            // Return HTML response
            return $this->renderHomepage($featuredMovies, $trendingMovies, $genres);

        } catch (\Exception $e) {
            $this->app->get('logger')?->error('Homepage error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return Response::error('Unable to load homepage', 500);
            }
            
            return $this->renderErrorPage();
        }
    }

    /**
     * Render the homepage HTML
     */
    private function renderHomepage(array $featuredMovies, array $trendingMovies, array $genres): Response
    {
        $appName = $this->app->config('app.name', 'CineVerse');
        $appDescription = 'The Future of Movie Discovery';
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$appName} - {$appDescription}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f0f0f; color: #fff; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        header { background: linear-gradient(135deg, #1a1a1a, #2d2d2d); padding: 20px 0; }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 2rem; font-weight: bold; color: #e50914; }
        nav a { color: #fff; text-decoration: none; margin: 0 15px; transition: color 0.3s; }
        nav a:hover { color: #e50914; }
        .hero { background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23333" width="1200" height="600"/></svg>'); 
                background-size: cover; padding: 100px 0; text-align: center; }
        .hero h1 { font-size: 3.5rem; margin-bottom: 20px; }
        .hero p { font-size: 1.2rem; margin-bottom: 30px; opacity: 0.9; }
        .btn { display: inline-block; background: #e50914; color: #fff; padding: 15px 30px; text-decoration: none; 
               border-radius: 5px; font-weight: bold; transition: background 0.3s; }
        .btn:hover { background: #b8070f; }
        .section { padding: 60px 0; }
        .section h2 { font-size: 2.5rem; margin-bottom: 40px; text-align: center; }
        .movies-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px; }
        .movie-card { background: #1a1a1a; border-radius: 10px; overflow: hidden; transition: transform 0.3s; }
        .movie-card:hover { transform: translateY(-5px); }
        .movie-poster { width: 100%; height: 300px; background: #333; display: flex; align-items: center; justify-content: center; }
        .movie-info { padding: 15px; }
        .movie-title { font-weight: bold; margin-bottom: 5px; }
        .movie-rating { color: #ffd700; }
        .genres { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-top: 30px; }
        .genre-tag { background: #333; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; }
        .stats { background: #1a1a1a; padding: 40px 0; text-align: center; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; }
        .stat-item h3 { font-size: 2rem; color: #e50914; margin-bottom: 10px; }
        footer { background: #0a0a0a; padding: 40px 0; text-align: center; }
        .footer-links { margin-bottom: 20px; }
        .footer-links a { color: #ccc; text-decoration: none; margin: 0 15px; }
        .footer-links a:hover { color: #fff; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">{$appName}</div>
                <nav>
                    <a href="/">Home</a>
                    <a href="/movies">Movies</a>
                    <a href="/auth/login">Login</a>
                    <a href="/auth/register">Register</a>
                </nav>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Welcome to {$appName}</h1>
            <p>{$appDescription}</p>
            <a href="/movies" class="btn">Explore Movies</a>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>Featured Movies</h2>
            <div class="movies-grid">
HTML;

        // Add featured movies
        if (!empty($featuredMovies)) {
            foreach (array_slice($featuredMovies, 0, 6) as $movie) {
                $title = htmlspecialchars($movie['title'] ?? 'Unknown Title');
                $rating = number_format($movie['vote_average'] ?? 0, 1);
                $html .= <<<MOVIE
                <div class="movie-card">
                    <div class="movie-poster">🎬</div>
                    <div class="movie-info">
                        <div class="movie-title">{$title}</div>
                        <div class="movie-rating">⭐ {$rating}</div>
                    </div>
                </div>
MOVIE;
            }
        } else {
            // Show placeholder movies
            for ($i = 1; $i <= 6; $i++) {
                $html .= <<<MOVIE
                <div class="movie-card">
                    <div class="movie-poster">🎬</div>
                    <div class="movie-info">
                        <div class="movie-title">Sample Movie {$i}</div>
                        <div class="movie-rating">⭐ 8.5</div>
                    </div>
                </div>
MOVIE;
            }
        }

        $html .= <<<HTML
            </div>
        </div>
    </section>

    <section class="section" style="background: #1a1a1a;">
        <div class="container">
            <h2>Browse by Genre</h2>
            <div class="genres">
HTML;

        // Add genres
        if (!empty($genres)) {
            foreach ($genres as $genre) {
                $name = htmlspecialchars($genre['name']);
                $slug = htmlspecialchars($genre['slug']);
                $html .= "<div class=\"genre-tag\"><a href=\"/movies/genre/{$slug}\" style=\"color: inherit; text-decoration: none;\">{$name}</a></div>";
            }
        } else {
            // Show default genres
            $defaultGenres = ['Action', 'Comedy', 'Drama', 'Horror', 'Romance', 'Sci-Fi', 'Thriller'];
            foreach ($defaultGenres as $genre) {
                $html .= "<div class=\"genre-tag\">{$genre}</div>";
            }
        }

        $movieCount = count($featuredMovies) + count($trendingMovies);
        $genreCount = count($genres) ?: 18;

        $html .= <<<HTML
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>{$movieCount}+</h3>
                    <p>Movies Available</p>
                </div>
                <div class="stat-item">
                    <h3>{$genreCount}</h3>
                    <p>Genres</p>
                </div>
                <div class="stat-item">
                    <h3>HD</h3>
                    <p>Quality Streaming</p>
                </div>
                <div class="stat-item">
                    <h3>24/7</h3>
                    <p>Available</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-links">
                <a href="/about">About</a>
                <a href="/contact">Contact</a>
                <a href="/privacy">Privacy</a>
                <a href="/terms">Terms</a>
                <a href="/help">Help</a>
            </div>
            <p>&copy; 2024 {$appName}. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
HTML;

        return Response::make($html);
    }

    /**
     * Render error page
     */
    private function renderErrorPage(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineVerse - Service Unavailable</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f0f0f; color: #fff; text-align: center; padding: 50px; }
        .error-container { max-width: 600px; margin: 0 auto; }
        h1 { color: #e50914; font-size: 3rem; margin-bottom: 20px; }
        p { font-size: 1.2rem; margin-bottom: 30px; }
        .btn { display: inline-block; background: #e50914; color: #fff; padding: 15px 30px; 
               text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Service Temporarily Unavailable</h1>
        <p>We're experiencing some technical difficulties. Please try again later.</p>
        <a href="/" class="btn">Try Again</a>
    </div>
</body>
</html>
HTML;

        return Response::make($html, 503);
    }
}
