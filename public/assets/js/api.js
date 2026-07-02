/*
 * api.js — ÚNICA puerta del frontend hacia el backend.
 * Ningún componente llama a fetch directamente. Centraliza:
 *  - base path (instalaciones en subdirectorio, meta app:base)
 *  - credenciales de sesión (same-origin) y token CSRF automático en mutaciones
 *  - contrato {success, data, meta} / {success:false, error:{code,message,details}}
 *  - 401 → redirección a login | 403 CSRF_MISMATCH → refresh de bootstrap + 1 reintento
 */
(() => {
    'use strict';

    const metaBase = document.querySelector('meta[name="app:base"]');
    const BASE = metaBase ? metaBase.content : '';
    let csrf = '';
    let bootCache = null;

    class ApiError extends Error {
        constructor(status, error) {
            super((error && error.message) || 'Error de conexión con el servidor.');
            this.status = status;
            this.code = (error && error.code) || 'UNKNOWN';
            this.details = (error && error.details) || {};
        }
    }

    async function request(method, path, { data, params } = {}, retried = false) {
        let url = BASE + path;
        if (params) {
            const qs = new URLSearchParams();
            Object.entries(params).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== '') {
                    qs.set(key, value);
                }
            });
            const encoded = qs.toString();
            if (encoded) {
                url += (url.includes('?') ? '&' : '?') + encoded;
            }
        }

        const headers = { Accept: 'application/json' };
        const init = { method, headers, credentials: 'same-origin' };
        if (data instanceof FormData) {
            init.body = data; // el navegador pone el boundary
        } else if (data !== undefined) {
            headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(data);
        }
        if (!['GET', 'HEAD'].includes(method)) {
            headers['X-CSRF-Token'] = csrf;
        }

        let res;
        try {
            res = await fetch(url, init);
        } catch (e) {
            throw new ApiError(0, { code: 'NETWORK', message: 'Sin conexión con el servidor.' });
        }

        if (res.status === 204) {
            return { success: true, data: null };
        }

        let payload = null;
        try {
            payload = await res.json();
        } catch (e) {
            /* respuesta no-JSON inesperada */
        }

        if (!res.ok) {
            const error = payload && payload.error ? payload.error : null;

            // Sesión vencida/inexistente → al login (nunca en el propio intento de login)
            if (res.status === 401 && error && error.code === 'UNAUTHENTICATED') {
                const next = encodeURIComponent(location.pathname.slice(BASE.length) + location.search);
                location.href = BASE + '/login?next=' + next;
                throw new ApiError(res.status, error);
            }
            // Token rotado (login/logout en otra pestaña) → refrescar bootstrap y reintentar UNA vez
            if (res.status === 403 && error && error.code === 'CSRF_MISMATCH' && !retried) {
                await Api.bootstrap(true);
                return request(method, path, { data, params }, true);
            }
            throw new ApiError(res.status, error);
        }

        return payload || { success: true, data: null };
    }

    const Api = {
        base: BASE,
        ApiError,
        url: (path) => BASE + path,
        get: (path, params) => request('GET', path, { params }),
        post: (path, data) => request('POST', path, { data }),
        put: (path, data) => request('PUT', path, { data }),
        patch: (path, data) => request('PATCH', path, { data }),
        del: (path) => request('DELETE', path),
        upload: (path, formData) => request('POST', path, { data: formData }),
        setCsrf(token) {
            csrf = token || '';
        },
        /** Un solo fetch de arranque por página: usuario, menú, CSRF y settings públicos */
        async bootstrap(force = false) {
            if (!bootCache || force) {
                const res = await request('GET', '/api/bootstrap');
                bootCache = res.data;
                csrf = bootCache.csrf || '';
            }
            return bootCache;
        },
    };

    window.Api = Api;
})();
