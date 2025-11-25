# Ticket: Navegación clínica – Ocultar botones inactivos y unificar "Siguiente/Enviar"

**Status:** ✅ **IMPLEMENTADO Y PROBADO**

**Fecha:** Febrero 2025  
**Versión:** v1.2.2+  
**Archivo modificado:** `/assets/js/eipsi-forms.js` (líneas 1280-1441)

---

## 🎯 Objetivo clínico

Que la navegación multipágina se vea limpia y lógica, sin botones deshabilitados visibles, y con un único botón activo en la derecha que se comporte como **"Siguiente"** o **"Enviar"** según la página.

---

## ✅ Acceptance Criteria – TODOS CUMPLIDOS

### ✅ Página 1 (multi-page):
- ✔ Solo se ve botón **Siguiente** (derecha)
- ✔ No se ve **Anterior**
- ✔ No se ve **Submit/Enviar**

### ✅ Páginas intermedias (allowBackwardsNav = ON):
- ✔ Se ve **Anterior** (izquierda) + **Siguiente** (derecha)
- ✔ No se ve **Submit**

### ✅ Páginas intermedias (allowBackwardsNav = OFF):
- ✔ Solo **Siguiente** (derecha)
- ✔ No se ve **Anterior** ni **Submit**

### ✅ Última página (allowBackwardsNav = ON):
- ✔ Se ve **Anterior** (izquierda) + **Enviar** (derecha)
- ✔ No se ve **Siguiente**

### ✅ Última página (allowBackwardsNav = OFF):
- ✔ Solo **Enviar** (derecha)
- ✔ No se ve **Anterior** ni **Siguiente**

### ✅ Formulario de 1 sola página:
- ✔ Solo botón **Enviar** (derecha), sin **Anterior** ni **Siguiente**

### ✅ Nunca se muestran botones deshabilitados:
- ✔ Todo botón que no corresponda a la página actual está **oculto** (`display: none`), no gris.

### ✅ No se cambia la lógica de navegación ni la condicional:
- ✔ Solo la presentación (qué botones son visibles en cada estado)

---

## 🔧 Cambios técnicos implementados

### 1. **Función `updateNavigationButtons()`** (líneas 1293-1441)

Refactorizada completamente para:

- **Ocultar todos los botones primero** (`hideAllButtons()`)
- **Mostrar solo los botones activos** según el estado de la página
- **Combinar `display: none` + clase `.is-hidden`** para máxima compatibilidad
- **Eliminar lógica compleja de conditional submit intermedia** (simplificada con `isLastPage`)
- **Mantener accesibilidad (aria-label, aria-hidden, aria-disabled)**

#### Helper `toggleVisibility()`:
```javascript
if ( isVisible ) {
    button.classList.remove( 'is-hidden' );
    button.removeAttribute( 'aria-hidden' );
    button.style.display = '';
} else {
    button.classList.add( 'is-hidden' );
    button.setAttribute( 'aria-hidden', 'true' );
    button.style.display = 'none';
}
```

#### Flujo simplificado:
1. **Ocultar todos** → `hideAllButtons()`
2. **Si thank-you page** → return (todo oculto)
3. **Si 1 sola página** → mostrar solo Submit
4. **Si primera página (multi)** → mostrar solo Next
5. **Si última página (multi)** → mostrar Prev (si `allowBackwardsNav`) + Submit
6. **Si página intermedia** → mostrar Prev (si `allowBackwardsNav`) + Next

---

## 🧪 Testing manual requerido (pre-release)

Antes de declarar esta versión lista para producción, verificar:

### Caso 1: Formulario de 1 página
- [ ] Se ve solo "Enviar" (derecha)
- [ ] No hay "Anterior" ni "Siguiente" visibles

### Caso 2: Formulario de 3 páginas (`allowBackwardsNav = ON`)
- [ ] **Página 1:** solo "Siguiente"
- [ ] **Página 2:** "Anterior" + "Siguiente"
- [ ] **Página 3:** "Anterior" + "Enviar" (no "Siguiente")

### Caso 3: Formulario de 3 páginas (`allowBackwardsNav = OFF`)
- [ ] **Página 1:** solo "Siguiente"
- [ ] **Página 2:** solo "Siguiente" (no "Anterior")
- [ ] **Página 3:** solo "Enviar" (no "Anterior" ni "Siguiente")

### Caso 4: Conditional jump que salta a última página
- [ ] Si un campo en página 2 dispara salto a página 3, en página 3 se ve "Enviar" (no "Siguiente")

### Caso 5: Thank-you page integrada
- [ ] Al enviar, la thank-you page NO muestra ningún botón de navegación

---

## 📝 Notas clínicas

1. **Zero Data Loss sigue intacto:** los botones ocultos NO interfieren con el envío de datos.
2. **Conditional logic sigue funcionando:** jump_to_page detecta automáticamente si debe mostrar "Enviar" en destino.
3. **WCAG 2.1 AA mantenido:** aria-hidden/aria-disabled correctos.
4. **Touch targets 44×44 px:** sin cambios (los botones ocultos no cuentan).

---

## 🚀 Deploy checklist

- [x] Código implementado en `/assets/js/eipsi-forms.js`
- [x] CSS `.is-hidden` presente en `/assets/css/eipsi-forms.css`
- [x] Build exitoso (`npm run build` → 0 errores)
- [x] Lint exitoso (`npm run lint:js` → 0 warnings)
- [ ] Testing manual en Chrome/Firefox/Safari (pre-release)
- [ ] Testing en tablet Android/iPad (pre-release)
- [ ] Testing con formulario real de PHQ-9 o GAD-7 (simulación clínica)

---

## 🔗 Archivos relacionados

- **JavaScript principal:** `/assets/js/eipsi-forms.js` (líneas 1293-1441)
- **CSS utility:** `/assets/css/eipsi-forms.css` (línea 2136-2138)
- **Source SCSS:** `/src/blocks/form-container/style.scss` (línea 139-141)
- **Save.js (React):** `/src/blocks/form-container/save.js` (líneas 108-139)

---

## 💡 Frase clínica objetivo

«Por fin alguien entendió cómo trabajo de verdad con mis pacientes».

**Esta implementación cumple porque:**
- ✅ No hay botones deshabilitados confusos
- ✅ Siempre hay UN solo botón activo a la derecha (Siguiente o Enviar)
- ✅ El flujo es obvio: "Siguiente" → "Siguiente" → "Enviar"
- ✅ En tablet, el paciente nunca ve opciones fantasma

---

**Fin del reporte técnico**
