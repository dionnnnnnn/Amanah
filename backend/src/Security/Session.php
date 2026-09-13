<?php

declare(strict_types=1);

namespace Amanah\Security;

final class Session
{
    public const NAME = 'amanah_session';
    private const TTL = 1800;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::expireIfIdle();
            return;
        }

        session_name(self::NAME);
        session_set_cookie_params([
            'httponly' => true,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
            'path' => '/',
        ]);
        session_start();
        self::expireIfIdle();
        $_SESSION['_last_activity'] = time();
    }

    public static function id(): string
    {
        self::start();
        return session_id();
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '',
            (bool) $params['secure'], (bool) $params['httponly']);
        session_destroy();
    }

    private static function expireIfIdle(): void
    {
        if (isset($_SESSION['_last_activity']) && time() - (int) $_SESSION['_last_activity'] > self::TTL) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
    }
}
