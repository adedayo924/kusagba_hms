<?php

class Database
{
    private static $pdo = null;

    public static function pdo()
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') ? DB_PORT : '3306')
                . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    public static function reset()
    {
        self::$pdo = null;
    }
}

function db()
{
    return Database::pdo();
}

function run($sql, $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function fetch($sql, $params = [])
{
    return run($sql, $params)->fetch() ?: null;
}

function fetch_all($sql, $params = [])
{
    return run($sql, $params)->fetchAll();
}

function fetch_val($sql, $params = [])
{
    return run($sql, $params)->fetchColumn();
}

function last_id()
{
    return (int)db()->lastInsertId();
}

function next_ticket($table, $column, $prefix, $pad = 4)
{
    $db = db();
    $key = 'seq_' . $table;
    // Seed the counter from MAX(id) once (auto-increment is monotonic and never reuses
    // numbers, so it stays safe even if rows were deleted; COUNT(*)+1 could collide).
    $qkey = $db->quote($key);
    $seed = (int)fetch_val("SELECT IFNULL(MAX(id),0) FROM `$table`");
    $db->exec("INSERT IGNORE INTO settings (skey, svalue) VALUES ($qkey, $seed)");
    // Atomic increment; LAST_INSERT_ID() captures the new value for each caller safely.
    $db->exec("UPDATE settings SET svalue = LAST_INSERT_ID(CAST(svalue AS UNSIGNED) + 1) WHERE skey = $qkey");
    $seq = (int)$db->lastInsertId();
    return $prefix . '-' . date('ymd') . '-' . str_pad($seq, $pad, '0', STR_PAD_LEFT);
}

function tx($callable)
{
    $db = db();
    $db->beginTransaction();
    try {
        $ret = $callable($db);
        $db->commit();
        return $ret;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}