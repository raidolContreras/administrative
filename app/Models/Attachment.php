<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class Attachment extends Model
{
    protected static string $table = 'attachments';
    protected static array $fillable = ['disk', 'path', 'original_name', 'mime', 'size', 'entity_type', 'entity_id', 'uploaded_by'];
    protected static array $sortable = ['id', 'original_name', 'size', 'created_at'];
    protected static array $searchable = ['original_name'];
    protected static array $filterable = ['entity_type', 'entity_id', 'uploaded_by'];

    public static function absolutePath(array $attachment): string
    {
        return storage_path('uploads/' . ltrim((string) $attachment['path'], '/\\'));
    }
}
