<?php

use Core\View;

/*
 * Layout principal. REGLA DEL TEMPLATE: aquí no se interpola ningún dato de negocio.
 * Todo (usuario, menú, nombre del negocio, logo) llega por /api/bootstrap y lo pinta Alpine.
 * Única excepción documentada: constantes de infraestructura (base path, versión de assets).
 */
?>
<!doctype html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="app:base" content="<?= htmlspecialchars(View::base(), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="noindex, nofollow">
    <title>Panel</title>
    <!-- Anti-FOUC: fija el tema antes de pintar (externo por la CSP; debe ir antes del CSS) -->
    <script src="<?= View::asset('js/theme-init.js') ?>"></script>
    <link rel="stylesheet" href="<?= View::asset('css/app.css') ?>">
</head>
<body class="h-full bg-canvas text-body antialiased" x-data="layout">

    <!-- Cargando (hasta que /api/bootstrap responde) -->
    <div x-show="!$store.app.ready" class="fixed inset-0 z-50 flex items-center justify-center bg-canvas">
        <div class="spinner"></div>
    </div>

    <div class="flex h-full" x-cloak x-show="$store.app.ready">

        <!-- Overlay móvil -->
        <div x-show="$store.app.sidebarOpen" x-transition.opacity @click="$store.app.sidebarOpen = false"
             class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 transform flex-col border-r border-line bg-surface transition-transform duration-200 lg:static lg:translate-x-0"
               :class="$store.app.sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="flex h-16 items-center gap-3 border-b border-line px-5">
                <img x-show="$store.app.settings.logo_path" x-cloak
                     :src="Api.base + ($store.app.settings.logo_path || '')"
                     class="h-9 w-9 rounded-lg object-cover" alt="">
                <div x-show="!$store.app.settings.logo_path"
                     class="flex h-9 w-9 items-center justify-center rounded-lg text-sm font-bold text-white"
                     style="background-color: var(--brand)"
                     x-text="($store.app.appName || 'P').charAt(0).toUpperCase()"></div>
                <span class="truncate text-sm font-semibold text-strong" x-text="$store.app.appName"></span>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <template x-for="item in $store.app.menu" :key="item.href">
                    <a :href="Api.url(item.href)"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                       :class="$store.app.isActive(item.href) ? 'bg-accent/10 text-accent font-semibold' : 'text-muted hover:bg-subtle hover:text-strong'">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="$store.app.icon(item.icon)"/>
                        </svg>
                        <span x-text="item.label"></span>
                    </a>
                </template>
            </nav>

            <div class="border-t border-line p-3 text-center text-[11px] text-faint">
                Sesión activa · <span x-text="$store.app.user ? $store.app.user.name : ''"></span>
            </div>
        </aside>

        <!-- Columna principal -->
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 shrink-0 items-center gap-3 border-b border-line bg-surface px-4 md:px-6">
                <button class="btn-icon lg:hidden" @click="$store.app.sidebarOpen = true" aria-label="Abrir menú">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
                <div class="flex-1"></div>

                <!-- Conmutador de tema -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button class="btn-icon" @click="open = !open" aria-label="Cambiar tema" :aria-expanded="open">
                        <!-- Sol (claro) -->
                        <svg x-show="!$store.app.effectiveDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                        </svg>
                        <!-- Luna (oscuro) -->
                        <svg x-show="$store.app.effectiveDark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition.origin.top.right x-cloak
                         class="absolute right-0 z-20 mt-2 w-40 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                        <template x-for="opt in [{k:'light',t:'Claro'},{k:'dark',t:'Oscuro'},{k:'system',t:'Sistema'}]" :key="opt.k">
                            <button class="flex w-full items-center justify-between px-4 py-2 text-left text-sm text-body hover:bg-subtle"
                                    @click="$store.app.setTheme(opt.k); open = false">
                                <span x-text="opt.t"></span>
                                <svg x-show="$store.app.themeMode === opt.k" class="h-4 w-4 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Menú de usuario -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-body hover:bg-subtle"
                            @click="open = !open">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold text-white"
                              style="background-color: var(--brand)"
                              x-text="$store.app.user ? $store.app.user.name.charAt(0).toUpperCase() : ''"></span>
                        <span class="hidden sm:block" x-text="$store.app.user ? $store.app.user.name : ''"></span>
                        <svg class="h-4 w-4 text-faint" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition.origin.top.right x-cloak
                         class="absolute right-0 z-20 mt-2 w-48 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                        <div class="border-b border-line px-4 py-2">
                            <p class="truncate text-sm font-semibold text-strong" x-text="$store.app.user ? $store.app.user.name : ''"></p>
                            <p class="truncate text-xs text-muted" x-text="$store.app.user ? $store.app.user.email : ''"></p>
                        </div>
                        <a :href="Api.url('/perfil')" class="block px-4 py-2 text-sm text-body hover:bg-subtle">Mi perfil</a>
                        <button class="block w-full px-4 py-2 text-left text-sm text-danger hover:bg-danger-soft" @click="logout()">
                            Cerrar sesión
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Toasts -->
    <div class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-80 flex-col gap-2">
        <template x-for="toast in $store.toast.items" :key="toast.id">
            <div class="pointer-events-auto flex items-start gap-2 rounded-lg border px-4 py-3 text-sm shadow-lg"
                 x-transition
                 :class="toast.type === 'ok' ? 'border-success/30 bg-success-soft text-success' : 'border-danger/30 bg-danger-soft text-danger'">
                <span class="flex-1" x-text="toast.message"></span>
                <button class="opacity-60 hover:opacity-100" @click="$store.toast.dismiss(toast.id)">✕</button>
            </div>
        </template>
    </div>

    <!-- Confirmación global -->
    <div x-show="$store.confirm.open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="modal-backdrop" @click="$store.confirm.answer(false)"></div>
        <div class="modal-panel max-w-md p-6" x-show="$store.confirm.open" x-transition.scale.origin.center>
            <h3 class="text-base font-semibold text-strong" x-text="$store.confirm.title"></h3>
            <p class="mt-2 text-sm text-muted" x-text="$store.confirm.message"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button class="btn btn-secondary" @click="$store.confirm.answer(false)">Cancelar</button>
                <button class="btn" :class="$store.confirm.danger ? 'btn-danger' : 'btn-primary'"
                        @click="$store.confirm.answer(true)">Confirmar</button>
            </div>
        </div>
    </div>

    <?php View::partial('scripts', ['pageScript' => $pageScript ?? null, 'viewName' => $viewName ?? '']); ?>
</body>
</html>
