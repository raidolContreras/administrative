<?php /* Shell de restablecimiento: el token viaja en la URL (?token=…) y el POST por fetch a /api/auth/reset */ ?>
<div class="card w-full max-w-sm p-6" x-data="resetPage" x-cloak x-show="$store.app.ready">
    <template x-if="!token">
        <div class="text-center">
            <h2 class="text-base font-semibold text-strong">Enlace incompleto</h2>
            <p class="mt-1 text-sm text-muted">
                Este enlace no es válido. Solicita uno nuevo desde “Recuperar contraseña”.
            </p>
            <a class="btn btn-primary mt-4 w-full" :href="Api.url('/recuperar')">Solicitar enlace</a>
        </div>
    </template>

    <template x-if="token">
        <div>
            <h2 class="text-base font-semibold text-strong">Nueva contraseña</h2>
            <p class="mt-1 text-sm text-muted">Elige la nueva contraseña de tu cuenta.</p>

            <div x-show="error" x-transition class="mt-4 rounded-xl border border-danger/30 bg-danger-soft px-3 py-2 text-sm text-danger"
                 x-text="error"></div>

            <form class="mt-4 space-y-4" @submit.prevent="submit()">
                <div>
                    <label class="label" for="password">Nueva contraseña</label>
                    <input id="password" :type="show ? 'text' : 'password'" class="input" x-model="password"
                           autocomplete="new-password" required minlength="8" maxlength="72" autofocus>
                    <p class="hint">Mínimo 8 caracteres.</p>
                </div>
                <div>
                    <label class="label" for="confirm">Confirmar contraseña</label>
                    <input id="confirm" :type="show ? 'text' : 'password'" class="input" x-model="confirm"
                           :class="confirm && confirm !== password && 'input-error'"
                           autocomplete="new-password" required>
                    <p class="field-error" x-show="confirm && confirm !== password">Las contraseñas no coinciden.</p>
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-muted">
                    <input type="checkbox" x-model="show" class="h-4 w-4 rounded border-line">
                    Mostrar contraseñas
                </label>
                <button type="submit" class="btn btn-primary w-full" :disabled="loading || !password || password !== confirm">
                    <span x-show="!loading">Guardar contraseña</span>
                    <span x-show="loading" x-cloak>Guardando…</span>
                </button>
            </form>
        </div>
    </template>

    <p class="mt-5 border-t border-line pt-4 text-center text-sm">
        <a class="font-semibold text-accent hover:opacity-80" :href="Api.url('/login')">Volver a iniciar sesión</a>
    </p>
</div>
