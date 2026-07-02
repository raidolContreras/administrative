<?php /* Instalador de primera ejecución (se autodesactiva cuando ya existe un usuario) */ ?>
<div class="w-full max-w-lg" x-data="installPage" x-cloak x-show="$store.app.ready">
    <div class="card p-6">
        <h2 class="text-base font-semibold text-slate-900">Instalación inicial</h2>
        <p class="mt-1 text-sm text-slate-500">Configura la base de datos y crea el primer administrador.</p>

        <!-- Requisitos -->
        <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Requisitos del servidor</p>
            <ul class="mt-2 space-y-1 text-sm">
                <template x-for="check in checks" :key="check.label">
                    <li class="flex items-center gap-2">
                        <span x-text="check.ok ? '✅' : '❌'"></span>
                        <span :class="check.ok ? 'text-slate-600' : 'font-semibold text-red-600'" x-text="check.label"></span>
                    </li>
                </template>
            </ul>
        </div>

        <form class="mt-5 space-y-4" @submit.prevent="submit()">
            <div>
                <label class="label">Nombre del negocio</label>
                <input type="text" class="input" :class="err('app_name') && 'input-error'" x-model="form.app_name"
                       placeholder="Veterinaria San Martín" required>
                <p class="field-error" x-show="err('app_name')" x-text="err('app_name')"></p>
            </div>

            <p class="border-t border-slate-100 pt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Base de datos MySQL</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Servidor</label>
                    <input type="text" class="input" :class="err('db_host') && 'input-error'" x-model="form.db_host" required>
                    <p class="field-error" x-show="err('db_host')" x-text="err('db_host')"></p>
                </div>
                <div>
                    <label class="label">Puerto</label>
                    <input type="number" class="input" x-model="form.db_port" placeholder="3306">
                </div>
            </div>
            <div>
                <label class="label">Base de datos</label>
                <input type="text" class="input" :class="err('db_database') && 'input-error'" x-model="form.db_database" required>
                <p class="field-error" x-show="err('db_database')" x-text="err('db_database')"></p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Usuario</label>
                    <input type="text" class="input" :class="err('db_username') && 'input-error'" x-model="form.db_username" required>
                    <p class="field-error" x-show="err('db_username')" x-text="err('db_username')"></p>
                </div>
                <div>
                    <label class="label">Contraseña</label>
                    <input type="password" class="input" x-model="form.db_password" autocomplete="off">
                </div>
            </div>

            <p class="border-t border-slate-100 pt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Primer administrador</p>
            <div>
                <label class="label">Nombre</label>
                <input type="text" class="input" :class="err('admin_name') && 'input-error'" x-model="form.admin_name" required>
                <p class="field-error" x-show="err('admin_name')" x-text="err('admin_name')"></p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Correo</label>
                    <input type="email" class="input" :class="err('admin_email') && 'input-error'" x-model="form.admin_email" required>
                    <p class="field-error" x-show="err('admin_email')" x-text="err('admin_email')"></p>
                </div>
                <div>
                    <label class="label">Contraseña (mín. 8)</label>
                    <input type="password" class="input" :class="err('admin_password') && 'input-error'"
                           x-model="form.admin_password" autocomplete="new-password" required minlength="8">
                    <p class="field-error" x-show="err('admin_password')" x-text="err('admin_password')"></p>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full" :disabled="installing">
                <span x-show="!installing">Instalar sistema</span>
                <span x-show="installing" x-cloak>Instalando…</span>
            </button>
        </form>
    </div>
</div>
