<?php

declare(strict_types=1);

namespace Modules\Vet\Controllers;

use App\Controllers\Api\BaseApiController;
use Core\Request;
use Modules\Vet\Models\Appointment;

final class AppointmentController extends BaseApiController
{
    protected string $model = Appointment::class;

    protected function rules(Request $request, ?int $id): array
    {
        return [
            'pet_id' => 'required|int|exists:vet_pets,id',
            'scheduled_at' => 'required|datetime',
            'reason' => 'required|string|max:190',
            'status' => 'nullable|in:' . implode(',', Appointment::STATUSES),
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected function filters(Request $request): array
    {
        return [
            'status' => $request->query('status'),
            'pet_id' => $request->query('pet_id'),
            'day' => $request->query('day'),
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        $data['status'] ??= 'programada';
        return $data;
    }
}
