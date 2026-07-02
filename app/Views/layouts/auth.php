<?php

use Core\View;

/* Layout de páginas públicas (login / instalador): shell sin datos, branding vía /api/bootstrap */
?>
<!doctype html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="app:base" content="<?= htmlspecialchars(View::base(), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="noindex, nofollow">
    <title>Acceso</title>
    <script src="<?= View::asset('js/theme-init.js') ?>"></script>
    <link rel="stylesheet" href="<?= View::asset('css/app.css') ?>">
</head>
<body class="h-full bg-canvas text-body antialiased" x-data="authShell">

    <!-- Conmutador de tema (claro/oscuro) -->
    <button class="btn-icon fixed right-4 top-4 z-10"
            @click="$store.app.setTheme($store.app.effectiveDark ? 'light' : 'dark')"
            aria-label="Cambiar tema">
        <svg x-show="!$store.app.effectiveDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
        </svg>
        <svg x-show="$store.app.effectiveDark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
        </svg>
    </button>

    <div class="flex min-h-full flex-col items-center justify-center px-4 py-10">
        <div class="mb-6 flex flex-col items-center gap-3" x-cloak x-show="$store.app.ready">
            <img x-show="$store.app.settings.logo_path" :src="Api.base + ($store.app.settings.logo_path || '')"
                 class="h-14 w-14 rounded-xl object-cover shadow" alt="">
            <div x-show="!$store.app.settings.logo_path"
                 class="bg-brand-grad flex h-14 w-14 items-center justify-center rounded-2xl text-xl font-bold text-white shadow"
                 x-text="($store.app.appName || 'P').charAt(0).toUpperCase()"></div>
            <h1 class="text-lg font-semibold text-strong" x-text="$store.app.appName"></h1>
        </div>

        <?= $content ?>
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

    <?php View::partial('scripts', ['pageScript' => $pageScript ?? null, 'viewName' => $viewName ?? '']); ?>
</body>
</html>
