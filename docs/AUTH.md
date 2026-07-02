# Módulo de autenticación

Autenticación por **sesiones PHP nativas** (sin JWT), con CSRF, rate limiting,
recuperación de contraseña por correo y registro de accesos. Todo el código es
parte del **núcleo reutilizable** (`core/` + `app/`): cada cliente lo recibe igual.

## Mapa del módulo

| Pieza | Archivo | Rol |
|---|---|---|
| Mecánica de sesión | [core/Session.php](../core/Session.php) | Cookies endurecidas, `readOnly()` (libera el lock), regeneración |
| Mecánica de auth | [core/Auth.php](../core/Auth.php) | login/logout/check, expiración por inactividad, resolver de usuario |
| CSRF | [core/Csrf.php](../core/Csrf.php) | Token por sesión, rotación, `hash_equals` |
| Controlador REST | [app/Controllers/Api/AuthController.php](../app/Controllers/Api/AuthController.php) | login, logout, me, cambio, forgot, reset |
| Service | [app/Services/PasswordResetService.php](../app/Services/PasswordResetService.php) | Orquesta la recuperación (tokens + correo + rate limit) |
| Correo | [app/Support/Mailer.php](../app/Support/Mailer.php) | `mail()` nativo + fallback a `storage/logs/mail.log` |
| Modelos | [User](../app/Models/User.php) · [LoginAttempt](../app/Models/LoginAttempt.php) · [PasswordReset](../app/Models/PasswordReset.php) | Datos con prepared statements |
| Middlewares | [Auth](../app/Middleware/AuthMiddleware.php) · [Guest](../app/Middleware/GuestMiddleware.php) · [Role](../app/Middleware/RoleMiddleware.php) · [Csrf](../app/Middleware/CsrfMiddleware.php) · [ReadOnly](../app/Middleware/ReadOnlyMiddleware.php) | Cadena por ruta |
| SQL | migraciones `0001` (users), `0004` (login_attempts), `0006`–`0007` (password_resets) | Esquema versionado |
| UI | `/login`, `/recuperar`, `/restablecer`, `/perfil` + `js/pages/*.js` | Shells sin datos; todo por fetch |

## Endpoints REST

| Método | Ruta | Middlewares | Descripción |
|---|---|---|---|
| POST | `/api/auth/login` | `csrf` | Inicia sesión. 401 genérico, 429 con rate limit |
| POST | `/api/auth/logout` | `auth, csrf` | Destruye la sesión |
| GET | `/api/auth/me` | `auth, readonly` | Usuario actual (hidrata el frontend) |
| POST | `/api/auth/password` | `auth, csrf` | Cambio de contraseña propio (exige la actual) |
| POST | `/api/auth/forgot` | `csrf` | Solicita enlace de recuperación. **Siempre 200 genérico** |
| POST | `/api/auth/reset` | `csrf` | Restablece con token. 422 `INVALID_TOKEN` si no sirve |
| GET | `/api/bootstrap` | — | Usuario + menú + **token CSRF** + settings públicos |

Los shells: `/login`, `/recuperar` y `/restablecer` llevan middleware `guest`
(si ya hay sesión → redirect a `/`); las páginas internas llevan `auth`.

## Flujos

**Login** — `login.js` envía `POST /api/auth/login` (con `X-CSRF-Token` del bootstrap de la
sesión anónima) → rate limit → `password_verify` → `session_regenerate_id(true)` → rota CSRF
→ responde usuario + token nuevo → el front redirige (solo a rutas internas, valida `next`).

**Recuperación** — `/recuperar` envía `POST /api/auth/forgot {email}` → el Service:
rate limit por correo (3/15 min, silencioso) → registra el acceso → si la cuenta existe y está
activa, emite token (64 hex, hash SHA-256 en BD, 60 min, único por correo) → correo con
`{APP_URL}/restablecer?token=…` → **respuesta idéntica en todos los casos**.
`/restablecer` envía `POST /api/auth/reset {token, password}` → valida por hash y vigencia →
`password_hash` nuevo → consume TODOS los tokens del correo (un solo uso) → registra
`reset-ok:` en el log de accesos → el front redirige a `/login`.

**Cambio de contraseña** (autenticado, en `/perfil`) — exige la contraseña actual; el error
llega como 422 sobre el campo, igual que cualquier validación.

## Decisiones arquitectónicas (y por qué)

1. **Sesiones PHP, no JWT.** Panel same-origin: la cookie `HttpOnly` es inmune a robo por XSS,
   la revocación es inmediata (destruir sesión) y no hay gestión de expiración/refresh en el
   cliente. JWT solo pagaría su complejidad con apps móviles o APIs de terceros.
2. **CSRF de triple capa.** Token synchronizer (sesión ↔ header `X-CSRF-Token`, comparado con
   `hash_equals`) + cookie `SameSite=Lax` + verificación `Sec-Fetch-Site`. El token viaja en el
   JSON de `/api/bootstrap` porque el frontend jamás renderiza PHP; se **rota en login/logout**
   (anti fijación de token).
3. **Tokens de recuperación hasheados (SHA-256) en BD.** Mismo principio que las contraseñas:
   una fuga de BD no permite tomar cuentas. SHA-256 simple (no bcrypt) porque el token ya tiene
   256 bits de entropía — no hay nada que "estirar".
4. **Anti-enumeración estricta.** Login: mensaje idéntico exista o no el correo. Forgot:
   respuesta 200 idéntica siempre (incluso bajo rate limit, para no crear otro oráculo).
5. **Rate limiting en MySQL** (tabla `login_attempts`), sin Redis: portable a cualquier cPanel.
   Login: 5 fallos/15 min por correo **o** IP. Recuperación: 3 solicitudes/15 min por correo,
   registradas con prefijo `reset:` y `success=1` para **no contaminar** el contador de fallos
   del login (que solo cuenta `success=0`) — el usuario bloqueado por fallos es exactamente el
   que necesita recuperar su contraseña.
6. **Registro de accesos** en la misma tabla: intentos de login (éxito/fallo, IP), solicitudes
   de recuperación (`reset:email`) y restablecimientos consumados (`reset-ok:email`). GC a 7 días.
   Además, el cambio de hash queda en `audit_log` vía el Model base — con `password_hash`
   excluido por `$hidden` (la auditoría **nunca** ve hashes).
7. **`password_hash()` con `PASSWORD_DEFAULT`** y columna de 255: el algoritmo puede migrar
   (bcrypt→argon2) sin tocar esquema. Entrada limitada a 72 bytes (tope real de bcrypt).
8. **Un Service, no una capa de Services.** La arquitectura es Controller→Model; solo la
   recuperación amerita Service porque coordina varias piezas (rate limit + token + correo +
   update). Regla: Services solo para lógica compuesta real, no por ceremonia.
9. **`mail()` nativo con fallback a log.** Único mecanismo garantizado en hosting compartido
   sin credenciales SMTP. En `APP_ENV=local` (o sin `MAIL_FROM`) el correo se escribe en
   `storage/logs/mail.log` — en desarrollo copias el enlace de ahí. Si un cliente exige SMTP,
   `Mailer::send()` es el único punto a sustituir. Sin datos del usuario en headers → sin
   inyección de cabeceras.
10. **`APP_URL` para los enlaces del correo.** Si está configurado, el enlace no depende del
    header `Host` (anti host-header poisoning). Sin configurar, se deriva de la petición
    (aceptable en dev; recomendado fijarlo en producción).
11. **Expiración por inactividad en servidor** (`SESSION_IDLE_MINUTES`, marca `la` en sesión) y
    `session_regenerate_id(true)` **solo** al autenticar (por request rompería fetch paralelos).
12. **Middleware `readonly`** (`session_write_close()`) en todos los GET: los fetch simultáneos
    del panel no se serializan por el lock del archivo de sesión.

## Configuración

| `.env` | Default | Uso |
|---|---|---|
| `SESSION_NAME` / `SESSION_IDLE_MINUTES` / `SESSION_SECURE` | `adm_session` / `120` / `auto` | Cookie y expiración |
| `APP_URL` | *(vacío)* | Base de los enlaces de correo (recomendado en prod) |
| `MAIL_FROM` / `MAIL_FROM_NAME` | *(vacío)* | Remitente; vacío = solo log |

En `config/config.php → security`: `login_max_attempts` (5), `login_window_minutes` (15),
`reset_max_requests` (3), `reset_window_minutes` (15), `reset_token_minutes` (60).

## Limitaciones conocidas (v1, documentadas a propósito)

- **Restablecer no cierra otras sesiones abiertas** del usuario: las sesiones son archivos sin
  índice usuario→sesión. Mitigación natural: expiración por inactividad. Si se vuelve requisito,
  se añade `session_version` en `users` y verificación en `Auth::check()`.
- El token viaja como query string (`?token=…`): correcto aquí porque la página no carga
  recursos externos (no hay fuga por `Referer`) y el token es de un solo uso y corta vida.
- `mail()` sin SPF/DKIM puede caer en spam según el hosting — configurarlos en cPanel del cliente.

## Verificación rápida

1. `/login` → credenciales malas ×5 → 429; correctas → entra y el token CSRF rota.
2. "¿Olvidaste tu contraseña?" → correo → misma respuesta con un correo inexistente.
3. En dev: copiar el enlace desde `storage/logs/mail.log` → `/restablecer?token=…` → nueva
   contraseña → login con ella. Reusar el mismo enlace → "no es válido o ya expiró".
4. 4ª solicitud en 15 min → misma respuesta genérica, sin token nuevo en BD.
5. `login_attempts`: filas `reset:` y `reset-ok:`; `audit_log`: update de users sin hash.
