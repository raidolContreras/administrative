<?php

declare(strict_types=1);

namespace Core;

use PDO;
use PDOStatement;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = config('db');
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $c['username'], $c['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false, // prepared statements reales, siempre
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            // Alinear la zona horaria de MySQL con la de PHP (por offset: los shared hosting no tienen tz tables)
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$pdo;
    }

    /** Ejecuta SQL con prepared statement; params posicionales (lista) o nombrados (assoc) */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $name = is_int($key) ? $key + 1 : (str_starts_with((string) $key, ':') ? $key : ':' . $key);
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_INT,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($name, is_bool($value) ? (int) $value : $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public static function select(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function selectOne(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function scalar(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /** INSERT construido con identificadores saneados; devuelve el id insertado */
    public static function insert(string $table, array $data): int
    {
        $columns = array_map(self::ident(...), array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = 'INSERT INTO ' . self::ident($table) . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')';
        self::run($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = self::ident($column) . ' = ?';
        }
        $sql = 'UPDATE ' . self::ident($table) . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
        return self::run($sql, array_merge(array_values($data), array_values($whereParams)))->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::run('DELETE FROM ' . self::ident($table) . ' WHERE ' . $where, $params)->rowCount();
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Sanea un identificador SQL (tabla/columna) — solo [A-Za-z0-9_] entre backticks */
    public static function ident(string $name): string
    {
        return '`' . preg_replace('/[^A-Za-z0-9_]/', '', $name) . '`';
    }

    /** Cierra la conexión actual (el instalador reconecta tras escribir el .env) */
    public static function purge(): void
    {
        self::$pdo = null;
    }
}
