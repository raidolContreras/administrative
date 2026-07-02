<?php /* Dueños: CRUD 100% con componentes genéricos (sin JS propio de página) */ ?>
<div x-data="formModal({
        url: '/api/duenos',
        defaults: { name: '', phone: '', email: '', address: '', notes: '' },
        pick: ['name', 'phone', 'email', 'address', 'notes']
     })">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-strong">Dueños</h1>
            <p class="text-sm text-muted">Propietarios de las mascotas.</p>
        </div>
        <button class="btn btn-primary" @click="openCreate()">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nuevo dueño
        </button>
    </div>

    <div class="card" x-data="dataTable({ url: '/api/duenos', sort: 'name', dir: 'asc' })">
        <div class="border-b border-line p-4">
            <input type="search" class="input max-w-xs" placeholder="Buscar por nombre, teléfono o correo…" x-model="q">
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="th-sort" @click="sortBy('name')">Nombre <span x-text="sortIcon('name')"></span></th>
                        <th class="th-sort" @click="sortBy('phone')">Teléfono <span x-text="sortIcon('phone')"></span></th>
                        <th>Correo</th>
                        <th>Dirección</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in rows" :key="row.id">
                        <tr>
                            <td class="font-medium text-strong" x-text="row.name"></td>
                            <td class="whitespace-nowrap" x-text="row.phone || '—'"></td>
                            <td x-text="row.email || '—'"></td>
                            <td class="max-w-xs truncate text-muted" x-text="row.address || '—'"></td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <button class="btn-icon" title="Editar" @click="openEdit(row)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                    </button>
                                    <button class="btn-icon hover:!text-danger" title="Eliminar" @click="destroy(row, 'a ' + row.name)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L5.772 5.79m13.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && rows.length === 0">
                        <td colspan="5" class="py-10 text-center text-sm text-faint">Sin resultados.</td>
                    </tr>
                    <tr x-show="loading">
                        <td colspan="5" class="py-10 text-center text-sm text-faint">Cargando…</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between border-t border-line px-4 py-3 text-sm text-muted">
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
            <h3 class="text-base font-semibold text-strong" x-text="mode === 'create' ? 'Nuevo dueño' : 'Editar dueño'"></h3>
            <form class="mt-4 space-y-4" @submit.prevent="submit()">
                <div>
                    <label class="label">Nombre completo</label>
                    <input type="text" class="input" :class="err('name') && 'input-error'" x-model="form.name">
                    <p class="field-error" x-show="err('name')" x-text="err('name')"></p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Teléfono</label>
                        <input type="tel" class="input" :class="err('phone') && 'input-error'" x-model="form.phone">
                        <p class="field-error" x-show="err('phone')" x-text="err('phone')"></p>
                    </div>
                    <div>
                        <label class="label">Correo (opcional)</label>
                        <input type="email" class="input" :class="err('email') && 'input-error'" x-model="form.email">
                        <p class="field-error" x-show="err('email')" x-text="err('email')"></p>
                    </div>
                </div>
                <div>
                    <label class="label">Dirección (opcional)</label>
                    <input type="text" class="input" x-model="form.address">
                </div>
                <div>
                    <label class="label">Notas (opcional)</label>
                    <textarea class="input" rows="2" x-model="form.notes"></textarea>
                </div>
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
