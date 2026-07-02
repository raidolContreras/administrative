<?php

declare(strict_types=1);

namespace Modules\Vet\Models;

use Core\Model;

final class Vaccine extends Model
{
    protected static string $table = 'vet_vacunas';
    protected static array $fillable = ['pet_id', 'name', 'applied_at', 'dose', 'next_dose_at', 'notes'];
    protected static array $sortable = ['id', 'created_at', 'name', 'applied_at', 'dose', 'next_dose_at'];
    protected static array $searchable = ['name', 'dose'];
    protected static bool $softDeletes = true;
}
