<?php

declare(strict_types=1);

namespace Amanah\Http;

final class Request
{
    private ?array $json = null;

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $body = $this->body();
        return $body[$key] ?? $_GET[$key] ?? $default;
    }

    public function body(): array
    {
        $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            if ($this->json === null) {
                $decoded = json_decode(file_get_contents('php://input') ?: '{}', true);
                $this->json = is_array($decoded) ? $decoded : [];
            }
            return $this->json;
        }
        return $_POST;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$key]) ? trim((string) $_SERVER[$key]) : null;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
