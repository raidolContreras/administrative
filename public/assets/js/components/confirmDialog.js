/* Diálogo de confirmación basado en promesas: await $store.confirm.ask(...) */
document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.store('confirm', {
        open: false,
        title: '',
        message: '',
        danger: true,
        _resolve: null,

        ask(title, message, danger = true) {
            this.title = title;
            this.message = message;
            this.danger = danger;
            this.open = true;
            return new Promise((resolve) => {
                this._resolve = resolve;
            });
        },
        answer(value) {
            this.open = false;
            if (this._resolve) {
                this._resolve(value);
                this._resolve = null;
            }
        },
    });
});
