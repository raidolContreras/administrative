<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\User;
use App\Support\Modules;
use Core\Database;
use Core\ErrorHandler;
use Core\Request;
use Core\Response;

final class DashboardController
{
    public function show(Request $request): Response
    {
        $stats = [
            ['label' => 'Usuarios activos', 'value' => User::count(), 'icon' => 'users'],
        ];

        // Widgets aportados por los módulos activos (punto de extensión del manifest)
        foreach (Modules::active() as $manifest) {
            foreach ($manifest->widgets() as $widget) {
                try {
                    $stats = array_merge($stats, (array) $widget());
                } catch (\Throwable $e) {
                    ErrorHandler::log($e); // un widget roto no tira el dashboard
                }
            }
        }

        $recent = Database::select(
            'SELECT a.action, a.entity_type, a.entity_id, a.created_at, u.name AS user_name
             FROM audit_log a LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT 8'
        );

        return Response::json(['stats' => $stats, 'recent' => $recent]);
    }
}
