/* Notificaciones flotantes (el contenedor vive en el layout) */
document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.store('toast', {
        items: [],
        _id: 0,
        push(type, message) {
            const id = ++this._id;
            this.items.push({ id, type, message });
            setTimeout(() => this.dismiss(id), 4500);
        },
        ok(message) {
            this.push('ok', message);
        },
        error(message) {
            this.push('error', message || 'Ocurrió un error inesperado.');
        },
        dismiss(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },
    });
});
