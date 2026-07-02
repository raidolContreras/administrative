<?php

declare(strict_types=1);

namespace Modules\Vet\Controllers;

use App\Controllers\Api\BaseApiController;
use Core\Request;
use Modules\Vet\Models\Vaccine;

final class VaccineController extends BaseApiController
{
    protected string $model = Vaccine::class;

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'pet_id' => 'required|int|exists:vet_pets,id',
            'name' => 'required|string|max:120',
            'applied_at' => 'required|date',
            'dose' => 'nullable|string|max:60',
            'next_dose_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
