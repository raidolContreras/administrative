<?php

declare(strict_types=1);

namespace Modules\Vet\Support;

use Modules\Vet\Models\Appointment;
use Modules\Vet\Models\Owner;
use Modules\Vet\Models\Pet;

final class DashboardWidgets
{
    /** Stats que el módulo aporta al dashboard común (declarado en module.php) */
    public static function stats(): array
    {
        return [
            ['label' => 'Mascotas registradas', 'value' => Pet::count(), 'icon' => 'heart'],
            ['label' => 'Citas pendientes hoy', 'value' => Appointment::todayPending(), 'icon' => 'calendar'],
            ['label' => 'Dueños', 'value' => Owner::count(), 'icon' => 'user-group'],
        ];
    }
}
