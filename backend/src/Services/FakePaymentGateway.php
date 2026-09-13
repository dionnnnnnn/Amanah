<?php

declare(strict_types=1);

namespace Amanah\Services;

final class FakePaymentGateway implements PaymentGateway
{
    public function createCheckout(array $donation, string $idempotencyKey): array
    {
        return [
            'provider' => 'fake',
            'session_id' => 'fake_' . hash('sha256', $idempotencyKey),
            'checkout_url' => '/don/retour?reference=' . rawurlencode($donation['public_reference']) . '&demo=1',
        ];
    }

    public function refund(array $donation, int $amountCents, string $idempotencyKey): array
    {
        return ['provider_refund_id' => 'fake_refund_' . hash('sha256', $idempotencyKey), 'status' => 'succeeded'];
    }
}
