<?php

declare(strict_types=1);

namespace Amanah\Services;

use Amanah\Domain\Money;
use Amanah\Http\HttpException;
use Amanah\Infrastructure\Database;
use Amanah\Support\Clock;
use Amanah\Support\Uuid;
use InvalidArgumentException;
use Throwable;

final class RefundService
{
    public function __construct(private readonly Database $database, private readonly PaymentGateway $gateway) {}

    public function refund(string $donationId, string $amount, string $idempotencyKey, string $actorId): array
    {
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 128) throw new InvalidArgumentException('Clé d’idempotence invalide.');
        $donation = $this->database->fetchOne('SELECT * FROM donations WHERE id = :id', ['id' => $donationId]);
        if (!$donation) throw new InvalidArgumentException('Don introuvable.');
        $money = Money::fromDecimal($amount, $donation['currency']);
        $previous = $this->database->fetchOne('SELECT * FROM refunds WHERE idempotency_key = :key', ['key' => $idempotencyKey]);
        if ($previous) {
            if ($previous['donation_id'] !== $donationId || (int) $previous['amount_cents'] !== $money->cents) throw new HttpException('La clé d’idempotence est déjà associée à un autre remboursement.', 409);
            return ['refund_id' => $previous['id'], 'status' => $previous['status']];
        }
        if (!in_array($donation['status'], ['paid', 'disputed'], true)) throw new InvalidArgumentException('Ce don ne peut pas être remboursé.');

        $reservation = $this->database->transaction(function () use ($donationId, $money, $idempotencyKey, $actorId): array {
            $lock = $this->database->driver() === 'mysql' ? ' FOR UPDATE' : '';
            $donation = $this->database->fetchOne('SELECT * FROM donations WHERE id = :id' . $lock, ['id' => $donationId]);
            if (!$donation || !in_array($donation['status'], ['paid', 'disputed'], true)) throw new InvalidArgumentException('Ce don ne peut pas être remboursé.');
            $total = (int) ($this->database->fetchOne(
                "SELECT COALESCE(SUM(amount_cents), 0) AS total FROM refunds WHERE donation_id = :id AND status IN ('requested','pending','succeeded')" , ['id' => $donationId]
            )['total'] ?? 0);
            if ($total + $money->cents > (int) $donation['amount_cents']) throw new InvalidArgumentException('Le remboursement dépasse le montant encaissé.');
            $id = Uuid::v4();
            $now = Clock::now();
            $this->database->execute(
                'INSERT INTO refunds(id, donation_id, idempotency_key, provider_refund_id, amount_cents, status, initiated_by, created_at, updated_at)
                 VALUES(:id, :donation, :key, :provider, :amount, \'requested\', :actor, :now, :now)',
                ['id' => $id, 'donation' => $donationId, 'key' => $idempotencyKey, 'provider' => 'pending_' . hash('sha256', $idempotencyKey), 'amount' => $money->cents, 'actor' => $actorId, 'now' => $now]
            );
            return ['id' => $id, 'donation' => $donation, 'amount_cents' => $money->cents];
        });

        try {
            $provider = $this->gateway->refund($reservation['donation'], $money->cents, $idempotencyKey);
        } catch (Throwable $exception) {
            $this->database->execute('UPDATE refunds SET status = \'pending\', updated_at = :now WHERE id = :id', ['now' => Clock::now(), 'id' => $reservation['id']]);
            throw $exception;
        }
        $status = in_array($provider['status'] ?? null, ['succeeded', 'pending', 'failed'], true) ? $provider['status'] : 'pending';
        $providerId = (string) ($provider['provider_refund_id'] ?? ('pending_' . hash('sha256', $idempotencyKey)));
        $this->database->transaction(function () use ($reservation, $status, $providerId, $money): void {
            $now = Clock::now();
            $this->database->execute('UPDATE refunds SET provider_refund_id = :provider, status = :status, updated_at = :now WHERE id = :id', ['provider' => $providerId, 'status' => $status, 'now' => $now, 'id' => $reservation['id']]);
            if ($status !== 'succeeded') return;
            $entry = $this->database->fetchOne('SELECT id FROM financial_entries WHERE type = \'refund\' AND external_reference = :external', ['external' => $providerId]);
            if (!$entry) $this->database->execute(
                'INSERT INTO financial_entries(id, donation_id, type, amount_cents, currency, external_reference, created_at)
                 VALUES(:id, :donation, \'refund\', :amount, :currency, :external, :now)',
                ['id' => Uuid::v4(), 'donation' => $reservation['donation']['id'], 'amount' => -$money->cents, 'currency' => $money->currency, 'external' => $providerId, 'now' => $now]
            );
            $total = (int) ($this->database->fetchOne("SELECT COALESCE(SUM(amount_cents), 0) AS total FROM refunds WHERE donation_id = :id AND status = 'succeeded'", ['id' => $reservation['donation']['id']])['total'] ?? 0);
            if ($total >= (int) $reservation['donation']['amount_cents']) $this->database->execute('UPDATE donations SET status = \'refunded\', updated_at = :now WHERE id = :id', ['now' => $now, 'id' => $reservation['donation']['id']]);
        });
        return ['refund_id' => $reservation['id'], 'status' => $status];
    }
}
