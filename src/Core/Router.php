<?php

namespace App\Core;

class Router {
    private array $routes = [];

    public function add(string $method, string $path, array|callable $handler, array $middlewares = []): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function get(string $path, array|callable $handler, array $middlewares = []): void {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): void {
        $this->add('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array|callable $handler, array $middlewares = []): void {
        $this->add('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, array|callable $handler, array $middlewares = []): void {
        $this->add('DELETE', $path, $handler, $middlewares);
    }

    public function options(string $path, array|callable $handler, array $middlewares = []): void {
        $this->add('OPTIONS', $path, $handler, $middlewares);
    }

    public function dispatch(Request $request): void {
        $method = $request->getMethod();
        $path = $request->getPath();

        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                foreach ($route['middlewares'] as $middleware) {
                    if (is_string($middleware)) {
                        $instance = new $middleware();
                        $instance->handle($request, $params);
                    } elseif (is_callable($middleware)) {
                        $middleware($request, $params);
                    }
                }

                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    $controller->$action($request, $params);
                } elseif (is_callable($handler)) {
                    $handler($request, $params);
                }
                return;
            }
        }

        Response::error('Ruta no encontrada', 404);
    }
}
