<?php

declare(strict_types=1);

namespace Amanah\Services;

use Amanah\Domain\Money;
use Amanah\Http\HttpException;
use Amanah\Infrastructure\Database;
use Amanah\Support\Clock;
use Amanah\Support\Uuid;
use InvalidArgumentException;
use RuntimeException;

final class DonationService
{
    public function __construct(private readonly Database $database, private readonly PaymentGateway $gateway,
        private readonly string $paymentMode, private readonly string $appUrl, private readonly bool $allowRecurring) {}

    public function checkout(array $input, string $sessionId): array
    {
        $money = Money::fromDecimal((string) ($input['amount'] ?? ''), (string) ($input['currency'] ?? 'CHF'));
        $frequency = (string) ($input['frequency'] ?? 'one_time');
        if (!in_array($frequency, ['one_time', 'monthly'], true)) throw new InvalidArgumentException('Fréquence de don non autorisée.');
        if ($frequency === 'monthly' && !$this->allowRecurring) throw new InvalidArgumentException('Le don mensuel n’est pas encore disponible.');
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Adresse e-mail invalide.');
        $key = trim((string) ($input['idempotency_key'] ?? ''));
        if ($key === '' || strlen($key) > 128) throw new InvalidArgumentException('Clé d’idempotence manquante ou invalide.');
        $projectId = $this->publishedProjectId($input['project'] ?? null);
        $contract = $this->contract($input, $email, $money, $frequency, $projectId);
        $existing = $this->database->fetchOne(
            'SELECT d.*, p.id AS attempt_id, p.session_id, p.checkout_url, p.status AS attempt_status
             FROM payment_attempts p JOIN donations d ON d.id = p.donation_id WHERE p.idempotency_key = :key', ['key' => $key]
        );
        if ($existing) {
            if ((string) $existing['session_id'] !== $sessionId) throw new HttpException('Cette clé d’idempotence appartient à une autre session.', 409);
            if ($existing['donor_snapshot'] !== json_encode($contract, JSON_UNESCAPED_UNICODE)) throw new HttpException('La clé d’idempotence est déjà associée à un autre don.', 409);
            if ((string) $existing['attempt_status'] === 'failed' && empty($existing['checkout_url'])) return $this->createCheckout($existing, $key);
            return $this->checkoutResponse($existing);
        }

        $donation = $this->database->transaction(function () use ($email, $input, $money, $frequency, $projectId, $key, $sessionId, $contract): array {
            $now = Clock::now();
            $donor = $this->database->fetchOne('SELECT id FROM donors WHERE email = :email', ['email' => $email]);
            $donorId = $donor['id'] ?? Uuid::v4();
            if (!$donor) $this->database->execute(
                'INSERT INTO donors(id, email, first_name, last_name, locale, created_at, updated_at) VALUES(:id, :email, :first, :last, :locale, :now, :now)',
                ['id' => $donorId, 'email' => $email, 'first' => $this->nullable($input['first_name'] ?? null), 'last' => $this->nullable($input['last_name'] ?? null), 'locale' => 'fr_CH', 'now' => $now]
            );
            $donationId = Uuid::v4();
            $reference = 'AM-' . strtoupper(substr(bin2hex(random_bytes(10)), 0, 16));
            $snapshot = json_encode($contract, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $this->database->execute(
                'INSERT INTO donations(id, public_reference, donor_id, project_id, amount_cents, currency, frequency, status, donor_snapshot, created_at, updated_at)
                 VALUES(:id, :reference, :donor, :project, :amount, :currency, :frequency, \'pending\', :snapshot, :now, :now)',
                ['id' => $donationId, 'reference' => $reference, 'donor' => $donorId, 'project' => $projectId, 'amount' => $money->cents, 'currency' => $money->currency, 'frequency' => $frequency, 'snapshot' => $snapshot, 'now' => $now]
            );
            $attemptId = Uuid::v4();
            $this->database->execute(
                'INSERT INTO payment_attempts(id, donation_id, idempotency_key, session_id, provider, mode, status, created_at, updated_at)
                 VALUES(:id, :donation, :key, :session, :provider, :mode, \'created\', :now, :now)',
                ['id' => $attemptId, 'donation' => $donationId, 'key' => $key, 'session' => $sessionId, 'provider' => $this->gateway instanceof FakePaymentGateway ? 'fake' : 'configured', 'mode' => $this->paymentMode, 'now' => $now]
            );
            return ['id' => $donationId, 'public_reference' => $reference, 'amount_cents' => $money->cents, 'currency' => $money->currency, 'frequency' => $frequency, 'donor_snapshot' => $snapshot, 'attempt_id' => $attemptId, 'status' => 'pending'];
        });
        return $this->createCheckout($donation, $key);
    }

    public function status(string $reference, string $sessionId): ?array
    {
        $row = $this->database->fetchOne(
            'SELECT d.public_reference, d.amount_cents, d.currency, d.frequency, d.status, d.paid_at FROM donations d JOIN payment_attempts p ON p.donation_id = d.id WHERE d.public_reference = :reference AND p.session_id = :session',
            ['reference' => $reference, 'session' => $sessionId]
        );
        return $row ? ['reference' => $row['public_reference'], 'amount_cents' => (int) $row['amount_cents'], 'currency' => $row['currency'], 'frequency' => $row['frequency'], 'status' => $row['status'], 'paid_at' => $row['paid_at']] : null;
    }

    private function createCheckout(array $donation, string $key): array
    {
        try {
            $checkout = $this->gateway->createCheckout($donation, $key);
            $this->database->execute('UPDATE payment_attempts SET provider = :provider, provider_session_id = :session, checkout_url = :url, status = \'redirected\', updated_at = :now WHERE id = :id', ['provider' => $checkout['provider'], 'session' => $checkout['session_id'], 'url' => $checkout['checkout_url'], 'now' => Clock::now(), 'id' => $donation['attempt_id']]);
            return ['reference' => $donation['public_reference'], 'checkout_url' => $checkout['checkout_url'], 'status' => 'pending'];
        } catch (RuntimeException $exception) {
            $this->database->execute('UPDATE payment_attempts SET status = \'failed\', updated_at = :now WHERE id = :id', ['now' => Clock::now(), 'id' => $donation['attempt_id']]);
            throw $exception;
        }
    }

    private function publishedProjectId(mixed $project): ?string
    {
        if ($project === null || $project === '') return null;
        $row = $this->database->fetchOne(
            'SELECT id FROM projects WHERE (id = :project OR slug = :project) AND editorial_status = \'published\' AND operational_status = \'active\' AND (starts_at IS NULL OR starts_at <= :now) AND (ends_at IS NULL OR ends_at >= :now)',
            ['project' => (string) $project, 'now' => Clock::now()]
        );
        if (!$row) throw new InvalidArgumentException('Le projet sélectionné n’est pas disponible.');
        return $row['id'];
    }

    private function contract(array $input, string $email, Money $money, string $frequency, ?string $projectId): array
    {
        return ['email' => $email, 'first_name' => $this->nullable($input['first_name'] ?? null), 'last_name' => $this->nullable($input['last_name'] ?? null), 'amount_cents' => $money->cents, 'currency' => $money->currency, 'frequency' => $frequency, 'project_id' => $projectId];
    }

    private function checkoutResponse(array $row): array
    {
        return ['reference' => $row['public_reference'], 'checkout_url' => $row['checkout_url'], 'status' => $row['status']];
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : mb_substr($value, 0, 120);
    }
}
