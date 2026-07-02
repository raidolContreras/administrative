<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class Setting extends Model
{
    protected static string $table = 'settings';
    protected static array $fillable = ['key', 'value', 'type', 'is_public'];
    protected static array $sortable = ['id', 'key'];

    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::map();
        return array_key_exists($key, $all) ? $all[$key]['casted'] : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $existing = static::firstWhere('key', $key);
        if ($existing !== null) {
            static::update((int) $existing['id'], ['value' => $value === null ? null : (string) $value]);
        } else {
            static::create(['key' => $key, 'value' => (string) $value, 'type' => 'string', 'is_public' => 0]);
        }
        self::$cache = null;
    }

    /** Solo los settings públicos, con tipo aplicado — es lo que expone /api/bootstrap */
    public static function publicAll(): array
    {
        $public = [];
        foreach (self::map() as $key => $item) {
            if ($item['is_public']) {
                $public[$key] = $item['casted'];
            }
        }
        return $public;
    }

    /** Alta idempotente de defaults (seeds e instalador) */
    public static function seedDefaults(array $defaults): int
    {
        $created = 0;
        foreach ($defaults as $key => $def) {
            $exists = (int) Database::scalar('SELECT COUNT(*) FROM settings WHERE `key` = ?', [$key]);
            if ($exists === 0) {
                static::create([
                    'key' => $key,
                    'value' => (string) $def['value'],
                    'type' => $def['type'] ?? 'string',
                    'is_public' => (int) ($def['is_public'] ?? 0),
                ]);
                $created++;
            }
        }
        self::$cache = null;
        return $created;
    }

    private static function map(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $map = [];
        foreach (Database::select('SELECT `key`, value, type, is_public FROM settings') as $row) {
            $map[$row['key']] = [
                'is_public' => (bool) $row['is_public'],
                'casted' => self::cast($row['value'], $row['type']),
            ];
        }
        return self::$cache = $map;
    }

    private static function cast(?string $value, string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => $value,
        };
    }
}
