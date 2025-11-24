# PR: Dark Mode Universal con Variantes por Preset

## 🎯 Objetivo Clínico

Implementar un sistema de dark mode universal que respeta cada preset de diseño, garantizando que:
- Cualquier psicólogo que trabaja en sala con luz baja puede activar el modo oscuro sin perder identidad visual del preset elegido.
- Los campos interactivos (inputs, textareas, radio, checkbox, select) **SIEMPRE** permanecen con fondo claro + texto oscuro para máxima legibilidad clínica.
- El modo persiste entre recargas de página.
- El toggle está accesible en desktop (header) y mobile (botón flotante bottom-right).

---

## 📦 Cambios Implementados

### 1. **Nuevo atributo `presetName` en Form Container**
- **Archivo:** `blocks/form-container/block.json`
- **Cambio:** Agregado atributo `presetName` con default `"Clinical Blue"`.
- **Propósito:** Permitir que CSS identifique el preset activo con `[data-preset="Clinical Blue"]`.

### 2. **Actualización de `edit.js` (Editor de Gutenberg)**
- **Archivo:** `src/blocks/form-container/edit.js`
- **Cambios:**
  - Agregado destructuring de `presetName`.
  - Migración automática: si no existe `presetName`, se asigna `"Clinical Blue"`.
  - Pasado `presetName` y `setPresetName` al `FormStylePanel`.

### 3. **Actualización de `save.js` (Frontend)**
- **Archivo:** `src/blocks/form-container/save.js`
- **Cambios:**
  - Agregado destructuring de `presetName`.
  - Incluido `data-preset={ presetName || 'Clinical Blue' }` en `blockProps.save()`.
  - **Resultado:** El contenedor del formulario ahora renderiza `<div class="vas-dinamico-form eipsi-form" data-preset="Clinical Blue" data-theme="light">`.

### 4. **Actualización de `FormStylePanel.js`**
- **Archivo:** `src/components/FormStylePanel.js`
- **Cambios:**
  - Agregados props `presetName` y `setPresetName`.
  - `applyPreset()` ahora guarda el nombre del preset con `setPresetName( preset.name )`.
  - `resetToDefaults()` ahora resetea `presetName` a `"Clinical Blue"`.

### 5. **Refactorización completa de `theme-toggle.css`**
- **Archivo:** `assets/css/theme-toggle.css`
- **Cambios:**
  - **Eliminado:** El set genérico dark mode anterior (`[data-theme="dark"]` solo).
  - **Agregado:** 4 variantes específicas por preset:
    - `[data-theme="dark"][data-preset="Clinical Blue"]` → Clinical Blue Dark
    - `[data-theme="dark"][data-preset="Minimal White"]` → Minimal Dark
    - `[data-theme="dark"][data-preset="Warm Neutral"]` → Warm Neutral Dark
    - `[data-theme="dark"][data-preset="Serene Teal"]` → Serene Teal Dark
  
  - **Cada variante define:**
    - Colores de fondo, texto, bordes (oscuros).
    - **Inputs CLAROS:** `--eipsi-color-input-bg: #ffffff;` y `--eipsi-color-input-text` con color oscuro.
    - Botones, VAS Slider, descripción, progreso, etc. todos adaptados al tema oscuro.
  
  - **Sin cambios:** Lógica de toggle, transiciones, responsive, noscript, print.

---

## ✅ Checklist Clínico

### Página de finalización
- [x] No depende de dark mode (ya integrada en v1.2.2).

### Navegación multipágina
- [x] No depende de dark mode (ya funciona desde v1.2.2).

### Campos de respuesta
- [x] **Radio, Checkbox, Likert:** Se pueden tocar cómodamente en mobile.
- [x] **Inputs, textareas, select:** Permanecen claros en dark mode.
- [x] **VAS Slider:** Track se oscurece, thumb y números permanecen claros.

### Dark mode
- [x] **Hay un solo toggle** (header desktop, flotante mobile).
- [x] **Cambia todo el formulario:** fondo, textos, labels, botones, navegación, thank-you.
- [x] **Campos interactivos CLAROS** (fondo blanco + texto oscuro).
- [x] **Cada preset tiene su versión dark:**
  - Clinical Blue → Clinical Blue Dark (azul frío)
  - Minimal White → Minimal Dark (grises neutros)
  - Warm Neutral → Warm Neutral Dark (tonos cálidos tostados)
  - Serene Teal → Serene Teal Dark (teal terapéutico)
- [x] **Persiste al recargar** (localStorage).
- [x] **Respeta `prefers-color-scheme`** como inicial (si no hay preferencia guardada).

### Datos y base de datos
- [x] No depende de dark mode (ya validado en v1.2.2).

---

## 🧪 Testing Requerido (Pre-Merge)

### Desktop (Chrome/Firefox/Edge)
- [ ] Abrir editor de Gutenberg, crear formulario, seleccionar preset "Clinical Blue".
- [ ] Guardar, abrir página frontend, activar dark mode.
- [ ] Verificar que fondo/texto/botones son azul frío oscuro.
- [ ] Verificar que inputs, textareas, radio, checkbox, select son CLAROS.
- [ ] Recargar página, verificar que dark mode persiste.
- [ ] Cambiar a preset "Warm Neutral" en editor, guardar, recargar frontend con dark mode activo.
- [ ] Verificar que el tema cambia a tonos cálidos oscuros (marrón tostado).

### Tablet (iPad o similar)
- [ ] Abrir formulario en Safari, activar dark mode.
- [ ] Tocar campos interactivos, verificar que área clickeable es cómoda.
- [ ] Verificar que toggle es visible (inline en header).

### Mobile (Android Chrome)
- [ ] Abrir formulario, activar dark mode.
- [ ] Verificar que toggle es flotante bottom-right, 44×44 px.
- [ ] Tocar toggle, verificar que cambia de light → dark → light.
- [ ] Recargar, verificar que persiste.

### Contraste WCAG 2.1 AA
- [ ] Usar herramienta de contraste (ej: Axe DevTools) en dark mode.
- [ ] Verificar que texto sobre fondo oscuro tiene contraste mínimo 4.5:1.
- [ ] Verificar que campos claros sobre fondo oscuro no tienen brillo excesivo (no #ffffff puro si el fondo es #000000 puro).

---

## 📝 Notas de Implementación

### ¿Por qué no se generan variantes dark automáticamente desde stylePresets.js?
- **Decisión:** Los dark tokens se definen manualmente en CSS, NO en JS.
- **Razón clínica:** Los presets light NO son inversiones automáticas. Cada preset dark tiene valores optimizados para:
  - Contraste clínico (no simplemente `filter: invert()`).
  - Identidad visual coherente (Clinical Blue Dark sigue siendo "azul profesional", no gris genérico).
  - Legibilidad de campos claros sobre fondos oscuros específicos.

### ¿Por qué los inputs son SIEMPRE claros?
- **Razón clínica:** En consultorio, el paciente lee y escribe en campos de texto. Un fondo oscuro con texto claro puede:
  - Cansar la vista en sesiones largas.
  - Dificultar la lectura para pacientes con baja visión o dislexia.
  - Romper la expectativa UX (la web médica mayormente usa inputs claros).

---

## 🚀 Post-Merge

Después de mergear este PR:
1. Actualizar changelog en `README.md` con "Dark mode universal por preset" en v1.3.0.
2. Probar en entorno de staging con investigadores reales antes de release público.
3. Documentar en manual de usuario cómo activar dark mode (toggle header/mobile).
4. Capturar screenshots de cada preset en dark mode para marketing.

---

## 🔗 Links Relacionados
- **Ticket original:** [feat-dark-mode-universal-form-presets](branch actual)
- **Referencia WCAG 2.1 AA:** https://www.w3.org/WAI/WCAG21/quickref/#contrast-minimum
- **System Dark Mode (prefers-color-scheme):** https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-color-scheme
