<?php

declare(strict_types=1);

use App\Models\Setting;

/** Settings por defecto del módulo base (idempotente) */
return static function (): void {
    Setting::seedDefaults([
        'app_name' => ['value' => config('app.name'), 'type' => 'string', 'is_public' => 1],
        'logo_path' => ['value' => '', 'type' => 'string', 'is_public' => 1],
        'primary_color' => ['value' => '#6366f1', 'type' => 'string', 'is_public' => 1],
        'secondary_color' => ['value' => '#8b5cf6', 'type' => 'string', 'is_public' => 1],
        'currency' => ['value' => 'MXN', 'type' => 'string', 'is_public' => 1],
    ]);
};
