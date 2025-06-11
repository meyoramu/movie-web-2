<?php

/**
 * Application Configuration
 * 
 * Main application configuration file for CineVerse
 */

return [
    'name' => $_ENV['APP_NAME'] ?? 'CineVerse',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key',
        'expiry' => (int)($_ENV['JWT_EXPIRY'] ?? 3600),
        'algorithm' => 'HS256'
    ],
    
    'mail' => [
        'mailer' => $_ENV['MAIL_MAILER'] ?? 'smtp',
        'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
        'port' => (int)($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@cineverse.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'CineVerse Team'
        ]
    ],
    
    'payment' => [
        'mtn' => [
            'api_url' => $_ENV['MTN_API_URL'] ?? 'https://sandbox.momodeveloper.mtn.com',
            'primary_key' => $_ENV['MTN_PRIMARY_KEY'] ?? '',
            'secondary_key' => $_ENV['MTN_SECONDARY_KEY'] ?? '',
            'subscription_key' => $_ENV['MTN_SUBSCRIPTION_KEY'] ?? ''
        ],
        'airtel' => [
            'api_url' => $_ENV['AIRTEL_API_URL'] ?? 'https://openapiuat.airtel.africa',
            'client_id' => $_ENV['AIRTEL_CLIENT_ID'] ?? '',
            'client_secret' => $_ENV['AIRTEL_CLIENT_SECRET'] ?? ''
        ]
    ],
    
    'social' => [
        'facebook' => [
            'app_id' => $_ENV['FACEBOOK_APP_ID'] ?? '',
            'app_secret' => $_ENV['FACEBOOK_APP_SECRET'] ?? ''
        ],
        'twitter' => [
            'api_key' => $_ENV['TWITTER_API_KEY'] ?? '',
            'api_secret' => $_ENV['TWITTER_API_SECRET'] ?? ''
        ],
        'google' => [
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
            'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? ''
        ]
    ],
    
    'analytics' => [
        'google_analytics_id' => $_ENV['GOOGLE_ANALYTICS_ID'] ?? ''
    ],
    
    'storage' => [
        'driver' => $_ENV['STORAGE_DRIVER'] ?? 'local',
        'aws' => [
            'access_key_id' => $_ENV['AWS_ACCESS_KEY_ID'] ?? '',
            'secret_access_key' => $_ENV['AWS_SECRET_ACCESS_KEY'] ?? '',
            'default_region' => $_ENV['AWS_DEFAULT_REGION'] ?? 'us-east-1',
            'bucket' => $_ENV['AWS_BUCKET'] ?? ''
        ]
    ],
    
    'cache' => [
        'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
        'redis' => [
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'port' => (int)($_ENV['REDIS_PORT'] ?? 6379)
        ]
    ],
    
    'session' => [
        'driver' => $_ENV['SESSION_DRIVER'] ?? 'file',
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 120),
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => true,
        'same_site' => 'lax'
    ],
    
    'logging' => [
        'channel' => $_ENV['LOG_CHANNEL'] ?? 'daily',
        'level' => $_ENV['LOG_LEVEL'] ?? 'info',
        'path' => __DIR__ . '/../storage/logs'
    ],
    
    'api' => [
        'tmdb_key' => $_ENV['TMDB_API_KEY'] ?? '',
        'omdb_key' => $_ENV['OMDB_API_KEY'] ?? ''
    ],
    
    'security' => [
        'encryption_key' => $_ENV['ENCRYPTION_KEY'] ?? '',
        'csrf_token_name' => $_ENV['CSRF_TOKEN_NAME'] ?? 'csrf_token',
        'rate_limit' => [
            'requests' => (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 100),
            'window' => (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 3600)
        ]
    ],
    
    'localization' => [
        'default_language' => $_ENV['DEFAULT_LANGUAGE'] ?? 'en',
        'supported_languages' => explode(',', $_ENV['SUPPORTED_LANGUAGES'] ?? 'en,rw,fr')
    ]
];
