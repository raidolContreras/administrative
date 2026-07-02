document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.data('profilePage', () => ({
        form: { current_password: '', new_password: '' },
        confirm: '',
        errors: {},
        saving: false,

        get mismatch() {
            return this.confirm !== '' && this.confirm !== this.form.new_password;
        },
        err(field) {
            return (this.errors[field] || [])[0];
        },

        async save() {
            if (this.mismatch) return;
            this.saving = true;
            this.errors = {};
            try {
                await Api.post('/api/auth/password', this.form);
                this.form = { current_password: '', new_password: '' };
                this.confirm = '';
                Alpine.store('toast').ok('Contraseña actualizada.');
            } catch (e) {
                if (e.status === 422) {
                    this.errors = e.details || {};
                } else {
                    Alpine.store('toast').error(e.message);
                }
            } finally {
                this.saving = false;
            }
        },
    }));
});
