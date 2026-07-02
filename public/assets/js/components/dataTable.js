/*
 * Tabla server-side genérica. El shell define el markup (th/td); este componente
 * gestiona datos, paginación, búsqueda, orden y filtros contra la API.
 *
 *   x-data="dataTable({ url: '/api/usuarios', sort: 'name', dir: 'asc', filters: { role: '' } })"
 *
 * Recarga externa: window.dispatchEvent(new CustomEvent('dt-refresh'))
 * (o $dispatch('dt-refresh') desde cualquier componente, burbujea hasta window).
 */
document.addEventListener('alpine:init', () => {
    'use strict';

    Alpine.data('dataTable', (cfg = {}) => ({
        rows: [],
        meta: { page: 1, per_page: cfg.perPage || 15, total: 0, total_pages: 1 },
        q: '',
        sort: cfg.sort || null,
        dir: cfg.dir || 'asc',
        filters: cfg.filters || {},
        loading: true,
        failed: false,
        _refreshHandler: null,

        async init() {
            const debounced = Alpine.debounce(() => this.load(1), 350);
            this.$watch('q', debounced);
            this.$watch('filters', () => this.load(1));

            this._refreshHandler = () => this.load(this.meta.page);
            window.addEventListener(cfg.refreshEvent || 'dt-refresh', this._refreshHandler);

            await this.load(1);
        },
        destroy() {
            window.removeEventListener(cfg.refreshEvent || 'dt-refresh', this._refreshHandler);
        },

        async load(page) {
            this.loading = true;
            this.failed = false;
            try {
                const res = await Api.get(cfg.url, {
                    page: page || this.meta.page,
                    per_page: this.meta.per_page,
                    q: this.q || undefined,
                    sort: this.sort || undefined,
                    dir: this.sort ? this.dir : undefined,
                    ...this.filters,
                });
                this.rows = res.data || [];
                if (res.meta) {
                    this.meta = res.meta;
                }
            } catch (e) {
                this.failed = true;
                Alpine.store('toast').error(e.message);
            } finally {
                this.loading = false;
            }
        },

        sortBy(key) {
            if (this.sort === key) {
                this.dir = this.dir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sort = key;
                this.dir = 'asc';
            }
            this.load(1);
        },
        sortIcon(key) {
            if (this.sort !== key) return '';
            return this.dir === 'asc' ? '▲' : '▼';
        },
        prev() {
            if (this.meta.page > 1) this.load(this.meta.page - 1);
        },
        next() {
            if (this.meta.page < this.meta.total_pages) this.load(this.meta.page + 1);
        },
    }));
});
