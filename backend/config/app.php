<?php

declare(strict_types=1);

$environment = getenv('APP_ENV') ?: 'production';

return [
    'env' => $environment,
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL),
    'url' => rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/'),
    'key' => getenv('APP_KEY') ?: '',
    'payment_driver' => getenv('PAYMENT_DRIVER') ?: ($environment === 'local' ? 'fake' : 'unavailable'),
    'payment_mode' => getenv('PAYMENT_MODE') ?: 'test',
    'allow_recurring' => filter_var(getenv('ALLOW_RECURRING') ?: 'false', FILTER_VALIDATE_BOOL),
    'webhook_secret' => getenv('PAYMENT_WEBHOOK_SECRET') ?: '',
    'internal_job_token' => getenv('INTERNAL_JOB_TOKEN') ?: '',
    'mail_from' => getenv('MAIL_FROM') ?: 'bonjour@example.org',
    'mail_to' => getenv('MAIL_TO') ?: 'bonjour@example.org',
];
