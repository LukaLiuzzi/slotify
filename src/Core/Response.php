<?php

namespace App\Core;

class Response {
    public static function json(mixed $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(mixed $data = null, string $message = '', int $statusCode = 200): void {
        $payload = [
            'success' => true
        ];

        if ($message !== '') {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        self::json($payload, $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, mixed $errors = null): void {
        $payload = [
            'success' => false,
            'error' => $message
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        self::json($payload, $statusCode);
    }
}
