<?php

declare(strict_types=1);

namespace Modules\Vet\Models;

use Core\Model;

final class Pet extends Model
{
    protected static string $table = 'vet_pets';
    protected static array $fillable = ['owner_id', 'name', 'species', 'breed', 'sex', 'birth_date', 'weight_kg', 'notes'];
    protected static array $sortable = ['id', 'name', 'species', 'birth_date', 'created_at', 'owner_name' => 'o.name'];
    protected static array $searchable = ['t.name', 't.breed', 'o.name'];
    protected static array $filterable = ['owner_id', 'species'];
    protected static bool $softDeletes = true;
    protected static string $select = 't.*, o.name AS owner_name';
    protected static string $joins = 'LEFT JOIN vet_owners o ON o.id = t.owner_id';

    public const SPECIES = ['perro', 'gato', 'ave', 'reptil', 'roedor', 'otro'];
}
