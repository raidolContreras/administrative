<?php /* Shell de login: cero datos; el POST va por fetch a /api/auth/login */ ?>
<div class="card w-full max-w-sm p-6" x-data="loginPage" x-cloak x-show="$store.app.ready">
    <h2 class="text-base font-semibold text-strong">Iniciar sesión</h2>
    <p class="mt-1 text-sm text-muted">Ingresa tus credenciales para continuar.</p>

    <div x-show="error" x-transition class="mt-4 rounded-lg border border-danger/30 bg-danger-soft px-3 py-2 text-sm text-danger"
         x-text="error"></div>

    <form class="mt-4 space-y-4" @submit.prevent="submit()">
        <div>
            <label class="label" for="email">Correo electrónico</label>
            <input id="email" type="email" class="input" x-model="email" autocomplete="username" required autofocus>
        </div>
        <div>
            <label class="label" for="password">Contraseña</label>
            <div class="relative">
                <input id="password" :type="show ? 'text' : 'password'" class="input pr-10" x-model="password"
                       autocomplete="current-password" required>
                <button type="button" class="absolute inset-y-0 right-0 px-3 text-faint hover:text-muted"
                        @click="show = !show" tabindex="-1" aria-label="Mostrar contraseña">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            <span x-show="!loading">Entrar</span>
            <span x-show="loading" x-cloak>Verificando…</span>
        </button>
    </form>
</div>
