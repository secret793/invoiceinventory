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
                $p   = parse_url($url);
                $host = $p['host'] ?? 'localhost';
                $port = $p['port'] ?? 5432;
                $db   = ltrim($p['path'] ?? '/heliumdb', '/');
                $user = $p['user'] ?? 'postgres';
                $pass = $p['pass'] ?? '';
                $query = $p['query'] ?? '';
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

    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function insert(string $sql, array $params = []): string
    {
        // For PostgreSQL use RETURNING id; for others fall back to lastInsertId
        if (stripos(self::getDsn(), 'pgsql') !== false) {
            // Append RETURNING id if not already present
            $returning = (stripos($sql, 'RETURNING') === false) ? ' RETURNING id' : '';
            $stmt = self::getInstance()->prepare($sql . $returning);
            $stmt->execute($params);
            $row = $stmt->fetch();
            return (string) ($row['id'] ?? 0);
        }
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return self::getInstance()->lastInsertId();
    }

    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    private static function getDsn(): string
    {
        $url = getenv('DATABASE_URL');
        if ($url && str_starts_with($url, 'postgresql')) return 'pgsql';
        return 'mysql';
    }
}
