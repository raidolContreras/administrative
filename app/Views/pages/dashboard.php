<?php /* Dashboard bento: hero con degradado de marca, KPIs (base + widgets de módulos) y actividad reciente */ ?>
<div x-data="dashboardPage">
    <div class="mb-4 lg:mb-5">
        <h1 class="text-xl font-bold text-strong lg:text-2xl">Inicio</h1>
        <p class="text-sm text-muted">Resumen general del negocio.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4">

        <!-- Hero: primer indicador en grande sobre el degradado de marca -->
        <div class="hero-card col-span-2 row-span-2">
            <div>
                <p class="text-[13px] font-semibold opacity-90" x-text="stats[0] ? stats[0].label : 'Resumen'"></p>
                <p class="tabular mt-1 text-4xl font-extrabold tracking-tight lg:text-5xl"
                   x-text="stats[0] ? stats[0].value : '—'"></p>
                <p class="mt-1 text-[13px] opacity-90" x-text="today"></p>
            </div>
            <div class="flex items-end justify-between gap-4">
                <p class="text-sm font-semibold opacity-90" x-text="$store.app.appName"></p>
                <svg class="h-10 w-10 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="$store.app.icon(stats[0] ? stats[0].icon : 'home')"/>
                </svg>
            </div>
        </div>

        <!-- KPIs restantes (widgets del base + módulos) -->
        <template x-for="stat in stats.slice(1)" :key="stat.label">
            <div class="card p-4 lg:p-5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11.5px] font-semibold uppercase tracking-wide text-muted" x-text="stat.label"></p>
                    <span class="badge-brand flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px]">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="$store.app.icon(stat.icon)"/>
                        </svg>
                    </span>
                </div>
                <p class="tabular mt-1 text-2xl font-extrabold tracking-tight text-strong" x-text="stat.value"></p>
            </div>
        </template>
        <div x-show="loading" class="card p-4 text-sm text-faint lg:p-5">Cargando…</div>

        <!-- Actividad reciente -->
        <div class="card col-span-2 p-5 xl:col-span-4">
            <h2 class="mb-2 text-sm font-bold text-strong">Actividad reciente</h2>
            <div>
                <template x-for="(item, i) in recent" :key="i">
                    <div class="list-row">
                        <span class="list-pic"
                              x-text="(item.user_name || 'S').slice(0, 2).toUpperCase()"></span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-strong" x-text="item.user_name || 'Sistema'"></p>
                            <p class="truncate text-xs text-muted"
                               x-text="item.entity_type + ' #' + item.entity_id + ' · ' + $store.app.fmtDateTime(item.created_at)"></p>
                        </div>
                        <span class="badge ml-auto shrink-0"
                              :class="{ create: 'badge-green', update: 'badge-amber', delete: 'badge-red' }[item.action] || 'badge-slate'"
                              x-text="{ create: 'Creación', update: 'Edición', delete: 'Eliminación' }[item.action] || item.action"></span>
                    </div>
                </template>
                <p x-show="!loading && recent.length === 0" class="py-6 text-center text-sm text-faint">
                    Sin actividad todavía.
                </p>
            </div>
        </div>
    </div>
</div>
