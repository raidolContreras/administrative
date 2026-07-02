<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Core\Request;
use Core\Response;
use Core\Validator;

/**
 * Motor CRUD genérico de la API. Los controladores concretos declaran el modelo
 * y sobreescriben SOLO los hooks que necesiten (herencia con hooks, no configuración):
 *   rules(), filters(), transform(), beforeStore(), afterStore(),
 *   beforeUpdate(), afterUpdate(), beforeDestroy().
 * Esto es lo que compra el ~80% de reutilización entre verticales.
 */
abstract class BaseApiController
{
    /** @var class-string<\Core\Model> */
    protected string $model;

    public function index(Request $request): Response
    {
        $result = ($this->model)::paginate([
            'page' => (int) $request->query('page', 1),
            'per_page' => (int) $request->query('per_page', 15),
            'search' => (string) $request->query('q', ''),
            'sort' => $request->query('sort'),
            'dir' => (string) $request->query('dir', 'desc'),
            'filters' => $this->filters($request),
        ]);
        $result['data'] = array_map(fn (array $row): array => $this->transform($row), $result['data']);
        return Response::json($result['data'], 200, $result['meta']);
    }

    public function show(Request $request): Response
    {
        $row = ($this->model)::findOrFail($this->id($request));
        return Response::json($this->transform($row));
    }

    public function store(Request $request): Response
    {
        $data = Validator::validate((array) $request->input(), $this->rules($request, null));
        $data = $this->beforeStore($data, $request);
        $row = ($this->model)::create($data);
        $row = $this->afterStore($row, $request);
        return Response::json($this->transform($row), 201);
    }

    public function update(Request $request): Response
    {
        $id = $this->id($request);
        $current = ($this->model)::findOrFail($id);
        $data = Validator::validate((array) $request->input(), $this->rules($request, $id), ['id' => $id]);
        $data = $this->beforeUpdate($data, $request, $current);
        $row = ($this->model)::update($id, $data);
        $row = $this->afterUpdate($row, $request);
        return Response::json($this->transform($row));
    }

    public function destroy(Request $request): Response
    {
        $id = $this->id($request);
        $current = ($this->model)::findOrFail($id);
        $this->beforeDestroy($current, $request);
        ($this->model)::delete($id);
        return Response::noContent();
    }

    // ---- Hooks (puntos de extensión) ----

    /** Reglas de validación; $id !== null en update (contexto para unique) */
    protected function rules(Request $request, ?int $id): array
    {
        return [];
    }

    /** Filtros exactos permitidos en index (se validan contra la whitelist del modelo) */
    protected function filters(Request $request): array
    {
        return [];
    }

    /** Transformación final de cada fila antes de responder */
    protected function transform(array $row): array
    {
        return $row;
    }

    protected function beforeStore(array $data, Request $request): array
    {
        return $data;
    }

    protected function afterStore(array $row, Request $request): array
    {
        return $row;
    }

    protected function beforeUpdate(array $data, Request $request, array $current): array
    {
        return $data;
    }

    protected function afterUpdate(array $row, Request $request): array
    {
        return $row;
    }

    /** Lanzar HttpException para abortar la eliminación */
    protected function beforeDestroy(array $current, Request $request): void
    {
    }

    protected function id(Request $request): int
    {
        return (int) $request->param('id');
    }
}
