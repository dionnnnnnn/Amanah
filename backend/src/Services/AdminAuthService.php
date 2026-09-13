<?php

declare(strict_types=1);

namespace Amanah\Services;

use Amanah\Infrastructure\Database;
use Amanah\Security\Totp;
use Amanah\Security\RateLimiter;
use Amanah\Support\Clock;
use RuntimeException;

final class AdminAuthService
{
    public function __construct(private readonly Database $database, private readonly string $appKey, private readonly RateLimiter $limiter)
    {
    }

    public function login(string $email, string $password, ?string $mfaCode, string $ip): void
    {
        if (!$this->limiter->allow('admin-login:' . hash('sha256', $ip), 10, 900)) {
            throw new RuntimeException('Trop de tentatives. Réessayez plus tard.');
        }
        $user = $this->database->fetchOne('SELECT * FROM users WHERE email = :email AND status = \'active\'',
            ['email' => strtolower(trim($email))]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Identifiants invalides.');
        }
        if ($user['mfa_secret_encrypted'] !== null && !Totp::valid($this->decrypt($user['mfa_secret_encrypted']), (string) $mfaCode)) {
            throw new RuntimeException('Code MFA invalide.');
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start(['cookie_httponly' => true, 'cookie_secure' => $this->isHttps(), 'cookie_samesite' => 'Lax']);
        }
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_roles'] = array_column($this->database->fetchAll(
            'SELECT r.name FROM roles r JOIN role_user ru ON ru.role_id = r.id WHERE ru.user_id = :id', ['id' => $user['id']]
        ), 'name');
        $this->database->execute('UPDATE users SET last_login_at = :now, updated_at = :now WHERE id = :id',
            ['now' => Clock::now(), 'id' => $user['id']]);
    }

    public function requireRole(string ...$roles): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start(['cookie_httponly' => true, 'cookie_secure' => $this->isHttps(), 'cookie_samesite' => 'Lax']);
        }
        $userId = (string) ($_SESSION['admin_id'] ?? '');
        $actual = array_column($this->database->fetchAll(
            'SELECT r.name FROM roles r JOIN role_user ru ON ru.role_id = r.id WHERE ru.user_id = :id', ['id' => $userId]
        ), 'name');
        $active = $userId !== '' && $this->database->fetchOne('SELECT id FROM users WHERE id = :id AND status = \'active\'', ['id' => $userId]);
        if (!$active || !array_intersect($roles, $actual)) {
            throw new RuntimeException('Accès administrateur requis.');
        }
        return $userId;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    private function decrypt(string $value): string
    {
        $key = hash('sha256', $this->appKey, true);
        [$iv, $cipher] = array_pad(explode(':', $value, 2), 2, '');
        $decoded = base64_decode($cipher, true);
        $plain = $decoded === false ? false : openssl_decrypt($decoded, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, base64_decode($iv, true));
        if ($plain === false) {
            throw new RuntimeException('MFA-configuratie ongeldig.');
        }
        return $plain;
    }

    private function isHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}
