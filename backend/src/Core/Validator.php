<?php

namespace App\Core;

class Validator {
    public static function validatePassword(string $password): bool {
        if (strlen($password) < 8) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        if (!preg_match('/[\W_]/', $password)) {
            return false;
        }

        return true;
    }

    public static function validateEmail(string $email): bool {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validateUsername(string $username): bool {
        return (bool)preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
    }

    public static function validateDate(string $date): bool {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public static function validateTime(string $time): bool {
        $pattern = '/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/';
        return (bool)preg_match($pattern, $time);
    }

    public static function sanitize(string $value): string {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}
