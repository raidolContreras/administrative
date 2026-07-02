document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.data('loginPage', () => ({
        email: '',
        password: '',
        error: '',
        loading: false,
        show: false,

        async init() {
            const boot = await Api.bootstrap();
            if (!boot.installed) {
                location.href = Api.url('/instalar');
                return;
            }
            if (boot.authenticated) {
                location.href = Api.url('/');
            }
        },

        async submit() {
            this.loading = true;
            this.error = '';
            try {
                const res = await Api.post('/api/auth/login', { email: this.email, password: this.password });
                Api.setCsrf(res.data.csrf);
                const next = new URLSearchParams(location.search).get('next');
                // solo rutas internas (nunca URLs absolutas de terceros)
                location.href = Api.url(next && next.startsWith('/') && !next.startsWith('//') ? next : '/');
            } catch (e) {
                this.error = e.status === 429
                    ? e.message
                    : (e.code === 'INVALID_CREDENTIALS' ? e.message : 'No se pudo iniciar sesión. ' + e.message);
                this.loading = false;
            }
        },
    }));
});
