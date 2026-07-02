<?php

use Core\View;

/* Layout de páginas públicas (login / instalador): shell sin datos, branding vía /api/bootstrap */
?>
<!doctype html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app:base" content="<?= htmlspecialchars(View::base(), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="noindex, nofollow">
    <title>Acceso</title>
    <link rel="stylesheet" href="<?= View::asset('css/app.css') ?>">
</head>
<body class="h-full bg-slate-100 antialiased" x-data="authShell">
    <div class="flex min-h-full flex-col items-center justify-center px-4 py-10">
        <div class="mb-6 flex flex-col items-center gap-3" x-cloak x-show="$store.app.ready">
            <img x-show="$store.app.settings.logo_path" :src="Api.base + ($store.app.settings.logo_path || '')"
                 class="h-14 w-14 rounded-xl bg-white object-cover shadow" alt="">
            <div x-show="!$store.app.settings.logo_path"
                 class="flex h-14 w-14 items-center justify-center rounded-xl text-xl font-bold text-white shadow"
                 style="background-color: var(--brand)"
                 x-text="($store.app.appName || 'P').charAt(0).toUpperCase()"></div>
            <h1 class="text-lg font-semibold text-slate-800" x-text="$store.app.appName"></h1>
        </div>

        <?= $content ?>
    </div>

    <!-- Toasts -->
    <div class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-80 flex-col gap-2">
        <template x-for="toast in $store.toast.items" :key="toast.id">
            <div class="pointer-events-auto flex items-start gap-2 rounded-lg border px-4 py-3 text-sm shadow-lg"
                 x-transition
                 :class="toast.type === 'ok' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'">
                <span class="flex-1" x-text="toast.message"></span>
                <button class="opacity-60 hover:opacity-100" @click="$store.toast.dismiss(toast.id)">✕</button>
            </div>
        </template>
    </div>

    <?php View::partial('scripts', ['pageScript' => $pageScript ?? null, 'viewName' => $viewName ?? '']); ?>
</body>
</html>
