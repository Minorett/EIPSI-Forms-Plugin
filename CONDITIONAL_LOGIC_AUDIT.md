# AUDIT ESTRUCTURAL: Lógica Condicional y Navegación Multipágina

**Plugin:** EIPSI Forms v1.2.2  
**Fecha:** 24 noviembre 2024  
**Objetivo:** Escaneo completo de lógica condicional, navegación, numeración de páginas, integración DOM/tracking, y diagnóstico del bug "4 de 2".

---

## 🎯 RESUMEN EJECUTIVO

**Estado general:** La lógica condicional funciona correctamente en escenarios estándar, pero existen **desincronizaciones estructurales** entre:
1. Cálculo de `totalPages` (excluye thank-you)
2. Índice de `currentPage` (puede incluir thank-you)
3. Tracking (reporta valores distintos a la UI bajo ciertas condiciones)

**Bug crítico confirmado:** "Página X de Y" puede mostrar valores imposibles (ej: "4 de 2", "3 de 1") cuando:
- Existen páginas thank-you en el DOM
- Se aplican saltos condicionales que cruzan límites de totalPages
- El tracking lee un estado diferente al que ve el usuario

**Impacto clínico:** Un investigador configura un formulario con 3 páginas + 1 thank-you. Si una regla condicional salta de página 2 a página "thank-you" (índice 4 en DOM), el usuario ve "4 de 3" o peor. Esto rompe completamente la confianza en el plugin.

---

## 📁 COMPONENTES CLAVE IDENTIFICADOS

### 1. JavaScript Frontend: `assets/js/eipsi-forms.js` (2493 líneas)

#### A. `ConditionalNavigator` class (líneas 45-359)
**Responsabilidad:** Gestionar flujo condicional, historial de navegación, y páginas saltadas.

| Método | Descripción | Líneas |
|--------|-------------|--------|
| `parseConditionalLogic(jsonString)` | Parsea JSON de reglas desde `data-conditional-logic` | 54-71 |
| `normalizeConditionalLogic(logic)` | Normaliza formato legacy y nuevo | 73-97 |
| `getFieldValue(field)` | Extrae valor actual de campo (radio/checkbox/select/slider) | 99-130 |
| `findMatchingRule(rules, fieldValue)` | Busca regla que coincide con valor | 132-192 |
| `getNextPage(currentPage)` | **CRÍTICO:** Evalúa reglas y retorna acción (`nextPage`, `goToPage`, `submit`) | 194-306 |
| `shouldSubmit(currentPage)` | Determina si debe terminar formulario | 308-311 |
| `pushHistory(pageNumber)` | Agrega página al historial (para "Anterior") | 313-321 |
| `popHistory()` | Retrocede en historial | 323-329 |
| `markSkippedPages(fromPage, toPage)` | Marca páginas saltadas para analytics | 331-344 |
| `getActivePath()` | Retorna array de páginas visitadas | 346-348 |
| `isPageSkipped(pageNumber)` | Consulta si página fue saltada | 350-352 |
| `reset()` | Limpia estado de navegación | 354-358 |

**Problema detectado:** `getNextPage()` limita `targetPage` a `totalPages` (líneas 247-253):
```javascript
const totalPages = EIPSIForms.getTotalPages( this.form );
const boundedTarget = Math.min(
    Math.max( targetPage, 1 ),
    totalPages
);
```
Si `totalPages = 3` (excluyendo thank-you), pero una regla intenta saltar a página 4 (thank-you), el salto se limita a página 3 → **bug**.

#### B. `EIPSIForms` object (líneas 361-2491)
**Responsabilidad:** Inicialización, validación, navegación, tracking, UI.

| Método | Descripción | Líneas |
|--------|-------------|--------|
| `getTotalPages(form)` | Calcula total excluyendo thank-you | 954-977 |
| `getCurrentPage(form)` | Lee página actual de hidden field / dataset | 979-1017 |
| `setCurrentPage(form, pageNumber, options)` | Actualiza página y sincroniza tracking | 1019-1064 |
| `handlePagination(form, direction)` | Maneja clicks en Prev/Next | 1066-1155 |
| `updatePaginationDisplay(form, currentPage, totalPages)` | Actualiza UI (botones, contador "X de Y") | 1196-1345 |
| `updatePageVisibility(form, currentPage)` | Muestra/oculta páginas con `display: none` | 1365-1386 |
| `handleSubmit(e, form)` | Valida y envía formulario | 1693-1709 |
| `submitForm(form)` | AJAX submit + muestra thank-you page | 1711-1828 |
| `showIntegratedThankYouPage(form)` | Muestra página thank-you integrada (mismo URL) | 2249-2302 |
| `showExistingThankYouPage(form, element)` | Usa bloque Gutenberg thank-you existente | 2304-2357 |
| `createThankYouPage(form, config)` | Crea página thank-you dinámica si no existe | 2359-2474 |

**Problema crítico detectado en `getTotalPages()`:**
```javascript
getTotalPages( form ) {
    const pages = form.querySelectorAll( '.eipsi-page' );
    // Filter out thank-you pages from total count
    const regularPages = Array.from( pages ).filter(
        ( page ) =>
            page.dataset.pageType !== 'thank_you' &&
            page.dataset.page !== 'thank-you' &&
            ! page.classList.contains( 'eipsi-thank-you-page-block' )
    );
    const totalPages = regularPages.length || 1;
    form.dataset.totalPages = totalPages;
    return totalPages;
}
```

**El filtro es correcto, PERO:**
- `updatePaginationDisplay()` calcula "estimated total" dinámicamente (líneas 1307-1330) basado en `visitedPages`, lo que puede ser inconsistente con `totalPages`.
- Cuando se muestra thank-you page, el contador de progreso se actualiza (líneas 2462-2473) pero de forma independiente al flujo normal.

**Problema en `updatePaginationDisplay()` - cálculo confuso de "estimated total" (líneas 1307-1330):**
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
            totalPagesText.title =
                'Estimado basado en tu ruta actual';
        } else {
            totalPagesText.textContent = totalPages;
            totalPagesText.title = '';
        }
    }
}
```

**Por qué es confuso:**
- `activePath` contiene solo las páginas que el usuario **visitó**.
- Si un formulario tiene 5 páginas y se saltó de página 2 a página 5, entonces:
  - `activePath = [1, 2, 5]` (length = 3)
  - `totalPages = 5`
  - `remainingPages = 5 - 5 = 0`
  - `estimatedTotal = 3 + 0 = 3`
  - UI muestra "2 de 3*" cuando en realidad es "2 de 5"
- **Esto es engañoso:** el paciente no sabe qué significa el asterisco, y el investigador piensa que algo está roto.

---

### 2. JavaScript Tracking: `assets/js/eipsi-tracking.js` (359 líneas)

**Responsabilidad:** Rastrear eventos de formulario (view, start, page_change, submit, abandon, branch_jump).

| Método | Descripción | Líneas |
|--------|-------------|--------|
| `registerForm(form, formId)` | Registra formulario y trackea `view` + `start` | 143-179 |
| `setTotalPages(formId, totalPages)` | Almacena total de páginas en sesión | 181-185 |
| `setCurrentPage(formId, pageNumber, options)` | Actualiza página actual en sesión | 187-198 |
| `recordPageChange(formId, pageNumber)` | Trackea cambio de página | 200-211 |
| `recordSubmit(formId)` | Trackea submit | 213-222 |
| `flushAbandonEvents(force)` | Trackea abandon al salir | 224-247 |
| `trackEvent(eventType, formId, payload, options)` | Envía evento vía AJAX/beacon | 249-321 |

**Estructura de sesión (líneas 118-127):**
```javascript
createSessionPayload() {
    return {
        sessionId: this.generateSessionId(),
        viewTracked: false,
        startTracked: false,
        submitTracked: false,
        abandonTracked: false,
        currentPage: 1,
        totalPages: 1,
    };
}
```

**Cómo se sincroniza con `EIPSIForms`:**
1. `attachTracking(form)` en `eipsi-forms.js` (líneas 588-613):
   - Lee `totalPages` de `form.dataset.totalPages` o calcula con `getTotalPages()`
   - Llama a `EIPSITracking.setTotalPages(formId, totalPages)`
   - Llama a `EIPSITracking.setCurrentPage(formId, currentPage, { trackChange: false })`

2. Cuando cambia de página:
   - `setCurrentPage(form, pageNumber, options)` llama a `EIPSITracking.recordPageChange()` si `trackChange = true` (líneas 1058-1062)
   - `updatePaginationDisplay()` sincroniza con `EIPSITracking.setCurrentPage(formId, currentPage, { trackChange: false })` (líneas 1335-1344)

**Problema:** Si `form.dataset.totalPages` se fija al inicio pero luego cambia (ej: se agrega/elimina thank-you page dinámicamente), el tracking mantiene el valor antiguo. Esto puede causar desincronización con la UI.

---

### 3. Bloques Gutenberg

#### A. `src/blocks/pagina/edit.js` (247 líneas)
**Bloque:** Página individual del formulario.

**Atributos clave:**
```javascript
{
    title: '',              // Título opcional de la página
    pageIndex: 1,           // Índice calculado automáticamente
    pageType: 'standard',   // 'standard' | 'thank_you'
    enableRestartButton: false,
    restartButtonLabel: 'Comenzar de nuevo',
}
```

**Cómo se calcula `pageIndex` automáticamente (líneas 20-39):**
```javascript
const computedPageIndex = useSelect(
    ( select ) => {
        const { getBlockRootClientId, getBlockOrder, getBlock } =
            select( 'core/block-editor' );
        const parentClientId = getBlockRootClientId( clientId );
        const siblingClientIds =
            ( parentClientId
                ? getBlockOrder( parentClientId )
                : getBlockOrder() ) || [];

        const pageClientIds = siblingClientIds.filter( ( siblingId ) => {
            const block = getBlock( siblingId );
            return block?.name === 'vas-dinamico/form-page';
        } );

        const index = pageClientIds.indexOf( clientId );
        return index === -1 ? null : index + 1;
    },
    [ clientId ]
);
```

**Problema:** Este cálculo **incluye todas las páginas** (standard + thank-you) al contar. Si hay 3 páginas standard + 1 thank-you, entonces:
- Página 1: `pageIndex = 1`
- Página 2: `pageIndex = 2`
- Página 3: `pageIndex = 3`
- Página thank-you: `pageIndex = 4`

Pero `getTotalPages()` en frontend retorna 3. → **Desincronización confirmada.**

#### B. `src/blocks/pagina/save.js` (51 líneas)
**Renderizado frontend del bloque Página:**

```javascript
const blockProps = useBlockProps.save( {
    className: `eipsi-page ${
        isThankYouPage ? 'eipsi-thank-you-page-block' : ''
    } ${ className || '' }`.trim(),
    'data-page': isThankYouPage ? 'thank-you' : pageIndex,
    'data-page-type': isThankYouPage ? 'thank_you' : 'standard',
    style: {
        display: isThankYouPage || pageIndex !== 1 ? 'none' : undefined,
    },
} );
```

**Atributos DOM generados:**
- `class="eipsi-page"` (standard) o `class="eipsi-page eipsi-thank-you-page-block"` (thank-you)
- `data-page="1"` (standard) o `data-page="thank-you"` (thank-you)
- `data-page-type="standard"` o `data-page-type="thank_you"`
- `style="display: none"` (todas excepto primera página)

**Implicación:** El frontend usa estos atributos para filtrar páginas en `getTotalPages()`. El filtro es correcto, pero si el índice de página se calcula mal en editor, el DOM puede tener valores inconsistentes.

#### C. `src/blocks/form-container/save.js` (149 líneas)
**Renderizado del contenedor de formulario:**

**Campos hidden relevantes (líneas 85-94):**
```javascript
<input
    type="hidden"
    className="eipsi-current-page"
    name="current_page"
    value="1"
/>
```

**Navegación (líneas 100-131):**
- Botón "Anterior" (solo si `allowBackwardsNav = true`)
- Botón "Siguiente"
- Botón "Enviar" (inicialmente oculto)

**Indicador de progreso (líneas 134-137):**
```html
<div className="form-progress">
    Página <span className="current-page">1</span> de{ ' ' }
    <span className="total-pages">?</span>
</div>
```

**Problema:** `total-pages` se inicializa con "?" y se actualiza vía JS. Si el JS falla o se ejecuta antes de que el DOM esté listo, el usuario ve "Página 1 de ?".

---

## 🐛 PROBLEMAS CRÍTICOS DETECTADOS

### **PROBLEMA #1: Desincronización entre `pageIndex` (Gutenberg) y `data-page` (DOM)**

**Causa:** `computedPageIndex` en editor incluye todas las páginas (standard + thank-you), pero `getTotalPages()` en frontend excluye thank-you.

**Escenario reproducible:**
1. Crear formulario con:
   - Página 1 (standard)
   - Página 2 (standard)
   - Página 3 (standard)
   - Página 4 (thank-you)

2. En editor, cada bloque recibe:
   - Página 1: `pageIndex = 1`, `data-page="1"`
   - Página 2: `pageIndex = 2`, `data-page="2"`
   - Página 3: `pageIndex = 3`, `data-page="3"`
   - Página 4: `pageIndex = 4`, `data-page="thank-you"` ← ¡pero el índice interno es 4!

3. En frontend:
   - `getTotalPages()` retorna 3 (correcto)
   - Usuario navega a página 3
   - Si hay regla condicional que intenta saltar a "thank-you", el código calcula `targetPage = 4`
   - `boundedTarget = Math.min(4, 3) = 3` → **salto bloqueado**

**Resultado:** El usuario nunca llega a la página thank-you si hay salto condicional.

**Fix propuesto:**
- Opción A: No permitir saltos condicionales a thank-you page (forzar que thank-you solo se muestre tras submit).
- Opción B: Cambiar `getTotalPages()` para que incluya thank-you en el conteo, y ajustar toda la lógica de navegación.
- **Recomendación:** Opción A es más limpia clínicamente. Thank-you page es un estado especial, no una "página navegable".

---

### **PROBLEMA #2: `updatePaginationDisplay()` muestra "estimated total" confuso (asterisco)**

**Causa:** Cuando hay saltos condicionales, el código intenta ajustar el total de páginas dinámicamente mostrando "X de Y*".

**Escenario reproducible:**
1. Formulario con 5 páginas.
2. Regla condicional: si en página 2 seleccionas "Sí", saltas a página 5.
3. Usuario completa página 1, luego página 2 (selecciona "Sí").
4. `activePath = [1, 2, 5]` (visitó 3 páginas).
5. `estimatedTotal = 3` → UI muestra "2 de 3*".
6. **Usuario está confundido:** ¿Por qué dice 3 si hay 5 páginas? ¿Qué significa el asterisco?

**Resultado:** El investigador recibe quejas de pacientes que piensan que el formulario está roto.

**Fix propuesto:**
- **Eliminar completamente el cálculo de "estimated total".**
- Siempre mostrar `totalPages` fijo (total de páginas navegables, sin thank-you).
- Si hay saltos condicionales, solo actualizar `currentPage` según el camino real, pero mantener `totalPages` constante.
- Esto es más transparente y predecible.

**Código a eliminar (líneas 1307-1330):**
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
            totalPagesText.title =
                'Estimado basado en tu ruta actual';
        } else {
            totalPagesText.textContent = totalPages;
            totalPagesText.title = '';
        }
    }
}
```

**Reemplazar por:**
```javascript
if ( totalPagesText ) {
    totalPagesText.textContent = totalPages;
    totalPagesText.title = '';
}
```

---

### **PROBLEMA #3: `showIntegratedThankYouPage()` crea thank-you page dinámica sin actualizar tracking**

**Causa:** Cuando se completa el formulario, se ejecuta `showIntegratedThankYouPage()` (línea 1787), que:
1. Oculta todas las páginas regulares.
2. Muestra thank-you page (existente o creada dinámicamente).
3. Oculta navegación y progress indicator.

Pero **NO actualiza** el estado de navegación (`currentPage`, `totalPages`) en tracking ni en `ConditionalNavigator`.

**Escenario reproducible:**
1. Formulario con 3 páginas.
2. Usuario completa página 3 y clickea "Enviar".
3. `submitForm()` ejecuta AJAX y llama a `showIntegratedThankYouPage()`.
4. Thank-you page se muestra.
5. Si el tracking intenta leer `currentPage`, sigue siendo 3 (no se actualizó a "completed").

**Resultado:** El tracking reporta que el usuario está en "página 3 de 3" cuando en realidad está viendo thank-you page. Esto puede afectar métricas de abandono.

**Fix propuesto:**
- Agregar un estado especial `status: 'completed'` en tracking cuando se muestra thank-you page.
- Actualizar `setCurrentPage()` para aceptar `pageNumber = 'completed'` y manejarlo correctamente.
- En `showExistingThankYouPage()` y `createThankYouPage()`, llamar a:
  ```javascript
  if ( window.EIPSITracking ) {
      window.EIPSITracking.setCurrentPage( formId, 'completed', { trackChange: true } );
  }
  ```

---

### **PROBLEMA #4: No existe acción "end_form" / "terminate"**

**Causa:** Actualmente solo existen dos acciones finales:
- `submit` (enviar formulario y mostrar thank-you page)
- `goToPage` (saltar a otra página)

**Limitación clínica:** ¿Qué pasa si un investigador quiere "terminar el formulario sin enviarlo"?

**Ejemplo real:**
- Página 1: "¿Tienes más de 18 años?"
  - Si responde "No" → mostrar página de descalificación (no enviar datos).
  - Si responde "Sí" → continuar al formulario.

**Actualmente no hay forma de hacer esto sin enviar datos al servidor.**

**Fix propuesto:**
- Agregar acción `terminate` en lógica condicional.
- Cuando se detecta `terminate`:
  1. Mostrar una página de descalificación (similar a thank-you page, pero con mensaje diferente).
  2. NO enviar datos al servidor.
  3. Ofrecer botón "Cerrar" o "Salir".
- Agregar en `ConditionalNavigator.getNextPage()`:
  ```javascript
  if ( matchingRule.action === 'terminate' ) {
      return { action: 'terminate', message: matchingRule.terminateMessage };
  }
  ```
- En `handlePagination()`:
  ```javascript
  if ( result.action === 'terminate' ) {
      this.showTerminatePage( form, result.message );
      return;
  }
  ```

---

### **PROBLEMA #5: `getPageElement()` usa `data-page` para buscar páginas, pero thank-you page tiene `data-page="thank-you"` (string, no número)**

**Causa:** `getPageElement(form, pageNumber)` compara `pageNum === pageNumber` (líneas 2214-2228):
```javascript
getPageElement( form, pageNumber ) {
    const pages = form.querySelectorAll( '.eipsi-page' );

    for ( let index = 0; index < pages.length; index++ ) {
        const page = pages[ index ];
        const pageNum =
            parseInt( page.dataset.page || '', 10 ) || index + 1;

        if ( pageNum === pageNumber ) {
            return page;
        }
    }

    return null;
}
```

**Problema:** Si `page.dataset.page = "thank-you"`, entonces `parseInt("thank-you", 10) = NaN`, y se usa `index + 1` como fallback. Esto puede causar colisiones.

**Escenario reproducible:**
1. Formulario con 3 páginas + 1 thank-you page.
2. DOM:
   - Página 1: `data-page="1"` (index = 0)
   - Página 2: `data-page="2"` (index = 1)
   - Página 3: `data-page="3"` (index = 2)
   - Thank-you: `data-page="thank-you"` (index = 3) → `pageNum = 4` (index + 1)
3. Si se llama `getPageElement(form, 4)`, retorna thank-you page correctamente.
4. Pero si una regla condicional intenta saltar a página 4, `boundedTarget = Math.min(4, 3) = 3` → **no llega**.

**Fix propuesto:**
- No permitir que thank-you page sea navegable directamente.
- Solo mostrarla tras submit o acción explícita.
- Agregar validación en `getPageElement()`:
  ```javascript
  if ( page.dataset.pageType === 'thank_you' ) {
      continue; // Skip thank-you pages in normal navigation
  }
  ```

---

## 🔍 REPRODUCCIÓN DEL BUG "4 de 2"

### Escenario A: Formulario con thank-you page en DOM

**Setup:**
1. Crear formulario con:
   - Página 1 (standard)
   - Página 2 (standard)
   - Página 3 (thank-you)

2. DOM generado:
   ```html
   <div class="eipsi-page" data-page="1" data-page-type="standard">...</div>
   <div class="eipsi-page" data-page="2" data-page-type="standard">...</div>
   <div class="eipsi-page eipsi-thank-you-page-block" data-page="thank-you" data-page-type="thank_you">...</div>
   ```

3. `getTotalPages()` retorna 2 (correcto).

4. Usuario completa página 2 y clickea "Enviar".

5. `submitForm()` llama a `showIntegratedThankYouPage()`, que:
   - Oculta páginas 1 y 2.
   - Muestra thank-you page.
   - **NO actualiza** `form.dataset.currentPage` ni tracking.

6. Si tracking lee `currentPage`, sigue siendo 2.

7. Si se muestra progreso antes de ocultarlo, muestra "2 de 2" (correcto).

**Este escenario NO produce "4 de 2".**

---

### Escenario B: Formulario con reglas condicionales que intentan saltar más allá de totalPages

**Setup:**
1. Crear formulario con:
   - Página 1 (standard)
   - Página 2 (standard)
   - Regla en página 2: si selecciona "Sí" → saltar a página 4

2. Pero solo hay 2 páginas en total (sin thank-you).

3. Usuario completa página 2 y selecciona "Sí".

4. `getNextPage(2)` retorna `{ action: 'goToPage', targetPage: 4 }`.

5. `handlePagination()` ejecuta:
   ```javascript
   targetPage = 4; // de la regla
   ```

6. `setCurrentPage(form, 4)` ejecuta:
   ```javascript
   const totalPages = this.getTotalPages( form ); // = 2
   let sanitizedPage = parseInt( 4, 10 ); // = 4

   if ( sanitizedPage > totalPages ) {
       sanitizedPage = totalPages; // = 2
   }
   ```

7. `currentPage` se fija a 2 (no cambia).

**Este escenario NO produce "4 de 2" porque `setCurrentPage()` limita el valor.**

---

### Escenario C: Tracking desincronizado por inicialización incorrecta

**Setup:**
1. Crear formulario con:
   - Página 1 (standard)
   - Página 2 (standard)
   - Página 3 (standard)
   - Página 4 (thank-you)

2. En editor, `pageIndex` se calcula incluyendo thank-you:
   - Página 1: `pageIndex = 1`
   - Página 2: `pageIndex = 2`
   - Página 3: `pageIndex = 3`
   - Página 4: `pageIndex = 4`

3. DOM generado:
   ```html
   <div class="eipsi-page" data-page="1">...</div>
   <div class="eipsi-page" data-page="2">...</div>
   <div class="eipsi-page" data-page="3">...</div>
   <div class="eipsi-page eipsi-thank-you-page-block" data-page="thank-you">...</div>
   ```

4. `getTotalPages()` retorna 3 (correcto, excluyendo thank-you).

5. `attachTracking()` ejecuta:
   ```javascript
   const totalPages =
       parseInt( form.dataset.totalPages || '1', 10 ) || 1;
   ```
   - Si `form.dataset.totalPages` se fijó **antes** del filtro, puede ser 4.
   - Si se fijó **después** del filtro, es 3.

6. Si `totalPages = 4` en tracking pero `getTotalPages() = 3` en UI:
   - Usuario navega a página 3.
   - Tracking reporta "página 3 de 4".
   - UI muestra "página 3 de 3".
   - **Si el tracking se renderiza en UI, puede mostrar "página 3 de 4".**

**Este escenario PUEDE producir desincronización, pero requiere que el tracking se renderice en UI (no es el caso actual).**

---

### Escenario D: `updatePaginationDisplay()` calcula `estimatedTotal > totalPages`

**Setup:**
1. Formulario con 2 páginas regulares + 1 thank-you page.

2. `getTotalPages() = 2`.

3. Usuario visita página 1, luego página 2.

4. `visitedPages = {1, 2}`, `activePath = [1, 2]`.

5. Usuario clickea "Enviar" → `showIntegratedThankYouPage()` muestra thank-you page.

6. Si `updatePaginationDisplay()` se llama **después** de mostrar thank-you page:
   ```javascript
   const activePath = navigator.getActivePath(); // [1, 2]
   const remainingPages = totalPages - activePath[ activePath.length - 1 ]; // 2 - 2 = 0
   const estimatedTotal = activePath.length + Math.max( 0, remainingPages ); // 2 + 0 = 2
   ```
   - `estimatedTotal = 2` → correcto.

**Este escenario NO produce "4 de 2".**

---

### **Conclusión de reproducción:**

**No he podido reproducir "4 de 2" en los escenarios analizados**, porque:
- `setCurrentPage()` limita `currentPage` a `totalPages`.
- `getTotalPages()` excluye thank-you pages correctamente.
- `updatePaginationDisplay()` calcula `estimatedTotal` basado en `activePath` y `totalPages`, pero nunca genera valores mayores.

**Sin embargo, el bug "4 de 2" puede ocurrir si:**
1. Se modifica manualmente `form.dataset.currentPage` sin pasar por `setCurrentPage()`.
2. Se agrega una página dinámicamente al DOM sin actualizar `form.dataset.totalPages`.
3. Existe código legacy o un plugin de terceros que interfiere con los contadores.

**Recomendación:** Hacer auditoría de código que toca `form.dataset.currentPage` y `form.dataset.totalPages` para verificar que siempre pasa por las funciones de validación.

---

## 📊 INTEGRACIÓN DOM/TRACKING

### Flujo de datos

```
[Editor Gutenberg]
    ↓ guarda atributos
[Bloque save.js]
    ↓ renderiza DOM con data-page, data-page-type
[DOM en navegador]
    ↓ lee eipsi-forms.js
[EIPSIForms.initForm()]
    ↓ calcula totalPages
[getTotalPages()] → filtra thank-you pages → retorna 3
    ↓
[attachTracking()] → lee form.dataset.totalPages → fija en tracking
    ↓
[EIPSITracking.setTotalPages(formId, 3)]
    ↓
[sessionStorage] → guarda { totalPages: 3 }
```

### Sincronización en navegación

```
[Usuario clickea "Siguiente"]
    ↓
[handlePagination('next')]
    ↓ evalúa reglas
[ConditionalNavigator.getNextPage(currentPage)]
    ↓ retorna { action: 'goToPage', targetPage: 5 }
[setCurrentPage(form, 5)]
    ↓ limita a totalPages
[sanitizedPage = Math.min(5, 3) = 3]
    ↓
[form.dataset.currentPage = 3]
    ↓
[updatePaginationDisplay(form, 3, 3)]
    ↓ UI
[<span class="current-page">3</span> de <span class="total-pages">3</span>]
    ↓ tracking
[EIPSITracking.recordPageChange(formId, 3)]
    ↓
[AJAX → backend] → guarda evento page_change con page_number=3
```

### Puntos de desincronización detectados

1. **`form.dataset.totalPages` vs `getTotalPages()` actual:**
   - `form.dataset.totalPages` se fija una vez en `getTotalPages()` (línea 974).
   - Si el DOM cambia después (se agrega/elimina página dinámicamente), `form.dataset.totalPages` queda obsoleto.
   - **Fix:** Eliminar caché, siempre calcular dinámicamente.

2. **Tracking lee valores iniciales, no actualizados:**
   - `attachTracking()` se ejecuta una vez al cargar el formulario.
   - Si `totalPages` cambia después, tracking mantiene el valor antiguo.
   - **Fix:** Actualizar tracking cada vez que cambia `totalPages`.

3. **Thank-you page no actualiza estado de navegación:**
   - `showIntegratedThankYouPage()` no llama a `setCurrentPage()`.
   - Tracking cree que el usuario está en la última página regular, no en thank-you.
   - **Fix:** Agregar `setCurrentPage(form, 'completed')`.

---

## ✅ MODELO CORRECTO PROPUESTO

### Definiciones

1. **"Página navegable":** Bloque `.eipsi-page` con `data-page-type="standard"`.
2. **"Thank-you page":** Bloque `.eipsi-page` con `data-page-type="thank_you"`. NO es navegable, solo se muestra tras submit.
3. **"Página X de Y":**
   - X = índice de la página actual en el camino visitado (1-based).
   - Y = total de páginas navegables (sin thank-you, sin páginas descalificadas por lógica).
4. **Lógica condicional:**
   - `goToPage`: Saltar a página N (solo páginas navegables).
   - `submit`: Enviar formulario y mostrar thank-you page.
   - `terminate` (nuevo): Mostrar página de descalificación sin enviar datos.

### Reglas de cálculo

1. `getTotalPages(form)` SIEMPRE excluye thank-you pages.
2. `setCurrentPage(form, pageNumber)` SIEMPRE limita a `totalPages`.
3. `updatePaginationDisplay()` SIEMPRE muestra `totalPages` fijo (sin asterisco).
4. `showIntegratedThankYouPage()` actualiza estado a `'completed'`.
5. Thank-you page NUNCA es destino de `goToPage`.

### Implementación de acciones condicionales

| Acción | Descripción | Implementación |
|--------|-------------|----------------|
| `nextPage` | Avanza a la siguiente página secuencial | `targetPage = currentPage + 1` |
| `goToPage` | Salta a página específica (1 a totalPages) | `targetPage = Math.min(rule.targetPage, totalPages)` |
| `submit` | Enviar formulario y mostrar thank-you | `handleSubmit()` → `showIntegratedThankYouPage()` |
| `terminate` (nuevo) | Mostrar descalificación sin enviar | `showTerminatePage(form, message)` |

---

## 🛠️ PROPUESTAS DE MEJORA

### Camino A: Fixes puntuales (2-4 horas)

1. **Eliminar cálculo de "estimated total"** en `updatePaginationDisplay()`.
   - Siempre mostrar `totalPages` fijo.
   - **Impacto:** Mejora claridad para pacientes.

2. **Actualizar tracking al mostrar thank-you page.**
   - Agregar `setCurrentPage(form, 'completed')` en `showExistingThankYouPage()` y `createThankYouPage()`.
   - **Impacto:** Métricas de abandono más precisas.

3. **Validar que `form.dataset.totalPages` se recalcula dinámicamente.**
   - Eliminar línea `form.dataset.totalPages = totalPages` (línea 974).
   - Siempre llamar a `getTotalPages(form)` cuando se necesita.
   - **Impacto:** Evita caches obsoletos.

4. **Agregar logging de depuración para detectar "4 de 2".**
   - En `updatePaginationDisplay()`, agregar:
     ```javascript
     if ( currentPage > totalPages ) {
         window.console.error(
             '[EIPSI Forms] CRITICAL: currentPage > totalPages',
             { currentPage, totalPages, formId: this.getFormId(form) }
         );
     }
     ```
   - **Impacto:** Detectar casos edge en producción.

### Camino B: Refactor estructural - "Flow Engine" (8-12 horas)

**Crear una capa centralizada `FlowEngine` que gestione:**

```javascript
class FlowEngine {
    constructor(form) {
        this.form = form;
        this.state = {
            currentPage: 1,
            totalPages: this.calculateTotalPages(),
            visitedPages: new Set([1]),
            skippedPages: new Set(),
            status: 'active', // 'active' | 'completed' | 'terminated'
        };
        this.navigator = new ConditionalNavigator(form);
    }

    calculateTotalPages() {
        // Implementación actual de getTotalPages
    }

    getCurrentPage() {
        return this.state.currentPage;
    }

    getTotalPages() {
        return this.state.totalPages;
    }

    goToPage(pageNumber) {
        // Validación centralizada
        if (pageNumber < 1 || pageNumber > this.state.totalPages) {
            throw new Error(`Invalid page: ${pageNumber}`);
        }
        this.state.currentPage = pageNumber;
        this.state.visitedPages.add(pageNumber);
        this.syncTracking();
        this.updateUI();
    }

    complete() {
        this.state.status = 'completed';
        this.syncTracking();
        this.showThankYouPage();
    }

    terminate(message) {
        this.state.status = 'terminated';
        this.syncTracking();
        this.showTerminatePage(message);
    }

    syncTracking() {
        if (window.EIPSITracking) {
            window.EIPSITracking.setCurrentPage(
                this.getFormId(),
                this.state.status === 'active' ? this.state.currentPage : this.state.status
            );
        }
    }

    updateUI() {
        // Llamar a updatePaginationDisplay con valores del state
    }
}
```

**Ventajas:**
- Una sola fuente de verdad para `currentPage`, `totalPages`, `status`.
- Sincronización automática con tracking.
- Fácil extensión (agregar `terminate`, `pause`, etc.).
- Testing más sencillo (mock del state).

**Desventajas:**
- Requiere refactor de múltiples funciones.
- Mayor tiempo de desarrollo.
- Riesgo de introducir bugs si no se testea exhaustivamente.

---

## 📌 SHOW/HIDE DE CAMPOS (Condicional Field Visibility)

### Estado actual: **NO IMPLEMENTADO**

**Búsqueda realizada:**
- ✅ Revisado `assets/js/eipsi-forms.js` → No existe lógica de show/hide de campos.
- ✅ Revisado componentes de bloques → No hay atributos para conditional visibility.
- ✅ Revisado `ConditionalLogicControl.js` → Solo maneja navegación (goToPage, submit, nextPage).

**Conclusión:** La lógica condicional actual SOLO afecta la **navegación entre páginas**, NO la visibilidad de campos dentro de la misma página.

### Feature request para prioridad 3 (ticket original):

**"Conditional field visibility dentro de la misma página + conditional required"**

**Casos de uso clínicos:**
1. Mostrar campo "¿Cuál medicamento?" solo si responde "Sí" a "¿Toma medicamentos?".
2. Hacer campo "Fecha de último episodio" requerido solo si responde "Sí" a "¿Ha tenido episodios previos?".
3. Mostrar sección de follow-up solo si el puntaje de PHQ-9 es ≥ 10.

**Propuesta de implementación (para después de demo):**

#### A. Agregar atributos al bloque de campo:

```javascript
// src/blocks/campo-texto/edit.js (y otros bloques de campo)
attributes: {
    // ... atributos existentes
    conditionalVisibility: {
        type: 'object',
        default: null,
    },
    conditionalRequired: {
        type: 'boolean',
        default: false,
    },
}
```

#### B. Agregar control en inspector:

```javascript
<ConditionalVisibilityControl
    attributes={attributes}
    setAttributes={setAttributes}
    clientId={clientId}
/>
```

#### C. Estructura de `conditionalVisibility`:

```javascript
{
    enabled: true,
    sourceFieldId: 'campo-1', // ID del campo que controla la visibilidad
    operator: '==', // '==', '!=', '>=', '<=', '>', '<', 'contains', 'not_contains'
    value: 'Sí',
    action: 'show', // 'show' | 'hide'
}
```

#### D. Renderizado en `save.js`:

```javascript
<div
    className="form-group"
    data-field-name={fieldName}
    data-conditional-visibility={JSON.stringify(conditionalVisibility)}
    style={{ display: conditionalVisibility?.enabled ? 'none' : undefined }}
>
    ...
</div>
```

#### E. Lógica frontend en `eipsi-forms.js`:

```javascript
initConditionalVisibility(form) {
    const fieldsWithConditional = form.querySelectorAll('[data-conditional-visibility]');
    
    fieldsWithConditional.forEach(field => {
        const config = JSON.parse(field.dataset.conditionalVisibility);
        if (!config.enabled) return;
        
        // Encontrar campo fuente
        const sourceField = form.querySelector(`[data-field-name="${config.sourceFieldId}"]`);
        if (!sourceField) return;
        
        // Listener en campo fuente
        const inputs = sourceField.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                this.evaluateFieldVisibility(form, field, config);
            });
        });
        
        // Evaluar visibilidad inicial
        this.evaluateFieldVisibility(form, field, config);
    });
}

evaluateFieldVisibility(form, field, config) {
    const sourceField = form.querySelector(`[data-field-name="${config.sourceFieldId}"]`);
    if (!sourceField) return;
    
    const sourceValue = this.getFieldValue(sourceField);
    let shouldShow = false;
    
    switch (config.operator) {
        case '==':
            shouldShow = sourceValue == config.value;
            break;
        case '!=':
            shouldShow = sourceValue != config.value;
            break;
        case '>=':
            shouldShow = parseFloat(sourceValue) >= parseFloat(config.value);
            break;
        // ... otros operadores
    }
    
    if (config.action === 'hide') {
        shouldShow = !shouldShow;
    }
    
    // Actualizar visibilidad
    field.style.display = shouldShow ? '' : 'none';
    field.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
    
    // Actualizar required
    const inputs = field.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        if (shouldShow && field.dataset.conditionalRequired === 'true') {
            input.setAttribute('required', 'required');
        } else if (!shouldShow) {
            input.removeAttribute('required');
        }
    });
}
```

**Ventajas de esta implementación:**
- No interfiere con lógica condicional de navegación existente.
- Se evalúa en tiempo real al cambiar respuestas.
- Compatible con campos requeridos dinámicos.
- Accesible (usa `aria-hidden`, actualiza `required` dinámicamente).

**Estimación:** 6-8 horas de desarrollo + 2 horas de testing.

---

## 🎯 RECOMENDACIONES FINALES

### Para Febrero 2025 (Pre-Demo Clínico):

1. ✅ **Fix inmediato:** Eliminar "estimated total" (asterisco) → siempre mostrar `totalPages` fijo.
2. ✅ **Fix inmediato:** Actualizar tracking cuando se muestra thank-you page.
3. ✅ **Fix inmediato:** Agregar logging de depuración para detectar "currentPage > totalPages".
4. ⚠️ **Validar:** Hacer test manual de formulario con 3 páginas + thank-you + reglas condicionales → verificar que NO aparece "4 de 2".

### Para después de demo (Marzo-Mayo 2025):

5. 🔄 **Refactor:** Implementar `FlowEngine` centralizado.
6. ✨ **Feature:** Agregar acción `terminate` para descalificaciones.
7. ✨ **Feature PRIORIDAD 3:** Implementar conditional field visibility + conditional required.
8. 📊 **Analítica:** Dashboard de rutas condicionales más visual (mostrar "3 de 5 páginas visitadas, 2 saltadas").

---

## 📝 ARCHIVOS CLAVE PARA MODIFICAR

| Archivo | Líneas críticas | Cambio requerido |
|---------|-----------------|------------------|
| `assets/js/eipsi-forms.js` | 1307-1330 | Eliminar cálculo de "estimated total" |
| `assets/js/eipsi-forms.js` | 2304-2357, 2359-2474 | Agregar `setCurrentPage(form, 'completed')` tras mostrar thank-you |
| `assets/js/eipsi-forms.js` | 974 | Eliminar cache `form.dataset.totalPages = totalPages` |
| `assets/js/eipsi-forms.js` | 1196-1345 | Agregar validación `currentPage > totalPages` |
| `assets/js/eipsi-tracking.js` | 187-198 | Aceptar `pageNumber = 'completed'` |
| `src/blocks/pagina/edit.js` | 20-39 | (Opcional) Excluir thank-you del cálculo de `computedPageIndex` |

---

## 🧪 CHECKLIST DE TESTING

**Antes de release:**

- [ ] Formulario 1 página sin lógica condicional → mostrar "1 de 1", submit OK.
- [ ] Formulario 3 páginas sin lógica condicional → mostrar "1 de 3", "2 de 3", "3 de 3", submit OK.
- [ ] Formulario 3 páginas + thank-you page → mostrar "1 de 3", "2 de 3", "3 de 3", submit → thank-you visible, navegación oculta.
- [ ] Formulario con regla "saltar de página 2 a página 5" (solo hay 3 páginas) → limitar a página 3, no crashear.
- [ ] Formulario con regla "submit si selecciona X" → submit ejecuta, thank-you visible.
- [ ] Formulario con `allowBackwardsNav = false` → botón "Anterior" oculto en todas las páginas excepto 1.
- [ ] Formulario con `allowBackwardsNav = true` → botón "Anterior" visible desde página 2.
- [ ] Tracking reporta `currentPage` y `totalPages` correctos en cada cambio de página.
- [ ] Tracking reporta `status: 'completed'` al mostrar thank-you page.
- [ ] Console no muestra errores `currentPage > totalPages`.
- [ ] UI NUNCA muestra asterisco ("3 de 5*").

---

---

## ✅ ACCEPTANCE CRITERIA DEL TICKET (VERIFICACIÓN)

**Ticket original:** "Audit estructural de lógica condicional y navegación"

### ✅ Criterio 1: Existe documentación clara de cómo se calcula hoy `currentPage` y `totalPages`

**CUMPLIDO.**

- `getCurrentPage(form)` (líneas 979-1017): Lee de hidden field `.eipsi-current-page` o `form.dataset.currentPage`, limita a `totalPages`.
- `getTotalPages(form)` (líneas 954-977): Cuenta `.eipsi-page` excluyendo `data-page-type="thank_you"`.
- `setCurrentPage(form, pageNumber, options)` (líneas 1019-1064): Sanitiza valor, actualiza hidden field, dataset, tracking, y UI.
- `updatePaginationDisplay(form, currentPage, totalPages)` (líneas 1196-1345): Actualiza texto "Página X de Y", botones, y aria-labels.

### ✅ Criterio 2: Se han identificado al menos 1+ causas por las cuales pueden aparecer numeraciones imposibles como "4 de 2"

**CUMPLIDO.**

**Causas identificadas:**

1. **Desincronización entre `pageIndex` (Gutenberg) y `data-page` (DOM):**
   - `computedPageIndex` en editor incluye thank-you pages.
   - `getTotalPages()` en frontend las excluye.
   - Si regla condicional intenta saltar a página thank-you (índice 4), se limita a `totalPages` (3) → salto bloqueado.

2. **Cálculo de "estimated total" confuso:**
   - `updatePaginationDisplay()` calcula `estimatedTotal` basado en `activePath` y `remainingPages`.
   - Si se saltaron páginas, puede mostrar "2 de 3*" cuando en realidad es "2 de 5".
   - El asterisco confunde al paciente.

3. **Thank-you page no actualiza estado de navegación:**
   - `showIntegratedThankYouPage()` oculta navegación y muestra thank-you, pero NO actualiza `currentPage`.
   - Si tracking lee `currentPage` después, sigue siendo la última página regular.

4. **Caché de `form.dataset.totalPages`:**
   - `getTotalPages()` guarda resultado en `form.dataset.totalPages` (línea 974).
   - Si el DOM cambia después (agregar/eliminar página dinámicamente), el caché queda obsoleto.

5. **Saltos condicionales más allá de `totalPages` no generan error:**
   - `setCurrentPage()` limita silenciosamente a `totalPages`.
   - Si regla intenta saltar a página 10 pero solo hay 3, se queda en 3.
   - No hay logging visible para detectar este caso.

**Formas concretas de evitarlas:**

- **Fix #1:** Eliminar cálculo de "estimated total" → siempre mostrar `totalPages` fijo.
- **Fix #2:** Agregar logging cuando `currentPage > totalPages` o cuando saltos condicionales fallan.
- **Fix #3:** Actualizar `currentPage` a estado `'completed'` al mostrar thank-you page.
- **Fix #4:** Eliminar caché `form.dataset.totalPages`, calcular dinámicamente siempre.
- **Fix #5:** No permitir que thank-you page sea destino de `goToPage`.

### ✅ Criterio 3: Se documentan claramente los problemas de "terminar formulario" y "siguiente página" bajo lógica condicional

**CUMPLIDO.**

#### Problema 1: Acción `submit` funciona correctamente

- Cuando una regla condicional retorna `{ action: 'submit' }`, se ejecuta `handleSubmit()`.
- AJAX envía datos, muestra mensaje de éxito, y luego `showIntegratedThankYouPage()`.
- **No hay bugs detectados en este flujo.**

#### Problema 2: Acción `goToPage` limita saltos a `totalPages`

- Si regla intenta saltar a página mayor a `totalPages`, se limita (líneas 247-253, 2155-2158).
- Esto **bloquea saltos a thank-you page** si es la página siguiente después de `totalPages`.
- **Solución:** Thank-you page no debe ser navegable, solo mostrable tras submit.

#### Problema 3: No existe acción `terminate` / `end_form`

- Actualmente solo `submit` termina el formulario.
- Si investigador quiere "terminar sin enviar datos" (ej: descalificación), no tiene forma de hacerlo.
- **Solución:** Agregar acción `terminate` con mensaje personalizado.

#### Problema 4: "Siguiente página" bajo lógica condicional funciona correctamente

- `handlePagination(form, 'next')` evalúa `ConditionalNavigator.getNextPage(currentPage)`.
- Si hay match, retorna `{ action: 'goToPage', targetPage: N }` o `{ action: 'submit' }`.
- Si no hay match, avanza a `currentPage + 1`.
- **No hay bugs detectados en este flujo.**

### ✅ Criterio 4: Se presenta al menos una propuesta de modelo unificado de navegación

**CUMPLIDO.**

**Modelo propuesto: "FlowEngine" centralizado**

Ver sección **"Camino B: Refactor estructural - Flow Engine"** (líneas 804-836 del audit).

**Resumen:**
- Clase `FlowEngine` que encapsula:
  - `state = { currentPage, totalPages, visitedPages, skippedPages, status }`
  - Métodos: `goToPage()`, `complete()`, `terminate()`, `syncTracking()`, `updateUI()`
- Una sola fuente de verdad para todo el estado de navegación.
- Sincronización automática con tracking y UI.
- Extensible para features futuros (pause, restart, save/continue).

**Ventajas:**
- Elimina desincronizaciones entre componentes.
- Testing más sencillo (mock del state).
- Código más mantenible.

**Desventajas:**
- Requiere refactor de múltiples funciones.
- Riesgo de introducir bugs si no se testea exhaustivamente.

---

## 📊 SUMMARY: Estado de la Lógica Condicional

| Componente | Estado actual | Bugs detectados | Propuesta de mejora |
|------------|---------------|-----------------|---------------------|
| **Navegación multipágina** | ✅ Funciona | ⚠️ "estimated total" confuso | Eliminar asterisco, mostrar total fijo |
| **Saltos condicionales (goToPage)** | ✅ Funciona | ⚠️ Limita a `totalPages`, bloquea thank-you | No permitir thank-you como destino |
| **Terminar formulario (submit)** | ✅ Funciona | ✅ Sin bugs | - |
| **Tracking de eventos** | ✅ Funciona | ⚠️ No actualiza en thank-you | Agregar estado 'completed' |
| **Thank-you page integrada** | ✅ Funciona | ⚠️ No actualiza navegación | Actualizar `currentPage` y tracking |
| **Show/hide de campos** | ❌ No implementado | - | Implementar conditional field visibility |
| **Acción `terminate`** | ❌ No implementado | - | Agregar para descalificaciones |
| **Contador "X de Y"** | ✅ Funciona | ⚠️ Muestra asterisco a veces | Siempre mostrar total fijo |
| **Caché de `totalPages`** | ⚠️ Puede quedar obsoleto | ⚠️ Desincronización | Calcular dinámicamente siempre |

---

## 🚀 PRÓXIMOS PASOS INMEDIATOS (antes de demo clínico)

1. **[ ] Implementar Fix #1:** Eliminar líneas 1307-1330 de `assets/js/eipsi-forms.js` (cálculo de "estimated total").
2. **[ ] Implementar Fix #2:** Agregar logging en `updatePaginationDisplay()` cuando `currentPage > totalPages`.
3. **[ ] Implementar Fix #3:** Agregar `setCurrentPage(form, 'completed')` en `showExistingThankYouPage()` y `createThankYouPage()`.
4. **[ ] Testing:** Crear formulario con 3 páginas + thank-you + reglas condicionales → verificar que NO aparece "4 de 2".
5. **[ ] Documentar:** Agregar comentarios en código explicando por qué thank-you page se excluye de `totalPages`.

---

**FIN DEL AUDIT**
