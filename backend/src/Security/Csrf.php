<?php

declare(strict_types=1);

namespace Amanah\Security;

use Amanah\Http\HttpException;

final class Csrf
{
    public static function token(): string
    {
        Session::start();
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function verify(?string $token): void
    {
        if (!is_string($token) || !hash_equals(self::token(), $token)) {
            throw new HttpException('Jeton CSRF invalide.', 419);
        }
    }
}
