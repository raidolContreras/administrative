<?php

declare(strict_types=1);

namespace Core;

use Core\Exceptions\HttpException;
use Core\Exceptions\ValidationException;

/**
 * Modelo base genérico: CRUD con prepared statements, paginación server-side,
 * whitelist de orden/filtros (el ORDER BY no es parametrizable), soft deletes,
 * timestamps y hook de auditoría. La tabla SIEMPRE se alias como `t`.
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    /** Campos asignables en create/update (whitelist anti mass-assignment) */
    protected static array $fillable = [];
    /** Columnas permitidas en ORDER BY: 'col' o 'alias' => 'expr.sql' */
    protected static array $sortable = [];
    /** Columnas para búsqueda con LIKE */
    protected static array $searchable = [];
    /** Columnas permitidas como filtro exacto: 'col' o 'alias' => 'expr.sql' (default: fillable) */
    protected static array $filterable = [];
    /** Campos que nunca salen en respuestas ni en auditoría (ej. password_hash) */
    protected static array $hidden = [];
    protected static bool $timestamps = true;
    protected static bool $softDeletes = false;
    protected static bool $audited = true;
    /** SELECT y JOINs para listados (index/paginate) */
    protected static string $select = 't.*';
    protected static string $joins = '';
    /** Columna/expresión de orden por defecto, SIN dirección (la dirección la pone paginate) */
    protected static string $defaultSort = '';

    /** @var callable|null fn(string $action, string $entity, int|string $id, ?array $old, ?array $new) */
    private static $observer = null;

    /** El composition root registra aquí la auditoría (core no conoce el dominio) */
    public static function observeUsing(?callable $fn): void
    {
        self::$observer = $fn;
    }

    public static function table(): string
    {
        return static::$table;
    }

    public static function find(int|string $id): ?array
    {
        $row = Database::selectOne(
            'SELECT t.* FROM ' . Database::ident(static::$table) . ' AS t WHERE t.' . Database::ident(static::$primaryKey) . ' = ?' . static::softClause(),
            [$id]
        );
        return $row === null ? null : static::scrub($row);
    }

    public static function findOrFail(int|string $id): array
    {
        return static::find($id) ?? throw HttpException::notFound();
    }

    public static function firstWhere(string $column, mixed $value): ?array
    {
        $row = Database::selectOne(
            'SELECT t.* FROM ' . Database::ident(static::$table) . ' AS t WHERE t.' . Database::ident($column) . ' = ?' . static::softClause() . ' LIMIT 1',
            [$value]
        );
        return $row === null ? null : static::scrub($row);
    }

    public static function all(?string $orderBy = null): array
    {
        $order = static::resolveSort($orderBy)[0] ?? 't.' . Database::ident(static::$primaryKey);
        $rows = Database::select(
            'SELECT ' . static::$select . ' FROM ' . Database::ident(static::$table) . ' AS t '
            . static::$joins . ' WHERE 1=1' . static::softClause() . ' ORDER BY ' . $order
        );
        return array_map(static::scrub(...), $rows);
    }

    public static function count(): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM ' . Database::ident(static::$table) . ' AS t WHERE 1=1' . static::softClause()
        );
    }

    public static function create(array $data): array
    {
        $data = static::onlyFillable($data);
        if (static::$timestamps) {
            $data['created_at'] = now();
            $data['updated_at'] = now();
        }
        $id = Database::insert(static::$table, $data);
        static::notify('create', $id, null, static::scrub($data));
        return static::find($id) ?? [static::$primaryKey => $id] + $data;
    }

    public static function update(int|string $id, array $data): array
    {
        $old = Database::selectOne(
            'SELECT t.* FROM ' . Database::ident(static::$table) . ' AS t WHERE t.' . Database::ident(static::$primaryKey) . ' = ?' . static::softClause(),
            [$id]
        ) ?? throw HttpException::notFound();

        $data = static::onlyFillable($data);
        $changedOld = [];
        $changedNew = [];
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $old) || (string) ($old[$key] ?? '') !== (string) ($value ?? '')) {
                $changedOld[$key] = $old[$key] ?? null;
                $changedNew[$key] = $value;
            }
        }
        if ($changedNew !== []) {
            $toSave = $changedNew;
            if (static::$timestamps) {
                $toSave['updated_at'] = now();
            }
            Database::update(static::$table, $toSave, Database::ident(static::$primaryKey) . ' = ?', [$id]);
            static::notify('update', $id, static::scrub($changedOld), static::scrub($changedNew));
        }
        return static::findOrFail($id);
    }

    public static function delete(int|string $id): void
    {
        $old = static::find($id) ?? throw HttpException::notFound();
        if (static::$softDeletes) {
            Database::update(static::$table, ['deleted_at' => now()], Database::ident(static::$primaryKey) . ' = ?', [$id]);
        } else {
            Database::delete(static::$table, Database::ident(static::$primaryKey) . ' = ?', [$id]);
        }
        static::notify('delete', $id, $old, null);
    }

    /**
     * Paginación server-side con búsqueda, orden y filtros exactos (todo con whitelist).
     * $opts: page, per_page, search, sort, dir, filters (assoc)
     * @return array{data: array[], meta: array}
     */
    public static function paginate(array $opts = []): array
    {
        $page = max(1, (int) ($opts['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($opts['per_page'] ?? 15)));
        $search = trim((string) ($opts['search'] ?? ''));
        $dir = strtolower((string) ($opts['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $where = ['1=1' . static::softClause()];
        $params = [];

        if ($search !== '' && static::$searchable !== []) {
            $likes = [];
            $term = '%' . addcslashes($search, '%_\\') . '%';
            foreach (static::$searchable as $i => $column) {
                $likes[] = static::qualify($column) . " LIKE :search{$i}";
                $params["search{$i}"] = $term;
            }
            $where[] = '(' . implode(' OR ', $likes) . ')';
        }

        $filterMap = static::filterMap();
        foreach ((array) ($opts['filters'] ?? []) as $key => $value) {
            if ($value === null || $value === '' || !isset($filterMap[$key])) {
                continue;
            }
            $param = 'f_' . preg_replace('/[^A-Za-z0-9_]/', '', (string) $key);
            $where[] = $filterMap[$key] . " = :{$param}";
            $params[$param] = $value;
        }

        [$sortExpr] = static::resolveSort(isset($opts['sort']) && $opts['sort'] !== null && $opts['sort'] !== '' ? (string) $opts['sort'] : null);

        $from = 'FROM ' . Database::ident(static::$table) . ' AS t ' . static::$joins . ' WHERE ' . implode(' AND ', $where);
        $total = (int) Database::scalar("SELECT COUNT(*) {$from}", $params);

        $params['limit'] = $perPage;
        $params['offset'] = ($page - 1) * $perPage;
        $rows = Database::select(
            'SELECT ' . static::$select . " {$from} ORDER BY {$sortExpr} {$dir} LIMIT :limit OFFSET :offset",
            $params
        );

        return [
            'data' => array_map(static::scrub(...), $rows),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    /** Quita los campos ocultos (nunca viajan al navegador ni a la auditoría) */
    public static function scrub(array $row): array
    {
        foreach (static::$hidden as $key) {
            unset($row[$key]);
        }
        return $row;
    }

    protected static function onlyFillable(array $data): array
    {
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    protected static function softClause(): string
    {
        return static::$softDeletes ? ' AND t.deleted_at IS NULL' : '';
    }

    /** @return array{0:string} expresión ORDER BY validada contra whitelist */
    protected static function resolveSort(?string $requested): array
    {
        $map = [];
        foreach (static::$sortable as $key => $value) {
            if (is_int($key)) {
                $map[$value] = static::qualify($value);
            } else {
                $map[$key] = $value;
            }
        }
        $pk = static::$primaryKey;
        $map[$pk] = $map[$pk] ?? 't.' . Database::ident($pk);

        if ($requested === null) {
            $default = static::$defaultSort !== '' ? static::$defaultSort : 't.' . Database::ident($pk);
            return [$default];
        }
        if (!isset($map[$requested])) {
            throw new ValidationException(['sort' => ['La columna de ordenamiento no está permitida.']]);
        }
        return [$map[$requested]];
    }

    protected static function filterMap(): array
    {
        $source = static::$filterable !== [] ? static::$filterable : static::$fillable;
        $map = [];
        foreach ($source as $key => $value) {
            if (is_int($key)) {
                $map[$value] = static::qualify($value);
            } else {
                $map[$key] = $value;
            }
        }
        return $map;
    }

    protected static function qualify(string $column): string
    {
        return str_contains($column, '.') ? $column : 't.' . Database::ident($column);
    }

    protected static function notify(string $action, int|string $id, ?array $old, ?array $new): void
    {
        if (static::$audited && self::$observer !== null) {
            try {
                (self::$observer)($action, static::$table, $id, $old, $new);
            } catch (\Throwable $e) {
                ErrorHandler::log($e); // la auditoría nunca debe romper la operación
            }
        }
    }
}
