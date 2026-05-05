<?php

namespace App\Core;

class Request
{
    private array  $params  = [];
    private ?array $jsonBody = null;
    private mixed  $user    = null;

    public static function fromGlobals(): static
    {
        return new static();
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim(preg_replace('#/+#', '/', $uri), '/') ?: '/';
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function queryAll(): array
    {
        return $_GET;
    }

    public function json(): array
    {
        if ($this->jsonBody === null) {
            $raw = file_get_contents('php://input');
            $this->jsonBody = json_decode($raw, true) ?? [];
        }
        return $this->jsonBody;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->json()[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    public function setRouteParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function params(): array
    {
        return $this->params;
    }

    public function setUser(mixed $user): void
    {
        $this->user = $user;
    }

    public function user(): mixed
    {
        return $this->user;
    }

    public function validated(array $rules): array
    {
        $data   = $this->json();
        $errors = [];
        $result = [];

        foreach ($rules as $field => $ruleStr) {
            $value = $data[$field] ?? null;
            $rules_list = explode('|', $ruleStr);

            foreach ($rules_list as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = "$field is required";
                }
            }
            $result[$field] = $value;
        }

        if (!empty($errors)) {
            Response::json(['success' => false, 'errors' => $errors], 422);
        }

        return $result;
    }
}
