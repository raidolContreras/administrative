# Sereno · Sistema de diseño

Sistema de diseño del template administrativo. Minimalista, profesional y con el modo
oscuro como ciudadano de primera. Está pensado para **rebrandear por cliente en minutos**
cambiando unas pocas semillas, sin tocar las pantallas.

Decisiones de identidad (v1): neutro **frío elegante**, tipografía **Inter** (self-hosted),
radio de **10 px**, y tema **sistema / claro / oscuro** conmutable y persistido en `localStorage`.

Fuente de verdad: [`resources/css/app.css`](../resources/css/app.css) (Tailwind v4 `@theme`).
El CSS compilado vive en `public/assets/css/app.css` (se versiona; se recompila con el CLI).

---

## 1. Principios

1. **El contenido manda.** El chrome (menús, barras) es tenue; los datos y acciones son los protagonistas.
2. **El espacio es un material**, no un sobrante. Aire generoso y rítmico.
3. **Un solo acento.** El color de marca se usa con escasez (acción primaria, estado activo, foco). Los neutros cargan el resto.
4. **Jerarquía por tipografía y espacio, no por cajas.** Líneas de 1 px (hairline) antes que sombras.
5. **Oscuro es de primera clase.** Se diseña en paralelo, invirtiendo solo los tokens semánticos.
6. **Consistencia por tokens.** Todo deriva de variables → rebrandear = cambiar semillas.

## 2. Arquitectura de tokens (3 capas)

- **Capa 1 · Primitivos** — escalas crudas: `--ink-0…1000` (neutros), la semilla `--brand`, y los estados. No se tocan por cliente (salvo `--brand`).
- **Capa 2 · Semánticos** — lo que usan los componentes: `--canvas`, `--surface`, `--surface-2`, `--subtle`, `--line`, `--strong`, `--body`, `--muted`, `--faint`, `--accent`, estados y sus `-soft`. **Esta capa es la que se invierte** bajo `[data-theme="dark"]`.
- **Capa 3 · Forma/uso** — `--radius`, `--ring`, `--shadow-1/2/3`, `--scrim`.

Los semánticos se exponen como **utilidades de Tailwind** vía `@theme` (`--color-*`), por eso en las
vistas se escribe `bg-surface`, `text-muted`, `border-line`, `text-accent`, `bg-danger-soft`, etc.
en lugar de colores crudos. Nunca uses `bg-white` / `text-slate-*` en vistas: no se adaptan al tema.

## 3. Paleta

### Neutros "Tinta" (frío)
`--ink-0 #fff` · `50 #f6f7f9` · `100 #edeff2` · `200 #e1e4e9` · `300 #c9ced6` · `400 #98a0ab` ·
`500 #6a727d` · `600 #4b525c` · `700 #343a42` · `800 #22272d` · `900 #171b20` · `950 #0f1215` · `1000 #090b0d`

### Semánticos (claro → oscuro)
| Token | Claro | Oscuro |
|---|---|---|
| canvas | `#f6f7f9` | `#0f1215` |
| surface | `#ffffff` | `#171b20` |
| surface-2 | `#edeff2` | `#22272d` |
| subtle (fills/hover) | `#f1f3f5` | `#20252b` |
| line (bordes) | `#e1e4e9` | `#2a3037` |
| strong (títulos) | `#171b20` | `#f3f5f7` |
| body (texto) | `#343a42` | `#e7eaee` |
| muted | `#6a727d` | `#9aa2ad` |
| faint (placeholder) | `#98a0ab` | `#6b7480` |

### Estado
success `#16a34a`/`#34d399` · warning `#b4740b`/`#fbbf24` · danger `#dc2626`/`#f87171` · info `#2563eb`/`#60a5fa`.
Los fondos `*-soft` se calculan con `color-mix` sobre `--surface`, así se adaptan solos al tema.

### Semillas de marca por vertical (solo se cambia `--brand`)
| Vertical | Semilla |
|---|---|
| Veterinaria | `#0fa598` teal salvia |
| Gimnasio | `#ea5a0c` naranja brasa |
| Escuela | `#4f46e5` índigo |
| Consultorio | `#0e93b8` cian médico |
| Tienda | `#7c43e0` violeta |

Default de fábrica: `#0284c7`. En oscuro, `--accent` (la marca como texto/ícono) se aclara automáticamente para contraste.

## 4. Modo oscuro

- El tema se fija en `<html data-theme="light|dark">`.
- **Anti-FOUC:** [`public/assets/js/theme-init.js`](../public/assets/js/theme-init.js) se carga como `<script>` bloqueante en `<head>` (externo, porque la CSP no permite inline) y fija el atributo antes de pintar.
- **Estado y toggle:** el store Alpine `app` (`themeMode`, `setTheme()`, `applyTheme()`) persiste en `localStorage['sereno-theme']` y, en modo `system`, sigue a `matchMedia('(prefers-color-scheme: dark)')` en vivo.
- `color-scheme` acompaña para que scrollbars y controles nativos combinen.

## 5. Tipografía

Familia única **Inter** variable, self-hosted en `public/assets/fonts/` (latin + latin-ext), `font-display: swap`.
Escala (base UI 14 px): Display 32/40 · H1 24/32 · H2 20/28 · H3 16/24 · cuerpo 14/22 · secundario 13/20 ·
etiqueta 12/16 MAYÚS `0.04em` · dato grande 28–32. Pesos 400/500/600/700. **Cifras tabulares** en tablas y datos (`.tabular`, `.tbl`).

## 6. Espaciado y forma

Base 4 px: `4·8·12·16·20·24·32·40·48·64·80·96`. Radio de componentes `--radius: 10px` (botones, inputs, cards, modales; badges = full).
Elevación: hairline + superficies tonales; sombras (`--shadow-1/2/3`) solo para capas flotantes (dropdown, popover, modal). En oscuro, elevación por luminosidad.
Foco: `:focus-visible` con anillo de marca (accesibilidad AA).

## 7. Componentes (clases en `@layer components`)

`.btn` + `.btn-primary|secondary|danger|icon` · `.label` `.input` `.input-error` `.field-error` `.hint` ·
`.card` · `.badge` + `.badge-green|red|amber|slate|brand` · `.tbl` `.th-sort` · `.modal-backdrop` `.modal-panel` · `.spinner`.
Todos derivan de tokens, por lo que se adaptan a claro/oscuro sin cambios en el marcado.
Componentes de interacción (Alpine): data-table, form-modal, confirm y toast.

## 8. Rebrandear para un cliente nuevo

1. **Color:** `settings.primary_color` (el JS lo aplica a `--brand` en runtime; toda la rampa, foco, nav activo y tiles se recolorean).
2. **Forma:** ajustar `--radius` si se quiere una personalidad más nítida (6 px) o amable (16 px).
3. **Tipografía:** cambiar `--font-sans` (y el `@font-face`) si el cliente trae su marca tipográfica.
4. **Tema por defecto:** el usuario lo elige; el default es `system`.

Ninguno de estos cambios toca las vistas. Extensiones previstas de `settings`: `theme_default`, `ui_radius`, `ui_density`, `font_family`.

## 9. Recompilar el CSS

```
bin\tailwindcss.exe -i resources\css\app.css -o public\assets\css\app.css --minify
```

El binario del CLI (`bin/tailwindcss.exe`) no se versiona; descárgalo del release de Tailwind v4 para tu plataforma.
