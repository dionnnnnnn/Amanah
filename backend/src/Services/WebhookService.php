<?php

declare(strict_types=1);

namespace Amanah\Services;

use Amanah\Http\HttpException;
use Amanah\Infrastructure\Database;
use Amanah\Support\Clock;
use Amanah\Support\Uuid;
use RuntimeException;
use Throwable;

final class WebhookService
{
    public function __construct(private readonly Database $database, private readonly string $secret, private readonly string $mode) {}

    public function handle(string $rawBody, ?string $signature): array
    {
        if ($this->secret === '' || !$this->validSignature($rawBody, $signature)) throw new HttpException('Signature webhook invalide.', 400);
        try {
            $event = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new HttpException('Événement webhook invalide.', 400);
        }
        if (!is_array($event) || !is_string($event['id'] ?? null) || !is_string($event['type'] ?? null) || $event['id'] === '' || $event['type'] === '') throw new HttpException('Événement webhook incomplet.', 400);
        $rawData = is_array($event['data'] ?? null) ? $event['data'] : $event;
        $data = is_array($rawData['object'] ?? null) ? $rawData['object'] : $rawData;
        $provider = (string) ($event['provider'] ?? (str_starts_with((string) ($signature ?? ''), 't=') ? 'stripe' : 'configured'));
        $eventRow = $this->receive($event, $data, $provider);
        if (($eventRow['duplicate'] ?? false) === true) return ['duplicate' => true, 'status' => $eventRow['status']];
        try {
            $result = $this->database->transaction(fn (): array => $this->process($event, $data, $provider));
            $this->database->execute('UPDATE payment_events SET status = \'processed\', processed_at = :now WHERE id = :id', ['now' => Clock::now(), 'id' => $eventRow['id']]);
            return $result;
        } catch (Throwable $exception) {
            $this->database->execute('UPDATE payment_events SET status = \'failed\', error_message = :error WHERE id = :id', ['error' => substr($exception->getMessage(), 0, 200), 'id' => $eventRow['id']]);
            throw $exception;
        }
    }

    private function receive(array $event, array $data, string $provider): array
    {
        $duplicate = $this->database->fetchOne('SELECT id, status FROM payment_events WHERE provider = :provider AND mode = :mode AND provider_event_id = :event', ['provider' => $provider, 'mode' => $this->mode, 'event' => $event['id']]);
        if ($duplicate && $duplicate['status'] === 'processed') return $duplicate + ['duplicate' => true];
        if ($duplicate) return $duplicate + ['duplicate' => false];
        $id = Uuid::v4();
        try {
            $this->database->execute(
                'INSERT INTO payment_events(id, provider, mode, provider_event_id, object_reference, status, received_at) VALUES(:id, :provider, :mode, :event, :object, \'received\', :now)',
                ['id' => $id, 'provider' => $provider, 'mode' => $this->mode, 'event' => $event['id'], 'object' => (string) ($data['id'] ?? ''), 'now' => Clock::now()]
            );
        } catch (Throwable) {
            $race = $this->database->fetchOne('SELECT id, status FROM payment_events WHERE provider = :provider AND mode = :mode AND provider_event_id = :event', ['provider' => $provider, 'mode' => $this->mode, 'event' => $event['id']]);
            if ($race) return $race + ['duplicate' => $race['status'] === 'processed'];
            throw new RuntimeException('Réception webhook impossible.');
        }
        return ['id' => $id, 'duplicate' => false];
    }

    private function process(array $event, array $data, string $provider): array
    {
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $reference = (string) (($data['donation_reference'] ?? null) ?: ($metadata['donation_reference'] ?? null));
        $donation = $this->database->fetchOne('SELECT * FROM donations WHERE public_reference = :reference', ['reference' => $reference]);
        if (!$donation) throw new HttpException('Don associé introuvable.', 422);
        $type = (string) $event['type'];
        $successful = ['payment_succeeded', 'checkout.session.completed', 'invoice.paid', 'payment_intent.succeeded'];
        if (in_array($type, $successful, true)) {
            $this->validatePayment($event, $data, $donation, $provider);
            $this->confirm($event, $donation, $data);
        } elseif (in_array($type, ['payment_failed', 'payment_canceled', 'checkout.session.expired', 'payment_intent.payment_failed', 'payment_intent.canceled'], true)
            && !in_array($donation['status'], ['paid', 'refunded', 'disputed'], true)) {
            $this->database->execute('UPDATE donations SET status = :status, updated_at = :now WHERE id = :id', ['status' => str_contains($type, 'canceled') || str_contains($type, 'expired') ? 'canceled' : 'failed', 'now' => Clock::now(), 'id' => $donation['id']]);
        }
        return ['duplicate' => false, 'status' => 'processed'];
    }

    private function validatePayment(array $event, array $data, array $donation, string $provider): void
    {
        $type = (string) $event['type'];
        $paymentStatus = strtolower((string) ($data['payment_status'] ?? $data['status'] ?? ''));
        $validStatuses = in_array($type, ['checkout.session.completed', 'invoice.paid'], true) ? ['paid'] : ['paid', 'succeeded'];
        if (!in_array($paymentStatus, $validStatuses, true)) throw new HttpException('Statut de paiement non confirmé.', 422);
        if (!array_key_exists('amount_total', $data) && !array_key_exists('amount_paid', $data) && !array_key_exists('amount_cents', $data) && !array_key_exists('amount', $data)) throw new HttpException('Montant webhook manquant.', 422);
        $amount = $data['amount_cents'] ?? $data['amount_total'] ?? $data['amount_paid'] ?? $data['amount'];
        if (!is_int($amount) && !ctype_digit((string) $amount)) throw new HttpException('Montant webhook invalide.', 422);
        if ((int) $amount !== (int) $donation['amount_cents']) throw new HttpException('Montant webhook incohérent.', 422);
        $currency = strtoupper((string) ($data['currency'] ?? ''));
        if ($currency === '' || $currency !== strtoupper((string) $donation['currency'])) throw new HttpException('Devise webhook incohérente.', 422);
        if (!array_key_exists('livemode', $data) || !is_bool($data['livemode']) || ($data['livemode'] ? 'live' : 'test') !== $this->mode) throw new HttpException('Mode webhook incohérent.', 422);
        $sessionId = (string) ($data['session_id'] ?? (($type === 'checkout.session.completed') ? ($data['id'] ?? '') : ($data['checkout_session_id'] ?? '')));
        $paymentId = (string) ($data['payment_intent_id'] ?? $data['payment_intent'] ?? $data['payment_id'] ?? (($type !== 'checkout.session.completed') ? ($data['id'] ?? '') : ''));
        if ($paymentId === '' || ($sessionId !== '' && $paymentId === $sessionId)) throw new HttpException('Identifiant de paiement manquant.', 422);
        $attempt = $this->database->fetchOne(
            'SELECT * FROM payment_attempts WHERE donation_id = :donation AND provider = :provider AND mode = :mode AND (provider_session_id = :session OR provider_payment_id = :payment)',
            ['donation' => $donation['id'], 'provider' => $provider, 'mode' => $this->mode, 'session' => $sessionId, 'payment' => $paymentId]
        );
        if (!$attempt) throw new HttpException('Tentative de paiement non rapprochée.', 422);
    }

    private function confirm(array $event, array $donation, array $data): void
    {
        if (in_array($donation['status'], ['paid', 'refunded', 'disputed'], true)) return;
        $now = Clock::now();
        $paymentId = (string) ($data['payment_intent_id'] ?? $data['payment_intent'] ?? $data['payment_id'] ?? $data['id']);
        $type = (string) $event['type'];
        $sessionId = (string) ($data['session_id'] ?? (($type === 'checkout.session.completed') ? ($data['id'] ?? '') : ($data['checkout_session_id'] ?? '')));
        $this->database->execute('UPDATE donations SET status = \'paid\', paid_at = :now, updated_at = :now WHERE id = :id', ['now' => $now, 'id' => $donation['id']]);
        $this->database->execute('UPDATE payment_attempts SET provider_payment_id = :payment, status = \'succeeded\', updated_at = :now WHERE donation_id = :donation AND mode = :mode AND (provider_session_id = :session OR provider_payment_id = :payment)', ['payment' => $paymentId, 'session' => $sessionId, 'now' => $now, 'donation' => $donation['id'], 'mode' => $this->mode]);
        $this->database->execute('INSERT INTO financial_entries(id, donation_id, type, amount_cents, currency, external_reference, created_at) VALUES(:id, :donation, \'donation\', :amount, :currency, :external, :now)', ['id' => Uuid::v4(), 'donation' => $donation['id'], 'amount' => $donation['amount_cents'], 'currency' => $donation['currency'], 'external' => $paymentId, 'now' => $now]);
        $this->database->execute('INSERT INTO outbox_messages(id, kind, aggregate_type, aggregate_id, dedupe_key, payload, available_at, created_at, updated_at) VALUES(:id, \'donation.confirmed\', \'donation\', :aggregate, :dedupe, :payload, :now, :now, :now)', ['id' => Uuid::v4(), 'aggregate' => $donation['id'], 'dedupe' => 'donation-confirmed:' . $donation['id'], 'payload' => json_encode(['donation_id' => $donation['id']], JSON_THROW_ON_ERROR), 'now' => $now]);
    }

    private function validSignature(string $body, ?string $signature): bool
    {
        if (!$signature) return false;
        if (str_starts_with($signature, 't=')) {
            $parts = [];
            foreach (explode(',', $signature) as $part) { [$key, $value] = array_pad(explode('=', $part, 2), 2, ''); $parts[$key] = $value; }
            $timestamp = (int) ($parts['t'] ?? 0);
            return $timestamp > 0 && abs(time() - $timestamp) <= 300 && isset($parts['v1']) && hash_equals(hash_hmac('sha256', $timestamp . '.' . $body, $this->secret), $parts['v1']);
        }
        $signature = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;
        return hash_equals(hash_hmac('sha256', $body, $this->secret), $signature);
    }
}
