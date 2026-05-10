<?php

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $url = getenv('DATABASE_URL');
            if ($url) {
                $p    = parse_url($url);
                $host = $p['host'] ?? 'localhost';
                $port = $p['port'] ?? 5432;
                $db   = ltrim($p['path'] ?? '/heliumdb', '/');
                $user = $p['user'] ?? 'postgres';
                $pass = $p['pass'] ?? '';
                $query   = $p['query'] ?? '';
                $sslmode = '';
                if (str_contains($query, 'sslmode=disable')) {
                    $sslmode = ';sslmode=disable';
                }
                $dsn = "pgsql:host={$host};port={$port};dbname={$db}{$sslmode}";
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } else {
                $config = require __DIR__ . '/../../config/database.php';
                $dsn    = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
                self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            }
        }
        return self::$instance;
    }

    // ── MySQL compatibility: replace PostgreSQL-only keywords ────────────────
    private static function normalise(string $sql): string
    {
        if (self::isMySQL()) {
            // ILIKE is not supported in MySQL; LIKE is already case-insensitive
            // with utf8mb4_unicode_ci collation (the default in our MySQL schema)
            $sql = preg_replace('/\bILIKE\b/i', 'LIKE', $sql);
        }
        return $sql;
    }

    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::getInstance()->prepare(self::normalise($sql));
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = self::getInstance()->prepare(self::normalise($sql));
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getInstance()->prepare(self::normalise($sql));
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function insert(string $sql, array $params = []): string
    {
        $sql = self::normalise($sql);
        if (!self::isMySQL()) {
            // PostgreSQL: use RETURNING id
            $returning = (stripos($sql, 'RETURNING') === false) ? ' RETURNING id' : '';
            $stmt = self::getInstance()->prepare($sql . $returning);
            $stmt->execute($params);
            $row = $stmt->fetch();
            return (string) ($row['id'] ?? 0);
        }
        // MySQL: standard INSERT + lastInsertId()
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return self::getInstance()->lastInsertId();
    }

    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    public static function isMySQL(): bool
    {
        $url = getenv('DATABASE_URL');
        return !($url && (str_starts_with($url, 'postgresql') || str_starts_with($url, 'postgres')));
    }

    private static function getDsn(): string
    {
        return self::isMySQL() ? 'mysql' : 'pgsql';
    }
}
