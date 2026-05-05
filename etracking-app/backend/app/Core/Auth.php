<?php

namespace App\Core;

class Auth
{
    private static function config(): array
    {
        return require __DIR__ . '/../../config/app.php';
    }

    public static function generateJWT(array $payload): string
    {
        $cfg    = self::config();
        $header = self::b64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['iat'] = $payload['iat'] ?? time();
        $payload['exp'] = $payload['exp'] ?? time() + $cfg['jwt_ttl'];
        $body = self::b64url(json_encode($payload));
        $sig  = self::b64url(hash_hmac('sha256', "$header.$body", $cfg['jwt_secret'], true));
        return "$header.$body.$sig";
    }

    public static function verifyJWT(string $token): ?array
    {
        $cfg   = self::config();
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $body, $sig] = $parts;
        $expected = self::b64url(hash_hmac('sha256', "$header.$body", $cfg['jwt_secret'], true));

        if (!hash_equals($expected, $sig)) return null;

        $payload = json_decode(self::b64urlDecode($body), true);
        if (!$payload) return null;
        if (isset($payload['exp']) && $payload['exp'] < time()) return null;

        return $payload;
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
