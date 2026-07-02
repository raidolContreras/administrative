/*
 * Modal de alta/edición genérico contra un recurso REST.
 * El shell define los campos del formulario (el 20% específico de cada vertical);
 * este componente gestiona estado, envío, errores 422 por campo y eliminación.
 *
 *   x-data="formModal({ url: '/api/usuarios', defaults: { role: 'employee' }, pick: ['name','email'] })"
 */
document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.data('formModal', (cfg = {}) => ({
        open: false,
        mode: 'create',
        saving: false,
        errors: {},
        form: {},
        id: null,

        openCreate(extra = {}) {
            this.mode = 'create';
            this.id = null;
            this.errors = {};
            this.form = { ...(cfg.defaults || {}), ...extra };
            this.open = true;
        },
        openEdit(row) {
            this.mode = 'edit';
            this.id = row.id;
            this.errors = {};
            const source = Array.isArray(cfg.pick) && cfg.pick.length
                ? Object.fromEntries(cfg.pick.map((k) => [k, row[k]]))
                : { ...row };
            this.form = { ...(cfg.defaults || {}), ...source };
            this.open = true;
        },
        close() {
            this.open = false;
        },
        err(field) {
            return (this.errors[field] || [])[0];
        },

        async submit() {
            this.saving = true;
            this.errors = {};
            try {
                this.mode === 'create'
                    ? await Api.post(cfg.url, this.form)
                    : await Api.put(cfg.url + '/' + this.id, this.form);
                this.open = false;
                Alpine.store('toast').ok(this.mode === 'create' ? 'Registro creado.' : 'Cambios guardados.');
                window.dispatchEvent(new CustomEvent(cfg.refreshEvent || 'dt-refresh'));
            } catch (e) {
                if (e.status === 422) {
                    this.errors = e.details || {};
                    Alpine.store('toast').error('Revisa los campos marcados.');
                } else {
                    Alpine.store('toast').error(e.message);
                }
            } finally {
                this.saving = false;
            }
        },

        async destroy(row, label) {
            const ok = await Alpine.store('confirm').ask(
                'Eliminar registro',
                `¿Seguro que deseas eliminar ${label || 'este registro'}? Esta acción no se puede deshacer.`
            );
            if (!ok) return;
            try {
                await Api.del(cfg.url + '/' + row.id);
                Alpine.store('toast').ok('Registro eliminado.');
                window.dispatchEvent(new CustomEvent(cfg.refreshEvent || 'dt-refresh'));
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },
    }));
});
