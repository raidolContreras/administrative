<?php /* Auditoría: tabla de solo lectura con detalle de cambios */ ?>
<div x-data="{
        detail: null,
        pretty(value) {
            if (!value) return '—';
            try { return JSON.stringify(JSON.parse(value), null, 2); } catch (e) { return value; }
        }
     }">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-900">Auditoría</h1>
        <p class="text-sm text-slate-500">Registro de todas las altas, cambios y bajas del sistema.</p>
    </div>

    <div class="card" x-data="dataTable({ url: '/api/auditoria', sort: 'id', dir: 'desc', filters: { action: '' } })">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
            <input type="search" class="input max-w-xs" placeholder="Buscar por entidad…" x-model="q">
            <select class="input w-auto" x-model="filters.action">
                <option value="">Todas las acciones</option>
                <option value="create">Creación</option>
                <option value="update">Edición</option>
                <option value="delete">Eliminación</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="th-sort" @click="sortBy('created_at')">Fecha <span x-text="sortIcon('created_at')"></span></th>
                        <th>Usuario</th>
                        <th class="th-sort" @click="sortBy('action')">Acción <span x-text="sortIcon('action')"></span></th>
                        <th class="th-sort" @click="sortBy('entity_type')">Entidad <span x-text="sortIcon('entity_type')"></span></th>
                        <th>IP</th>
                        <th class="text-right">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in rows" :key="row.id">
                        <tr>
                            <td class="whitespace-nowrap" x-text="$store.app.fmtDateTime(row.created_at)"></td>
                            <td x-text="row.user_name || 'Sistema'"></td>
                            <td>
                                <span class="badge"
                                      :class="{ create: 'badge-green', update: 'badge-amber', delete: 'badge-red' }[row.action] || 'badge-slate'"
                                      x-text="{ create: 'Creación', update: 'Edición', delete: 'Eliminación' }[row.action] || row.action"></span>
                            </td>
                            <td class="text-slate-500" x-text="row.entity_type + ' #' + row.entity_id"></td>
                            <td class="text-slate-400" x-text="row.ip || '—'"></td>
                            <td class="text-right">
                                <button class="btn-icon" title="Ver cambios" @click="detail = row">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && rows.length === 0">
                        <td colspan="6" class="py-10 text-center text-sm text-slate-400">Sin registros.</td>
                    </tr>
                    <tr x-show="loading">
                        <td colspan="6" class="py-10 text-center text-sm text-slate-400">Cargando…</td>
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

    <!-- Modal de detalle -->
    <div x-show="detail" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="modal-backdrop" @click="detail = null"></div>
        <div class="modal-panel max-w-2xl p-6" x-show="detail" x-transition.scale.origin.center @keydown.escape.window="detail = null">
            <h3 class="text-base font-semibold text-slate-900">Detalle del cambio</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <p class="label">Valores anteriores</p>
                    <pre class="max-h-64 overflow-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700"
                         x-text="detail ? pretty(detail.old_values) : ''"></pre>
                </div>
                <div>
                    <p class="label">Valores nuevos</p>
                    <pre class="max-h-64 overflow-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700"
                         x-text="detail ? pretty(detail.new_values) : ''"></pre>
                </div>
            </div>
            <div class="mt-5 flex justify-end">
                <button class="btn btn-secondary" @click="detail = null">Cerrar</button>
            </div>
        </div>
    </div>
</div>
