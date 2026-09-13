<?php

declare(strict_types=1);

namespace Amanah\Support;

final class Clock
{
    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
