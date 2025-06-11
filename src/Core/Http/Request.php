<?php

namespace CineVerse\Core\Http;

/**
 * HTTP Request Handler
 * 
 * Handles incoming HTTP requests and provides convenient access to request data
 */
class Request
{
    private array $query;
    private array $post;
    private array $files;
    private array $server;
    private array $headers;
    private string $method;
    private string $uri;
    private string $path;
    private ?string $body = null;

    public function __construct(
        array $query = [],
        array $post = [],
        array $files = [],
        array $server = []
    ) {
        $this->query = $query;
        $this->post = $post;
        $this->files = $files;
        $this->server = $server;
        $this->method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $server['REQUEST_URI'] ?? '/';
        $this->path = parse_url($this->uri, PHP_URL_PATH) ?? '/';
        $this->headers = $this->parseHeaders();
    }

    /**
     * Create request from PHP globals
     */
    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_FILES, $_SERVER);
    }

    /**
     * Get request method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get request URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get request path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Check if request method matches
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /**
     * Check if request is GET
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Check if request is POST
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if request is PUT
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /**
     * Check if request is DELETE
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Check if request expects JSON response
     */
    public function expectsJson(): bool
    {
        return $this->isAjax() || 
               str_contains($this->getHeader('Accept', ''), 'application/json');
    }

    /**
     * Get input value from query or post data
     */
    public function get(string $key, $default = null)
    {
        return $this->query[$key] ?? $this->post[$key] ?? $default;
    }

    /**
     * Get query parameter
     */
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get POST data
     */
    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * Get all input data
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * Get only specified keys from input
     */
    public function only(array $keys): array
    {
        $result = [];
        $input = $this->all();
        
        foreach ($keys as $key) {
            if (isset($input[$key])) {
                $result[$key] = $input[$key];
            }
        }
        
        return $result;
    }

    /**
     * Get input except specified keys
     */
    public function except(array $keys): array
    {
        $input = $this->all();
        
        foreach ($keys as $key) {
            unset($input[$key]);
        }
        
        return $input;
    }

    /**
     * Check if input has key
     */
    public function has(string $key): bool
    {
        return isset($this->query[$key]) || isset($this->post[$key]);
    }

    /**
     * Check if input has value (not empty)
     */
    public function filled(string $key): bool
    {
        return $this->has($key) && !empty($this->get($key));
    }

    /**
     * Get uploaded file
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if file was uploaded
     */
    public function hasFile(string $key): bool
    {
        $file = $this->file($key);
        return $file && $file['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get request header
     */
    public function getHeader(string $name, $default = null): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get request body
     */
    public function getBody(): string
    {
        if ($this->body === null) {
            $this->body = file_get_contents('php://input') ?: '';
        }
        return $this->body;
    }

    /**
     * Get JSON data from request body
     */
    public function json(): ?array
    {
        $body = $this->getBody();
        if (empty($body)) {
            return null;
        }

        $data = json_decode($body, true);
        return json_last_error() === JSON_ERROR_NONE ? $data : null;
    }

    /**
     * Get client IP address
     */
    public function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipKeys as $key) {
            if (!empty($this->server[$key])) {
                $ips = explode(',', $this->server[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get user agent
     */
    public function getUserAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Get referrer
     */
    public function getReferrer(): ?string
    {
        return $this->server['HTTP_REFERER'] ?? null;
    }

    /**
     * Check if request is secure (HTTPS)
     */
    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ||
               $this->server['SERVER_PORT'] == 443 ||
               $this->getHeader('X-Forwarded-Proto') === 'https';
    }

    /**
     * Get request scheme
     */
    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Get host
     */
    public function getHost(): string
    {
        return $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Get full URL
     */
    public function getUrl(): string
    {
        return $this->getScheme() . '://' . $this->getHost() . $this->getUri();
    }

    /**
     * Parse headers from server variables
     */
    private function parseHeaders(): array
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($header)] = $value;
            }
        }
        
        // Add content type and length if present
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['content-type'] = $this->server['CONTENT_TYPE'];
        }
        
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['content-length'] = $this->server['CONTENT_LENGTH'];
        }
        
        return $headers;
    }

    /**
     * Validate input data
     */
    public function validate(array $rules): array
    {
        $validator = new RequestValidator($this->all(), $rules);
        return $validator->validate();
    }
}
