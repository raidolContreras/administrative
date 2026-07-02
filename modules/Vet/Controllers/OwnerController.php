<?php

declare(strict_types=1);

namespace Modules\Vet\Controllers;

use App\Controllers\Api\BaseApiController;
use Core\Request;
use Modules\Vet\Models\Owner;

final class OwnerController extends BaseApiController
{
    protected string $model = Owner::class;

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:190',
            'address' => 'nullable|string|max:190',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
