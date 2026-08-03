<?php
declare(strict_types=1);

final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function generateToken(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function validateRequest(): void
    {
        $headerToken  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';

        if (empty($sessionToken) || empty($headerToken) || !hash_equals($sessionToken, $headerToken)) {
            JsonResponse::send(false, 403, 'Invalid or missing CSRF token.');
        }
    }
}
