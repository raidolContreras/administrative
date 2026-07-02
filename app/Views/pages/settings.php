<?php /* Configuración del negocio: branding en runtime (los valores llegan por la API) */ ?>
<div x-data="settingsPage" class="max-w-2xl">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-strong">Configuración</h1>
        <p class="text-sm text-muted">Identidad del negocio. Los cambios aplican de inmediato para todos.</p>
    </div>

    <form class="card space-y-5 p-6" @submit.prevent="save()">
        <div>
            <label class="label">Nombre del negocio</label>
            <input type="text" class="input" x-model="values.app_name" required maxlength="80">
        </div>

        <!-- Colores de la marca (el admin puede cambiarlos cuando quiera) -->
        <div>
            <label class="label">Colores de la marca</label>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="flex items-center gap-3 rounded-xl border border-line p-3">
                    <input type="color" class="h-10 w-14 cursor-pointer rounded-lg border border-line"
                           x-model="values.primary_color">
                    <div>
                        <p class="text-sm font-semibold text-strong">Principal</p>
                        <p class="text-xs text-muted" x-text="values.primary_color"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-line p-3">
                    <input type="color" class="h-10 w-14 cursor-pointer rounded-lg border border-line"
                           x-model="values.secondary_color">
                    <div>
                        <p class="text-sm font-semibold text-strong">Secundario</p>
                        <p class="text-xs text-muted">Degradados y detalles</p>
                    </div>
                </div>
            </div>

            <!-- Paletas sugeridas -->
            <p class="mt-3 text-xs font-semibold text-muted">Paletas sugeridas</p>
            <div class="mt-2 flex flex-wrap gap-2">
                <template x-for="p in presets" :key="p.name">
                    <button type="button"
                            class="flex items-center gap-2 rounded-full border border-line py-1.5 pl-1.5 pr-3 text-xs font-semibold text-body hover:bg-subtle"
                            @click="applyPreset(p)" :title="p.name">
                        <span class="h-6 w-6 rounded-full"
                              :style="'background-image: linear-gradient(135deg,' + p.a + ',' + p.b + ')'"></span>
                        <span x-text="p.name"></span>
                    </button>
                </template>
            </div>

            <!-- Vista previa en vivo -->
            <div class="mt-3 overflow-hidden rounded-xl border border-line">
                <div class="flex items-center justify-between p-4 text-white"
                     :style="'background-image: linear-gradient(140deg,' + values.primary_color + ',' + values.secondary_color + ')'">
                    <div>
                        <p class="text-xs font-semibold opacity-90">Vista previa</p>
                        <p class="text-xl font-extrabold tracking-tight" x-text="values.app_name || 'Tu negocio'"></p>
                    </div>
                    <span class="rounded-xl px-3.5 py-2 text-sm font-semibold"
                          :style="'background-color:' + values.primary_color + '; box-shadow: 0 0 0 3px rgba(255,255,255,.35)'">Botón</span>
                </div>
            </div>
            <p class="hint">Los colores se aplican a botones, menú, degradados y acentos en todo el sistema, al guardar.</p>
        </div>

        <div>
            <label class="label">Moneda</label>
            <select class="input" x-model="values.currency">
                <option value="MXN">Peso mexicano (MXN)</option>
                <option value="USD">Dólar (USD)</option>
                <option value="GTQ">Quetzal (GTQ)</option>
                <option value="COP">Peso colombiano (COP)</option>
            </select>
        </div>

        <div>
            <label class="label">Logo</label>
            <div class="flex items-center gap-4">
                <img x-show="logoPreview" :src="logoPreview" class="h-14 w-14 rounded-lg border border-line bg-surface object-cover" alt="">
                <div x-show="!logoPreview" class="flex h-14 w-14 items-center justify-center rounded-lg border border-dashed border-line text-faint">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/>
                    </svg>
                </div>
                <label class="btn btn-secondary cursor-pointer">
                    Elegir imagen
                    <input type="file" class="hidden" accept="image/png,image/jpeg,image/webp" @change="pickLogo($event)">
                </label>
            </div>
            <p class="hint">PNG, JPG o WebP · máximo 2 MB. Se muestra en el menú y en la pantalla de acceso.</p>
        </div>

        <div class="flex justify-end border-t border-line pt-4">
            <button type="submit" class="btn btn-primary" :disabled="saving">
                <span x-show="!saving">Guardar cambios</span>
                <span x-show="saving" x-cloak>Guardando…</span>
            </button>
        </div>
    </form>
</div>
