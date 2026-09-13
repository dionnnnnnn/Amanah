<?php

declare(strict_types=1);

namespace Amanah\Services;

use Amanah\Http\HttpException;

final class UnavailablePaymentGateway implements PaymentGateway
{
    public function createCheckout(array $donation, string $idempotencyKey): array
    {
        throw new HttpException('Le prestataire de paiement n’est pas configuré.', 503);
    }

    public function refund(array $donation, int $amountCents, string $idempotencyKey): array
    {
        throw new HttpException('Le remboursement nécessite un adaptateur de paiement configuré.', 503);
    }
}
