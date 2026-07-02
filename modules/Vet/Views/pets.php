<?php /* Mascotas: tabla con JOIN (dueño) + modal con catálogo de dueños cargado por API */ ?>
<div x-data="petsPage">
<div x-data="formModal({
        url: '/api/mascotas',
        defaults: { owner_id: '', name: '', species: 'perro', breed: '', sex: '', birth_date: '', weight_kg: '', notes: '' },
        pick: ['owner_id', 'name', 'species', 'breed', 'sex', 'birth_date', 'weight_kg', 'notes']
     })">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Mascotas</h1>
            <p class="text-sm text-slate-500">Pacientes de la veterinaria.</p>
        </div>
        <button class="btn btn-primary" @click="openCreate()">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nueva mascota
        </button>
    </div>

    <div class="card" x-data="dataTable({ url: '/api/mascotas', sort: 'name', dir: 'asc', filters: { species: '' } })">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
            <input type="search" class="input max-w-xs" placeholder="Buscar por mascota, raza o dueño…" x-model="q">
            <select class="input w-auto" x-model="filters.species">
                <option value="">Todas las especies</option>
                <template x-for="sp in species" :key="sp">
                    <option :value="sp" x-text="speciesLabel(sp)"></option>
                </template>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="th-sort" @click="sortBy('name')">Mascota <span x-text="sortIcon('name')"></span></th>
                        <th class="th-sort" @click="sortBy('species')">Especie <span x-text="sortIcon('species')"></span></th>
                        <th>Raza</th>
                        <th class="th-sort" @click="sortBy('owner_name')">Dueño <span x-text="sortIcon('owner_name')"></span></th>
                        <th class="th-sort" @click="sortBy('birth_date')">Nacimiento <span x-text="sortIcon('birth_date')"></span></th>
                        <th>Peso</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in rows" :key="row.id">
                        <tr>
                            <td class="font-medium text-slate-900" x-text="row.name"></td>
                            <td><span class="badge badge-brand" x-text="speciesLabel(row.species)"></span></td>
                            <td class="text-slate-500" x-text="row.breed || '—'"></td>
                            <td x-text="row.owner_name || '—'"></td>
                            <td class="whitespace-nowrap text-slate-500" x-text="$store.app.fmtDate(row.birth_date)"></td>
                            <td class="whitespace-nowrap text-slate-500" x-text="row.weight_kg ? row.weight_kg + ' kg' : '—'"></td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <button class="btn-icon" title="Editar" @click="openEdit(row)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                    </button>
                                    <button class="btn-icon hover:!text-red-600" title="Eliminar" @click="destroy(row, 'a ' + row.name)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L5.772 5.79m13.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && rows.length === 0">
                        <td colspan="7" class="py-10 text-center text-sm text-slate-400">Sin resultados.</td>
                    </tr>
                    <tr x-show="loading">
                        <td colspan="7" class="py-10 text-center text-sm text-slate-400">Cargando…</td>
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
            <h3 class="text-base font-semibold text-slate-900" x-text="mode === 'create' ? 'Nueva mascota' : 'Editar mascota'"></h3>
            <form class="mt-4 space-y-4" @submit.prevent="submit()">
                <div>
                    <label class="label">Dueño</label>
                    <select class="input" :class="err('owner_id') && 'input-error'" x-model="form.owner_id">
                        <option value="">— Selecciona un dueño —</option>
                        <template x-for="owner in owners" :key="owner.id">
                            <option :value="owner.id" x-text="owner.name"></option>
                        </template>
                    </select>
                    <p class="field-error" x-show="err('owner_id')" x-text="err('owner_id')"></p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Nombre</label>
                        <input type="text" class="input" :class="err('name') && 'input-error'" x-model="form.name">
                        <p class="field-error" x-show="err('name')" x-text="err('name')"></p>
                    </div>
                    <div>
                        <label class="label">Especie</label>
                        <select class="input" x-model="form.species">
                            <template x-for="sp in species" :key="sp">
                                <option :value="sp" x-text="speciesLabel(sp)"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="label">Raza (opcional)</label>
                        <input type="text" class="input" x-model="form.breed">
                    </div>
                    <div>
                        <label class="label">Sexo</label>
                        <select class="input" x-model="form.sex">
                            <option value="">—</option>
                            <option value="M">Macho</option>
                            <option value="H">Hembra</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Peso (kg)</label>
                        <input type="number" step="0.01" min="0" class="input" :class="err('weight_kg') && 'input-error'" x-model="form.weight_kg">
                        <p class="field-error" x-show="err('weight_kg')" x-text="err('weight_kg')"></p>
                    </div>
                </div>
                <div>
                    <label class="label">Fecha de nacimiento (opcional)</label>
                    <input type="date" class="input" :class="err('birth_date') && 'input-error'" x-model="form.birth_date">
                    <p class="field-error" x-show="err('birth_date')" x-text="err('birth_date')"></p>
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
</div>
