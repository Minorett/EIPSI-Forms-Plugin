# UX Improvements: Fields & Navigation Toggles

**Fecha**: Febrero 2025  
**Versión**: Post v1.2.2  
**Tipo de cambio**: Mejoras de UX en el editor, sin cambios conceptuales

---

## 🎯 Objetivo

Reducir fricción en la configuración de formularios para psicólogos clínicos:

1. **Clarificar** la diferencia entre Label, Placeholder y Helper text en campos de texto.
2. **Garantizar** que el toggle de navegación hacia atrás funcione de forma predecible.
3. **Permitir** activar/desactivar la barra de progreso desde el editor.

---

## ✅ Cambios implementados

### PARTE 1: UX de Label / Placeholder / Helper text

**Archivos modificados**:
- `src/components/FieldSettings.js`

**Mejoras**:
- ✅ **Secciones lógicas** con títulos claros:
  - "Texto que ve el paciente" → Label + Helper text
  - "Placeholder (opcional)" → Texto fantasma
  - "Validación" → Toggle de campo obligatorio
- ✅ **Helper text ampliado**: `rows={4}` (antes era TextareaControl sin rows explícito).
- ✅ **Descripciones mejoradas** en español:
  - Label: "Aparece en negrita sobre el campo"
  - Helper text: "Texto de ayuda permanente que se muestra debajo del campo. Ideal para instrucciones clínicas."
  - Placeholder: "Texto fantasma (desaparece al escribir)" + ejemplo: "Escribe tu respuesta aquí…"

**Sin cambios en**:
- Semántica de los campos (se guardan exactamente igual).
- Vista previa del bloque en el editor.
- Renderizado en frontend.

---

### PARTE 2: Toggle "Mostrar botón Anterior" (allowBackwardsNav)

**Archivos modificados**:
- `src/blocks/form-container/edit.js`
- `src/blocks/form-container/save.js`
- `blocks/form-container/block.json` (ya existía, sin cambios)

**Mejoras**:
- ✅ **Toggle traducido**: "Mostrar botón Anterior" (antes "Allow backwards navigation").
- ✅ **Descripción clara**: "Permite al paciente volver a la página anterior. Si está desactivado, el botón 'Anterior' no aparecerá nunca."
- ✅ **Normalización de valores**: Se asegura que el valor booleano se trate correctamente en edit.js y save.js.

**Comportamiento en frontend** (sin cambios):
- **ON (default)**: Botón "Anterior" aparece en páginas intermedias y última página.
- **OFF**: Botón "Anterior" nunca se renderiza (ni siquiera oculto).

**Lógica de navegación existente** (`assets/js/eipsi-forms.js`, líneas 1358-1441):
- Ya estaba correctamente implementada.
- Lee `form.dataset.allowBackwardsNav` y ajusta visibilidad del botón.

---

### PARTE 3: Toggle "Mostrar barra de progreso" (showProgressBar)

**Archivos modificados**:
- `blocks/form-container/block.json` → Nuevo atributo `showProgressBar` (boolean, default: `true`)
- `src/blocks/form-container/edit.js` → Nuevo toggle en panel "Navigation Settings"
- `src/blocks/form-container/save.js` → Renderiza `<div class="form-progress">` solo si `showProgressBar === true`
- `assets/js/eipsi-forms.js` → Lee `form.dataset.showProgressBar` y oculta el elemento si está en `false`

**Comportamiento**:
- **ON (default)**: Barra de progreso "Página X de Y" aparece si `totalPages > 1`.
- **OFF**: Barra de progreso nunca aparece, independientemente del número de páginas.

**Atributo en el DOM**:
```html
<form data-show-progress-bar="true|false">
```

**Lógica JS** (líneas 806-829 de `eipsi-forms.js`):
```javascript
const rawShowProgressPref = form.dataset.showProgressBar;
const showProgressBar =
    rawShowProgressPref === undefined ||
    rawShowProgressPref === '' ||
    rawShowProgressPref === 'true' ||
    rawShowProgressPref === '1';

const progressContainer = form.querySelector('.form-progress');
if (progressContainer) {
    if (!showProgressBar) {
        progressContainer.style.display = 'none';
    } else {
        progressContainer.style.display = totalPages > 1 ? '' : 'none';
    }
}
```

---

## 📦 Archivos afectados (resumen)

```
src/components/FieldSettings.js                → Reorganización + textarea más grande
blocks/form-container/block.json                → Atributo showProgressBar añadido
src/blocks/form-container/edit.js               → Toggles traducidos + showProgressBar
src/blocks/form-container/save.js               → Renderizado condicional de progress bar
assets/js/eipsi-forms.js                        → Respeto a data-show-progress-bar
```

---

## ✅ Checklist de QA

### Parte 1: UX de campos de texto
- [ ] Abrir cualquier bloque de campo de texto (campo-texto, campo-textarea, campo-select, etc.).
- [ ] Ver el panel "Field Settings" en el inspector.
- [ ] Verificar que los campos estén organizados con secciones claras.
- [ ] Verificar que el Helper text tenga un área de texto más grande (4 líneas visibles).
- [ ] Escribir un Helper text largo y confirmar que se guarda correctamente.

### Parte 2: Toggle de navegación hacia atrás
- [ ] Crear un formulario con 3+ páginas.
- [ ] En el Form Container, ir a "Navigation Settings".
- [ ] Desactivar "Mostrar botón Anterior".
- [ ] Guardar y ver el formulario en frontend.
- [ ] Confirmar que NO aparece el botón "Anterior" en páginas intermedias ni en la última.
- [ ] Reactivar el toggle y confirmar que el botón "Anterior" aparece correctamente.

### Parte 3: Toggle de barra de progreso
- [ ] Crear un formulario con 3+ páginas.
- [ ] En el Form Container, ir a "Navigation Settings".
- [ ] Desactivar "Mostrar barra de progreso".
- [ ] Guardar y ver el formulario en frontend.
- [ ] Confirmar que NO aparece "Página X de Y".
- [ ] Reactivar el toggle y confirmar que la barra aparece correctamente.
- [ ] En un formulario de 1 sola página, confirmar que la barra nunca aparece (con toggle ON o OFF).

---

## 🔒 Compatibilidad hacia atrás

✅ **Formularios existentes**:
- `allowBackwardsNav`: Si no está definido, se asume `true` (comportamiento anterior).
- `showProgressBar`: Si no está definido, se asume `true` (comportamiento anterior).

✅ **FieldSettings**:
- Todos los atributos (`label`, `placeholder`, `helperText`, `required`) se guardan igual.
- Solo cambia la UI del editor, no la semántica.

---

## 📝 Notas para futuras mejoras (fuera de scope)

- Considerar usar `<PanelRow>` o componentes Gutenberg nativos para secciones en lugar de `<div style={...}>`.
- Evaluar si el toggle de progress bar debería estar en un panel separado de "Display Settings" (actualmente está en "Navigation Settings").
- Considerar añadir un preview visual de cómo se ve el Helper text vs Placeholder en el bloque mismo.

---

## 🚀 Cómo probar localmente

```bash
npm install
npm run build
npm run lint:js  # Debe salir 0 errors, 0 warnings
```

Luego abrir WordPress, editar cualquier formulario EIPSI y verificar:
1. Panel "Field Settings" en cualquier campo de texto.
2. Panel "Navigation Settings" en el Form Container.
3. Comportamiento de navegación y progress bar en frontend.

---

## 🎓 Mensajes clave para el clínico

### Antes de este cambio:
- "¿Cuál es la diferencia entre Placeholder y Helper text?"
- "¿Cómo oculto el botón Anterior si no quiero que el paciente vuelva?"
- "¿Puedo ocultar la barra de progreso en formularios cortos?"

### Después de este cambio:
- ✅ "Ah, el Helper text es la descripción permanente que se ve siempre."
- ✅ "Genial, desactivo el toggle y el botón Anterior desaparece."
- ✅ "Perfecto, oculto la barra de progreso con un solo click."

---

**Este ticket cumple con la regla de oro de EIPSI Forms**:  
«¿Esto hace que un psicólogo clínico hispanohablante diga mañana:  
"Por fin alguien entendió cómo trabajo de verdad con mis pacientes"?»  
→ **Sí, porque elimina fricción en la configuración sin tocar la esencia clínica.**
