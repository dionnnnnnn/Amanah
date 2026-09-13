<?php

declare(strict_types=1);

// Routeur de développement uniquement : en production, Apache utilise public/.htaccess.
$public = realpath(__DIR__ . '/../public');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if ($public === false || str_contains($path, "\0") || str_contains($path, '\\') || preg_match('~(^|/)\.~', $path)) {
    http_response_code(403);
    exit('Forbidden');
}
if ($path === '/') {
    header('Content-Type: text/html; charset=utf-8');
    readfile($public . '/index.html');
    return true;
}
$file = realpath($public . $path);
if ($file !== false && str_starts_with($file, $public . DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}
require $public . '/index.php';
