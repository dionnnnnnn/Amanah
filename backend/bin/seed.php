<?php

declare(strict_types=1);

[$config, $database] = require dirname(__DIR__) . '/src/bootstrap.php';

$roles = [
    'administrator' => 'role-admin-0000-0000-000000000001',
    'editor' => 'role-editor-0000-0000-000000000002',
    'finance' => 'role-finance-0000-0000-000000000003',
    'support' => 'role-support-0000-0000-000000000004',
];
foreach ($roles as $name => $id) {
    if (!$database->fetchOne('SELECT id FROM roles WHERE name = :name', ['name' => $name])) {
        $database->execute('INSERT INTO roles(id, name) VALUES(:id, :name)', ['id' => $id, 'name' => $name]);
    }
}
fwrite(STDOUT, "Rôles initialisés.\n");
