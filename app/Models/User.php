<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class User extends Model
{
    protected static string $table = 'users';
    protected static array $fillable = ['name', 'email', 'password_hash', 'role', 'is_active', 'last_login_at'];
    protected static array $hidden = ['password_hash'];
    protected static array $sortable = ['id', 'name', 'email', 'role', 'is_active', 'last_login_at', 'created_at'];
    protected static array $searchable = ['name', 'email'];
    protected static array $filterable = ['role', 'is_active'];
    protected static bool $softDeletes = true;

    public const ROLES = ['admin', 'employee'];

    /** Único acceso que incluye el hash — exclusivo del flujo de login */
    public static function findByEmailForAuth(string $email): ?array
    {
        return Database::selectOne(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1',
            [$email]
        );
    }

    /** Hash actual para verificar contraseña del propio usuario */
    public static function passwordHash(int $id): ?string
    {
        $hash = Database::scalar('SELECT password_hash FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
        return is_string($hash) ? $hash : null;
    }

    /** Marca último acceso sin pasar por auditoría (ruido) */
    public static function touchLastLogin(int $id): void
    {
        Database::update('users', ['last_login_at' => now()], 'id = ?', [$id]);
    }

    public static function activeAdminsExcept(int $exceptId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND deleted_at IS NULL AND id != ?",
            [$exceptId]
        );
    }
}
