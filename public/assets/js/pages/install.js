document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.data('installPage', () => ({
        checks: [],
        errors: {},
        installing: false,
        form: {
            app_name: '',
            db_host: 'localhost',
            db_port: '3306',
            db_database: '',
            db_username: '',
            db_password: '',
            admin_name: '',
            admin_email: '',
            admin_password: '',
        },

        async init() {
            const status = await Api.get('/api/install/status');
            if (status.data.installed) {
                location.href = Api.url('/login');
                return;
            }
            try {
                const health = await Api.get('/api/health');
                this.applyChecks(health.data.checks);
            } catch (e) {
                // health responde 503 si algo falla, pero igual trae los checks
                if (e.status === 503 && e.details) {
                    this.applyChecks(e.details);
                }
            }
        },

        applyChecks(checks) {
            const labels = {
                php: 'PHP 8.2 o superior',
                extensions: 'Extensiones requeridas (pdo_mysql, mbstring, fileinfo)',
                storage_writable: 'Permisos de escritura en storage/',
                database: 'Conexión a base de datos (se configura abajo)',
            };
            this.checks = Object.entries(labels).map(([key, label]) => ({
                label,
                ok: !!(checks && checks[key]),
            }));
        },

        err(field) {
            return (this.errors[field] || [])[0];
        },

        async submit() {
            this.installing = true;
            this.errors = {};
            try {
                await Api.post('/api/install', this.form);
                Alpine.store('toast').ok('Sistema instalado. Redirigiendo al acceso…');
                setTimeout(() => (location.href = Api.url('/login')), 1400);
            } catch (e) {
                if (e.status === 422) {
                    this.errors = e.details || {};
                    Alpine.store('toast').error('Revisa los campos marcados.');
                } else {
                    Alpine.store('toast').error(e.message);
                }
                this.installing = false;
            }
        },
    }));
});
