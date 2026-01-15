<?php
declare(strict_types=1);

namespace App\Core;

class Security
{
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        if (!isset($_SESSION['csrf_token']) || $token === null) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function requireCsrfToken(): void
    {
        $token = $_POST['csrf_token'] ?? null;
        if (!self::validateCsrfToken($token)) {
            http_response_code(400);
            echo 'Token CSRF inválido';
            exit;
        }
    }
}

