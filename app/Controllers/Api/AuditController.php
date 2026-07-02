<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\AuditLog;
use Core\Request;

final class AuditController extends BaseApiController
{
    protected string $model = AuditLog::class;

    protected function filters(Request $request): array
    {
        return [
            'action' => $request->query('action'),
            'entity_type' => $request->query('entity_type'),
            'user_id' => $request->query('user_id'),
        ];
    }
}
