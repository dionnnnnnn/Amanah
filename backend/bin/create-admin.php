<?php

declare(strict_types=1);

[$config, $database] = require dirname(__DIR__) . '/src/bootstrap.php';

function readSecret(string $prompt): string
{
    fwrite(STDOUT, $prompt);
    if (PHP_OS_FAMILY === 'Windows' && function_exists('shell_exec')) {
        $secret = shell_exec('powershell -NoProfile -NonInteractive -Command "$p=Read-Host -AsSecureString; $b=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($p); [Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)"');
        if (is_string($secret) && trim($secret) !== '') return trim($secret);
    }
    if (function_exists('shell_exec')) {
        shell_exec('stty -echo 2>/dev/null');
        $secret = trim((string) fgets(STDIN));
        shell_exec('stty echo 2>/dev/null');
        fwrite(STDOUT, "\n");
        return $secret;
    }
    return trim((string) readline());
}

$email = trim((string) readline('E-mail administrateur: '));
$password = readSecret("Mot de passe (12 caractères minimum): ");
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "E-mail invalide ou mot de passe trop court (12 caractères minimum).\n");
    exit(1);
}
$id = Amanah\Support\Uuid::v4();
$now = Amanah\Support\Clock::now();
$mfaSecret = Amanah\Security\Totp::generateSecret();
$database->execute(
    'INSERT INTO users(id, email, password_hash, mfa_secret_encrypted, status, created_at, updated_at)
     VALUES(:id, :email, :hash, :mfa, \'active\', :now, :now)',
    ['id' => $id, 'email' => strtolower($email), 'hash' => password_hash($password, PASSWORD_DEFAULT),
     'mfa' => Amanah\Security\Totp::encryptSecret($mfaSecret, $config['key']), 'now' => $now]
);
$role = $database->fetchOne('SELECT id FROM roles WHERE name = \'administrator\'');
if (!$role) {
    $database->execute('INSERT INTO roles(id, name) VALUES(:id, \'administrator\')', ['id' => 'role-admin-0000-0000-000000000001']);
    $role = ['id' => 'role-admin-0000-0000-000000000001'];
}
$database->execute('INSERT INTO role_user(user_id, role_id) VALUES(:user, :role)', ['user' => $id, 'role' => $role['id']]);
fwrite(STDOUT, "Compte administrateur créé. Secret MFA à enregistrer dans votre application : {$mfaSecret}\n");
