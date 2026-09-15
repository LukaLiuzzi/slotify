<?php

namespace App\Core;

class Request {
    private string $method;
    private string $path;
    private array $body;
    private array $queryParams;

    public function __construct() {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsedUrl = parse_url($uri, PHP_URL_PATH);
        $this->path = rawurldecode($parsedUrl ?: '/');

        $this->queryParams = $_GET;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $rawInput = file_get_contents('php://input');
            $decoded = json_decode($rawInput, true);
            $this->body = is_array($decoded) ? $decoded : [];
        } else {
            $this->body = $_POST;
        }
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getBody(): array {
        return $this->body;
    }

    public function getQueryParams(): array {
        return $this->queryParams;
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->body[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function getHeader(string $name): ?string {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$normalized])) {
            return $_SERVER[$normalized];
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, $name) === 0) {
                    return $value;
                }
            }
        }

        return null;
    }

    public function getIpAddress(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function getUserAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}
