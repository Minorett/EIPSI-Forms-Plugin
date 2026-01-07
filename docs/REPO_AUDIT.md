# Auditoría de repositorio — EIPSI Forms (v1.2.2)

Fecha: 2026-01-07

## Objetivo
Reducir fricción para devs y evitar “ruido” en el repo:
- Ordenar documentación temporal sin perder historial
- Dejar `lint`/`format` en verde
- Revisar duplicados CSS y archivos “sueltos”
- Eliminar/mitigar vulnerabilidades (al menos `high`/`critical`)

---

## 1) Documentación temporal (root)
**Acción:** se creó `docs/completed-tickets/` y se **movieron** allí los archivos de tickets y resúmenes que estaban en la raíz.

- Mantuvimos en root: `README.md`, `CHANGELOG.md`, `LICENSE`
- Movidos a: `docs/completed-tickets/`
  - `TICKET_*.md`, `VAS_*.md`, `RESUMEN_*.md`
  - `IMPLEMENTATION_SUMMARY.md`, `DARK_MODE_REPLICATION_SUMMARY.md`, etc.
  - `REPLICATION_COMPLETE.txt`

**Verificación:** búsqueda de referencias a `TICKET_` → no hay referencias externas rotas.

---

## 2) Archivos de prueba temporal
### 2.1 Script `test-clinical-values.js`
**Acción:** se movió desde la raíz a:
- `scripts/test-clinical-values.js`

**Nota técnica:** se ajustó el `require()` a `../src/...` y se amplió el override de ESLint para permitir `console` en `scripts/test-*.js`.

### 2.2 HTML/PHP de pruebas manuales
**Acción adicional (limpieza de root):** se movieron `test-*.html` y `test-*.php` a:
- `dev-tests/`

---

## 3) Fixes de código (lint + prettier)
### 3.1 ESLint
- **Estado final:** `npm run lint:js` ✅ (0 errores)

Fix aplicado:
- `blocks/form-container/edit.js`: se evitó un falso positivo de i18n por el uso de `100%` (regla de placeholders). Se cambió el copy a **“no es completamente fiable”**.

### 3.2 Prettier
- `npm run format` ✅
- `npm run format -- --check` ✅

---

## 4) Vulnerabilidades (npm audit)
- Antes: `2 high` (transitivas vía `body-parser -> qs` dentro de `@wordpress/scripts`)
- Acción: `npm audit fix`
- Después: `npm audit --audit-level=high` ✅ `found 0 vulnerabilities`

---

## 5) Auditoría de duplicados en CSS
Se hizo un chequeo automático (heurístico) para detectar reglas **idénticas** duplicadas (mismo selector y mismo cuerpo) en:
- `assets/css/eipsi-forms.css`
- `assets/css/eipsi-save-continue.css`
- `assets/css/admin-style.css`
- `assets/css/configuration-panel.css`
- `assets/css/theme-toggle.css`

**Resultado:** 0 reglas duplicadas exactas detectadas.

### theme-toggle.css vs _theme-toggle.scss
- `theme-toggle.css`: versión minificada (runtime)
- `_theme-toggle.scss`: fuente legible (y referenciada por el sourcemap)

Se mantiene ambos por trazabilidad y DX. Si más adelante queremos “cero duplicación”, lo ideal sería introducir un paso de build SCSS→CSS y dejar el SCSS fuera de `assets/css/`.

---

## Validación final
- `npm run build` ✅ (compila con warnings de bundle, sin errores fatales)
- `npm run lint:js` ✅
- `npm run format -- --check` ✅
