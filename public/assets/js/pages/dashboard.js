document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.data('dashboardPage', () => ({
        stats: [],
        recent: [],
        loading: true,

        async init() {
            try {
                const res = await Api.get('/api/dashboard');
                this.stats = res.data.stats || [];
                this.recent = res.data.recent || [];
            } catch (e) {
                Alpine.store('toast').error(e.message);
            } finally {
                this.loading = false;
            }
        },
    }));
});
