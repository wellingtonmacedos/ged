<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function addRoute(string $method, string $path, callable|array $handler): void
    {
        $method = strtoupper($method);
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path = rtrim($path, '/') ?: '/';

        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];
            $this->invokeHandler($handler);
            return;
        }

        http_response_code(404);
        echo '404 - Rota não encontrada';
    }

    private function invokeHandler(callable|array $handler): void
    {
        if (is_array($handler)) {
            $class = $handler[0];
            $method = $handler[1];
            $controller = new $class();
            $controller->$method();
            return;
        }

        $handler();
    }
}

