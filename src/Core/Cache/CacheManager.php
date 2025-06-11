<?php

namespace CineVerse\Core\Cache;

use Exception;

/**
 * Cache Manager
 * 
 * Handles caching functionality for CineVerse application
 */
class CacheManager
{
    private array $config;
    private string $driver;
    private ?object $connection = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->driver = $config['driver'] ?? 'file';
        $this->initializeDriver();
    }

    /**
     * Initialize cache driver
     */
    private function initializeDriver(): void
    {
        switch ($this->driver) {
            case 'redis':
                $this->initializeRedis();
                break;
            case 'file':
            default:
                $this->initializeFile();
                break;
        }
    }

    /**
     * Initialize Redis driver
     */
    private function initializeRedis(): void
    {
        if (!extension_loaded('redis')) {
            throw new Exception('Redis extension is not installed');
        }

        try {
            $redis = new \Redis();
            $redis->connect(
                $this->config['redis']['host'] ?? '127.0.0.1',
                $this->config['redis']['port'] ?? 6379
            );

            if (!empty($this->config['redis']['password'])) {
                $redis->auth($this->config['redis']['password']);
            }

            $this->connection = $redis;
        } catch (Exception $e) {
            throw new Exception('Failed to connect to Redis: ' . $e->getMessage());
        }
    }

    /**
     * Initialize file driver
     */
    private function initializeFile(): void
    {
        $cachePath = __DIR__ . '/../../../storage/cache';
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
    }

    /**
     * Get cached value
     */
    public function get(string $key, $default = null)
    {
        try {
            switch ($this->driver) {
                case 'redis':
                    return $this->getFromRedis($key, $default);
                case 'file':
                default:
                    return $this->getFromFile($key, $default);
            }
        } catch (Exception $e) {
            error_log("Cache get failed: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Set cached value
     */
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        try {
            switch ($this->driver) {
                case 'redis':
                    return $this->setToRedis($key, $value, $ttl);
                case 'file':
                default:
                    return $this->setToFile($key, $value, $ttl);
            }
        } catch (Exception $e) {
            error_log("Cache set failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete cached value
     */
    public function delete(string $key): bool
    {
        try {
            switch ($this->driver) {
                case 'redis':
                    return $this->deleteFromRedis($key);
                case 'file':
                default:
                    return $this->deleteFromFile($key);
            }
        } catch (Exception $e) {
            error_log("Cache delete failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all cached values
     */
    public function clear(): bool
    {
        try {
            switch ($this->driver) {
                case 'redis':
                    return $this->clearRedis();
                case 'file':
                default:
                    return $this->clearFile();
            }
        } catch (Exception $e) {
            error_log("Cache clear failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get multiple cached values
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * Set multiple cached values
     */
    public function setMultiple(array $values, int $ttl = 3600): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Remember - get from cache or execute callback and cache result
     */
    public function remember(string $key, callable $callback, int $ttl = 3600)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    // Redis-specific methods
    private function getFromRedis(string $key, $default = null)
    {
        $value = $this->connection->get($key);
        return $value !== false ? unserialize($value) : $default;
    }

    private function setToRedis(string $key, $value, int $ttl): bool
    {
        return $this->connection->setex($key, $ttl, serialize($value));
    }

    private function deleteFromRedis(string $key): bool
    {
        return $this->connection->del($key) > 0;
    }

    private function clearRedis(): bool
    {
        return $this->connection->flushDB();
    }

    // File-specific methods
    private function getFromFile(string $key, $default = null)
    {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($filename));
        
        // Check if expired
        if ($data['expires'] < time()) {
            unlink($filename);
            return $default;
        }
        
        return $data['value'];
    }

    private function setToFile(string $key, $value, int $ttl): bool
    {
        $filename = $this->getFilename($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($filename, serialize($data), LOCK_EX) !== false;
    }

    private function deleteFromFile(string $key): bool
    {
        $filename = $this->getFilename($key);
        return file_exists($filename) ? unlink($filename) : true;
    }

    private function clearFile(): bool
    {
        $cachePath = __DIR__ . '/../../../storage/cache';
        $files = glob($cachePath . '/*.cache');
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                return false;
            }
        }
        
        return true;
    }

    private function getFilename(string $key): string
    {
        $cachePath = __DIR__ . '/../../../storage/cache';
        return $cachePath . '/' . md5($key) . '.cache';
    }

    /**
     * Clean up expired cache files
     */
    public function gc(): void
    {
        if ($this->driver !== 'file') {
            return;
        }

        $cachePath = __DIR__ . '/../../../storage/cache';
        $files = glob($cachePath . '/*.cache');
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                $data = unserialize(file_get_contents($file));
                if ($data['expires'] < time()) {
                    unlink($file);
                }
            }
        }
    }
}
