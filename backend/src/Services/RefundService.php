<?php

declare(strict_types=1);

namespace Amanah\Services;

use Amanah\Domain\Money;
use Amanah\Infrastructure\Database;
use Amanah\Support\Clock;
use Amanah\Support\Uuid;
use InvalidArgumentException;

final class RefundService
{
    public function __construct(private readonly Database $database, private readonly PaymentGateway $gateway)
    {
    }

    public function refund(string $donationId, string $amount, string $idempotencyKey, string $actorId): array
    {
        $donation = $this->database->fetchOne('SELECT * FROM donations WHERE id = :id', ['id' => $donationId]);
        if (!$donation || !in_array($donation['status'], ['paid', 'disputed'], true)) {
            throw new InvalidArgumentException('Ce don ne peut pas être remboursé.');
        }
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 128) {
            throw new InvalidArgumentException('Clé d’idempotence invalide.');
        }
        $previous = $this->database->fetchOne('SELECT * FROM refunds WHERE idempotency_key = :key', ['key' => $idempotencyKey]);
        if ($previous) {
            return ['refund_id' => $previous['id'], 'status' => $previous['status']];
        }
        $money = Money::fromDecimal($amount, $donation['currency']);
        $refunded = (int) ($this->database->fetchOne(
            'SELECT COALESCE(SUM(amount_cents), 0) AS total FROM refunds WHERE donation_id = :id AND status = \'succeeded\'',
            ['id' => $donationId]
        )['total'] ?? 0);
        if ($refunded + $money->cents > (int) $donation['amount_cents']) {
            throw new InvalidArgumentException('Le remboursement dépasse le montant encaissé.');
        }
        $provider = $this->gateway->refund($donation, $money->cents, $idempotencyKey);
        $now = Clock::now();
        $refundId = Uuid::v4();
        $newTotalRefunded = $refunded + $money->cents;
        $this->database->transaction(function () use ($donation, $money, $idempotencyKey, $provider, $actorId, $now, $refundId, $newTotalRefunded): void {
            $this->database->execute(
                'INSERT INTO refunds(id, donation_id, idempotency_key, provider_refund_id, amount_cents, status, initiated_by, created_at, updated_at)
                 VALUES(:id, :donation, :key, :provider, :amount, :status, :actor, :now, :now)',
                ['id' => $refundId, 'donation' => $donation['id'], 'key' => $idempotencyKey,
                 'provider' => $provider['provider_refund_id'], 'amount' => $money->cents,
                 'status' => $provider['status'], 'actor' => $actorId, 'now' => $now]
            );
            $this->database->execute(
                'INSERT INTO financial_entries(id, donation_id, type, amount_cents, currency, external_reference, created_at)
                 VALUES(:id, :donation, \'refund\', :amount, :currency, :external, :now)',
                ['id' => Uuid::v4(), 'donation' => $donation['id'], 'amount' => -$money->cents,
                 'currency' => $money->currency, 'external' => $provider['provider_refund_id'], 'now' => $now]
            );
            $newStatus = ((int) $donation['amount_cents'] === $newTotalRefunded) ? 'refunded' : $donation['status'];
            $this->database->execute('UPDATE donations SET status = :status, updated_at = :now WHERE id = :id',
                ['status' => $newStatus, 'now' => $now, 'id' => $donation['id']]);
        });
        return ['refund_id' => $refundId, 'status' => $provider['status']];
    }
}
