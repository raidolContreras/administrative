document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.data('forgotPage', () => ({
        email: '',
        sent: false,
        message: '',
        loading: false,

        async init() {
            const boot = await Api.bootstrap();
            if (boot.authenticated) {
                location.href = Api.url('/');
            }
        },

        async submit() {
            this.loading = true;
            try {
                const res = await Api.post('/api/auth/forgot', { email: this.email });
                // Mensaje genérico del servidor: idéntico exista o no la cuenta
                this.message = res.data.message;
                this.sent = true;
            } catch (e) {
                Alpine.store('toast').error(e.message);
            } finally {
                this.loading = false;
            }
        },
    }));
});
