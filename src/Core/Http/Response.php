<?php

namespace CineVerse\Core\Http;

/**
 * HTTP Response Handler
 * 
 * Handles HTTP responses and provides convenient methods for different response types
 */
class Response
{
    private string $content = '';
    private int $statusCode = 200;
    private array $headers = [];
    private static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable'
    ];

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Create a new response instance
     */
    public static function make(string $content = '', int $statusCode = 200, array $headers = []): self
    {
        return new self($content, $statusCode, $headers);
    }

    /**
     * Create JSON response
     */
    public static function json($data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($content === false) {
            throw new \Exception('Failed to encode JSON response');
        }
        
        return new self($content, $statusCode, $headers);
    }

    /**
     * Create redirect response
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    /**
     * Create view response
     */
    public static function view(string $template, array $data = [], int $statusCode = 200): self
    {
        // TODO: Implement template rendering
        $content = "<!-- Template: {$template} -->";
        return new self($content, $statusCode);
    }

    /**
     * Create error response
     */
    public static function error(string $message, int $statusCode = 500): self
    {
        return self::json([
            'error' => true,
            'message' => $message,
            'status_code' => $statusCode
        ], $statusCode);
    }

    /**
     * Create success response
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): self
    {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return self::json($response, $statusCode);
    }

    /**
     * Create validation error response
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): self
    {
        return self::json([
            'error' => true,
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    /**
     * Create unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return self::json([
            'error' => true,
            'message' => $message
        ], 401);
    }

    /**
     * Create forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return self::json([
            'error' => true,
            'message' => $message
        ], 403);
    }

    /**
     * Create not found response
     */
    public static function notFound(string $message = 'Not Found'): self
    {
        return self::json([
            'error' => true,
            'message' => $message
        ], 404);
    }

    /**
     * Create file download response
     */
    public static function download(string $filePath, string $filename = null): self
    {
        if (!file_exists($filePath)) {
            throw new \Exception('File not found');
        }

        $filename = $filename ?: basename($filePath);
        $content = file_get_contents($filePath);
        
        $headers = [
            'Content-Type' => mime_content_type($filePath) ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => filesize($filePath)
        ];

        return new self($content, 200, $headers);
    }

    /**
     * Set response content
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get response content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set status code
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get header
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Set multiple headers
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Add cookie
     */
    public function cookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true
    ): self {
        $cookie = [
            'name' => $name,
            'value' => $value,
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly
        ];

        $this->headers['Set-Cookie'][] = $this->buildCookieHeader($cookie);
        return $this;
    }

    /**
     * Send the response
     */
    public function send(): void
    {
        // Send status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            if ($name === 'Set-Cookie' && is_array($value)) {
                foreach ($value as $cookie) {
                    header("Set-Cookie: {$cookie}", false);
                }
            } else {
                header("{$name}: {$value}");
            }
        }

        // Send content
        echo $this->content;
    }

    /**
     * Get status text for status code
     */
    public function getStatusText(): string
    {
        return self::$statusTexts[$this->statusCode] ?? 'Unknown Status';
    }

    /**
     * Check if response is successful (2xx)
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is redirect (3xx)
     */
    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if response is client error (4xx)
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is server error (5xx)
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Build cookie header string
     */
    private function buildCookieHeader(array $cookie): string
    {
        $header = "{$cookie['name']}={$cookie['value']}";

        if ($cookie['expires'] > 0) {
            $header .= '; Expires=' . gmdate('D, d M Y H:i:s T', $cookie['expires']);
        }

        if (!empty($cookie['path'])) {
            $header .= '; Path=' . $cookie['path'];
        }

        if (!empty($cookie['domain'])) {
            $header .= '; Domain=' . $cookie['domain'];
        }

        if ($cookie['secure']) {
            $header .= '; Secure';
        }

        if ($cookie['httponly']) {
            $header .= '; HttpOnly';
        }

        return $header;
    }

    /**
     * Convert response to string
     */
    public function __toString(): string
    {
        return $this->content;
    }
}
