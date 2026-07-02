document.addEventListener('alpine:init', () => {
    'use strict';

    const LABELS = { perro: 'Perro', gato: 'Gato', ave: 'Ave', reptil: 'Reptil', roedor: 'Roedor', otro: 'Otro' };

    Alpine.data('petsPage', () => ({
        owners: [],
        species: Object.keys(LABELS),

        speciesLabel(value) {
            return LABELS[value] || value;
        },

        async init() {
            try {
                const res = await Api.get('/api/duenos', { per_page: 100, sort: 'name', dir: 'asc' });
                this.owners = res.data;
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },
    }));
});
