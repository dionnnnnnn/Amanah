<?php

declare(strict_types=1);

namespace Amanah\Services;

interface PaymentGateway
{
    public function createCheckout(array $donation, string $idempotencyKey): array;

    public function refund(array $donation, int $amountCents, string $idempotencyKey): array;
}
