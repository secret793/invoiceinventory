<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Response;

abstract class BaseModel
{
    protected static string $table;
    protected static string $primaryKey = 'id';

    // ── Finders ───────────────────────────────────────────────────────────────

    public static function find(int $id): ?array
    {
        return Database::queryOne(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?',
            [$id]
        );
    }

    public static function findOrFail(int $id): array
    {
        $row = static::find($id);
        if (!$row) Response::notFound(class_basename(static::class) . ' not found');
        return $row;
    }

    public static function all(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        return Database::query('SELECT * FROM ' . static::$table . " ORDER BY $orderBy $dir");
    }

    // ── Pagination ────────────────────────────────────────────────────────────

    public static function paginate(
        int    $page,
        int    $perPage,
        string $where  = '',
        array  $params = [],
        string $select = '*',
        string $joins  = '',
        string $order  = 'id DESC'
    ): array {
        $offset   = ($page - 1) * $perPage;
        $table    = static::$table;
        $whereStr = $where ? "WHERE $where" : '';

        $total = (int) Database::queryOne(
            "SELECT COUNT(*) as cnt FROM $table $joins $whereStr",
            $params
        )['cnt'];

        $rows = Database::query(
            "SELECT $select FROM $table $joins $whereStr ORDER BY $order LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / max(1, $perPage)),
        ];
    }

    // ── Write helpers ─────────────────────────────────────────────────────────

    public static function create(array $data): array
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        $cols   = implode(', ', array_keys($data));
        $places = implode(', ', array_fill(0, count($data), '?'));

        Database::insert(
            'INSERT INTO ' . static::$table . " ($cols) VALUES ($places)",
            array_values($data)
        );

        $id = Database::lastInsertId();
        return static::find((int) $id) ?? $data;
    }

    public static function update(int $id, array $data): array
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));

        Database::execute(
            'UPDATE ' . static::$table . " SET $sets WHERE " . static::$primaryKey . ' = ?',
            [...array_values($data), $id]
        );

        return static::find($id) ?? $data;
    }

    public static function delete(int $id): bool
    {
        $affected = Database::execute(
            'DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?',
            [$id]
        );
        return $affected > 0;
    }

    public static function count(string $where = '', array $params = []): int
    {
        $whereStr = $where ? "WHERE $where" : '';
        return (int) Database::queryOne(
            'SELECT COUNT(*) as cnt FROM ' . static::$table . " $whereStr",
            $params
        )['cnt'];
    }
}

function class_basename(string $class): string
{
    return basename(str_replace('\\', '/', $class));
}
