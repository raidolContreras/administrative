<?php

declare(strict_types=1);

namespace Modules\Vet\Models;

use Core\Model;

final class Owner extends Model
{
    protected static string $table = 'vet_owners';
    protected static array $fillable = ['name', 'phone', 'email', 'address', 'notes'];
    protected static array $sortable = ['id', 'name', 'phone', 'created_at'];
    protected static array $searchable = ['name', 'phone', 'email'];
    protected static bool $softDeletes = true;
}
