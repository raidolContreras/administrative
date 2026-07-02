<?php /* Citas: filtros por estado/día, acción rápida "atendida" y modal con catálogo de mascotas */ ?>
<div x-data="citasPage">
<div x-data="formModal({
        url: '/api/citas',
        defaults: { pet_id: '', scheduled_at: '', reason: '', status: 'programada', notes: '' },
        pick: ['pet_id', 'scheduled_at', 'reason', 'status', 'notes']
     })">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Citas</h1>
            <p class="text-sm text-slate-500">Agenda de consultas y servicios.</p>
        </div>
        <button class="btn btn-primary" @click="openCreate(); form.scheduled_at = defaultSlot()">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nueva cita
        </button>
    </div>

    <div class="card" x-data="dataTable({ url: '/api/citas', sort: 'scheduled_at', dir: 'desc', filters: { status: '', day: '' } })">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
            <input type="search" class="input max-w-xs" placeholder="Buscar por mascota, dueño o motivo…" x-model="q">
            <select class="input w-auto" x-model="filters.status">
                <option value="">Todos los estados</option>
                <option value="programada">Programadas</option>
                <option value="atendida">Atendidas</option>
                <option value="cancelada">Canceladas</option>
            </select>
            <input type="date" class="input w-auto" x-model="filters.day" title="Filtrar por día">
            <button class="btn btn-secondary !px-3 !py-1.5" x-show="filters.day || filters.status" x-cloak
                    @click="filters.day = ''; filters.status = ''">Limpiar</button>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="th-sort" @click="sortBy('scheduled_at')">Fecha y hora <span x-text="sortIcon('scheduled_at')"></span></th>
                        <th class="th-sort" @click="sortBy('pet_name')">Mascota <span x-text="sortIcon('pet_name')"></span></th>
                        <th>Dueño</th>
                        <th>Motivo</th>
                        <th class="th-sort" @click="sortBy('status')">Estado <span x-text="sortIcon('status')"></span></th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in rows" :key="row.id">
                        <tr>
                            <td class="whitespace-nowrap font-medium text-slate-900" x-text="$store.app.fmtDateTime(row.scheduled_at)"></td>
                            <td x-text="row.pet_name || '—'"></td>
                            <td class="text-slate-500" x-text="row.owner_name || '—'"></td>
                            <td class="max-w-xs truncate" x-text="row.reason"></td>
                            <td>
                                <span class="badge" :class="statusClass(row.status)" x-text="statusLabel(row.status)"></span>
                            </td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <button class="btn-icon hover:!text-green-600" title="Marcar como atendida"
                                            x-show="row.status === 'programada'"
                                            @click="markDone(row)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                    <button class="btn-icon" title="Editar" @click="openEdit(row); form.scheduled_at = toLocal(row.scheduled_at)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                    </button>
                                    <button class="btn-icon hover:!text-red-600" title="Eliminar" @click="destroy(row, 'la cita de ' + (row.pet_name || ''))">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L5.772 5.79m13.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && rows.length === 0">
                        <td colspan="6" class="py-10 text-center text-sm text-slate-400">Sin citas para los filtros elegidos.</td>
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

    <!-- Modal -->
    <div x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="modal-backdrop" @click="close()"></div>
        <div class="modal-panel p-6" x-show="open" x-transition.scale.origin.center @keydown.escape.window="close()">
            <h3 class="text-base font-semibold text-slate-900" x-text="mode === 'create' ? 'Nueva cita' : 'Editar cita'"></h3>
            <form class="mt-4 space-y-4" @submit.prevent="submit()">
                <div>
                    <label class="label">Mascota</label>
                    <select class="input" :class="err('pet_id') && 'input-error'" x-model="form.pet_id">
                        <option value="">— Selecciona una mascota —</option>
                        <template x-for="pet in pets" :key="pet.id">
                            <option :value="pet.id" x-text="pet.name + (pet.owner_name ? ' · ' + pet.owner_name : '')"></option>
                        </template>
                    </select>
                    <p class="field-error" x-show="err('pet_id')" x-text="err('pet_id')"></p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Fecha y hora</label>
                        <input type="datetime-local" class="input" :class="err('scheduled_at') && 'input-error'" x-model="form.scheduled_at">
                        <p class="field-error" x-show="err('scheduled_at')" x-text="err('scheduled_at')"></p>
                    </div>
                    <div>
                        <label class="label">Estado</label>
                        <select class="input" x-model="form.status">
                            <option value="programada">Programada</option>
                            <option value="atendida">Atendida</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label">Motivo</label>
                    <input type="text" class="input" :class="err('reason') && 'input-error'" x-model="form.reason"
                           placeholder="Vacuna, consulta, estética…">
                    <p class="field-error" x-show="err('reason')" x-text="err('reason')"></p>
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
