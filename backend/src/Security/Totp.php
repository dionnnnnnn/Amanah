<?php

declare(strict_types=1);

namespace Amanah\Security;

final class Totp
{
    public static function generateSecret(int $bytes = 20): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = random_bytes($bytes);
        $bits = '';
        foreach (str_split($binary) as $byte) $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        $secret = '';
        foreach (str_split($bits, 5) as $chunk) $secret .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
        return $secret;
    }

    public static function encryptSecret(string $secret, string $appKey): string
    {
        $key = hash('sha256', $appKey, true);
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($secret, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) throw new \RuntimeException('Chiffrement MFA indisponible.');
        return base64_encode($iv) . ':' . base64_encode($cipher);
    }

    public static function valid(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $secret = strtoupper($secret);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($secret) as $char) {
            $value = strpos($alphabet, $char);
            if ($value === false) {
                return false;
            }
            $binary .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $key .= chr(bindec($chunk));
            }
        }
        $counter = intdiv(time(), 30);
        for ($offset = -$window; $offset <= $window; $offset++) {
            $packed = pack('N2', ($counter + $offset) >> 32, ($counter + $offset) & 0xffffffff);
            $hash = hash_hmac('sha1', $packed, $key, true);
            $position = ord($hash[19]) & 0x0f;
            $value = ((ord($hash[$position]) & 0x7f) << 24)
                | ((ord($hash[$position + 1]) & 0xff) << 16)
                | ((ord($hash[$position + 2]) & 0xff) << 8)
                | (ord($hash[$position + 3]) & 0xff);
            if (hash_equals(str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT), $code)) {
                return true;
            }
        }
        return false;
    }
}
