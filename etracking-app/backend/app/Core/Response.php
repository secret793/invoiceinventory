<?php

namespace App\Core;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): void
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    public static function paginated(array $items, int $total, int $page, int $perPage, string $message = 'OK'): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $items,
            'meta'    => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / max(1, $perPage)),
                'from'         => $total ? ($page - 1) * $perPage + 1 : 0,
                'to'           => min($total, $page * $perPage),
            ],
        ]);
    }

    public static function error(string $message, int $status = 400, array $errors = []): void
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors) $body['errors'] = $errors;
        self::json($body, $status);
    }

    public static function notFound(string $message = 'Record not found'): void
    {
        self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthenticated'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    public static function file(string $content, string $filename, string $mimeType = 'application/octet-stream'): void
    {
        header("Content-Type: $mimeType");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }
}
