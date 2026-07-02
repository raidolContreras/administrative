document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.data('citasPage', () => ({
        pets: [],

        async init() {
            try {
                const res = await Api.get('/api/mascotas', { per_page: 100, sort: 'name', dir: 'asc' });
                this.pets = res.data;
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },

        statusLabel(status) {
            return { programada: 'Programada', atendida: 'Atendida', cancelada: 'Cancelada' }[status] || status;
        },
        statusClass(status) {
            return { programada: 'badge-amber', atendida: 'badge-green', cancelada: 'badge-red' }[status] || 'badge-slate';
        },

        /* 'Y-m-d H:i:s' de la API ↔ 'Y-m-dTH:i' del input datetime-local */
        toLocal(value) {
            return value ? String(value).replace(' ', 'T').slice(0, 16) : '';
        },
        defaultSlot() {
            const d = new Date();
            d.setHours(d.getHours() + 1, 0, 0, 0);
            const pad = (n) => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:00`;
        },

        async markDone(row) {
            const ok = await Alpine.store('confirm').ask(
                'Marcar como atendida',
                `¿Confirmas que la cita de ${row.pet_name || 'la mascota'} ya fue atendida?`,
                false
            );
            if (!ok) return;
            try {
                await Api.put('/api/citas/' + row.id, {
                    pet_id: row.pet_id,
                    scheduled_at: row.scheduled_at,
                    reason: row.reason,
                    status: 'atendida',
                    notes: row.notes,
                });
                Alpine.store('toast').ok('Cita marcada como atendida.');
                window.dispatchEvent(new CustomEvent('dt-refresh'));
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },
    }));
});
