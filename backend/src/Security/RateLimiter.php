<?php

declare(strict_types=1);

namespace Amanah\Security;

use Amanah\Infrastructure\Database;
use Amanah\Http\HttpException;
use Amanah\Support\Clock;
use DateTimeImmutable;
use DateTimeZone;

final class RateLimiter
{
    public function __construct(private readonly Database $database)
    {
    }

    public function allow(string $key, int $maxHits, int $windowSeconds): bool
    {
        return $this->database->transaction(function () use ($key, $maxHits, $windowSeconds): bool {
            $now = Clock::now();
            $row = $this->database->fetchOne('SELECT * FROM rate_limits WHERE bucket_key = :key', ['key' => $key]);
            $started = $row ? DateTimeImmutable::createFromFormat(
                '!Y-m-d H:i:s', (string) $row['window_started_at'], new DateTimeZone('UTC')
            ) : false;
            $expired = !$started || $started->getTimestamp() + $windowSeconds <= time();
            if (!$row || $expired) {
                if (!$row) {
                    $this->database->execute(
                        'INSERT INTO rate_limits(bucket_key, hits, window_started_at) VALUES(:key, 1, :now)',
                        ['key' => $key, 'now' => $now]
                    );
                } else {
                    $this->database->execute(
                        'UPDATE rate_limits SET hits = 1, window_started_at = :now WHERE bucket_key = :key',
                        ['key' => $key, 'now' => $now]
                    );
                }
                return true;
            }
            if ((int) $row['hits'] >= $maxHits) {
                return false;
            }
            $this->database->execute('UPDATE rate_limits SET hits = hits + 1 WHERE bucket_key = :key', ['key' => $key]);
            return true;
        });
    }

    public function requireAllowed(string $key, int $maxHits, int $windowSeconds): void
    {
        if (!$this->allow($key, $maxHits, $windowSeconds)) {
            throw new HttpException('Trop de demandes. Réessayez plus tard.', 429);
        }
    }
}
