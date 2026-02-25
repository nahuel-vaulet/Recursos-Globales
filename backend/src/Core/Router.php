<?php
/**
 * [!] ARCH: Lightweight Router for REST API
 * [✓] AUDIT: Supports parameterized routes (/api/odt/{id})
 * [→] EDITAR: Add rate limiting for production
 */

declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<string, array<string, callable>> Routes grouped by method */
    private array $routes = [];

    /**
     * Register a GET route
     */
    public function get(string $path, callable $handler): self
    {
        $this->routes['GET'][$path] = $handler;
        return $this;
    }

    /**
     * Register a POST route
     */
    public function post(string $path, callable $handler): self
    {
        $this->routes['POST'][$path] = $handler;
        return $this;
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, callable $handler): self
    {
        $this->routes['PUT'][$path] = $handler;
        return $this;
    }

    /**
     * Register a PATCH route
     */
    public function patch(string $path, callable $handler): self
    {
        $this->routes['PATCH'][$path] = $handler;
        return $this;
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, callable $handler): self
    {
        $this->routes['DELETE'][$path] = $handler;
        return $this;
    }

    /**
     * Dispatch the current request to the matching route.
     */
    public function dispatch(string $method, string $uri): void
    {
        // Strip query string
        $uri = strtok($uri, '?') ?: $uri;
        // Normalize trailing slashes
        $uri = rtrim($uri, '/') ?: '/';

        $methodRoutes = $this->routes[$method] ?? [];

        foreach ($methodRoutes as $pattern => $handler) {
            $params = $this->matchRoute($pattern, $uri);
            if ($params !== null) {
                $handler($params);
                return;
            }
        }

        // No match found
        http_response_code(404);
        echo json_encode([
            'error' => 'ERR-ROUTE-404',
            'message' => "Endpoint not found: {$method} {$uri}",
        ]);
    }

    /**
     * Match a route pattern against a URI.
     * Supports {paramName} placeholders.
     *
     * @return array<string, string>|null  Matched params or null
     */
    private function matchRoute(string $pattern, string $uri): ?array
    {
        // Convert pattern to regex: /api/odt/{id} → /api/odt/(?P<id>[^/]+)
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Return only named params
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }
}
