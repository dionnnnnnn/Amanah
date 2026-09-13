<?php

declare(strict_types=1);

namespace Amanah\Services;

use Amanah\Infrastructure\Database;
use Amanah\Security\RateLimiter;
use Amanah\Support\Clock;
use Amanah\Support\Uuid;
use InvalidArgumentException;

final class CommunicationService
{
    public function __construct(private readonly Database $database, private readonly RateLimiter $limiter)
    {
    }

    public function contact(array $input, string $ip): void
    {
        if ((string) ($input['website'] ?? '') !== '') {
            throw new InvalidArgumentException('Demande refusée.');
        }
        if (!$this->limiter->allow('contact:' . hash('sha256', $ip), 5, 3600)) {
            throw new InvalidArgumentException('Trop de demandes. Réessayez plus tard.');
        }
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $message = trim((string) ($input['message'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '' || strlen($message) > 10000) {
            throw new InvalidArgumentException('Adresse e-mail ou message invalide.');
        }
        $now = Clock::now();
        $id = Uuid::v4();
        $this->database->transaction(function () use ($input, $email, $message, $now, $id): void {
            $this->database->execute(
                'INSERT INTO contact_messages(id, email, name, subject, message, purge_at, created_at)
                 VALUES(:id, :email, :name, :subject, :message, :purge, :now)',
                ['id' => $id, 'email' => $email, 'name' => $this->short($input['name'] ?? null, 200),
                 'subject' => $this->short($input['subject'] ?? null, 255), 'message' => $message,
                 'purge' => gmdate('Y-m-d H:i:s', time() + 180 * 86400), 'now' => $now]
            );
            $this->enqueue('contact.received', 'contact_message', $id, 'contact:' . $id, ['message_id' => $id], $now);
        });
    }

    public function subscribe(string $email, string $ip): string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$this->limiter->allow('newsletter:' . hash('sha256', $ip), 5, 3600)) {
            throw new InvalidArgumentException('Inscription impossible pour le moment.');
        }
        $confirmationToken = bin2hex(random_bytes(32));
        $unsubscribeToken = bin2hex(random_bytes(32));
        $now = Clock::now();
        $existing = $this->database->fetchOne('SELECT * FROM newsletter_subscribers WHERE email = :email', ['email' => $email]);
        if ($existing && $existing['status'] === 'suppressed') {
            return '';
        }
        $id = $existing['id'] ?? Uuid::v4();
        $hash = hash('sha256', $confirmationToken);
        $expires = gmdate('Y-m-d H:i:s', time() + 86400);
        if ($existing) {
            $this->database->execute(
                'UPDATE newsletter_subscribers SET status = \'pending\', confirmation_token_hash = :hash,
                 unsubscribe_token_hash = COALESCE(unsubscribe_token_hash, :unsubscribe_hash),
                 token_expires_at = :expires, updated_at = :now WHERE id = :id',
                ['hash' => $hash, 'unsubscribe_hash' => hash('sha256', $unsubscribeToken), 'expires' => $expires, 'now' => $now, 'id' => $id]
            );
        } else {
            $this->database->execute(
                'INSERT INTO newsletter_subscribers(id, email, status, confirmation_token_hash, unsubscribe_token_hash,
                 token_expires_at, created_at, updated_at)
                 VALUES(:id, :email, \'pending\', :hash, :unsubscribe_hash, :expires, :now, :now)',
                ['id' => $id, 'email' => $email, 'hash' => $hash, 'unsubscribe_hash' => hash('sha256', $unsubscribeToken),
                 'expires' => $expires, 'now' => $now]
            );
        }
        $this->enqueue('newsletter.confirmation', 'newsletter_subscriber', $id, 'newsletter-confirm:' . $id . ':' . $hash,
            ['subscriber_id' => $id, 'confirmation_token' => $confirmationToken, 'unsubscribe_token' => $unsubscribeToken], $now);
        return $confirmationToken;
    }

    public function confirm(string $token): bool
    {
        $row = $this->subscriberByToken($token);
        if (!$row) {
            return false;
        }
        $now = Clock::now();
        $this->database->transaction(function () use ($row, $now): void {
            $this->database->execute(
                'UPDATE newsletter_subscribers SET status = \'subscribed\', confirmation_token_hash = NULL,
                 token_expires_at = NULL, subscribed_at = :now, updated_at = :now WHERE id = :id',
                ['now' => $now, 'id' => $row['id']]
            );
            $this->consent($row['id'], 'subscribe', $now);
        });
        return true;
    }

    public function unsubscribe(string $token): bool
    {
        $row = $this->database->fetchOne('SELECT * FROM newsletter_subscribers WHERE unsubscribe_token_hash = :hash',
            ['hash' => hash('sha256', $token)]);
        if (!$row) {
            return false;
        }
        $now = Clock::now();
        $this->database->transaction(function () use ($row, $now): void {
            $this->database->execute(
                'UPDATE newsletter_subscribers SET status = \'unsubscribed\', confirmation_token_hash = NULL,
                 token_expires_at = NULL, unsubscribed_at = :now, updated_at = :now WHERE id = :id',
                ['now' => $now, 'id' => $row['id']]
            );
            $this->consent($row['id'], 'unsubscribe', $now);
        });
        return true;
    }

    private function subscriberByToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return $this->database->fetchOne(
            'SELECT * FROM newsletter_subscribers WHERE confirmation_token_hash = :hash AND token_expires_at > :now',
            ['hash' => hash('sha256', $token), 'now' => Clock::now()]
        );
    }

    private function consent(string $subscriberId, string $action, string $now): void
    {
        $this->database->execute(
            'INSERT INTO consent_events(id, subscriber_id, action, policy_version, created_at)
             VALUES(:id, :subscriber, :action, :version, :now)',
            ['id' => Uuid::v4(), 'subscriber' => $subscriberId, 'action' => $action, 'version' => 'newsletter-v1', 'now' => $now]
        );
    }

    private function enqueue(string $kind, string $type, string $aggregateId, string $dedupe, array $payload, string $now): void
    {
        $this->database->execute(
            'INSERT INTO outbox_messages(id, kind, aggregate_type, aggregate_id, dedupe_key, payload, available_at, created_at, updated_at)
             VALUES(:id, :kind, :type, :aggregate, :dedupe, :payload, :now, :now, :now)',
            ['id' => Uuid::v4(), 'kind' => $kind, 'type' => $type, 'aggregate' => $aggregateId,
             'dedupe' => $dedupe, 'payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'now' => $now]
        );
    }

    private function short(mixed $value, int $length): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : substr($value, 0, $length);
    }
}
