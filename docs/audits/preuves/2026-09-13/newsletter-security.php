<?php
spl_autoload_register(function ($class) {
    $prefix = 'Amanah\\';
    if (str_starts_with($class, $prefix)) {
        require 'C:/Users/diplk/Documents/amanah/backend/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});
$pdo = new PDO('sqlite::memory:');
$pdo->exec(file_get_contents('C:/Users/diplk/Documents/amanah/backend/database/schema.sql'));
$db = new Amanah\Infrastructure\Database($pdo);
$service = new Amanah\Services\CommunicationService($db, new Amanah\Security\RateLimiter($db));
$token1 = $service->subscribe('audit@example.org', '192.0.2.1');
$first = $db->fetchOne('SELECT * FROM outbox_messages');
$payload1 = json_decode($first['payload'], true);
$confirmed1 = $service->confirm($token1);
$before = $db->fetchOne('SELECT status FROM newsletter_subscribers')['status'];
// Simulate successful delivery without invoking mail or JobRunner.
$db->execute("UPDATE outbox_messages SET status = 'done'");
$token2 = $service->subscribe('audit@example.org', '192.0.2.2');
$after = $db->fetchOne('SELECT status, unsubscribe_token_hash FROM newsletter_subscribers');
$second = $db->fetchOne('SELECT * FROM outbox_messages WHERE id != :id', ['id' => $first['id']]);
$payload2 = json_decode($second['payload'], true);
$newMatches = hash_equals($after['unsubscribe_token_hash'], hash('sha256', $payload2['unsubscribe_token']));
$oldMatches = hash_equals($after['unsubscribe_token_hash'], hash('sha256', $payload1['unsubscribe_token']));
$confirmed2 = $service->confirm($token2);
$newUnsubscribe = $service->unsubscribe($payload2['unsubscribe_token']);
$statusAfterNew = $db->fetchOne('SELECT status FROM newsletter_subscribers')['status'];
$oldUnsubscribe = $service->unsubscribe($payload1['unsubscribe_token']);
$token3 = $service->subscribe('audit@example.org', '192.0.2.3');
echo json_encode([
    'first_confirmation'=>$confirmed1,
    'status_before_resubscribe'=>$before,
    'status_after_resubscribe'=>$after['status'],
    'new_unsubscribe_token_matches_database'=>$newMatches,
    'old_unsubscribe_token_matches_database'=>$oldMatches,
    'second_confirmation'=>$confirmed2,
    'new_token_unsubscribe_result'=>$newUnsubscribe,
    'status_after_new_token_unsubscribe'=>$statusAfterNew,
    'old_token_unsubscribe_result'=>$oldUnsubscribe,
    'outbox_count_after_third_subscription'=>$db->fetchOne('SELECT COUNT(*) AS total FROM outbox_messages')['total'],
    'outbox_distinct_dedupe_keys'=>$db->fetchOne('SELECT COUNT(DISTINCT dedupe_key) AS total FROM outbox_messages')['total'],
    'all_checks_local'=>true,
    'emails_sent'=>0
], JSON_PRETTY_PRINT), PHP_EOL;
