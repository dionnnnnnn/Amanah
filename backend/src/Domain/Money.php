<?php

declare(strict_types=1);

namespace Amanah\Domain;

use InvalidArgumentException;

final readonly class Money
{
    private function __construct(public int $cents, public string $currency)
    {
    }

    public static function fromDecimal(string $value, string $currency = 'CHF', int $maxCents = 100000000): self
    {
        $value = trim($value);
        if (!preg_match('/^(?:0|[1-9]\d{0,8})(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Le montant doit être positif et comporter au maximum deux décimales.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $cents = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
        if ($cents < 1 || $cents > $maxCents) {
            throw new InvalidArgumentException('Le montant demandé est hors des limites autorisées.');
        }
        if (!in_array($currency, ['CHF'], true)) {
            throw new InvalidArgumentException('Devise non autorisée.');
        }

        return new self($cents, $currency);
    }

    public function decimal(): string
    {
        return intdiv($this->cents, 100) . '.' . str_pad((string) ($this->cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
