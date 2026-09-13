<?php

declare(strict_types=1);

namespace Amanah\Services;

use Amanah\Infrastructure\Database;
use Amanah\Support\Clock;
use Amanah\Support\Uuid;
use RuntimeException;

final class WebhookService
{
    public function __construct(
        private readonly Database $database,
        private readonly string $secret,
        private readonly string $mode,
    ) {
    }

    public function handle(string $rawBody, ?string $signature): array
    {
        if ($this->secret === '' || !$this->validSignature($rawBody, $signature)) {
            throw new RuntimeException('Signature webhook invalide.');
        }
        $event = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        foreach (['id', 'type'] as $key) {
            if (!isset($event[$key]) || !is_string($event[$key]) || $event[$key] === '') {
                throw new RuntimeException('Événement webhook incomplet.');
            }
        }
        $rawData = is_array($event['data'] ?? null) ? $event['data'] : $event;
        $data = is_array($rawData['object'] ?? null) ? $rawData['object'] : $rawData;
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $data['donation_reference'] ??= $metadata['donation_reference'] ?? null;
        $data['payment_id'] ??= $data['id'] ?? null;
        $data['amount_cents'] ??= $data['amount_total'] ?? $data['amount_paid'] ?? null;
        if (isset($data['currency'])) {
            $data['currency'] = strtoupper((string) $data['currency']);
        }
        $provider = (string) ($event['provider'] ?? (str_starts_with((string) ($signature ?? ''), 't=') ? 'stripe' : 'configured'));
        $now = Clock::now();

        return $this->database->transaction(function () use ($event, $data, $provider, $now): array {
            $duplicate = $this->database->fetchOne(
                'SELECT id, status FROM payment_events WHERE provider = :provider AND mode = :mode AND provider_event_id = :event',
                ['provider' => $provider, 'mode' => $this->mode, 'event' => $event['id']]
            );
            if ($duplicate) {
                return ['duplicate' => true, 'status' => $duplicate['status']];
            }

            $eventId = Uuid::v4();
            $this->database->execute(
                'INSERT INTO payment_events(id, provider, mode, provider_event_id, object_reference, status, received_at)
                 VALUES(:id, :provider, :mode, :event, :object, \'received\', :now)',
                ['id' => $eventId, 'provider' => $provider, 'mode' => $this->mode, 'event' => $event['id'],
                 'object' => $data['payment_id'] ?? null, 'now' => $now]
            );
            $reference = (string) ($data['donation_reference'] ?? '');
            $donation = $this->database->fetchOne('SELECT * FROM donations WHERE public_reference = :reference', ['reference' => $reference]);
            if (!$donation) {
                $this->markEventFailed($eventId, 'don introuvable');
                throw new RuntimeException('Don associé introuvable.');
            }
            if (isset($data['amount_cents']) && (int) $data['amount_cents'] !== (int) $donation['amount_cents']) {
                $this->markEventFailed($eventId, 'montant incohérent');
                throw new RuntimeException('Montant webhook incohérent.');
            }
            if (isset($data['currency']) && strtoupper((string) $data['currency']) !== $donation['currency']) {
                $this->markEventFailed($eventId, 'devise incohérente');
                throw new RuntimeException('Devise webhook incohérente.');
            }
            $type = (string) $event['type'];
            if (in_array($type, ['payment_succeeded', 'checkout.session.completed', 'invoice.paid', 'payment_intent.succeeded'], true)) {
                $this->confirm($donation, $data, $now);
            } elseif (in_array($type, ['payment_failed', 'payment_canceled', 'checkout.session.expired', 'payment_intent.payment_failed', 'payment_intent.canceled'], true)
                && !in_array($donation['status'], ['paid', 'refunded', 'disputed'], true)) {
                $this->database->execute('UPDATE donations SET status = :status, updated_at = :now WHERE id = :id',
                    ['status' => str_contains($type, 'canceled') || str_contains($type, 'expired') ? 'canceled' : 'failed',
                     'now' => $now, 'id' => $donation['id']]);
            }
            $this->database->execute('UPDATE payment_events SET status = \'processed\', processed_at = :now WHERE id = :id',
                ['now' => $now, 'id' => $eventId]);
            return ['duplicate' => false, 'status' => 'processed'];
        });
    }

    private function confirm(array $donation, array $data, string $now): void
    {
        if (in_array($donation['status'], ['paid', 'refunded', 'disputed'], true)) {
            return;
        }
        $this->database->execute('UPDATE donations SET status = \'paid\', paid_at = :now, updated_at = :now WHERE id = :id',
            ['now' => $now, 'id' => $donation['id']]);
        $this->database->execute(
            'UPDATE payment_attempts SET provider_payment_id = :payment, status = \'succeeded\', updated_at = :now WHERE donation_id = :donation',
            ['payment' => $data['payment_id'] ?? null, 'now' => $now, 'donation' => $donation['id']]
        );
        $payload = json_encode(['donation_id' => $donation['id']], JSON_THROW_ON_ERROR);
        $this->database->execute(
            'INSERT INTO financial_entries(id, donation_id, type, amount_cents, currency, external_reference, created_at)
             VALUES(:id, :donation, \'donation\', :amount, :currency, :external, :now)',
            ['id' => Uuid::v4(), 'donation' => $donation['id'], 'amount' => $donation['amount_cents'],
             'currency' => $donation['currency'], 'external' => $data['payment_id'] ?? null, 'now' => $now]
        );
        $this->database->execute(
            'INSERT INTO outbox_messages(id, kind, aggregate_type, aggregate_id, dedupe_key, payload, available_at, created_at, updated_at)
             VALUES(:id, \'donation.confirmed\', \'donation\', :aggregate, :dedupe, :payload, :now, :now, :now)',
            ['id' => Uuid::v4(), 'aggregate' => $donation['id'], 'dedupe' => 'donation-confirmed:' . $donation['id'],
             'payload' => $payload, 'now' => $now]
        );
    }

    private function markEventFailed(string $eventId, string $message): void
    {
        $this->database->execute('UPDATE payment_events SET status = \'failed\', error_message = :error WHERE id = :id',
            ['error' => substr($message, 0, 200), 'id' => $eventId]);
    }

    private function validSignature(string $body, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }
        if (str_starts_with($signature, 't=')) {
            $parts = [];
            foreach (explode(',', $signature) as $part) {
                [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
                $parts[$key] = $value;
            }
            $timestamp = (int) ($parts['t'] ?? 0);
            $expected = hash_hmac('sha256', $timestamp . '.' . $body, $this->secret);
            return $timestamp > 0 && abs(time() - $timestamp) <= 300
                && isset($parts['v1']) && hash_equals($expected, $parts['v1']);
        }
        $signature = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;
        return hash_equals(hash_hmac('sha256', $body, $this->secret), $signature);
    }
}
