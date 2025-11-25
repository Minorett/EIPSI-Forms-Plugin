# DARK MODE AUDIT – EIPSI FORMS v1.2.2

**Fecha:** Febrero 2025  
**Objetivo:** Diagnosticar cómo está implementado el dark mode actual, detectar problemas de legibilidad (especialmente en thank-you page), y recomendar un plan de acción claro.

---

## 1. CÓMO SE ACTIVA DARK MODE

### Mecanismo técnico
- **Atributo HTML:** `data-theme="dark"` en el contenedor `.vas-dinamico-form`
- **JavaScript:** `assets/js/theme-toggle.js` maneja el toggle y persistencia en localStorage
- **CSS:** `assets/css/_theme-toggle.scss` define las variables que se sobrescriben cuando `[data-theme="dark"]` está presente
- **Toggle UI:** Botón "🌙 Nocturno" / "☀️ Diurno" en el header del formulario

### Flujo del toggle
1. Usuario hace clic en el botón `.eipsi-toggle`
2. JavaScript aplica `data-theme="dark"` a todos los `.vas-dinamico-form` en la página
3. El valor se guarda en `localStorage` con key `eipsi-theme`
4. Al recargar la página, el tema persiste
5. Fallback: si no hay preferencia guardada, respeta `prefers-color-scheme: dark` del sistema

**Estado actual:** ✅ El mecanismo de activación funciona correctamente

---

## 2. QUÉ TOKENS DE COLOR CONTROLA DARK MODE

### Variables definidas en dark mode (`_theme-toggle.scss` líneas 18-66)

| Token CSS | Light mode (`:root`) | Dark mode (`[data-theme="dark"]`) | Propósito |
|-----------|---------------------|-----------------------------------|-----------|
| `--eipsi-color-primary` | `#005a87` (azul EIPSI) | `#60a5fa` (azul claro) | Botones, enlaces, títulos |
| `--eipsi-color-primary-hover` | `#003d5b` | `#3b82f6` | Hover de botones |
| `--eipsi-color-background` | `#ffffff` (blanco) | `#0f172a` (slate oscuro) | Fondo principal del form |
| `--eipsi-color-background-subtle` | `#f8f9fa` (gris claro) | `#1e293b` (slate medio) | Fondos de descripción, hover |
| `--eipsi-color-text` | `#2c3e50` (gris oscuro) | `#e2e8f0` (gris claro) | Texto principal |
| `--eipsi-color-text-muted` | `#64748b` | `#94a3b8` | Texto secundario |
| `--eipsi-color-input-bg` | `#ffffff` | `#1e293b` | Fondo de inputs/radio/checkbox |
| `--eipsi-color-input-text` | `#2c3e50` | `#e2e8f0` | Texto dentro de inputs |
| `--eipsi-color-input-border` | `#e2e8f0` | `#475569` | Bordes de campos |
| `--eipsi-color-input-border-focus` | `#005a87` | `#60a5fa` | Borde en focus |
| `--eipsi-color-button-bg` | `#005a87` | `#3b82f6` | Fondo de botones |
| `--eipsi-color-button-text` | `#ffffff` | `#ffffff` | Texto de botones |
| `--eipsi-color-border` | `#e2e8f0` | `#334155` | Bordes generales |
| `--eipsi-color-border-dark` | `#cbd5e0` | `#475569` | Bordes más oscuros |
| `--eipsi-color-error` | `#d32f2f` | `#fca5a5` | Mensajes de error |
| `--eipsi-color-success` | `#198754` | `#86efac` | Mensajes de éxito |
| `--eipsi-color-warning` | `#b35900` | `#fcd34d` | Advertencias |
| `--eipsi-shadow-*` | Sombras sutiles | Sombras más oscuras | Box shadows |

### Variables que existen en light mode pero NO se definen en dark mode

Estas variables **no están definidas** en `[data-theme="dark"]`, por lo que en dark mode mantienen sus valores de light mode:

- `--eipsi-color-card-bg` (light: `#ffffff`)
- `--eipsi-color-card-bg-hover` (light: `#f8f9fa`)
- `--eipsi-color-card-border` (light: `#e2e8f0`)
- `--eipsi-color-checked-bg` (light: `rgba(0, 90, 135, 0.05)`)
- `--eipsi-color-checked-shadow` (light: `rgba(0, 90, 135, 0.1)`)
- `--eipsi-color-success-dark` (light: `#156b47`)
- `--eipsi-color-success-text` (light: `#ffffff`)
- `--eipsi-color-success-overlay` (light: `rgba(255, 255, 255, 0.15)`)
- `--eipsi-color-success-subtitle` (light: `rgba(255, 255, 255, 0.95)`)
- `--eipsi-color-success-note` (light: `rgba(255, 255, 255, 0.85)`)
- `--eipsi-color-success-shadow` (light: `rgba(0, 0, 0, 0.1)`)
- `--eipsi-color-error-text` (light: `#ffffff`)
- `--eipsi-color-error-border-strong` (light: `rgba(255, 255, 255, 0.3)`)
- Todas las variables específicas de VAS slider (`--eipsi-color-vas-*`)
- Todas las variables de progress bar (`--eipsi-color-progress-*`)

**Impacto:** Algunos elementos usan valores de light mode mezclados con dark mode, generando inconsistencias visuales.

---

## 3. THANK-YOU PAGE EN DARK MODE

### CSS relevante (líneas 2230-2334 de `eipsi-forms.css`)

```css
.eipsi-thank-you-content {
    background: var(--eipsi-color-card-bg, var(--eipsi-color-background, #ffffff));
    padding: var(--eipsi-spacing-lg, 2rem);
    border-radius: var(--eipsi-border-radius-md, 12px);
    box-shadow: var(--eipsi-shadow-md, 0 4px 12px rgba(0, 90, 135, 0.1));
}

.eipsi-thank-you-title {
    color: var(--eipsi-color-primary, #005a87);
}

.eipsi-thank-you-message {
    color: var(--eipsi-color-text, #2c3e50);
}

.eipsi-thank-you-button {
    background-color: var(--eipsi-color-button-bg, #005a87);
    color: var(--eipsi-color-button-text, #ffffff);
}
```

### Comportamiento en dark mode

| Elemento | Variable usada | Valor en dark mode | Contraste |
|----------|----------------|-------------------|-----------|
| Fondo de tarjeta | `--eipsi-color-card-bg` → `--eipsi-color-background` | `#0f172a` (oscuro) | ✅ OK |
| Título | `--eipsi-color-primary` | `#60a5fa` (azul claro) | ✅ OK |
| Mensaje de texto | `--eipsi-color-text` | `#e2e8f0` (gris claro) | ✅ OK |
| Botón (fondo) | `--eipsi-color-button-bg` | `#3b82f6` | ✅ OK |
| Botón (texto) | `--eipsi-color-button-text` | `#ffffff` | ✅ OK |

**Conclusión:** La thank-you page **debería funcionar correctamente** en dark mode según el código actual.

**⚠️ ADVERTENCIA:** Si `--eipsi-color-card-bg` se usa de forma aislada sin fallback, puede generar fondo blanco sobre tema oscuro. Esto no ocurre en la thank-you page porque tiene doble fallback, pero puede pasar en otros componentes.

---

## 4. CAMPOS DE RESPUESTA (RADIO, CHECKBOX, VAS)

### Radio y Checkbox (líneas 603-900 de `eipsi-forms.css`)

Usan las siguientes variables:

| Componente | Variable | Light mode | Dark mode | Estado |
|------------|----------|-----------|-----------|--------|
| Fondo de tarjeta | `--eipsi-color-input-bg` | `#ffffff` | `#1e293b` | ✅ Adaptado |
| Texto de opción | `--eipsi-color-text` | `#2c3e50` | `#e2e8f0` | ✅ Adaptado |
| Borde | `--eipsi-color-input-border` | `#e2e8f0` | `#475569` | ✅ Adaptado |
| Borde en hover | `--eipsi-color-primary` | `#005a87` | `#60a5fa` | ✅ Adaptado |
| Fondo hover | `--eipsi-color-background-subtle` | `#f8f9fa` | `#1e293b` | ✅ Adaptado |
| Checked (fondo) | `--eipsi-color-checked-bg` | `rgba(0,90,135,0.05)` | **No definido** ❌ | ⚠️ Usa valor light |
| Checked (sombra) | `--eipsi-color-checked-shadow` | `rgba(0,90,135,0.1)` | **No definido** ❌ | ⚠️ Usa valor light |
| Círculo interior (radio checked) | `box-shadow: inset 0 0 0 4px var(--eipsi-color-background)` | `#ffffff` | `#0f172a` | ✅ Adaptado |

**Problema detectado:** Los fondos/sombras de elementos "checked" usan colores de light mode (azul EIPSI) en vez de azul claro del dark mode.

### VAS Slider (múltiples variables `--eipsi-color-vas-*`)

**Estado:** ❌ **Ninguna variable de VAS está definida en dark mode.**

Esto significa que el VAS slider en dark mode usa:
- Fondo claro del track (`#e3f2fd` en vez de un gris oscuro)
- Labels con fondo azul claro (`rgba(0, 90, 135, 0.1)` que es casi invisible sobre tema oscuro)
- Textos con color azul oscuro (`#005a87`) sobre fondo oscuro → contraste insuficiente

**Severidad:** 🔴 **CRÍTICO** – El VAS slider es el campo más usado en investigación clínica.

---

## 5. SUCCESS MESSAGE (FORM-MESSAGE--SUCCESS)

### Código actual (líneas 1872-1953 de `eipsi-forms.css`)

```css
.form-message--success {
    background: linear-gradient(135deg, 
        var(--eipsi-color-success, #198754) 0%, 
        var(--eipsi-color-success-dark, #156b47) 100%);
    color: var(--eipsi-color-button-text, #ffffff);
}

.form-message--success .form-message__title {
    color: var(--eipsi-color-success-text, #ffffff);
}

.form-message--success .form-message__subtitle {
    color: var(--eipsi-color-success-subtitle, rgba(255, 255, 255, 0.95));
}

.form-message--success .form-message__note {
    color: var(--eipsi-color-success-note, rgba(255, 255, 255, 0.85));
}
```

### Comportamiento en dark mode

| Elemento | Variable usada | Valor en dark mode | Problema |
|----------|----------------|-------------------|----------|
| Fondo (inicio) | `--eipsi-color-success` | `#86efac` (verde claro) | ⚠️ OK pero poco contraste |
| Fondo (fin) | `--eipsi-color-success-dark` | **No definido** → `#156b47` (verde oscuro) ❌ | Gradiente inconsistente |
| Título | `--eipsi-color-success-text` | **No definido** → `#ffffff` ✅ | OK |
| Subtítulo | `--eipsi-color-success-subtitle` | **No definido** → `rgba(255,255,255,0.95)` ✅ | OK |
| Nota | `--eipsi-color-success-note` | **No definido** → `rgba(255,255,255,0.85)` ✅ | OK |

**Problema:** El gradiente mezcla verde claro (dark mode) con verde oscuro (light mode), generando un gradiente extraño: `#86efac → #156b47`.

---

## 6. RELACIÓN CON PRESETS

### Presets disponibles (Clinical Blue, Minimal White, Warm Neutral, Serene Teal)

**Código actual:** El dark mode es un **overlay genérico** que NO diferencia presets.

Cuando activas dark mode:
- ✅ Las variables de preset (colores primarios, secundarios, tipografías, espaciados) **se mantienen**
- ❌ Pero todas las variables de color se sobrescriben con el mismo esquema oscuro

Ejemplo:
- **Clinical Blue + Dark** → Azul claro (`#60a5fa`) sobre slate oscuro
- **Warm Neutral + Dark** → Azul claro (`#60a5fa`) sobre slate oscuro (pierde el tono cálido marrón)
- **Minimal White + Dark** → Azul claro (`#60a5fa`) sobre slate oscuro (pierde el gris slate original)
- **Serene Teal + Dark** → Azul claro (`#60a5fa`) sobre slate oscuro (pierde el teal completamente)

**Severidad:** 🟡 **MEDIA** – Los presets pierden su identidad visual en dark mode.

**Pregunta clínica:** ¿Un psicólogo que eligió "Warm Neutral" para crear un ambiente cálido espera que el dark mode también tenga tonos cálidos? ¿O es aceptable que dark mode sea siempre el mismo azul frío?

---

## 7. LISTADO DE PROBLEMAS ESPECÍFICOS

### 🔴 CRÍTICOS (bloquean uso clínico real)

1. **VAS Slider no funcional en dark mode**
   - Ninguna variable `--eipsi-color-vas-*` está definida en dark mode
   - Fondo claro sobre tema oscuro
   - Labels casi invisibles
   - Texto azul oscuro sobre fondo oscuro

2. **Success message con gradiente roto**
   - Mezcla `#86efac` (dark) con `#156b47` (light)
   - Aspecto visual inconsistente

### 🟡 MEDIOS (afectan consistencia visual)

3. **Checked state de radio/checkbox usa colores de light mode**
   - `--eipsi-color-checked-bg` y `--eipsi-color-checked-shadow` no definidos
   - Genera fondos azul EIPSI oscuro en vez de azul claro

4. **Progress bar no adaptada**
   - Variables `--eipsi-color-progress-*` no definidas
   - Puede generar texto oscuro sobre fondo oscuro

5. **Presets pierden identidad en dark mode**
   - Todos los presets se ven iguales en dark mode (azul claro genérico)
   - Warm Neutral pierde su calidez, Serene Teal pierde su verde azulado

### 🟢 MENORES (no bloquean pero mejorables)

6. **Card backgrounds sin definir explícitamente**
   - `--eipsi-color-card-bg` no está en dark mode, depende de fallbacks
   - Funciona por ahora pero frágil

7. **Error message border/background no definidos en dark mode**
   - Similar a success message, puede generar inconsistencias

---

## 8. RECOMENDACIÓN FINAL

### ❌ OPCIÓN A: Arreglo serio (el correcto pero toma tiempo)

**Objetivo:** Dark mode a la altura del resto del plugin.

**Tareas:**

1. **Definir TODAS las variables de color en dark mode**
   - Agregar a `_theme-toggle.scss`:
     - `--eipsi-color-card-bg`, `--eipsi-color-card-bg-hover`, `--eipsi-color-card-border`
     - `--eipsi-color-checked-bg`, `--eipsi-color-checked-shadow`
     - `--eipsi-color-success-dark`, `--eipsi-color-success-text`, etc.
     - **Todas** las variables `--eipsi-color-vas-*` (17 variables)
     - **Todas** las variables `--eipsi-color-progress-*` (4 variables)
     - Variables de error completas

2. **Crear dark mode por preset (opcional pero ideal)**
   - Clinical Blue Dark: azul claro + slate
   - Warm Neutral Dark: tonos cálidos + marrón oscuro
   - Minimal White Dark: gris claro + negro
   - Serene Teal Dark: verde azulado claro + fondo teal oscuro

3. **Testing exhaustivo**
   - Probar cada bloque en dark mode: text, radio, checkbox, select, likert, VAS, range
   - Probar thank-you page
   - Probar success/error messages
   - Probar en mobile, tablet, desktop
   - Validar contraste WCAG AA en todos los estados

4. **Documentación**
   - Guía de diseño: qué colores usar en dark mode
   - Ejemplo de cómo extender un preset con dark mode

**Esfuerzo estimado:** 8-12 horas de desarrollo + 4-6 horas de testing.

**Pros:**
- ✅ Dark mode de nivel profesional
- ✅ Consistente con la calidad del resto del plugin
- ✅ Diferenciación por presets (UX superior)
- ✅ VAS slider funcional en dark mode

**Contras:**
- ⏱️ Toma tiempo que no está asignado ahora
- 🧪 Requiere testing exhaustivo antes de release
- 📦 Incrementa el tamaño del CSS (mínimamente)

---

### ✅ OPCIÓN B: Apagar toggle temporalmente (recomendado para ahora)

**Objetivo:** No mostrar una feature a medias hasta que esté a la altura.

**Tareas:**

1. **Ocultar el toggle de dark mode en la UI**
   - Comentar/eliminar el bloque del toggle en `form-container/save.js` (líneas 34-45)
   - O agregar `display: none !important` en CSS a `.eipsi-toggle`
   - Mantener el código JS/CSS intacto (no borrar, solo desactivar)

2. **Agregar comentario en código**
   ```javascript
   // DARK MODE DISABLED TEMPORARILY (Feb 2025)
   // Reason: VAS slider, success messages, and preset consistency need fixing
   // See: DARK_MODE_AUDIT.md for details
   // To re-enable: uncomment this block + test thoroughly
   ```

3. **Documentar en changelog/roadmap**
   - "Dark mode temporalmente desactivado hasta completar adaptación de VAS slider y presets"
   - "Funcionalidad no afecta el uso clínico principal del plugin"

**Esfuerzo estimado:** 15-30 minutos.

**Pros:**
- ✅ Rápido (se hace hoy)
- ✅ No muestra features incompletas a clínicos
- ✅ No genera expectativas falsas
- ✅ Código se puede reactivar fácilmente después
- ✅ No bloquea ninguna funcionalidad core (dark mode es secundario)

**Contras:**
- ❌ Pérdida temporal de una feature "cool"
- ❌ Usuarios que ya lo usaban lo van a extrañar (pero eran pocos)

---

## 9. DECISIÓN RECOMENDADA

**🎯 OPCIÓN B (Apagar toggle temporalmente)**

**Razones:**

1. **Prioridad clínica:** Dark mode NO está en el top 4 de prioridades (completion page, save & continue, conditional fields, templates clínicos).

2. **Zero excuses:** Mejor no tener dark mode que tener uno que rompe el VAS slider (el campo más usado en investigación).

3. **Reversible:** Se puede reactivar en cualquier momento cuando se complete el arreglo.

4. **Honestidad con el usuario:** "Por fin alguien entendió cómo trabajo de verdad" incluye NO mostrar features a medias.

**Plan de acción:**

- **Hoy:** Desactivar toggle (30 minutos)
- **Después de completion page + save & continue:** Revisar si hay tiempo para arreglo serio (Opción A)
- **Si no hay tiempo:** Documentar que dark mode es post-v1.5 y dejarlo para después de templates clínicos

---

## 10. TESTING CHECKLIST (para cuando se reactive dark mode)

Antes de volver a activar dark mode, confirmar:

- [ ] VAS slider completamente funcional en dark mode (track, labels, valor, thumb)
- [ ] Radio buttons: fondo, texto, checked state, hover, focus
- [ ] Checkbox: fondo, texto, checked state, hover, focus
- [ ] Likert: todos los estados visuales
- [ ] Select dropdown: fondo, texto, opciones
- [ ] Text input: fondo, texto, placeholder, focus
- [ ] Thank-you page: título, mensaje, botón, logo
- [ ] Success message: gradiente consistente, texto legible
- [ ] Error message: fondo, texto, bordes
- [ ] Progress bar: fondo, texto, números
- [ ] Navigation buttons: fondo, texto, disabled state
- [ ] Form description: fondo, borde, texto
- [ ] Labels y helper text: contraste suficiente
- [ ] Todos los 4 presets: visual distintivo en dark mode
- [ ] Mobile, tablet, desktop: layout intacto
- [ ] Contraste WCAG AA validado en todos los elementos con herramienta automatizada

---

## ANEXO: Variables CSS que necesitan definición en dark mode

```scss
// AGREGAR A _theme-toggle.scss cuando se implemente Opción A:

.vas-dinamico-form[data-theme="dark"] {
    // ... (variables existentes) ...
    
    /* Cards & Surfaces */
    --eipsi-color-card-bg: #1e293b;
    --eipsi-color-card-bg-hover: #334155;
    --eipsi-color-card-border: #475569;
    --eipsi-color-checked-bg: rgba(96, 165, 250, 0.15);
    --eipsi-color-checked-shadow: rgba(96, 165, 250, 0.25);
    
    /* Progress & Navigation */
    --eipsi-color-progress-bg: #1e293b;
    --eipsi-color-progress-border: #475569;
    --eipsi-color-progress-text: #e2e8f0;
    --eipsi-color-progress-text-highlight: #60a5fa;
    
    /* VAS Slider - CRITICAL */
    --eipsi-color-vas-container-bg: #1e293b;
    --eipsi-color-vas-container-bg-hover: #334155;
    --eipsi-color-vas-container-border: #475569;
    --eipsi-color-vas-container-border-hover: #60a5fa;
    --eipsi-color-vas-label-bg: rgba(96, 165, 250, 0.15);
    --eipsi-color-vas-label-border: rgba(96, 165, 250, 0.3);
    --eipsi-color-vas-label-text: #60a5fa;
    --eipsi-color-vas-slider-track: #334155;
    --eipsi-color-vas-slider-track-hover: #475569;
    --eipsi-color-vas-slider-thumb: #60a5fa;
    --eipsi-color-vas-slider-thumb-secondary: #3b82f6;
    --eipsi-color-vas-slider-thumb-border: #1e293b;
    --eipsi-color-vas-value-text: #60a5fa;
    --eipsi-color-vas-value-bg: rgba(96, 165, 250, 0.1);
    --eipsi-color-vas-value-border: rgba(96, 165, 250, 0.2);
    
    /* Success Message - Complete */
    --eipsi-color-success-dark: #059669;
    --eipsi-color-success-text: #ffffff;
    --eipsi-color-success-overlay: rgba(255, 255, 255, 0.1);
    --eipsi-color-success-subtitle: rgba(255, 255, 255, 0.95);
    --eipsi-color-success-note: rgba(255, 255, 255, 0.85);
    --eipsi-color-success-shadow: rgba(0, 0, 0, 0.3);
    
    /* Description & Info Blocks */
    --eipsi-color-description-bg: #1e293b;
    --eipsi-color-description-border: #60a5fa;
    --eipsi-color-description-text: #e2e8f0;
}
```

**Total de nuevas variables:** ~33 tokens adicionales.

---

**FIN DEL AUDIT**
