<?php

declare(strict_types=1);

use Amanah\Http\Request;
use Amanah\Http\Response;
use Amanah\Security\Csrf;
use Amanah\Security\RateLimiter;
use Amanah\Services\AdminAuthService;
use Amanah\Services\CommunicationService;
use Amanah\Services\DonationService;
use Amanah\Services\FakePaymentGateway;
use Amanah\Services\JobRunner;
use Amanah\Services\RefundService;
use Amanah\Services\UnavailablePaymentGateway;
use Amanah\Services\WebhookService;
use InvalidArgumentException;
use Throwable;

[$config, $database] = require dirname(__DIR__) . '/src/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$request = new Request();
$startSession = static function (): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('amanah_session');
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'cookie_samesite' => 'Lax',
        ]);
    }
};
$csrf = static function () use ($startSession): void {
    $startSession();
    Csrf::verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? null);
};
$sessionId = static function () use ($startSession): string {
    $startSession();
    return session_id();
};
$jsonBody = static function (Request $request): array {
    return $request->body();
};
$gateway = $config['payment_driver'] === 'fake' ? new FakePaymentGateway() : new UnavailablePaymentGateway();
$donations = new DonationService($database, $gateway, $config['payment_mode'], $config['url'], $config['allow_recurring']);
$communication = new CommunicationService($database, new RateLimiter($database));
$auth = new AdminAuthService($database, $config['key'], new RateLimiter($database));

try {
    $method = $request->method();
    $path = $request->path();

    if ($method === 'GET' && $path === '/up') {
        Response::json(['status' => 'ok', 'service' => 'amanah-backend']);
    }
    if ($method === 'GET' && $path === '/session/csrf') {
        Response::json(['token' => Csrf::token()]);
    }
    if ($method === 'POST' && $path === '/dons/checkout') {
        $csrf();
        $input = $jsonBody($request);
        $key = $request->header('Idempotency-Key') ?: ($input['idempotency_key'] ?? '');
        $input['idempotency_key'] = $key;
        $result = $donations->checkout($input, $sessionId());
        $_SESSION['donation_references'][] = $result['reference'];
        Response::json($result, 201);
    }
    if ($method === 'GET' && ($path === '/don/retour' || preg_match('#^/dons/([^/]+)/statut$#', $path, $match))) {
        $reference = $match[1] ?? (string) $request->input('reference', '');
        $startSession();
        if ($reference === '' || !in_array($reference, $_SESSION['donation_references'] ?? [], true)) {
            Response::json(['error' => 'Référence indisponible.'], 404);
        }
        $status = $donations->status($reference, session_id());
        Response::json($status ?? ['error' => 'Don introuvable.'], $status ? 200 : 404);
    }
    if ($method === 'POST' && $path === '/webhooks/paiement') {
        $rawBody = file_get_contents('php://input') ?: '';
        $result = (new WebhookService($database, $config['webhook_secret'], $config['payment_mode']))
            ->handle($rawBody, $request->header('Stripe-Signature') ?: $request->header('X-Payment-Signature'));
        Response::json($result, 200);
    }
    if ($method === 'POST' && $path === '/contact') {
        $csrf();
        $communication->contact($jsonBody($request), $request->ip());
        Response::json(['message' => 'Votre message a bien été reçu.'], 202);
    }
    if ($method === 'POST' && $path === '/newsletter/inscription') {
        $csrf();
        $input = $jsonBody($request);
        $communication->subscribe((string) ($input['email'] ?? ''), $request->ip());
        Response::json(['message' => 'Si l’adresse est éligible, un e-mail de confirmation a été envoyé.'], 202);
    }
    if (preg_match('#^/newsletter/confirmer/([a-f0-9]{64})$#', $path, $match)) {
        if ($method === 'GET') {
            Response::json(['message' => 'Confirmez votre inscription avec une requête POST.']);
        }
        if ($method === 'POST') {
            $csrf();
            Response::json(['confirmed' => $communication->confirm($match[1])]);
        }
    }
    if (preg_match('#^/newsletter/desinscription/([a-f0-9]{64})$#', $path, $match)) {
        if ($method === 'GET') {
            Response::json(['message' => 'Confirmez votre désinscription avec une requête POST.']);
        }
        if ($method === 'POST') {
            $csrf();
            Response::json(['unsubscribed' => $communication->unsubscribe($match[1])]);
        }
    }
    if ($method === 'GET' && $path === '/internal/jobs/run') {
        $provided = $request->header('X-Internal-Job-Token') ?? '';
        if ($config['internal_job_token'] === '' || !hash_equals($config['internal_job_token'], $provided)) {
            Response::json(['error' => 'Non autorisé.'], 401);
        }
        Response::json((new JobRunner($database, $config))->run());
    }
    if ($method === 'POST' && $path === '/admin/login') {
        $csrf();
        $input = $jsonBody($request);
        $auth->login((string) ($input['email'] ?? ''), (string) ($input['password'] ?? ''), $input['mfa_code'] ?? null, $request->ip());
        Response::json(['authenticated' => true]);
    }
    if ($method === 'POST' && $path === '/admin/logout') {
        $auth->requireRole('administrator', 'editor', 'finance', 'support');
        $auth->logout();
        Response::json(['authenticated' => false]);
    }
    if ($method === 'POST' && preg_match('#^/admin/dons/([^/]+)/remboursements$#', $path, $match)) {
        $csrf();
        $actorId = $auth->requireRole('administrator', 'finance');
        $input = $jsonBody($request);
        $key = $request->header('Idempotency-Key') ?: (string) ($input['idempotency_key'] ?? '');
        $result = (new RefundService($database, $gateway))->refund(
            $match[1], (string) ($input['amount'] ?? ''), $key, $actorId
        );
        Response::json($result, 201);
    }

    Response::json(['error' => 'Route introuvable.'], 404);
} catch (InvalidArgumentException $exception) {
    Response::json(['error' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $status = str_contains($exception->getMessage(), 'non autorisé') || str_contains($exception->getMessage(), 'requis') ? 403 : 500;
    Response::json(['error' => $config['debug'] ? $exception->getMessage() : 'Une erreur interne est survenue.'], $status);
}
