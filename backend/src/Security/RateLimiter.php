<?php

declare(strict_types=1);

namespace Amanah\Security;

use Amanah\Infrastructure\Database;
use Amanah\Support\Clock;

final class RateLimiter
{
    public function __construct(private readonly Database $database)
    {
    }

    public function allow(string $key, int $maxHits, int $windowSeconds): bool
    {
        $now = Clock::now();
        $row = $this->database->fetchOne('SELECT * FROM rate_limits WHERE bucket_key = :key', ['key' => $key]);
        if (!$row || strtotime($row['window_started_at']) + $windowSeconds <= time()) {
            if ($this->database->driver() === 'sqlite') {
                $this->database->execute(
                    'INSERT INTO rate_limits(bucket_key, hits, window_started_at) VALUES(:key, 1, :now)
                     ON CONFLICT(bucket_key) DO UPDATE SET hits = 1, window_started_at = excluded.window_started_at',
                    ['key' => $key, 'now' => $now]
                );
            } else {
                $this->database->execute(
                    'INSERT INTO rate_limits(bucket_key, hits, window_started_at) VALUES(:key, 1, :now)
                     ON DUPLICATE KEY UPDATE hits = 1, window_started_at = VALUES(window_started_at)',
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
    }
}
