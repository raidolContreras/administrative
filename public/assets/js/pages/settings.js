document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.data('settingsPage', () => ({
        values: { app_name: '', primary_color: '#6366f1', secondary_color: '#8b5cf6', currency: 'MXN' },
        logoPreview: '',
        logoFile: null,
        saving: false,

        // Paletas sugeridas por giro de negocio (primario → secundario del degradado)
        presets: [
            { name: 'Índigo', a: '#6366f1', b: '#8b5cf6' },
            { name: 'Océano', a: '#0284c7', b: '#22d3ee' },
            { name: 'Salvia', a: '#0fa598', b: '#34d399' },
            { name: 'Brasa', a: '#ea5a0c', b: '#f59e0b' },
            { name: 'Rosa', a: '#db2777', b: '#f472b6' },
            { name: 'Grafito', a: '#334155', b: '#64748b' },
        ],
        applyPreset(p) {
            this.values.primary_color = p.a;
            this.values.secondary_color = p.b;
        },

        async init() {
            try {
                const res = await Api.get('/api/configuracion');
                for (const row of res.data) {
                    if (row.key in this.values) {
                        this.values[row.key] = row.value || this.values[row.key];
                    }
                    if (row.key === 'logo_path' && row.value) {
                        this.logoPreview = Api.base + row.value;
                    }
                }
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },

        pickLogo(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.logoFile = file;
            this.logoPreview = URL.createObjectURL(file);
        },

        async save() {
            this.saving = true;
            try {
                if (this.logoFile) {
                    const fd = new FormData();
                    fd.append('logo', this.logoFile);
                    await Api.upload('/api/configuracion/logo', fd);
                    this.logoFile = null;
                }
                await Api.put('/api/configuracion', { values: this.values });
                // refrescar branding en vivo (menú, título, color)
                const boot = await Api.bootstrap(true);
                Alpine.store('app').applyBranding(boot);
                Alpine.store('toast').ok('Configuración guardada.');
            } catch (e) {
                Alpine.store('toast').error(e.message);
            } finally {
                this.saving = false;
            }
        },
    }));
});
