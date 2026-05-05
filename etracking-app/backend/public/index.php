<?php

declare(strict_types=1);

// ── Autoloader ────────────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $base = __DIR__ . '/../';
    $file = $base . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// ── CORS ──────────────────────────────────────────────────────────────────────
\App\Middleware\CorsMiddleware::handle();

// ── Routes ────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../routes/api.php';

// ── Dispatch ──────────────────────────────────────────────────────────────────
$router = \App\Core\Router::getInstance();
$request = \App\Core\Request::fromGlobals();
$router->dispatch($request);
