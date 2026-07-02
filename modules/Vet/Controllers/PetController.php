<?php

declare(strict_types=1);

namespace Modules\Vet\Controllers;

use App\Controllers\Api\BaseApiController;
use Core\Request;
use Modules\Vet\Models\Pet;

final class PetController extends BaseApiController
{
    protected string $model = Pet::class;

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'owner_id' => 'required|int|exists:vet_owners,id',
            'name' => 'required|string|max:80',
            'species' => 'required|in:' . implode(',', Pet::SPECIES),
            'breed' => 'nullable|string|max:80',
            'sex' => 'nullable|in:M,H',
            'birth_date' => 'nullable|date',
            'weight_kg' => 'nullable|numeric|min:0|max:999',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected function filters(Request $request): array
    {
        return [
            'owner_id' => $request->query('owner_id'),
            'species' => $request->query('species'),
        ];
    }
}
