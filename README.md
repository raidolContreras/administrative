# Template Administrativo — PHP 8.2 puro

Template reutilizable para vender sistemas administrativos a negocios pequeños (veterinarias, gimnasios, bibliotecas, escuelas, consultorios, tiendas). Un núcleo común (~80%) + módulos verticales por giro (~20%).

**Stack:** PHP 8.2 sin frameworks · MySQL/MariaDB (PDO, prepared statements siempre) · API REST interna JSON · Frontend desacoplado con TailwindCSS + AlpineJS + Fetch · URLs amigables por `.htaccess` · Sesiones PHP (no JWT) · CSRF por token synchronizer.

**Regla de oro del frontend:** las vistas PHP son *shells* estructurales — jamás interpolan datos de negocio. Todo dato llega al navegador por `fetch` a `/api/*` (única excepción documentada: constantes de infraestructura como el base path, vía `<meta name="app:base">`).

---

## Arquitectura en 30 segundos

```
modules/  →  app/  →  core/          (dependencia en un solo sentido)
```

| Anillo | Qué contiene | ¿Se edita por cliente? |
|---|---|---|
| `core/` | Mini-framework: Router, Request/Response, Database, Session, Auth, Csrf, Validator, Model/Migrator, ErrorHandler, View | **Nunca** |
| `app/` | Módulo base: login, usuarios, configuración, dashboard, auditoría, archivos, instalador | **Nunca** |
| `modules/<Vertical>/` | El giro del negocio: modelos, controladores, vistas, rutas, migraciones, seeds | **Sí** |
| `config/` + `.env` | Qué módulos están activos y credenciales de la instalación | **Sí** |

Un módulo se conecta por **6 puntos de extensión**: rutas api, rutas web, menú, migraciones, seeds y widgets de dashboard (ver `modules/Vet/module.php` como ejemplo canónico). Prohibido que un módulo dependa de otro módulo.

**Mantenimiento multi-cliente:** este repo es el *upstream*. Cada cliente es un clon. Los fixes se hacen aquí y se propagan con `git pull upstream main` en cada cliente. Como `core/` y `app/` nunca se tocan por cliente, los merges son triviales.

---

## Requisitos del servidor

- PHP ≥ 8.2 con `pdo_mysql`, `mbstring`, `fileinfo`
- MySQL 5.7+ / MariaDB 10.4+
- Apache con `mod_rewrite` (`AllowOverride All`)
- **No requiere** Composer ni Node en el servidor (autoloader con fallback propio; CSS precompilado)

Diagnóstico en cualquier hosting: `GET /api/health`.

## Desarrollo local (Windows + XAMPP)

```powershell
composer install                       # opcional (hay autoloader de respaldo)
copy .env.example .env                 # ajustar DB_*
C:\xampp\mysql\bin\mysql -u root -e "CREATE DATABASE admin_template CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php bin/console migrate
php bin/console seed
php bin/console user:create-admin --name="Admin" --email=admin@demo.mx --password=Demo12345
php bin/console serve                  # http://127.0.0.1:8000
```

Con Apache de XAMPP (modo cPanel real, con `.htaccess`): crear una junction
`New-Item -ItemType Junction -Path C:\xampp\htdocs\admtest -Target <ruta-del-proyecto>` y abrir `http://localhost/admtest/`.

### Compilar CSS (solo en desarrollo)

Tailwind se compila con el **CLI standalone** (binario en `bin/`, sin Node) y el resultado `public/assets/css/app.css` **se versiona**:

```powershell
.\bin\tailwindcss.exe -i resources\css\app.css -o public\assets\css\app.css --minify
```

Ejecutar cada vez que agregues clases nuevas en vistas o JS. Alpine se sirve local desde `assets/js/vendor/`.

## Desplegar un cliente

**Modo A — VPS:** DocumentRoot → `public/`. El `.htaccess` raíz no interviene.

**Modo B — cPanel compartido:** subir TODO el proyecto a `public_html/` (o un subdirectorio). El `.htaccess` raíz reenvía a `public/` y bloquea los directorios de código; cada directorio de código además trae su propio `Require all denied`. Soporta subdirectorios (`midominio.com/sistema/`) sin configurar nada.

**Primera ejecución sin SSH:** entrar a `/instalar` — prueba la conexión MySQL, escribe el `.env` (solo si no existe), migra, siembra y crea el primer administrador. Se autodesactiva en cuanto existe un usuario. Con SSH, el equivalente es: `.env` a mano + `migrate` + `seed core` + `user:create-admin`.

**Checklist post-despliegue:** `/api/health` en verde · `https://` forzado desde cPanel · `GET /.env` devuelve 403 · login OK · respaldo programado (cron: `php bin/console db:backup`).

## Consola

```
php bin/console <comando>

migrate | migrate:rollback [--steps=N] | migrate:status
seed [nombre]                          p.ej. core, vet:demo
module:list | module:enable <m> | module:disable <m>
user:create-admin --name= --email= --password=
make:crud <modulo> <Entidad> --route= --fields= [--table=] [--label=]
db:backup [--out=ruta.sql]             respaldo SQL sin mysqldump
serve [--host=] [--port=]              servidor de desarrollo
```

## Crear un vertical nuevo (la receta 80/20)

1. Generar el primer CRUD (crea también el esqueleto del módulo si no existe):

```powershell
php bin/console make:crud gym Member --route=socios --label=Socios `
  --fields="name:string:120,phone:string:30?,email:email?,plan:string:60,active:bool,joined_at:date"
```

2. Añadir el ítem de menú que imprime el comando a `modules/Gym/module.php`.
3. `php bin/console module:enable gym` (activa y migra).
4. Ajustar el shell generado (selects con catálogo, columnas) y las reglas del controlador.
5. Widgets de dashboard: método estático que devuelve `[['label','value','icon']]`, declarado en el manifest (ver `modules/Vet/Support/DashboardWidgets.php`).

Tipos de campo: `string[:len] · text · int · decimal · bool · date · datetime · email · ref:tabla` (+ sufijo `?` = opcional). Cada relación `ref` genera `exists:` en la validación; cambia su input numérico por un `<select>` con catálogo (patrón en `modules/Vet/Views/pets.php`).

## Contrato de la API

- Éxito: `{ "success": true, "data": …, "meta": … }` — errores: `{ "success": false, "error": { "code", "message", "details" } }`
- Códigos: 200/201/204 · 401 `UNAUTHENTICATED` · 403 `FORBIDDEN`/`CSRF_MISMATCH` · 404 · 409 · 422 con `details` por campo · 429 rate limit
- Listados: `?page= &per_page= &q= &sort= &dir= &<filtros>` — `sort` y filtros validan contra whitelist del modelo (jamás SQL directo)
- `GET /api/bootstrap`: usuario, menú por rol, token CSRF y settings públicos — el único fetch de arranque por página
- Los decimales viajan como *string* (dinero nunca se opera con floats en JS)

## Reglas del frontend

- `api.js` es la única puerta a la API: credenciales same-origin, CSRF automático, 401 → login, 403 CSRF → refresh + 1 reintento, 422 → errores por campo.
- Componentes genéricos (exactamente 4): `dataTable`, `formModal`, `$store.confirm`, `$store.toast`. Los formularios de cada vertical se escriben a mano — son el 20%.
- **Siempre `x-text`, nunca `x-html`** con datos de la API (el XSS no desaparece por no renderizar PHP; se mueve al navegador).
- Branding en runtime: `settings.primary_color` → CSS var `--brand`; logo y nombre llegan por bootstrap.

## Seguridad incluida

Sesiones endurecidas (HttpOnly, SameSite=Lax, Secure auto, strict mode, save_path propio, `session_regenerate_id` en login, expiración por inactividad) · CSRF en 3 capas (token + `Sec-Fetch-Site` + SameSite) · rate limit de login por correo+IP con tabla `login_attempts` · prepared statements reales (`EMULATE_PREPARES=false`) · whitelist de `ORDER BY`/filtros · campos ocultos (`password_hash`) fuera de respuestas Y de auditoría · uploads privados en `storage/` servidos con auth + `finfo` + nombre regenerado · `public/uploads` sin ejecución PHP · CSP + X-Frame-Options + nosniff · errores JSON limpios sin stack trace en producción (logs en `storage/logs/`) · auditoría automática de todo el CRUD · `session_write_close()` en lecturas para no serializar fetch paralelos.

**Nota CSP:** Alpine estándar requiere `'unsafe-eval'` (decisión documentada; migrar al build CSP de Alpine implica reescribir la sintaxis de las vistas).

## Estructura

```
public/          único directorio expuesto (index.php, .htaccess, assets)
core/            mini-framework genérico            ← no tocar por cliente
app/             módulo base (auth, usuarios, …)    ← no tocar por cliente
modules/<X>/     verticales (module.php + rutas/modelos/vistas/migraciones/seeds)
config/          .env loader, módulos activos, rutas, composition root (routes.php)
database/        migraciones y seeds del núcleo
storage/         logs, sesiones, uploads privados, backups (todo denegado al navegador)
resources/css/   fuente de Tailwind (se compila a public/assets/css/app.css)
bin/console      consola de operación
```
