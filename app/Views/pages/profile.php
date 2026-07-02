<?php /* Perfil propio: datos de sesión + cambio de contraseña */ ?>
<div x-data="profilePage" class="max-w-xl space-y-6">
    <div>
        <h1 class="text-xl font-bold text-strong">Mi perfil</h1>
        <p class="text-sm text-muted">Tu cuenta en este sistema.</p>
    </div>

    <div class="card flex items-center gap-4 p-6">
        <span class="bg-brand-grad flex h-14 w-14 items-center justify-center rounded-full text-lg font-bold text-white"
              x-text="$store.app.user ? $store.app.user.name.charAt(0).toUpperCase() : ''"></span>
        <div class="min-w-0">
            <p class="truncate font-semibold text-strong" x-text="$store.app.user ? $store.app.user.name : ''"></p>
            <p class="truncate text-sm text-muted" x-text="$store.app.user ? $store.app.user.email : ''"></p>
            <span class="badge badge-brand mt-1"
                  x-text="$store.app.user && $store.app.user.role === 'admin' ? 'Administrador' : 'Empleado'"></span>
        </div>
    </div>

    <form class="card space-y-4 p-6" @submit.prevent="save()">
        <h2 class="text-sm font-semibold text-strong">Cambiar contraseña</h2>
        <div>
            <label class="label">Contraseña actual</label>
            <input type="password" class="input" :class="err('current_password') && 'input-error'"
                   x-model="form.current_password" autocomplete="current-password" required>
            <p class="field-error" x-show="err('current_password')" x-text="err('current_password')"></p>
        </div>
        <div>
            <label class="label">Nueva contraseña</label>
            <input type="password" class="input" :class="err('new_password') && 'input-error'"
                   x-model="form.new_password" autocomplete="new-password" required minlength="8">
            <p class="field-error" x-show="err('new_password')" x-text="err('new_password')"></p>
        </div>
        <div>
            <label class="label">Confirmar nueva contraseña</label>
            <input type="password" class="input" :class="mismatch && 'input-error'"
                   x-model="confirm" autocomplete="new-password" required>
            <p class="field-error" x-show="mismatch">Las contraseñas no coinciden.</p>
        </div>
        <div class="flex justify-end border-t border-line pt-4">
            <button type="submit" class="btn btn-primary" :disabled="saving || mismatch">
                <span x-show="!saving">Actualizar contraseña</span>
                <span x-show="saving" x-cloak>Guardando…</span>
            </button>
        </div>
    </form>
</div>
