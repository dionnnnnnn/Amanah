<?php

declare(strict_types=1);

use Amanah\Infrastructure\Database;
use PDO;

$projectDir = dirname(__DIR__);
if (is_file($projectDir . '/.env')) {
    foreach (file($projectDir . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $value = trim($value, " \t\"");
        if (getenv(trim($name)) === false) {
            putenv(trim($name) . '=' . str_replace('%kernel.project_dir%', $projectDir, $value));
        }
    }
}

if (is_file($projectDir . '/vendor/autoload.php')) {
    require_once $projectDir . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class) use ($projectDir): void {
        $prefix = 'Amanah\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $file = $projectDir . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
}

$config = require $projectDir . '/config/app.php';
$dsn = getenv('DB_DSN') ?: 'sqlite:' . $projectDir . '/storage/database.sqlite';
$dsn = str_replace('%kernel.project_dir%', $projectDir, $dsn);
$database = new Database(new PDO($dsn, getenv('DB_USER') ?: null, getenv('DB_PASSWORD') ?: null));

return [$config, $database];
