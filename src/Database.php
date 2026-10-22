<?php

declare(strict_types=1);

namespace Lvt;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $host = (string) env('DB_HOST', '127.0.0.1');
        $port = (string) env('DB_PORT', '3306');
        $name = (string) env('DB_NAME', 'loaded_valet');
        $user = (string) env('DB_USER', 'loaded');
        $pass = (string) env('DB_PASS', '');
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed. Check DB_HOST, DB_NAME, DB_USER, and DB_PASS. ' . $e->getMessage(), 0, $e);
        }
        return self::$pdo;
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }

    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(array_values($params));
        return $stmt->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(array_values($params));
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(array_values($params));
        return $stmt->fetchColumn();
    }

    public static function exec(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(array_values($params));
        return $stmt->rowCount();
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::exec($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    public static function insertRow(string $table, array $data): int
    {
        self::guardIdent($table);
        $cols = array_keys($data);
        foreach ($cols as $col) {
            self::guardIdent($col);
        }
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')';
        return self::insert($sql, array_values($data));
    }

    public static function updateRow(string $table, array $data, string $where, array $params): void
    {
        self::guardIdent($table);
        $sets = [];
        $values = [];
        foreach ($data as $col => $value) {
            self::guardIdent((string) $col);
            $sets[] = $col . ' = ?';
            $values[] = $value;
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
        self::exec($sql, array_merge($values, $params));
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function guardIdent(string $ident): void
    {
        if (preg_match('/^[a-z_]+$/', $ident) !== 1) {
            throw new \InvalidArgumentException('Unsafe identifier.');
        }
    }
}
