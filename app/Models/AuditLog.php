<?php

declare(strict_types=1);

namespace App\Models;

use Core\Auth;
use Core\Model;
use Core\Request;

final class AuditLog extends Model
{
    protected static string $table = 'audit_log';
    protected static array $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'old_values', 'new_values', 'ip', 'user_agent', 'created_at'];
    protected static array $sortable = ['id', 'action', 'entity_type', 'created_at'];
    protected static array $searchable = ['entity_type', 'action'];
    protected static array $filterable = ['action', 'entity_type', 'user_id'];
    protected static bool $timestamps = false;
    protected static bool $audited = false; // evita recursión
    protected static string $select = 't.*, u.name AS user_name';
    protected static string $joins = 'LEFT JOIN users u ON u.id = t.user_id';

    /** Hook registrado en Core\Model::observeUsing() — lo alimenta todo el CRUD automáticamente */
    public static function record(string $action, string $entity, int|string $id, ?array $old, ?array $new): void
    {
        $isCli = PHP_SAPI === 'cli';
        static::create([
            'user_id' => $isCli ? null : Auth::id(),
            'action' => $action,
            'entity_type' => $entity,
            'entity_id' => (string) $id,
            'old_values' => $old === null || $old === [] ? null : json_encode($old, JSON_UNESCAPED_UNICODE),
            'new_values' => $new === null || $new === [] ? null : json_encode($new, JSON_UNESCAPED_UNICODE),
            'ip' => $isCli ? null : Request::current()->ip(),
            'user_agent' => $isCli ? 'cli' : Request::current()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
