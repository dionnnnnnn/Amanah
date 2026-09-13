<?php

declare(strict_types=1);

$projectDir = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($projectDir): void {
    $prefix = 'Amanah\\';
    if (str_starts_with($class, $prefix)) {
        require_once $projectDir . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

use Amanah\Domain\Money;
use Amanah\Security\Totp;

$money = Money::fromDecimal('150.50');
assert($money->cents === 15050);
assert($money->decimal() === '150.50');
foreach (['0', '-1', '10.123', 'abc', '1000000000'] as $invalid) {
    try {
        Money::fromDecimal($invalid);
        throw new RuntimeException('Montant invalide accepté: ' . $invalid);
    } catch (InvalidArgumentException) {
        // attendu
    }
}
assert(Totp::valid('JBSWY3DPEHPK3PXP', '000000') === false);

fwrite(STDOUT, "Smoke tests OK\n");
