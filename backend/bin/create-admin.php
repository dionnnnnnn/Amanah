<?php

declare(strict_types=1);

[$config, $database] = require dirname(__DIR__) . '/src/bootstrap.php';

$email = trim((string) readline('E-mail administrateur: '));
$password = (string) readline('Mot de passe (saisi localement): ');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "E-mail invalide ou mot de passe trop court (12 caractères minimum).\n");
    exit(1);
}
$id = Amanah\Support\Uuid::v4();
$now = Amanah\Support\Clock::now();
$database->execute(
    'INSERT INTO users(id, email, password_hash, status, created_at, updated_at)
     VALUES(:id, :email, :hash, \'active\', :now, :now)',
    ['id' => $id, 'email' => strtolower($email), 'hash' => password_hash($password, PASSWORD_DEFAULT), 'now' => $now]
);
$role = $database->fetchOne('SELECT id FROM roles WHERE name = \'administrator\'');
if (!$role) {
    $database->execute('INSERT INTO roles(id, name) VALUES(:id, \'administrator\')', ['id' => 'role-admin-0000-0000-000000000001']);
    $role = ['id' => 'role-admin-0000-0000-000000000001'];
}
$database->execute('INSERT INTO role_user(user_id, role_id) VALUES(:user, :role)', ['user' => $id, 'role' => $role['id']]);
fwrite(STDOUT, "Compte administrateur créé. Configurez ensuite la MFA avant production.\n");
