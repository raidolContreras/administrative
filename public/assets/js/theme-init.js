/*
 * Anti-FOUC: fija data-theme en <html> ANTES de pintar, según la preferencia
 * guardada (localStorage 'sereno-theme' = system|light|dark) o el sistema.
 * Se carga como <script> bloqueante en <head> (archivo externo por la CSP,
 * que no permite scripts inline). Debe ser mínimo y sin dependencias.
 */
(function () {
    try {
        var mode = localStorage.getItem('sereno-theme') || 'system';
        var dark = mode === 'dark'
            || (mode !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.dataset.theme = dark ? 'dark' : 'light';
    } catch (e) {
        document.documentElement.dataset.theme = 'light';
    }
})();
