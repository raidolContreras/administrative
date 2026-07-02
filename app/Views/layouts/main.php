<?php

use Core\View;

/*
 * Layout principal (Bento v2). REGLA DEL TEMPLATE: aquí no se interpola ningún dato de negocio.
 * Todo (usuario, menú, nombre del negocio, logo, colores) llega por /api/bootstrap y lo pinta Alpine.
 * Única excepción documentada: constantes de infraestructura (base path, versión de assets).
 *
 * Navegación: sidebar "isla" flotante en escritorio (≥1024px) y barra inferior + hoja "Más" en móvil.
 */
?>
<!doctype html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="app:base" content="<?= htmlspecialchars(View::base(), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="noindex, nofollow">
    <title>Panel</title>
    <!-- Anti-FOUC: fija el tema antes de pintar (externo por la CSP; debe ir antes del CSS) -->
    <script src="<?= View::asset('js/theme-init.js') ?>"></script>
    <link rel="stylesheet" href="<?= View::asset('css/app.css') ?>">
</head>
<body class="min-h-full bg-canvas text-body antialiased" x-data="layout">

    <!-- Cargando (hasta que /api/bootstrap responde) -->
    <div x-show="!$store.app.ready" class="fixed inset-0 z-50 flex items-center justify-center bg-canvas">
        <div class="spinner"></div>
    </div>

    <div class="flex items-start" x-cloak x-show="$store.app.ready">

        <!-- Sidebar isla (escritorio) -->
        <aside class="rail">
            <div class="flex items-center gap-3 px-2 pb-4">
                <img x-show="$store.app.settings.logo_path" x-cloak
                     :src="Api.base + ($store.app.settings.logo_path || '')"
                     class="h-10 w-10 rounded-xl object-cover" alt="">
                <div x-show="!$store.app.settings.logo_path"
                     class="bg-brand-grad flex h-10 w-10 items-center justify-center rounded-xl text-sm font-bold text-white"
                     x-text="($store.app.appName || 'P').charAt(0).toUpperCase()"></div>
                <span class="truncate text-sm font-bold text-strong" x-text="$store.app.appName"></span>
            </div>

            <nav class="flex flex-1 flex-col gap-1">
                <template x-for="item in $store.app.menu" :key="item.href">
                    <a :href="Api.url(item.href)" class="rail-link"
                       :class="$store.app.isActive(item.href) && 'rail-link-active'">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="$store.app.icon(item.icon)"/>
                        </svg>
                        <span x-text="item.label"></span>
                    </a>
                </template>
            </nav>

            <div class="border-t border-line pt-3 text-center text-[11px] text-faint">
                Sesión activa · <span x-text="$store.app.user ? $store.app.user.name : ''"></span>
            </div>
        </aside>

        <!-- Columna principal -->
        <div class="flex min-h-svh min-w-0 flex-1 flex-col">
            <header class="topbar">
                <!-- Marca (solo móvil; en escritorio vive en la isla) -->
                <div class="flex items-center gap-2.5 lg:hidden">
                    <img x-show="$store.app.settings.logo_path" x-cloak
                         :src="Api.base + ($store.app.settings.logo_path || '')"
                         class="h-9 w-9 rounded-[10px] object-cover" alt="">
                    <div x-show="!$store.app.settings.logo_path"
                         class="bg-brand-grad flex h-9 w-9 items-center justify-center rounded-[10px] text-sm font-bold text-white"
                         x-text="($store.app.appName || 'P').charAt(0).toUpperCase()"></div>
                    <span class="truncate text-sm font-bold text-strong" x-text="$store.app.appName"></span>
                </div>

                <div class="flex-1"></div>

                <!-- Conmutador de tema -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button class="btn-icon" @click="open = !open" aria-label="Cambiar tema" :aria-expanded="open">
                        <svg x-show="!$store.app.effectiveDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                        </svg>
                        <svg x-show="$store.app.effectiveDark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition.origin.top.right x-cloak
                         class="absolute right-0 z-20 mt-2 w-40 overflow-hidden rounded-2xl border border-line bg-surface py-1 shadow-lg">
                        <template x-for="opt in [{k:'light',t:'Claro'},{k:'dark',t:'Oscuro'},{k:'system',t:'Sistema'}]" :key="opt.k">
                            <button class="flex w-full items-center justify-between px-4 py-2.5 text-left text-sm text-body hover:bg-subtle"
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
                    <button class="flex items-center gap-2 rounded-xl py-1 pl-1 pr-2 text-sm font-medium text-body hover:bg-subtle"
                            @click="open = !open">
                        <span class="bg-brand-grad flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold text-white"
                              x-text="$store.app.user ? $store.app.user.name.charAt(0).toUpperCase() : ''"></span>
                        <span class="hidden sm:block" x-text="$store.app.user ? $store.app.user.name : ''"></span>
                        <svg class="hidden h-4 w-4 text-faint sm:block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition.origin.top.right x-cloak
                         class="absolute right-0 z-20 mt-2 w-52 overflow-hidden rounded-2xl border border-line bg-surface py-1 shadow-lg">
                        <div class="border-b border-line px-4 py-2.5">
                            <p class="truncate text-sm font-semibold text-strong" x-text="$store.app.user ? $store.app.user.name : ''"></p>
                            <p class="truncate text-xs text-muted" x-text="$store.app.user ? $store.app.user.email : ''"></p>
                        </div>
                        <a :href="Api.url('/perfil')" class="block px-4 py-2.5 text-sm text-body hover:bg-subtle">Mi perfil</a>
                        <button class="block w-full px-4 py-2.5 text-left text-sm text-danger hover:bg-danger-soft" @click="logout()">
                            Cerrar sesión
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 pb-28 pt-2 lg:px-7 lg:pb-10">
                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Barra de navegación inferior (móvil) -->
    <nav class="bottomnav" x-cloak x-show="$store.app.ready">
        <template x-for="item in $store.app.menu.slice(0, 4)" :key="item.href">
            <a :href="Api.url(item.href)" class="bottomnav-link"
               :class="$store.app.isActive(item.href) && 'bottomnav-link-active'">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="$store.app.icon(item.icon)"/>
                </svg>
                <span x-text="item.label"></span>
            </a>
        </template>
        <button class="bottomnav-link" @click="moreOpen = true"
                :class="moreOpen && 'bottomnav-link-active'">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm6 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm6 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                <circle cx="6" cy="12" r="1.4" fill="currentColor" stroke="none"/>
                <circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/>
                <circle cx="18" cy="12" r="1.4" fill="currentColor" stroke="none"/>
            </svg>
            <span>Más</span>
        </button>
    </nav>

    <!-- Hoja "Más" (móvil): menú completo + perfil -->
    <div x-show="moreOpen" x-cloak class="lg:hidden">
        <div class="modal-backdrop" style="z-index: 55" @click="moreOpen = false"></div>
        <div class="sheet-panel" x-show="moreOpen" x-transition.origin.bottom>
            <div class="sheet-handle"></div>
            <div class="mb-2 grid grid-cols-1 gap-1">
                <template x-for="item in $store.app.menu" :key="item.href">
                    <a :href="Api.url(item.href)" class="rail-link"
                       :class="$store.app.isActive(item.href) && 'rail-link-active'">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="$store.app.icon(item.icon)"/>
                        </svg>
                        <span x-text="item.label"></span>
                    </a>
                </template>
            </div>
            <div class="border-t border-line pt-2">
                <a :href="Api.url('/perfil')" class="rail-link">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Mi perfil</span>
                </a>
                <button class="rail-link w-full text-danger" @click="logout()">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                    <span>Cerrar sesión</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toasts -->
    <div class="pointer-events-none fixed bottom-24 right-4 z-[70] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2 lg:bottom-4">
        <template x-for="toast in $store.toast.items" :key="toast.id">
            <div class="pointer-events-auto flex items-start gap-2 rounded-2xl border px-4 py-3 text-sm shadow-lg"
                 x-transition
                 :class="toast.type === 'ok' ? 'border-success/30 bg-success-soft text-success' : 'border-danger/30 bg-danger-soft text-danger'">
                <span class="flex-1" x-text="toast.message"></span>
                <button class="opacity-60 hover:opacity-100" @click="$store.toast.dismiss(toast.id)">✕</button>
            </div>
        </template>
    </div>

    <!-- Confirmación global -->
    <div x-show="$store.confirm.open" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4">
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
