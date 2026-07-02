<?php /* Shell de recuperación: cero datos; el POST va por fetch a /api/auth/forgot */ ?>
<div class="card w-full max-w-sm p-6" x-data="forgotPage" x-cloak x-show="$store.app.ready">
    <template x-if="!sent">
        <div>
            <h2 class="text-base font-semibold text-strong">Recuperar contraseña</h2>
            <p class="mt-1 text-sm text-muted">
                Escribe tu correo y te enviaremos un enlace para restablecerla.
            </p>

            <form class="mt-4 space-y-4" @submit.prevent="submit()">
                <div>
                    <label class="label" for="email">Correo electrónico</label>
                    <input id="email" type="email" class="input" x-model="email" autocomplete="username" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-full" :disabled="loading">
                    <span x-show="!loading">Enviar enlace</span>
                    <span x-show="loading" x-cloak>Enviando…</span>
                </button>
            </form>
        </div>
    </template>

    <!-- Confirmación genérica: se muestra igual exista o no el correo (anti-enumeración) -->
    <template x-if="sent">
        <div class="text-center">
            <span class="badge-brand mx-auto flex h-12 w-12 items-center justify-center rounded-full">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
            </span>
            <h2 class="mt-3 text-base font-semibold text-strong">Revisa tu correo</h2>
            <p class="mt-1 text-sm text-muted" x-text="message"></p>
        </div>
    </template>

    <p class="mt-5 border-t border-line pt-4 text-center text-sm">
        <a class="font-semibold text-accent hover:opacity-80" :href="Api.url('/login')">Volver a iniciar sesión</a>
    </p>
</div>
