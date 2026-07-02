<?php

declare(strict_types=1);

namespace Modules\Vet\Models;

use Core\Database;
use Core\Model;

final class Appointment extends Model
{
    protected static string $table = 'vet_appointments';
    protected static array $fillable = ['pet_id', 'scheduled_at', 'reason', 'status', 'notes'];
    protected static array $sortable = ['id', 'scheduled_at', 'status', 'created_at', 'pet_name' => 'p.name'];
    protected static array $searchable = ['p.name', 'o.name', 't.reason'];
    // 'day' filtra por fecha exacta gracias al mapa de expresiones de la whitelist
    protected static array $filterable = ['status', 'pet_id', 'day' => 'DATE(t.scheduled_at)'];
    protected static string $select = 't.*, p.name AS pet_name, o.name AS owner_name';
    protected static string $joins = 'LEFT JOIN vet_pets p ON p.id = t.pet_id LEFT JOIN vet_owners o ON o.id = p.owner_id';
    protected static string $defaultSort = 't.scheduled_at';

    public const STATUSES = ['programada', 'atendida', 'cancelada'];

    public static function todayPending(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM vet_appointments WHERE DATE(scheduled_at) = CURDATE() AND status = 'programada'"
        );
    }
}
