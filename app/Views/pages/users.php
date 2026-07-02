<?php /* CRUD de usuarios: composición de los componentes genéricos dataTable + formModal (patrón de referencia) */ ?>
<div x-data="formModal({
        url: '/api/usuarios',
        defaults: { name: '', email: '', password: '', role: 'employee', is_active: true },
        pick: ['name', 'email', 'role', 'is_active']
     })">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Usuarios</h1>
            <p class="text-sm text-slate-500">Personas con acceso al panel.</p>
        </div>
        <button class="btn btn-primary" @click="openCreate()">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nuevo usuario
        </button>
    </div>

    <div class="card" x-data="dataTable({ url: '/api/usuarios', sort: 'name', dir: 'asc', filters: { role: '', is_active: '' } })">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
            <input type="search" class="input max-w-xs" placeholder="Buscar por nombre o correo…" x-model="q">
            <select class="input w-auto" x-model="filters.role">
                <option value="">Todos los roles</option>
                <option value="admin">Administrador</option>
                <option value="employee">Empleado</option>
            </select>
            <select class="input w-auto" x-model="filters.is_active">
                <option value="">Activos e inactivos</option>
                <option value="1">Solo activos</option>
                <option value="0">Solo inactivos</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="th-sort" @click="sortBy('name')">Nombre <span x-text="sortIcon('name')"></span></th>
                        <th class="th-sort" @click="sortBy('email')">Correo <span x-text="sortIcon('email')"></span></th>
                        <th class="th-sort" @click="sortBy('role')">Rol <span x-text="sortIcon('role')"></span></th>
                        <th>Estado</th>
                        <th class="th-sort" @click="sortBy('last_login_at')">Último acceso <span x-text="sortIcon('last_login_at')"></span></th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in rows" :key="row.id">
                        <tr>
                            <td class="font-medium text-slate-900" x-text="row.name"></td>
                            <td x-text="row.email"></td>
                            <td>
                                <span class="badge" :class="row.role === 'admin' ? 'badge-brand' : 'badge-slate'"
                                      x-text="row.role === 'admin' ? 'Administrador' : 'Empleado'"></span>
                            </td>
                            <td>
                                <span class="badge" :class="row.is_active ? 'badge-green' : 'badge-red'"
                                      x-text="row.is_active ? 'Activo' : 'Inactivo'"></span>
                            </td>
                            <td class="whitespace-nowrap text-slate-500" x-text="$store.app.fmtDateTime(row.last_login_at)"></td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <button class="btn-icon" title="Editar" @click="openEdit(row)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                    </button>
                                    <button class="btn-icon hover:!text-red-600" title="Eliminar" @click="destroy(row, 'al usuario ' + row.name)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L5.772 5.79m13.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && rows.length === 0">
                        <td colspan="6" class="py-10 text-center text-sm text-slate-400">Sin resultados.</td>
                    </tr>
                    <tr x-show="loading">
                        <td colspan="6" class="py-10 text-center text-sm text-slate-400">Cargando…</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
            <span>
                <span x-text="meta.total"></span> registro(s) · página <span x-text="meta.page"></span> de <span x-text="meta.total_pages"></span>
            </span>
            <div class="flex gap-1">
                <button class="btn btn-secondary !px-3 !py-1.5" :disabled="meta.page <= 1" @click="prev()">Anterior</button>
                <button class="btn btn-secondary !px-3 !py-1.5" :disabled="meta.page >= meta.total_pages" @click="next()">Siguiente</button>
            </div>
        </div>
    </div>

    <!-- Modal alta/edición -->
    <div x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="modal-backdrop" @click="close()"></div>
        <div class="modal-panel p-6" x-show="open" x-transition.scale.origin.center @keydown.escape.window="close()">
            <h3 class="text-base font-semibold text-slate-900"
                x-text="mode === 'create' ? 'Nuevo usuario' : 'Editar usuario'"></h3>

            <form class="mt-4 space-y-4" @submit.prevent="submit()">
                <div>
                    <label class="label">Nombre completo</label>
                    <input type="text" class="input" :class="err('name') && 'input-error'" x-model="form.name">
                    <p class="field-error" x-show="err('name')" x-text="err('name')"></p>
                </div>
                <div>
                    <label class="label">Correo electrónico</label>
                    <input type="email" class="input" :class="err('email') && 'input-error'" x-model="form.email">
                    <p class="field-error" x-show="err('email')" x-text="err('email')"></p>
                </div>
                <div>
                    <label class="label">Contraseña</label>
                    <input type="password" class="input" :class="err('password') && 'input-error'" x-model="form.password"
                           autocomplete="new-password">
                    <p class="hint" x-show="mode === 'edit'">Déjala vacía para no cambiarla.</p>
                    <p class="field-error" x-show="err('password')" x-text="err('password')"></p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Rol</label>
                        <select class="input" x-model="form.role">
                            <option value="employee">Empleado</option>
                            <option value="admin">Administrador</option>
                        </select>
                        <p class="field-error" x-show="err('role')" x-text="err('role')"></p>
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" class="h-4 w-4 rounded border-slate-300" x-model="form.is_active">
                            Usuario activo
                        </label>
                    </div>
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
