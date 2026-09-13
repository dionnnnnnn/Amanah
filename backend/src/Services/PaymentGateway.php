<?php

declare(strict_types=1);

namespace Amanah\Services;

use RuntimeException;

interface PaymentGateway
{
    public function createCheckout(array $donation, string $idempotencyKey): array;

    public function refund(array $donation, int $amountCents, string $idempotencyKey): array;
}

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

final class UnavailablePaymentGateway implements PaymentGateway
{
    public function createCheckout(array $donation, string $idempotencyKey): array
    {
        throw new RuntimeException('Le prestataire de paiement n’est pas configuré.');
    }

    public function refund(array $donation, int $amountCents, string $idempotencyKey): array
    {
        throw new RuntimeException('Le remboursement nécessite un adaptateur de paiement configuré.');
    }
}
