# Dark Mode Reactivation Guide

## Estado actual

✅ DESACTIVADO desde Febrero 2025  
**Razón:** VAS slider roto en dark mode, mensajes de éxito inconsistentes y presets sin identidad.  
Ver `DARK_MODE_AUDIT.md` para el diagnóstico completo.

---

## Para reactivar dark mode (cuando Dark Mode v2 esté listo)

### 1. Frontend (toggle)

- Archivo: `src/blocks/form-container/save.js`
- Acción: Descomentar el bloque JSX del toggle:

  ```jsx
  <div className="eipsi-theme-toggle-container">
      <button type="button" className="eipsi-toggle" ...>
          ...
      </button>
  </div>
  ```
- Recompilar bloques (`npm run build`).

### 2. JavaScript

- Archivo: `assets/js/theme-toggle.js`
- Acción:
  - Eliminar el `console.log` de desactivación.
  - Descomentar / restaurar el `document.addEventListener('DOMContentLoaded', ...)`.
  - Volver a habilitar la lógica que:
    - Lee `localStorage` (`eipsi-theme`).
    - Aplica `data-theme="dark" / "light"` a `.vas-dinamico-form`.
    - Sincroniza el estado visual del toggle.

### 3. Variables CSS faltantes

- Archivo: `assets/css/_theme-toggle.scss`
- Acción: Incorporar todas las variables listadas en el anexo de `DARK_MODE_AUDIT.md`:
  - `--eipsi-color-card-bg`, `--eipsi-color-card-bg-hover`, `--eipsi-color-card-border`
  - `--eipsi-color-checked-bg`, `--eipsi-color-checked-shadow`
  - `--eipsi-color-progress-*`
  - **Todas** las `--eipsi-color-vas-*` (críticas para el slider)
  - Variables completas de estados success/error

### 4. Testing (obligatorio)

Seguir el checklist de `DARK_MODE_AUDIT.md` y documentar resultados:

- VAS, radio, checkbox, Likert, select, inputs.
- Thank-you page.
- Mensajes success/error.
- Progress bar.
- Presets (los 4) en light/dark.
- Mobile + tablet + desktop reales.
- Contraste WCAG 2.1 AA.

### Recomendación

No reactivar dark mode hasta que:

1. Completion page, Save & Continue y lógica condicional estén clínicamente cerrados.
2. Se asignen 8–12 horas enfocadas para implementar Dark Mode v2 siguiendo el audit.

---

Este documento acompaña a `DARK_MODE_AUDIT.md` para asegurar un regreso sin sorpresas cuando la versión v2 esté lista.
