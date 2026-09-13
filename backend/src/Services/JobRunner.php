<?php

declare(strict_types=1);

namespace Amanah\Services;

use Amanah\Infrastructure\Database;
use Amanah\Support\Clock;

final class JobRunner
{
    public function __construct(private readonly Database $database, private readonly array $config)
    {
    }

    public function run(int $limit = 20): array
    {
        $limit = max(1, min($limit, 20));
        $this->database->execute(
            'UPDATE outbox_messages SET status = \'pending\', reserved_at = NULL, updated_at = :now
             WHERE status = \'processing\' AND reserved_at < :stale',
            ['now' => Clock::now(), 'stale' => gmdate('Y-m-d H:i:s', time() - 900)]
        );
        $rows = $this->database->fetchAll(
            'SELECT id, kind FROM outbox_messages WHERE status = \'pending\' AND available_at <= :now
             ORDER BY created_at ASC LIMIT ' . $limit, ['now' => Clock::now()]
        );
        $processed = 0;
        foreach ($rows as $row) {
            $claimed = $this->database->execute(
                'UPDATE outbox_messages SET status = \'processing\', reserved_at = :now, attempts = attempts + 1,
                 updated_at = :now WHERE id = :id AND status = \'pending\'',
                ['now' => Clock::now(), 'id' => $row['id']]
            );
            if ($claimed !== 1) {
                continue;
            }
            $message = $this->database->fetchOne('SELECT * FROM outbox_messages WHERE id = :id', ['id' => $row['id']]);
            try {
                $this->deliver($message);
                $this->database->execute(
                    'UPDATE outbox_messages SET status = \'done\', updated_at = :now WHERE id = :id',
                    ['now' => Clock::now(), 'id' => $row['id']]
                );
                $processed++;
            } catch (\Throwable $exception) {
                $attempts = (int) $message['attempts'];
                $status = $attempts >= 5 ? 'failed' : 'pending';
                $this->database->execute(
                    'UPDATE outbox_messages SET status = :status, available_at = :available, last_error = :error, updated_at = :now WHERE id = :id',
                    ['status' => $status, 'available' => gmdate('Y-m-d H:i:s', time() + 900),
                     'error' => substr($exception->getMessage(), 0, 200), 'now' => Clock::now(), 'id' => $row['id']]
                );
                if ($status === 'failed') {
                    $this->database->execute(
                        'INSERT INTO failed_jobs(id, outbox_id, error_message, failed_at) VALUES(:id, :outbox, :error, :now)',
                        ['id' => \Amanah\Support\Uuid::v4(), 'outbox' => $row['id'],
                         'error' => substr($exception->getMessage(), 0, 200), 'now' => Clock::now()]
                    );
                }
            }
        }
        return ['processed' => $processed, 'available' => count($rows)];
    }

    private function deliver(array $message): void
    {
        $payload = json_decode($message['payload'], true, 512, JSON_THROW_ON_ERROR);
        $to = (string) ($this->config['mail_to'] ?? '');
        $from = (string) ($this->config['mail_from'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Configuration e-mail invalide.');
        }
        $subject = 'Amanah — message automatique';
        $body = '';
        $headers = ['From: ' . $from, 'Content-Type: text/plain; charset=UTF-8'];
        if ($message['kind'] === 'newsletter.confirmation') {
            $subscriber = $this->database->fetchOne('SELECT email FROM newsletter_subscribers WHERE id = :id', ['id' => $payload['subscriber_id']]);
            $baseUrl = rtrim((string) ($this->config['url'] ?? ''), '/');
            $body = "Confirmez votre inscription à la newsletter Amanah :\n" . $baseUrl
                . '/newsletter/confirmer/' . rawurlencode((string) $payload['confirmation_token'])
                . "\n\nVous pouvez vous désinscrire à tout moment :\n" . $baseUrl
                . '/newsletter/desinscription/' . rawurlencode((string) $payload['unsubscribe_token']);
            $to = (string) ($subscriber['email'] ?? '');
            $subject = 'Amanah — confirmez votre inscription';
        } elseif ($message['kind'] === 'donation.confirmed') {
            $donation = $this->database->fetchOne(
                'SELECT d.public_reference, d.amount_cents, d.currency, dn.email FROM donations d JOIN donors dn ON dn.id = d.donor_id WHERE d.id = :id',
                ['id' => $payload['donation_id']]
            );
            $to = (string) ($donation['email'] ?? '');
            $subject = 'Amanah — confirmation de votre don';
            $body = 'Votre don ' . ($donation['public_reference'] ?? '') . ' de ' . number_format(((int) ($donation['amount_cents'] ?? 0)) / 100, 2, '.', '')
                . ' ' . ($donation['currency'] ?? 'CHF') . ' est confirmé.';
        } elseif ($message['kind'] === 'contact.received') {
            $contact = $this->database->fetchOne('SELECT email, name, subject, message FROM contact_messages WHERE id = :id', ['id' => $payload['message_id']]);
            $subject = 'Amanah — nouvelle demande de contact';
            $body = (string) ($contact['message'] ?? '');
            if (filter_var($contact['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                $headers[] = 'Reply-To: ' . $contact['email'];
            }
        } else {
            throw new \RuntimeException('Type de message non pris en charge.');
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !mail($to, $subject, $body, implode("\r\n", $headers))) {
            throw new \RuntimeException('Envoi e-mail échoué.');
        }
    }
}
