<?php

namespace CineVerse\Core\Http;

use CineVerse\Core\Http\Request;
use CineVerse\Core\Http\Response;
use Exception;

/**
 * Route class for handling individual routes
 */
class Route
{
    private string $method;
    private string $path;
    private $handler;
    private array $middleware;
    private array $constraints = [];
    private Router $router;

    public function __construct(Router $router, string $method, string $path, $handler, array $middleware = [])
    {
        $this->router = $router;
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->middleware = $middleware;
    }

    /**
     * Add parameter constraint
     */
    public function where(string $parameter, string $pattern): self
    {
        $this->constraints[$parameter] = $pattern;
        $this->router->updateRoute($this);
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }
}

/**
 * HTTP Router
 *
 * Handles routing of HTTP requests to appropriate controllers
 */
class Router
{
    private array $routes = [];
    private array $middleware = [];
    private array $routeGroups = [];
    private string $currentGroupPrefix = '';
    private array $currentGroupMiddleware = [];

    /**
     * Add GET route
     */
    public function get(string $path, $handler, array $middleware = []): Route
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Add POST route
     */
    public function post(string $path, $handler, array $middleware = []): Route
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Add PUT route
     */
    public function put(string $path, $handler, array $middleware = []): Route
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Add DELETE route
     */
    public function delete(string $path, $handler, array $middleware = []): Route
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Add route for multiple methods
     */
    public function match(array $methods, string $path, $handler, array $middleware = []): void
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $path, $handler, $middleware);
        }
    }

    /**
     * Add route for all methods
     */
    public function any(string $path, $handler, array $middleware = []): void
    {
        $this->match(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], $path, $handler, $middleware);
    }

    /**
     * Create route group with prefix and middleware
     */
    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix = $this->currentGroupPrefix;
        $previousMiddleware = $this->currentGroupMiddleware;

        $this->currentGroupPrefix = $previousPrefix . ($attributes['prefix'] ?? '');
        $this->currentGroupMiddleware = array_merge(
            $previousMiddleware,
            $attributes['middleware'] ?? []
        );

        $callback($this);

        $this->currentGroupPrefix = $previousPrefix;
        $this->currentGroupMiddleware = $previousMiddleware;
    }

    /**
     * Add middleware
     */
    public function middleware(string $name, callable $handler): void
    {
        $this->middleware[$name] = $handler;
    }

    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        // Find matching route
        $route = $this->findRoute($method, $path);

        if (!$route) {
            return Response::notFound('Route not found');
        }

        try {
            // Execute middleware
            $response = $this->executeMiddleware($route['middleware'], $request, function() use ($route, $request) {
                return $this->executeHandler($route['handler'], $request, $route['params']);
            });

            return $response instanceof Response ? $response : Response::make((string) $response);
        } catch (Exception $e) {
            return Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Add route
     */
    private function addRoute(string $method, string $path, $handler, array $middleware): void
    {
        $fullPath = $this->currentGroupPrefix . $path;
        $allMiddleware = array_merge($this->currentGroupMiddleware, $middleware);

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'pattern' => $this->compilePattern($fullPath),
            'handler' => $handler,
            'middleware' => $allMiddleware
        ];
    }

    /**
     * Find matching route
     */
    private function findRoute(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract parameters
                $params = [];
                if (preg_match_all('/\{(\w+)\}/', $route['path'], $paramMatches)) {
                    foreach ($paramMatches[1] as $index => $paramName) {
                        $params[$paramName] = $matches[$index + 1] ?? null;
                    }
                }

                return [
                    'handler' => $route['handler'],
                    'middleware' => $route['middleware'],
                    'params' => $params
                ];
            }
        }

        return null;
    }

    /**
     * Compile route pattern to regex
     */
    private function compilePattern(string $path): string
    {
        // Escape special regex characters except {}
        $pattern = preg_quote($path, '/');
        
        // Replace parameter placeholders with regex groups
        $pattern = preg_replace('/\\\{(\w+)\\\}/', '([^/]+)', $pattern);
        
        return '/^' . $pattern . '$/';
    }

    /**
     * Execute middleware chain
     */
    private function executeMiddleware(array $middlewareNames, Request $request, callable $next)
    {
        if (empty($middlewareNames)) {
            return $next();
        }

        $middlewareName = array_shift($middlewareNames);
        
        if (!isset($this->middleware[$middlewareName])) {
            throw new Exception("Middleware '{$middlewareName}' not found");
        }

        $middleware = $this->middleware[$middlewareName];
        
        return $middleware($request, function() use ($middlewareNames, $request, $next) {
            return $this->executeMiddleware($middlewareNames, $request, $next);
        });
    }

    /**
     * Execute route handler
     */
    private function executeHandler($handler, Request $request, array $params)
    {
        if (is_callable($handler)) {
            return $handler($request, $params);
        }

        if (is_string($handler)) {
            return $this->executeControllerAction($handler, $request, $params);
        }

        throw new Exception('Invalid route handler');
    }

    /**
     * Execute controller action
     */
    private function executeControllerAction(string $handler, Request $request, array $params)
    {
        if (!str_contains($handler, '@')) {
            throw new Exception('Controller action must be in format Controller@method');
        }

        [$controllerName, $method] = explode('@', $handler, 2);
        
        // Add namespace if not present
        if (!str_contains($controllerName, '\\')) {
            $controllerName = 'CineVerse\\Controllers\\' . $controllerName;
        }

        if (!class_exists($controllerName)) {
            throw new Exception("Controller '{$controllerName}' not found");
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $method)) {
            throw new Exception("Method '{$method}' not found in controller '{$controllerName}'");
        }

        // Inject dependencies if controller has constructor
        if (method_exists($controller, '__construct')) {
            // TODO: Implement dependency injection
        }

        return $controller->$method($request, $params);
    }

    /**
     * Generate URL for named route
     */
    public function url(string $name, array $params = []): string
    {
        // TODO: Implement named routes and URL generation
        return '/';
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
