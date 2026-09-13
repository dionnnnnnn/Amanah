<?php

declare(strict_types=1);

namespace Amanah\Security;

use RuntimeException;

final class Csrf
{
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'cookie_samesite' => 'Lax',
            ]);
        }
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function verify(?string $token): void
    {
        if (!is_string($token) || !hash_equals(self::token(), $token)) {
            throw new RuntimeException('Jeton CSRF invalide.');
        }
    }
}
