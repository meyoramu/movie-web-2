-- CineVerse Database Schema
-- Production-ready database structure with proper indexing and constraints

-- Create database (MySQL)
CREATE DATABASE IF NOT EXISTS cineverse_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cineverse_db;

-- Users table with comprehensive user management
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL,
    date_of_birth DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    country VARCHAR(2) DEFAULT 'RW',
    language VARCHAR(5) DEFAULT 'en',
    role ENUM('user', 'admin', 'moderator') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended', 'banned') DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_uuid (uuid),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Password reset tokens
CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
);

-- Email verification tokens
CREATE TABLE email_verification_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token)
);

-- User sessions
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
);

-- Movies table (cached from external APIs)
CREATE TABLE movies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_id VARCHAR(50) NOT NULL,
    source VARCHAR(20) NOT NULL DEFAULT 'tmdb',
    title VARCHAR(255) NOT NULL,
    original_title VARCHAR(255) NULL,
    overview TEXT NULL,
    release_date DATE NULL,
    runtime INT NULL,
    budget BIGINT NULL,
    revenue BIGINT NULL,
    vote_average DECIMAL(3,1) DEFAULT 0.0,
    vote_count INT DEFAULT 0,
    popularity DECIMAL(8,3) DEFAULT 0.0,
    poster_path VARCHAR(255) NULL,
    backdrop_path VARCHAR(255) NULL,
    trailer_url VARCHAR(255) NULL,
    imdb_id VARCHAR(20) NULL,
    status ENUM('rumored', 'planned', 'in_production', 'post_production', 'released', 'canceled') DEFAULT 'released',
    adult BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_external_source (external_id, source),
    INDEX idx_title (title),
    INDEX idx_release_date (release_date),
    INDEX idx_vote_average (vote_average),
    INDEX idx_popularity (popularity),
    FULLTEXT idx_search (title, original_title, overview)
);

-- Movie genres
CREATE TABLE genres (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_slug (slug)
);

-- Movie-Genre pivot table
CREATE TABLE movie_genres (
    movie_id BIGINT UNSIGNED NOT NULL,
    genre_id INT UNSIGNED NOT NULL,
    
    PRIMARY KEY (movie_id, genre_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);

-- User watchlists
CREATE TABLE user_watchlists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    movie_id BIGINT UNSIGNED NOT NULL,
    status ENUM('want_to_watch', 'watching', 'watched', 'dropped') DEFAULT 'want_to_watch',
    rating TINYINT NULL CHECK (rating >= 1 AND rating <= 10),
    review TEXT NULL,
    watched_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_movie (user_id, movie_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_rating (rating)
);

-- User activity logs
CREATE TABLE user_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
);

-- Payment transactions
CREATE TABLE payment_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    payment_method ENUM('mtn_mobile_money', 'airtel_money', 'card', 'bank_transfer') NOT NULL,
    transaction_type ENUM('subscription', 'purchase', 'refund') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'RWF',
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    external_transaction_id VARCHAR(255) NULL,
    phone_number VARCHAR(20) NULL,
    description TEXT NULL,
    metadata JSON NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method),
    INDEX idx_created_at (created_at),
    INDEX idx_external_transaction_id (external_transaction_id)
);

-- Subscriptions
CREATE TABLE subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_name VARCHAR(50) NOT NULL,
    status ENUM('active', 'cancelled', 'expired', 'suspended') DEFAULT 'active',
    starts_at TIMESTAMP NULL,
    ends_at TIMESTAMP NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_ends_at (ends_at)
);

-- Multi-language content
CREATE TABLE translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    translatable_type VARCHAR(50) NOT NULL,
    translatable_id BIGINT UNSIGNED NOT NULL,
    language VARCHAR(5) NOT NULL,
    field VARCHAR(50) NOT NULL,
    value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_translation (translatable_type, translatable_id, language, field),
    INDEX idx_translatable (translatable_type, translatable_id),
    INDEX idx_language (language)
);

-- System settings
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NULL,
    type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key_name (key_name)
);

-- Analytics data
CREATE TABLE analytics_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    session_id VARCHAR(128) NULL,
    event_name VARCHAR(100) NOT NULL,
    event_data JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    referrer VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_event_name (event_name),
    INDEX idx_created_at (created_at),
    INDEX idx_session_id (session_id)
);

-- Migrations tracking
CREATE TABLE migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INT NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default data
INSERT INTO settings (key_name, value, type, description) VALUES
('site_name', 'CineVerse', 'string', 'Website name'),
('site_description', 'The Future of Movie Discovery', 'string', 'Website description'),
('maintenance_mode', 'false', 'boolean', 'Maintenance mode status'),
('user_registration', 'true', 'boolean', 'Allow user registration'),
('email_verification', 'true', 'boolean', 'Require email verification'),
('default_language', 'en', 'string', 'Default language'),
('supported_languages', '["en", "rw", "fr"]', 'json', 'Supported languages');

-- Insert default genres
INSERT INTO genres (external_id, name, slug) VALUES
(28, 'Action', 'action'),
(12, 'Adventure', 'adventure'),
(16, 'Animation', 'animation'),
(35, 'Comedy', 'comedy'),
(80, 'Crime', 'crime'),
(99, 'Documentary', 'documentary'),
(18, 'Drama', 'drama'),
(10751, 'Family', 'family'),
(14, 'Fantasy', 'fantasy'),
(36, 'History', 'history'),
(27, 'Horror', 'horror'),
(10402, 'Music', 'music'),
(9648, 'Mystery', 'mystery'),
(10749, 'Romance', 'romance'),
(878, 'Science Fiction', 'science-fiction'),
(10770, 'TV Movie', 'tv-movie'),
(53, 'Thriller', 'thriller'),
(10752, 'War', 'war'),
(37, 'Western', 'western');
