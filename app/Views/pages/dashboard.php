<?php /* Dashboard: tarjetas de stats (base + widgets de módulos) y actividad reciente */ ?>
<div x-data="dashboardPage">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Inicio</h1>
            <p class="text-sm text-slate-500">Resumen general del negocio.</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <template x-for="stat in stats" :key="stat.label">
            <div class="card flex items-center gap-4 p-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg badge-brand">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="$store.app.icon(stat.icon)"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm text-slate-500" x-text="stat.label"></p>
                    <p class="text-2xl font-bold text-slate-900" x-text="stat.value"></p>
                </div>
            </div>
        </template>
        <template x-if="loading">
            <div class="card p-5 text-sm text-slate-400">Cargando…</div>
        </template>
    </div>

    <!-- Actividad reciente -->
    <div class="card mt-6">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Actividad reciente</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Entidad</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, i) in recent" :key="i">
                        <tr>
                            <td class="whitespace-nowrap" x-text="$store.app.fmtDateTime(item.created_at)"></td>
                            <td x-text="item.user_name || 'Sistema'"></td>
                            <td>
                                <span class="badge"
                                      :class="{ create: 'badge-green', update: 'badge-amber', delete: 'badge-red' }[item.action] || 'badge-slate'"
                                      x-text="{ create: 'Creación', update: 'Edición', delete: 'Eliminación' }[item.action] || item.action"></span>
                            </td>
                            <td class="text-slate-500" x-text="item.entity_type + ' #' + item.entity_id"></td>
                        </tr>
                    </template>
                    <tr x-show="!loading && recent.length === 0">
                        <td colspan="4" class="py-8 text-center text-sm text-slate-400">Sin actividad todavía.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
