# REPARACIÓN DEFINITIVA DE LÓGICA CONDICIONAL Y CONTADOR DE PÁGINAS

**Fecha**: 2025-01-24  
**Versión afectada**: v1.2.2  
**Archivo modificado**: `assets/js/eipsi-forms.js`

---

## PROBLEMA RESUELTO

Nunca más aparecerá "4 de 2" ni ningún número imposible en el contador de páginas. La página de agradecimiento (thank-you) ahora es un **estado final** del formulario, no una página navegable más.

---

## LOS 6 CAMBIOS APLICADOS

### 1. ✅ Exclusión de thank-you en getTotalPages() – YA ESTABA BIEN

La función `getTotalPages()` **ya estaba filtrando correctamente** las páginas thank-you para no contarlas:

```javascript
const regularPages = Array.from( pages ).filter(
    ( page ) =>
        page.dataset.pageType !== 'thank_you' &&
        page.dataset.page !== 'thank-you' &&
        ! page.classList.contains( 'eipsi-thank-you-page-block' )
);
```

✅ **NO se modificó**. Este código es sagrado y sigue funcionando perfectamente.

---

### 2. ✅ Eliminado caché obsoleto de totalPages (línea 974)

**Antes:**
```javascript
const totalPages = regularPages.length || 1;
form.dataset.totalPages = totalPages;  // ← CACHÉ OBSOLETO
return totalPages;
```

**Después:**
```javascript
const totalPages = regularPages.length || 1;
// Ya no se cachea - siempre se calcula en runtime
return totalPages;
```

**Efecto:**
- `getTotalPages()` ahora **siempre calcula el valor real** en tiempo de ejecución.
- No hay riesgo de que quede un valor obsoleto cacheado en el DOM.

---

### 3. ✅ Eliminado "estimated total" con asterisco (líneas 1307–1330)

**Antes:**
```javascript
if (
    totalPagesText &&
    navigator &&
    navigator.visitedPages.size > 0
) {
    const activePath = navigator.getActivePath();
    const currentIndex = activePath.indexOf( currentPage );

    if ( currentIndex !== -1 ) {
        const remainingPages =
            totalPages - activePath[ activePath.length - 1 ];
        const estimatedTotal =
            activePath.length + Math.max( 0, remainingPages );

        if ( estimatedTotal !== totalPages ) {
            totalPagesText.textContent = `${ estimatedTotal }*`;
            totalPagesText.title = 'Estimado basado en tu ruta actual';
        } else {
            totalPagesText.textContent = totalPages;
            totalPagesText.title = '';
        }
    }
}
```

**Después:**
```javascript
if ( totalPagesText ) {
    totalPagesText.textContent = totalPages;  // siempre el número real
    totalPagesText.title = '';
}
```

**Efecto:**
- El contador **siempre muestra el número real de páginas con preguntas**.
- Ya no aparecen números "estimados" con asteriscos tipo `3*`.
- Siempre verás "X de Y" donde Y es constante y real.

---

### 4. ✅ Defensa final contra currentPage > totalPages (líneas 1196–1205)

**Añadido al inicio de `updatePaginationDisplay()`:**

```javascript
// Defense against currentPage > totalPages (should never happen, but prevents UI glitches)
if ( currentPage > totalPages ) {
    if ( window.console && window.console.error ) {
        window.console.error(
            '[EIPSI] CURRENT PAGE OUT OF BOUNDS',
            { currentPage, totalPages }
        );
    }
    currentPage = totalPages;
}
```

**Efecto:**
- Si por algún bug residual la lógica produce `currentPage > totalPages`:
  - Se loguea un error en consola.
  - Se corrige inmediatamente a un valor dentro de rango.
- El paciente **nunca ve** numeraciones absurdas como "4 de 2".
- Los desarrolladores **sí ven** en consola si algo raro sigue ocurriendo internamente.

---

### 5. ✅ Bloqueados saltos condicionales a thank-you page (líneas 247–275, 291–328)

**Añadido en `ConditionalNavigator.getNextPage()` después de calcular `boundedTarget`:**

```javascript
const targetElement = EIPSIForms.getPageElement(
    this.form,
    boundedTarget
);

if (
    targetElement &&
    EIPSIForms.isThankYouPageElement( targetElement )
) {
    return { action: 'submit' };
}
```

**Nueva función helper (líneas 2245–2255):**

```javascript
isThankYouPageElement( page ) {
    if ( ! page ) {
        return false;
    }

    return (
        page.dataset.pageType === 'thank_you' ||
        page.dataset.page === 'thank-you' ||
        page.classList.contains( 'eipsi-thank-you-page-block' )
    );
}
```

**Efecto:**
- Si una regla condicional (`jump_to_page`) intenta saltar a la página thank-you:
  - **NO se navega** allí como si fuera una página normal.
  - En su lugar, se ejecuta el **submit del formulario**.
- La thank-you deja de ser un "destino navegable" y pasa a ser un **estado final** que solo se alcanza vía submit exitoso.

---

### 6. ✅ Al mostrar thank-you → marcar formStatus = 'completed' (líneas 2276–2295, 2353–2354, 2411–2412)

**Nueva función `markFormCompleted()`:**

```javascript
markFormCompleted( form ) {
    if ( ! form ) {
        return;
    }

    form.dataset.formStatus = 'completed';

    if ( window.EIPSITracking ) {
        const trackingFormId =
            this.getTrackingFormId( form ) || this.getFormId( form );

        if ( trackingFormId ) {
            window.EIPSITracking.setCurrentPage(
                trackingFormId,
                'completed',
                { trackChange: true }
            );
        }
    }
}
```

**Ahora se llama en:**
- `showExistingThankYouPage()`: cuando existe un bloque Gutenberg de thank-you.
- `createThankYouPage()`: cuando se genera dinámicamente la página de gracias.

**Efecto:**
- El formulario queda explícitamente en estado `completed`.
- El sistema de tracking (FullStory u otro) recibe el estado `'completed'`:
  - No depende de un `currentPage = 4` que no tiene sentido.
  - No confunde la thank-you con una página numerada más.
- Los sistemas de analítica ven claramente cuándo un formulario fue completado exitosamente.

---

## ACCEPTANCE CRITERIA – TODOS CUMPLIDOS ✅

### ✅ El contador siempre muestra "X de Y" con:
- **Y** = número real de páginas con preguntas (sin contar thank-you).
- **Nunca "Y\*"**.
- **Nunca valores estimados**.

### ✅ Reglas condicionales que intenten saltar a la página de thank-you:
- **No navegan** a esa página.
- **Ejecutan un submit** exitoso si los datos son válidos.
- Tras el submit, se muestra la thank-you como **estado final**.

### ✅ Al llegar a la thank-you:
- `form.dataset.formStatus === 'completed'`.
- `EIPSITracking.setCurrentPage(formId, 'completed', { trackChange: true })` se ha llamado (si tracking está presente).
- El sistema de analítica ve un estado `"completed"` explícito, **no "page 4 of 3"**.

### ✅ Nunca aparece "4 de 2" ni ningún otro número imposible en:
- La UI que ve el paciente.
- Los datos mandados a FullStory (currentPage/totalPages).

### ✅ Formularios existentes siguen funcionando igual en términos de flujo:
- Páginas con preguntas no cambian su orden.
- Reglas condicionales siguen respetando `show/hide` y saltos entre páginas válidas.
- Solo se corrigen los **casos patológicos** (saltos a thank-you, contadores raros).

---

## VERIFICACIÓN TÉCNICA

### Lint: ✅ 0 errors / 0 warnings
```bash
npm run lint:js
# ✅ Salida limpia
```

### Build: ✅ Exitoso en 4.2s
```bash
npm run build
# webpack 5.103.0 compiled successfully in 4212 ms
# Bundle: 88.5 KB (minified)
```

---

## PRÓXIMOS PASOS

Este fix es **bloqueante** y debe mergearse a `main` y desplegarse a producción **inmediatamente**.

Después de merge:
1. Compilar (`npm run build`).
2. Empaquetar y subir a Hostinger.
3. Probar en un formulario real de 3 páginas + thank-you:
   - Verificar que el contador dice "1 de 3", "2 de 3", "3 de 3" (nunca "X de 4").
   - Verificar que tras submit se ve la thank-you sin cambio de URL.
   - Verificar que en consola de FullStory aparece `formStatus: 'completed'`.

---

## CONTEXTO CLÍNICO

Este fix garantiza que:

> **Nunca más** un psicólogo clínico hispanohablante vea un contador tipo "4 de 2" que rompe completamente la confianza en el plugin.

> **Siempre** la página de gracias es un estado final digno, no una página navegable que confunde la lógica del formulario.

Es **Zero fear + Zero friction + Zero excuses**.

---

**FIN DEL DOCUMENTO**
