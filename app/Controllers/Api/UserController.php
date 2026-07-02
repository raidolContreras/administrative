<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\User;
use Core\Auth;
use Core\Exceptions\HttpException;
use Core\Request;

final class UserController extends BaseApiController
{
    protected string $model = User::class;

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => $id === null ? 'required|min:8|max:72' : 'nullable|min:8|max:72',
            'role' => 'required|in:admin,employee',
            'is_active' => 'nullable|bool',
        ];
    }

    protected function filters(Request $request): array
    {
        return [
            'role' => $request->query('role'),
            'is_active' => $request->query('is_active'),
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        $data['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        $data['is_active'] ??= 1;
        return $data;
    }

    protected function beforeUpdate(array $data, Request $request, array $current): array
    {
        if (!empty($data['password'])) {
            $data['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }
        unset($data['password']);

        $isSelf = (int) $current['id'] === (int) Auth::id();
        $demotes = ($data['role'] ?? $current['role']) !== 'admin';
        $deactivates = isset($data['is_active']) && (int) $data['is_active'] === 0;

        if ($isSelf && ($demotes || $deactivates)) {
            throw HttpException::conflict('No puedes quitarte permisos ni desactivar tu propio usuario.');
        }
        // Nunca dejar la instalación sin administradores activos
        if ($current['role'] === 'admin' && ($demotes || $deactivates)
            && User::activeAdminsExcept((int) $current['id']) === 0) {
            throw HttpException::conflict('Debe existir al menos un administrador activo.');
        }
        return $data;
    }

    protected function beforeDestroy(array $current, Request $request): void
    {
        if ((int) $current['id'] === (int) Auth::id()) {
            throw HttpException::conflict('No puedes eliminar tu propio usuario.');
        }
        if ($current['role'] === 'admin' && User::activeAdminsExcept((int) $current['id']) === 0) {
            throw HttpException::conflict('Debe existir al menos un administrador activo.');
        }
    }
}
