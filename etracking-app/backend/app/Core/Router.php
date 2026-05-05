<?php

namespace App\Core;

class Router
{
    private static ?Router $instance = null;
    private array $routes = [];

    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array|callable $handler, array $middleware): void
    {
        $this->routes[] = [
            'method'     => $method,
            'path'       => $path,
            'regex'      => $this->pathToRegex($path),
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $req): void
    {
        if ($req->method() === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $req->method()) continue;

            if (preg_match($route['regex'], $req->path(), $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $req->setRouteParams($params);

                // Run middleware chain
                foreach ($route['middleware'] as $mw) {
                    if ($mw === 'auth') {
                        (new \App\Middleware\AuthMiddleware())->handle($req);
                    } elseif (str_starts_with($mw, 'role:')) {
                        $roles = array_map('trim', explode(',', substr($mw, 5)));
                        (new \App\Middleware\RbacMiddleware($roles))->handle($req);
                    }
                }

                // Dispatch handler
                try {
                    if (is_callable($route['handler'])) {
                        call_user_func($route['handler'], $req);
                    } else {
                        [$class, $method] = $route['handler'];
                        (new $class())->$method($req);
                    }
                } catch (\Throwable $e) {
                    $cfg = require __DIR__ . '/../../config/app.php';
                    $msg = $cfg['env'] === 'development' ? $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() : 'Internal server error';
                    Response::error($msg, 500);
                }
                return;
            }
        }

        Response::notFound("Route {$req->method()} {$req->path()} not found");
    }

    private function pathToRegex(string $path): string
    {
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}
