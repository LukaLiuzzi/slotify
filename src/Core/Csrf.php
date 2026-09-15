<?php

namespace App\Core;

class Csrf {
    private const SESSION_KEY = 'csrf_token';

    public static function getToken(): string {
        Session::start();
        $token = Session::get(self::SESSION_KEY);
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    public static function validateToken(?string $token): bool {
        Session::start();
        $stored = Session::get(self::SESSION_KEY);
        if (!$stored || !$token) {
            return false;
        }
        return hash_equals($stored, $token);
    }
}
