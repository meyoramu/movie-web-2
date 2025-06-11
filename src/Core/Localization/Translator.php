<?php

namespace CineVerse\Core\Localization;

use Exception;

/**
 * Translator Class
 * 
 * Handles multi-language support for CineVerse application
 */
class Translator
{
    private array $config;
    private string $currentLanguage;
    private array $translations = [];
    private string $fallbackLanguage;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->currentLanguage = $config['default_language'] ?? 'en';
        $this->fallbackLanguage = 'en';
        
        $this->loadTranslations($this->currentLanguage);
    }

    /**
     * Set current language
     */
    public function setLanguage(string $language): void
    {
        if (!in_array($language, $this->config['supported_languages'] ?? ['en'])) {
            throw new Exception("Unsupported language: {$language}");
        }

        $this->currentLanguage = $language;
        $this->loadTranslations($language);
    }

    /**
     * Get current language
     */
    public function getCurrentLanguage(): string
    {
        return $this->currentLanguage;
    }

    /**
     * Get supported languages
     */
    public function getSupportedLanguages(): array
    {
        return $this->config['supported_languages'] ?? ['en'];
    }

    /**
     * Translate a key
     */
    public function translate(string $key, array $parameters = [], ?string $language = null): string
    {
        $language = $language ?? $this->currentLanguage;
        
        // Get translation from loaded translations
        $translation = $this->getTranslation($key, $language);
        
        // If not found and not using fallback, try fallback language
        if ($translation === $key && $language !== $this->fallbackLanguage) {
            $translation = $this->getTranslation($key, $this->fallbackLanguage);
        }
        
        // Replace parameters in translation
        return $this->replaceParameters($translation, $parameters);
    }

    /**
     * Alias for translate method
     */
    public function t(string $key, array $parameters = [], ?string $language = null): string
    {
        return $this->translate($key, $parameters, $language);
    }

    /**
     * Translate with pluralization
     */
    public function translatePlural(string $key, int $count, array $parameters = [], ?string $language = null): string
    {
        $language = $language ?? $this->currentLanguage;
        
        // Add count to parameters
        $parameters['count'] = $count;
        
        // Determine plural form
        $pluralKey = $this->getPluralKey($key, $count, $language);
        
        return $this->translate($pluralKey, $parameters, $language);
    }

    /**
     * Check if translation exists
     */
    public function has(string $key, ?string $language = null): bool
    {
        $language = $language ?? $this->currentLanguage;
        return $this->getTranslation($key, $language) !== $key;
    }

    /**
     * Load translations for a language
     */
    private function loadTranslations(string $language): void
    {
        if (isset($this->translations[$language])) {
            return;
        }

        $this->translations[$language] = [];
        
        // Load from language files
        $langPath = __DIR__ . '/../../../resources/lang/' . $language;
        
        if (!is_dir($langPath)) {
            // Create directory if it doesn't exist
            mkdir($langPath, 0755, true);
            $this->createDefaultTranslations($language);
        }
        
        // Load all PHP files in language directory
        $files = glob($langPath . '/*.php');
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            $translations = include $file;
            
            if (is_array($translations)) {
                $this->translations[$language][$filename] = $translations;
            }
        }
    }

    /**
     * Get translation for a key
     */
    private function getTranslation(string $key, string $language): string
    {
        // Ensure translations are loaded
        if (!isset($this->translations[$language])) {
            $this->loadTranslations($language);
        }
        
        // Parse key (format: file.key or file.nested.key)
        $parts = explode('.', $key);
        
        if (count($parts) < 2) {
            return $key; // Invalid key format
        }
        
        $file = array_shift($parts);
        $translations = $this->translations[$language][$file] ?? [];
        
        // Navigate through nested keys
        foreach ($parts as $part) {
            if (!is_array($translations) || !isset($translations[$part])) {
                return $key; // Translation not found
            }
            $translations = $translations[$part];
        }
        
        return is_string($translations) ? $translations : $key;
    }

    /**
     * Replace parameters in translation
     */
    private function replaceParameters(string $translation, array $parameters): string
    {
        foreach ($parameters as $key => $value) {
            $translation = str_replace(':' . $key, $value, $translation);
        }
        
        return $translation;
    }

    /**
     * Get plural key based on count and language
     */
    private function getPluralKey(string $key, int $count, string $language): string
    {
        // Simple pluralization rules (can be extended for different languages)
        switch ($language) {
            case 'en':
                return $count === 1 ? $key : $key . '_plural';
            case 'fr':
                return $count <= 1 ? $key : $key . '_plural';
            case 'rw': // Kinyarwanda
                return $key; // Kinyarwanda doesn't have plural forms like English
            default:
                return $count === 1 ? $key : $key . '_plural';
        }
    }

    /**
     * Create default translation files
     */
    private function createDefaultTranslations(string $language): void
    {
        $langPath = __DIR__ . '/../../../resources/lang/' . $language;
        
        // Create common.php with basic translations
        $commonTranslations = $this->getDefaultCommonTranslations($language);
        file_put_contents($langPath . '/common.php', "<?php\n\nreturn " . var_export($commonTranslations, true) . ";\n");
        
        // Create auth.php with authentication translations
        $authTranslations = $this->getDefaultAuthTranslations($language);
        file_put_contents($langPath . '/auth.php', "<?php\n\nreturn " . var_export($authTranslations, true) . ";\n");
    }

    /**
     * Get default common translations
     */
    private function getDefaultCommonTranslations(string $language): array
    {
        switch ($language) {
            case 'en':
                return [
                    'welcome' => 'Welcome',
                    'home' => 'Home',
                    'about' => 'About',
                    'contact' => 'Contact',
                    'login' => 'Login',
                    'logout' => 'Logout',
                    'register' => 'Register',
                    'search' => 'Search',
                    'movies' => 'Movies',
                    'tv_shows' => 'TV Shows',
                    'genres' => 'Genres',
                    'year' => 'Year',
                    'rating' => 'Rating',
                    'duration' => 'Duration',
                    'cast' => 'Cast',
                    'director' => 'Director',
                    'plot' => 'Plot',
                    'trailer' => 'Trailer',
                    'watch_now' => 'Watch Now',
                    'add_to_watchlist' => 'Add to Watchlist',
                    'remove_from_watchlist' => 'Remove from Watchlist',
                ];
            case 'fr':
                return [
                    'welcome' => 'Bienvenue',
                    'home' => 'Accueil',
                    'about' => 'À propos',
                    'contact' => 'Contact',
                    'login' => 'Connexion',
                    'logout' => 'Déconnexion',
                    'register' => 'S\'inscrire',
                    'search' => 'Rechercher',
                    'movies' => 'Films',
                    'tv_shows' => 'Séries TV',
                    'genres' => 'Genres',
                    'year' => 'Année',
                    'rating' => 'Note',
                    'duration' => 'Durée',
                    'cast' => 'Distribution',
                    'director' => 'Réalisateur',
                    'plot' => 'Intrigue',
                    'trailer' => 'Bande-annonce',
                    'watch_now' => 'Regarder maintenant',
                    'add_to_watchlist' => 'Ajouter à la liste',
                    'remove_from_watchlist' => 'Retirer de la liste',
                ];
            case 'rw':
                return [
                    'welcome' => 'Murakaza neza',
                    'home' => 'Ahabanza',
                    'about' => 'Ibibazo',
                    'contact' => 'Twandikire',
                    'login' => 'Injira',
                    'logout' => 'Sohoka',
                    'register' => 'Iyandikishe',
                    'search' => 'Shakisha',
                    'movies' => 'Filime',
                    'tv_shows' => 'Ikiganiro cya TV',
                    'genres' => 'Ubwoko',
                    'year' => 'Umwaka',
                    'rating' => 'Amanota',
                    'duration' => 'Igihe',
                    'cast' => 'Abakinnyi',
                    'director' => 'Umuyobozi',
                    'plot' => 'Inkuru',
                    'trailer' => 'Igice',
                    'watch_now' => 'Reba ubu',
                    'add_to_watchlist' => 'Ongeraho ku rutonde',
                    'remove_from_watchlist' => 'Kuraho ku rutonde',
                ];
            default:
                return [];
        }
    }

    /**
     * Get default auth translations
     */
    private function getDefaultAuthTranslations(string $language): array
    {
        switch ($language) {
            case 'en':
                return [
                    'login_success' => 'Login successful',
                    'login_failed' => 'Login failed',
                    'logout_success' => 'Logout successful',
                    'register_success' => 'Registration successful',
                    'register_failed' => 'Registration failed',
                    'invalid_credentials' => 'Invalid credentials',
                    'account_locked' => 'Account locked',
                    'password_reset_sent' => 'Password reset email sent',
                ];
            case 'fr':
                return [
                    'login_success' => 'Connexion réussie',
                    'login_failed' => 'Échec de la connexion',
                    'logout_success' => 'Déconnexion réussie',
                    'register_success' => 'Inscription réussie',
                    'register_failed' => 'Échec de l\'inscription',
                    'invalid_credentials' => 'Identifiants invalides',
                    'account_locked' => 'Compte verrouillé',
                    'password_reset_sent' => 'Email de réinitialisation envoyé',
                ];
            case 'rw':
                return [
                    'login_success' => 'Kwinjira byagenze neza',
                    'login_failed' => 'Kwinjira ntibyakunze',
                    'logout_success' => 'Gusohoka byagenze neza',
                    'register_success' => 'Kwiyandikisha byagenze neza',
                    'register_failed' => 'Kwiyandikisha ntibyakunze',
                    'invalid_credentials' => 'Amakuru atari yo',
                    'account_locked' => 'Konti ifunze',
                    'password_reset_sent' => 'Ubutumwa bwo guhindura ijambo ry\'ibanga bwoherejwe',
                ];
            default:
                return [];
        }
    }
}
