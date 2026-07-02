<?php

declare(strict_types=1);

namespace Core\Console;

/**
 * Generador de CRUDs para módulos verticales (bin/console make:crud).
 * A partir de "campo:tipo[:param][?]" produce: migración SQL, modelo, controlador,
 * shell de página y rutas — siguiendo exactamente los patrones del módulo base.
 *
 * Tipos: string[:len] | text | int | decimal | bool | date | datetime | email | ref:tabla
 * Sufijo '?' = opcional (nullable).
 */
final class CrudGenerator
{
    public function generate(string $module, string $entity, string $route, string $fieldsSpec, array $opts = []): array
    {
        $moduleKey = strtolower(trim($module));
        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $moduleKey)));
        $entity = ucfirst(trim($entity));
        $route = strtolower(trim($route, " /"));
        $table = strtolower((string) ($opts['table'] ?? "{$moduleKey}_{$route}"));
        $label = (string) ($opts['label'] ?? ucfirst($route));
        $fields = $this->parseFields($fieldsSpec);
        if ($fields === []) {
            throw new \InvalidArgumentException('Define al menos un campo en --fields.');
        }

        $modulePath = base_path("modules/{$studly}");
        $files = [];
        $notes = [];

        $files = array_merge($files, $this->ensureModuleSkeleton($modulePath, $studly, $moduleKey));

        // 1) Migración
        $migration = $this->nextMigrationPath($modulePath, $table);
        $this->put($migration, $this->migrationSql($table, $fields));
        $files[] = $migration;

        // 2) Modelo
        $modelFile = "{$modulePath}/Models/{$entity}.php";
        $this->put($modelFile, $this->modelPhp($studly, $entity, $table, $fields));
        $files[] = $modelFile;

        // 3) Controlador
        $controllerFile = "{$modulePath}/Controllers/{$entity}Controller.php";
        $this->put($controllerFile, $this->controllerPhp($studly, $entity, $fields));
        $files[] = $controllerFile;

        // 4) Shell de página
        $viewFile = "{$modulePath}/Views/{$route}.php";
        $this->put($viewFile, $this->viewPhp($entity, $route, $label, $fields));
        $files[] = $viewFile;

        // 5) Rutas (append)
        $this->appendOnce(
            "{$modulePath}/routes/api.php",
            "\$router->resource('/api/{$route}', \\Modules\\{$studly}\\Controllers\\{$entity}Controller::class);"
        );
        $this->appendOnce(
            "{$modulePath}/routes/web.php",
            "\$router->get('/{$route}', [\\App\\Controllers\\PageController::class, 'show'], ['auth'], ['view' => __DIR__ . '/../Views/{$route}.php']);"
        );
        $files[] = "{$modulePath}/routes/api.php (+resource)";
        $files[] = "{$modulePath}/routes/web.php (+página)";

        $notes[] = "\nAgrega el item de menú en modules/{$studly}/module.php:";
        $notes[] = "  ['label' => '{$label}', 'icon' => 'box', 'href' => '/{$route}', 'role' => null, 'order' => 50],";
        $notes[] = "\nLuego ejecuta: php bin/console migrate";
        foreach ($fields as $field) {
            if ($field['type'] === 'ref') {
                $notes[] = "Nota: el campo '{$field['name']}' es referencia a {$field['param']} — cambia el input numérico por un <select> con catálogo (ver modules/Vet/Views/pets.php).";
            }
        }

        return ['files' => $files, 'notes' => $notes];
    }

    /** @return array<int, array{name:string,type:string,param:?string,nullable:bool}> */
    private function parseFields(string $spec): array
    {
        $fields = [];
        foreach (array_filter(array_map('trim', explode(',', $spec))) as $item) {
            $nullable = str_ends_with($item, '?');
            $item = rtrim($item, '?');
            $parts = explode(':', $item);
            $name = strtolower(preg_replace('/[^A-Za-z0-9_]/', '', $parts[0]));
            $type = strtolower($parts[1] ?? 'string');
            if ($name === '' || in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }
            $fields[] = ['name' => $name, 'type' => $type, 'param' => $parts[2] ?? null, 'nullable' => $nullable];
        }
        return $fields;
    }

    private function migrationSql(string $table, array $fields): string
    {
        $columns = [];
        $keys = [];
        foreach ($fields as $f) {
            $null = $f['nullable'] ? 'NULL' : 'NOT NULL';
            $columns[] = match ($f['type']) {
                'text' => "    {$f['name']} TEXT {$null},",
                'int' => "    {$f['name']} INT {$null},",
                'decimal' => "    {$f['name']} DECIMAL(10,2) {$null},",
                'bool' => "    {$f['name']} TINYINT(1) NOT NULL DEFAULT 0,",
                'date' => "    {$f['name']} DATE {$null},",
                'datetime' => "    {$f['name']} DATETIME {$null},",
                'email' => "    {$f['name']} VARCHAR(190) {$null},",
                'ref' => "    {$f['name']} BIGINT UNSIGNED {$null},",
                default => "    {$f['name']} VARCHAR(" . max(1, (int) ($f['param'] ?? 190)) . ") {$null},",
            };
            if ($f['type'] === 'ref') {
                $keys[] = "    KEY idx_{$table}_{$f['name']} ({$f['name']}),";
            }
        }
        $columnsSql = implode("\n", $columns);
        $keysSql = $keys === [] ? '' : implode("\n", $keys) . "\n";

        return <<<SQL
        -- @UP
        CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        {$columnsSql}
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
        {$keysSql}    KEY idx_{$table}_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- @DOWN
        DROP TABLE IF EXISTS {$table};

        SQL;
    }

    private function modelPhp(string $studly, string $entity, string $table, array $fields): string
    {
        $names = array_column($fields, 'name');
        $fillable = "'" . implode("', '", $names) . "'";
        $sortable = ['id', 'created_at'];
        $searchable = [];
        foreach ($fields as $f) {
            if (in_array($f['type'], ['string', 'email', 'int', 'decimal', 'date', 'datetime', 'bool'], true)) {
                $sortable[] = $f['name'];
            }
            if (in_array($f['type'], ['string', 'email'], true)) {
                $searchable[] = $f['name'];
            }
        }
        $sortableStr = "'" . implode("', '", array_unique($sortable)) . "'";
        $searchableStr = $searchable === [] ? '' : "'" . implode("', '", $searchable) . "'";

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Modules\\{$studly}\\Models;

        use Core\\Model;

        final class {$entity} extends Model
        {
            protected static string \$table = '{$table}';
            protected static array \$fillable = [{$fillable}];
            protected static array \$sortable = [{$sortableStr}];
            protected static array \$searchable = [{$searchableStr}];
            protected static bool \$softDeletes = true;
        }

        PHP;
    }

    private function controllerPhp(string $studly, string $entity, array $fields): string
    {
        $rules = [];
        foreach ($fields as $f) {
            $parts = [$f['nullable'] ? 'nullable' : 'required'];
            $parts[] = match ($f['type']) {
                'text' => 'string|max:2000',
                'int' => 'int',
                'decimal' => 'numeric|min:0',
                'bool' => 'bool',
                'date' => 'date',
                'datetime' => 'datetime',
                'email' => 'email|max:190',
                'ref' => 'int|exists:' . ($f['param'] ?? 'CAMBIA_TABLA') . ',id',
                default => 'string|max:' . max(1, (int) ($f['param'] ?? 190)),
            };
            $rules[] = "            '{$f['name']}' => '" . implode('|', $parts) . "',";
        }
        $rulesStr = implode("\n", $rules);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Modules\\{$studly}\\Controllers;

        use App\\Controllers\\Api\\BaseApiController;
        use Core\\Request;
        use Modules\\{$studly}\\Models\\{$entity};

        final class {$entity}Controller extends BaseApiController
        {
            protected string \$model = {$entity}::class;

            protected function rules(Request \$request, ?int \$id): array
            {
                return [
        {$rulesStr}
                ];
            }
        }

        PHP;
    }

    private function viewPhp(string $entity, string $route, string $label, array $fields): string
    {
        $defaults = [];
        $pick = [];
        $ths = [];
        $tds = [];
        $inputs = [];

        foreach ($fields as $f) {
            $pick[] = "'{$f['name']}'";
            $defaults[] = "{$f['name']}: " . ($f['type'] === 'bool' ? 'false' : "''");
        }

        foreach (array_slice($fields, 0, 4) as $f) {
            $ths[] = "                        <th class=\"th-sort\" @click=\"sortBy('{$f['name']}')\">" . ucfirst(str_replace('_', ' ', $f['name'])) . " <span x-text=\"sortIcon('{$f['name']}')\"></span></th>";
            $tds[] = match ($f['type']) {
                'bool' => "                            <td><span class=\"badge\" :class=\"row.{$f['name']} ? 'badge-green' : 'badge-slate'\" x-text=\"row.{$f['name']} ? 'Sí' : 'No'\"></span></td>",
                'date' => "                            <td class=\"whitespace-nowrap\" x-text=\"\$store.app.fmtDate(row.{$f['name']})\"></td>",
                'datetime' => "                            <td class=\"whitespace-nowrap\" x-text=\"\$store.app.fmtDateTime(row.{$f['name']})\"></td>",
                'decimal' => "                            <td x-text=\"row.{$f['name']} ?? '—'\"></td>",
                default => "                            <td x-text=\"row.{$f['name']} ?? '—'\"></td>",
            };
        }

        foreach ($fields as $f) {
            $fieldLabel = ucfirst(str_replace('_', ' ', $f['name'])) . ($f['nullable'] ? ' (opcional)' : '');
            $inputs[] = match ($f['type']) {
                'text' => $this->inputBlock($fieldLabel, "<textarea class=\"input\" rows=\"2\" x-model=\"form.{$f['name']}\"></textarea>", $f['name']),
                'bool' => "                <label class=\"flex items-center gap-2 text-sm font-medium text-slate-700\">\n                    <input type=\"checkbox\" class=\"h-4 w-4 rounded border-slate-300\" x-model=\"form.{$f['name']}\">\n                    {$fieldLabel}\n                </label>",
                'int', 'ref' => $this->inputBlock($fieldLabel, "<input type=\"number\" step=\"1\" class=\"input\" :class=\"err('{$f['name']}') && 'input-error'\" x-model=\"form.{$f['name']}\">", $f['name']),
                'decimal' => $this->inputBlock($fieldLabel, "<input type=\"number\" step=\"0.01\" class=\"input\" :class=\"err('{$f['name']}') && 'input-error'\" x-model=\"form.{$f['name']}\">", $f['name']),
                'date' => $this->inputBlock($fieldLabel, "<input type=\"date\" class=\"input\" :class=\"err('{$f['name']}') && 'input-error'\" x-model=\"form.{$f['name']}\">", $f['name']),
                'datetime' => $this->inputBlock($fieldLabel, "<input type=\"datetime-local\" class=\"input\" :class=\"err('{$f['name']}') && 'input-error'\" x-model=\"form.{$f['name']}\">", $f['name']),
                'email' => $this->inputBlock($fieldLabel, "<input type=\"email\" class=\"input\" :class=\"err('{$f['name']}') && 'input-error'\" x-model=\"form.{$f['name']}\">", $f['name']),
                default => $this->inputBlock($fieldLabel, "<input type=\"text\" class=\"input\" :class=\"err('{$f['name']}') && 'input-error'\" x-model=\"form.{$f['name']}\">", $f['name']),
            };
        }

        $datetimeFix = '';
        foreach ($fields as $f) {
            if ($f['type'] === 'datetime') {
                $datetimeFix .= "; form.{$f['name']} = (row.{$f['name']} || '').replace(' ', 'T').slice(0, 16)";
            }
        }

        $defaultsStr = implode(', ', $defaults);
        $pickStr = implode(', ', $pick);
        $thsStr = implode("\n", $ths);
        $tdsStr = implode("\n", $tds);
        $inputsStr = implode("\n", $inputs);
        $colspan = count(array_slice($fields, 0, 4)) + 1;

        return <<<HTML
        <?php /* {$label}: generado por make:crud — ajusta columnas y campos a tu gusto */ ?>
        <div x-data="formModal({
                url: '/api/{$route}',
                defaults: { {$defaultsStr} },
                pick: [{$pickStr}]
             })">

            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">{$label}</h1>
                </div>
                <button class="btn btn-primary" @click="openCreate()">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Nuevo registro
                </button>
            </div>

            <div class="card" x-data="dataTable({ url: '/api/{$route}', sort: 'id', dir: 'desc' })">
                <div class="border-b border-slate-100 p-4">
                    <input type="search" class="input max-w-xs" placeholder="Buscar…" x-model="q">
                </div>
                <div class="overflow-x-auto">
                    <table class="tbl">
                        <thead>
                            <tr>
        {$thsStr}
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in rows" :key="row.id">
                                <tr>
        {$tdsStr}
                                    <td>
                                        <div class="flex justify-end gap-1">
                                            <button class="btn-icon" title="Editar" @click="openEdit(row){$datetimeFix}">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                                </svg>
                                            </button>
                                            <button class="btn-icon hover:!text-red-600" title="Eliminar" @click="destroy(row)">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L5.772 5.79m13.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!loading && rows.length === 0">
                                <td colspan="{$colspan}" class="py-10 text-center text-sm text-slate-400">Sin resultados.</td>
                            </tr>
                            <tr x-show="loading">
                                <td colspan="{$colspan}" class="py-10 text-center text-sm text-slate-400">Cargando…</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
                    <span><span x-text="meta.total"></span> registro(s) · página <span x-text="meta.page"></span> de <span x-text="meta.total_pages"></span></span>
                    <div class="flex gap-1">
                        <button class="btn btn-secondary !px-3 !py-1.5" :disabled="meta.page <= 1" @click="prev()">Anterior</button>
                        <button class="btn btn-secondary !px-3 !py-1.5" :disabled="meta.page >= meta.total_pages" @click="next()">Siguiente</button>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                <div class="modal-backdrop" @click="close()"></div>
                <div class="modal-panel p-6" x-show="open" x-transition.scale.origin.center @keydown.escape.window="close()">
                    <h3 class="text-base font-semibold text-slate-900" x-text="mode === 'create' ? 'Nuevo registro' : 'Editar registro'"></h3>
                    <form class="mt-4 space-y-4" @submit.prevent="submit()">
        {$inputsStr}
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="btn btn-secondary" @click="close()">Cancelar</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span x-show="!saving">Guardar</span>
                                <span x-show="saving" x-cloak>Guardando…</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        HTML;
    }

    private function inputBlock(string $label, string $input, string $field): string
    {
        return <<<HTML
                        <div>
                            <label class="label">{$label}</label>
                            {$input}
                            <p class="field-error" x-show="err('{$field}')" x-text="err('{$field}')"></p>
                        </div>
        HTML;
    }

    private function ensureModuleSkeleton(string $modulePath, string $studly, string $moduleKey): array
    {
        $created = [];
        foreach (['Models', 'Controllers', 'Views', 'routes', 'migrations', 'seeds', 'Support'] as $dir) {
            if (!is_dir("{$modulePath}/{$dir}")) {
                mkdir("{$modulePath}/{$dir}", 0775, true);
            }
        }
        if (!is_file("{$modulePath}/module.php")) {
            $name = ucfirst($moduleKey);
            $this->put("{$modulePath}/module.php", <<<PHP
            <?php

            declare(strict_types=1);

            return [
                'name' => '{$name}',
                'version' => '1.0.0',
                'menu' => [
                    // ['label' => '...', 'icon' => 'box', 'href' => '/...', 'role' => null, 'order' => 50],
                ],
                'widgets' => [],
            ];

            PHP);
            $created[] = "{$modulePath}/module.php";
        }
        foreach (['api', 'web'] as $type) {
            $file = "{$modulePath}/routes/{$type}.php";
            if (!is_file($file)) {
                $this->put($file, "<?php\n\ndeclare(strict_types=1);\n\n/** @var Core\\Router \$router */\n");
                $created[] = $file;
            }
        }
        return $created;
    }

    private function nextMigrationPath(string $modulePath, string $table): string
    {
        $max = 0;
        foreach (glob("{$modulePath}/migrations/*.sql") ?: [] as $file) {
            if (preg_match('/^(\d+)_/', basename($file), $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        return sprintf('%s/migrations/%04d_%s.sql', $modulePath, $max + 1, $table);
    }

    private function appendOnce(string $file, string $line): void
    {
        $current = is_file($file) ? (string) file_get_contents($file) : '';
        if (!str_contains($current, $line)) {
            file_put_contents($file, rtrim($current) . "\n" . $line . "\n", LOCK_EX);
        }
    }

    private function put(string $file, string $content): void
    {
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }
        if (is_file($file)) {
            throw new \RuntimeException('Ya existe: ' . $file . ' (elimínalo si quieres regenerarlo)');
        }
        file_put_contents($file, $content, LOCK_EX);
    }
}
