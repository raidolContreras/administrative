document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.data('resetPage', () => ({
        token: new URLSearchParams(location.search).get('token') || '',
        password: '',
        confirm: '',
        show: false,
        error: '',
        loading: false,

        async init() {
            const boot = await Api.bootstrap();
            if (boot.authenticated) {
                location.href = Api.url('/');
            }
        },

        async submit() {
            if (this.password !== this.confirm) return;
            this.loading = true;
            this.error = '';
            try {
                await Api.post('/api/auth/reset', { token: this.token, password: this.password });
                Alpine.store('toast').ok('Contraseña actualizada. Ya puedes iniciar sesión.');
                setTimeout(() => { location.href = Api.url('/login'); }, 900);
            } catch (e) {
                // INVALID_TOKEN (vencido/usado) o validación de la contraseña
                this.error = e.message;
                this.loading = false;
            }
        },
    }));
});
