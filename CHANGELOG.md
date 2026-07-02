# Changelog

Todas las versiones notables del template. Cada proyecto-cliente registra aquí desde qué versión del core partió y a cuál se ha actualizado.

## [1.0.0] — 2026-07-01

Primera versión estable del template.

- Núcleo HTTP: front controller único, Router con parámetros `{id}` y `resource()`, middlewares, ErrorHandler JSON/HTML, URLs amigables con doble `.htaccess` (VPS y cPanel/subdirectorio).
- Datos: PDO endurecido, `Model` base con paginación server-side, búsqueda, whitelist de orden/filtros (con expresiones para JOINs), soft deletes, timestamps y auditoría automática; migrador multi-módulo con rollback.
- Seguridad: sesiones endurecidas con expiración por inactividad, CSRF en 3 capas entregado vía `/api/bootstrap`, rate limit de login, roles admin/empleado, uploads privados validados con finfo.
- Módulo base: login, dashboard con widgets extensibles, CRUD de usuarios con autoprotección (último admin), configuración con branding en runtime (color/logo/nombre), auditoría con detalle de cambios, perfil con cambio de contraseña, instalador web autodesactivable, `/api/health`.
- Frontend desacoplado: `api.js`, 4 componentes Alpine genéricos (dataTable, formModal, confirm, toast), Tailwind v4 compilado con CLI standalone, Alpine local.
- Sistema de módulos: manifest con 6 puntos de extensión, activación por config, `module:enable/disable`.
- Módulo de referencia **Vet**: dueños, mascotas (JOIN con dueño), citas (filtro por día, acción "atendida"), vacunas (generado con `make:crud`), seeds demo.
- Consola: `migrate`, `seed`, `module:*`, `user:create-admin`, `make:crud`, `db:backup` (sin mysqldump), `serve`.
