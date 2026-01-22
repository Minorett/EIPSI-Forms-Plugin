# 🧪 TESTING: Exit and Continue v1.3.15

**Fecha:** 2025-01-25  
**Versión:** 1.3.15  
**Feature:** Save & Continue - Recuperación de sesión  
**Estado:** ✅ IMPLEMENTADO - LISTO PARA TESTING

---

## 🎯 OBJETIVO DEL TEST

Verificar que el sistema de "Exit and Continue" funciona correctamente:
- Usuario puede salir del formulario y volver sin perder progreso
- Modal de recuperación aparece correctamente
- Datos se restauran en la página correcta
- Autosave funciona en background
- Sesión se limpia al enviar formulario

---

## 📋 PRE-REQUISITOS

### 1. Preparación del Entorno

```bash
# 1. Verificar archivos copiados
ls -lah assets/js/eipsi-save-continue.js
ls -lah assets/css/eipsi-save-continue.css

# 2. Verificar build
npm run lint:js  # Debe salir sin errores
npm run build    # Debe compilar 12 bloques

# 3. Verificar versión del plugin
grep "Version:" eipsi-forms.php
# Debe mostrar: Version: 1.3.15
```

### 2. WordPress Setup

1. Activar el plugin EIPSI Forms v1.3.15
2. Crear un formulario de prueba con al menos 3 páginas:
   - Página 1: Consentimiento
   - Página 2: Datos demográficos (nombre, edad, etc.)
   - Página 3: Preguntas clínicas
3. Publicar el formulario en una página pública

---

## ✅ TEST SUITE COMPLETO

### TEST 1: Verificar que el JS se carga

**Pasos:**
1. Abrir la página del formulario en frontend
2. Abrir DevTools (F12) → Network tab
3. Recargar página (Ctrl+R)

**Resultado Esperado:**
- ✅ Aparece `eipsi-save-continue.js` en la lista de archivos cargados
- ✅ Status: 200 OK
- ✅ Size: ~25 KB
- ✅ Aparece `eipsi-save-continue.css` en la lista
- ✅ Status: 200 OK
- ✅ Size: ~7.3 KB

**Si falla:**
- Verificar que el archivo existe en `assets/js/` y `assets/css/`
- Verificar que `eipsi_forms_enqueue_frontend_assets()` tiene el enqueue (líneas 594-611)

---

### TEST 2: Verificar que el script se inicializa

**Pasos:**
1. Con el formulario abierto, abrir DevTools → Console tab
2. Ejecutar: `window.EIPSISaveContinue`

**Resultado Esperado:**
```javascript
ƒ EIPSISaveContinue(form, config) { ... }
```

**Si falla:**
- Revisar Console por errores de JavaScript
- Verificar que el archivo se cargó correctamente (Test 1)

---

### TEST 3: Autosave funciona

**Pasos:**
1. Abrir el formulario
2. Aceptar consentimiento (ir a página 2)
3. Completar el campo "Nombre" con "Juan Pérez"
4. Esperar 2 segundos (debounce)
5. Abrir DevTools → Application tab → IndexedDB
6. Expandir `eipsi_forms` → `partial_responses`
7. Click en la fila para ver datos

**Resultado Esperado:**
```javascript
{
  form_id: "TEST-123abc",
  participant_id: "p-abc123def456",
  session_id: "sess-1234567890-abc123",
  page_index: 1,  // o 2 dependiendo de cómo se indexe
  responses: {
    nombre: "Juan Pérez"
  },
  updated_at: "2025-01-25T15:30:00.000Z"
}
```

**Si falla:**
- Revisar Console por errores
- Verificar que `setupAutosave()` se ejecutó
- Verificar que IndexedDB está disponible en el navegador

---

### TEST 4: Modal de recuperación aparece al recargar

**Pasos:**
1. Continuar del Test 3 (ya hay datos guardados)
2. Presionar F5 (reload) o cerrar pestaña y volver a abrir

**Resultado Esperado:**
- ✅ Modal aparece INMEDIATAMENTE al cargar la página
- ✅ Fondo oscuro semi-transparente (overlay)
- ✅ Caja blanca central con el texto:
  ```
  Continuar donde quedaste
  
  Tenés respuestas guardadas del 25 de enero de 2025, 15:30.
  
  ¿Querés continuar donde quedaste?
  
  [Continuar] [Empezar de nuevo]
  ```
- ✅ Modal está centrado vertical y horizontalmente
- ✅ Botones tienen buen tamaño (touch targets 44×44px)
- ✅ Animación de entrada suave (slide-in)

**Si falla:**
- Revisar Console → Buscar `[EIPSI Save & Continue]` logs
- Verificar que `checkForPartialResponse()` se ejecuta
- Verificar que `showRecoveryPopup()` se llama
- Verificar que el CSS se cargó (Test 1)

---

### TEST 5: Botón "Continuar" restaura sesión

**Pasos:**
1. Con el modal abierto (Test 4)
2. Click en botón "Continuar"

**Resultado Esperado:**
- ✅ Modal se cierra con animación suave
- ✅ Formulario aparece en página 2 (donde estaba el usuario)
- ✅ Campo "Nombre" tiene el valor "Juan Pérez"
- ✅ Usuario puede seguir completando el formulario

**Si falla:**
- Revisar Console por errores en `restorePartial()`
- Verificar que `setFieldValue()` se ejecuta
- Verificar que `EIPSIForms.setCurrentPage()` funciona

---

### TEST 6: Botón "Empezar de nuevo" borra sesión

**Pasos:**
1. Repetir Test 3 y Test 4 (guardar datos y recargar)
2. Cuando aparece el modal, click en "Empezar de nuevo"

**Resultado Esperado:**
- ✅ Modal se cierra
- ✅ Formulario aparece en página 1 (consentimiento)
- ✅ Todos los campos están vacíos
- ✅ Sesión borrada de IndexedDB
- ✅ Si se recarga de nuevo (F5), NO aparece el modal

**Verificación adicional:**
1. DevTools → Application → IndexedDB → `eipsi_forms` → `partial_responses`
2. La fila debe estar vacía o no existir

**Si falla:**
- Revisar Console por errores en `discardPartial()`
- Verificar que `clearFromIDB()` se ejecuta
- Verificar que `discardFromServer()` se ejecuta

---

### TEST 7: BeforeUnload warning funciona

**Pasos:**
1. Abrir formulario nuevo
2. Ir a página 2
3. Completar un campo
4. Intentar cerrar la pestaña (Ctrl+W) o cambiar de URL

**Resultado Esperado:**
- ✅ Navegador muestra alerta nativa:
  ```
  Tienes cambios sin guardar. ¿Seguro que quieres salir?
  
  [Quedarse en la página] [Salir]
  ```

**Si falla:**
- Verificar que `setupBeforeUnload()` se ejecuta
- Verificar que el listener se agregó correctamente
- Algunos navegadores no muestran el mensaje personalizado (es normal)

---

### TEST 8: Sesión se limpia al enviar formulario

**Pasos:**
1. Abrir formulario nuevo
2. Completar todas las páginas hasta la última
3. Click en "Enviar"
4. Esperar a que el formulario se envíe correctamente
5. Volver a abrir la página del formulario

**Resultado Esperado:**
- ✅ Formulario inicia desde consentimiento (página 1)
- ✅ NO aparece el modal de "Continuar donde quedaste"
- ✅ IndexedDB está vacío (partial_responses sin datos)

**Verificación adicional:**
1. DevTools → Console → Buscar logs de `handleFormCompleted()`
2. Debe mostrar: `[EIPSI Save & Continue] Session cleared`

**Si falla:**
- Revisar que `handleFormCompleted()` se llama al enviar
- Verificar que `clearFromIDB()` se ejecuta
- Verificar que el autosave se detiene (clearInterval)

---

### TEST 9: Autosave periódico (30 segundos)

**Pasos:**
1. Abrir formulario nuevo
2. Ir a página 2
3. Completar campo "Nombre" con "María López"
4. NO tocar nada más
5. Esperar 30 segundos
6. DevTools → Application → IndexedDB → `eipsi_forms` → `partial_responses`

**Resultado Esperado:**
- ✅ Datos aparecen en IndexedDB después de 30 segundos
- ✅ `updated_at` se actualiza cada 30 segundos
- ✅ En Console aparece log: `[EIPSI Save & Continue] Autosave triggered`

**Si falla:**
- Verificar que `setupAutosave()` configura el interval correctamente
- Verificar que `AUTOSAVE_INTERVAL = 30000` (línea 17 del JS)

---

### TEST 10: Dark Mode funciona en modal

**Pasos:**
1. Activar Dark Mode en el formulario (si tiene toggle)
   - O en DevTools → Console: `document.body.setAttribute('data-theme', 'dark')`
2. Guardar datos y recargar (F5)
3. Modal aparece

**Resultado Esperado:**
- ✅ Modal tiene fondo oscuro (#1e293b)
- ✅ Texto es blanco/claro (#f8fafc)
- ✅ Botones tienen colores adaptados a dark mode
- ✅ Overlay es más oscuro (rgba(0, 0, 0, 0.85))

**Si falla:**
- Verificar que el CSS tiene `@media (prefers-color-scheme: dark)` y `[data-theme="dark"]`
- Verificar que el CSS se cargó correctamente

---

### TEST 11: Responsive en Mobile

**Pasos:**
1. Abrir formulario en mobile (o DevTools → Toggle Device Toolbar)
2. Seleccionar iPhone 12 Pro o similar
3. Guardar datos y recargar (F5)
4. Modal aparece

**Resultado Esperado:**
- ✅ Modal se adapta al ancho de pantalla
- ✅ Padding correcto (no se corta en los bordes)
- ✅ Botones en columna (uno arriba del otro)
- ✅ Texto legible (no demasiado pequeño)
- ✅ Touch targets ≥ 44×44px

**Si falla:**
- Verificar que el CSS tiene `@media (max-width: 640px)`
- Verificar que `.eipsi-recovery-buttons` tiene `flex-direction: column`

---

### TEST 12: Backend AJAX funciona

**Pasos:**
1. Abrir formulario
2. Completar campo "Nombre" con "Carlos Rodríguez"
3. Esperar 2-3 segundos
4. DevTools → Network tab → XHR filter
5. Buscar request a `admin-ajax.php` con `action=eipsi_save_partial_response`

**Resultado Esperado:**
- ✅ Request aparece en Network tab
- ✅ Status: 200 OK
- ✅ Response (click en el request → Response tab):
  ```json
  {
    "success": true,
    "data": {
      "saved": true,
      "message": "Partial response saved"
    }
  }
  ```

**Verificación en MySQL:**
```sql
SELECT * FROM wp_eipsi_partial_responses 
ORDER BY updated_at DESC LIMIT 1;
```

**Resultado Esperado:**
- ✅ Fila con datos actualizados
- ✅ `responses_json` contiene `{"nombre":"Carlos Rodríguez"}`
- ✅ `page_index` correcto
- ✅ `completed = 0`

**Si falla:**
- Verificar que los AJAX handlers están registrados (líneas 149-152 de ajax-handlers.php)
- Verificar que la tabla existe: `SHOW TABLES LIKE 'wp_eipsi_partial_responses'`
- Revisar PHP error logs

---

## 🐛 DEBUGGING AVANZADO

### Si el modal NO aparece:

1. **Verificar en Console:**
   ```javascript
   // ¿El script se cargó?
   window.EIPSISaveContinue
   
   // ¿Hay instancia del formulario?
   document.querySelector('.eipsi-form form').eipsiSaveContinue
   
   // ¿Hay datos en IndexedDB?
   // DevTools → Application → IndexedDB → eipsi_forms → partial_responses
   ```

2. **Verificar en Network:**
   - XHR filter → Buscar `eipsi_load_partial_response`
   - Si no aparece: El script no está haciendo el request
   - Si aparece con error 400/500: Problema en backend

3. **Revisar logs de Console:**
   - Filtrar por `[EIPSI Save & Continue]`
   - Debe mostrar logs de inicialización, autosave, etc.

### Si los datos NO se restauran:

1. **Verificar en Console:**
   ```javascript
   // ¿Los datos existen?
   indexedDB.open('eipsi_forms').onsuccess = function(e) {
     let db = e.target.result;
     let tx = db.transaction('partial_responses', 'readonly');
     let store = tx.objectStore('partial_responses');
     store.getAll().onsuccess = function(e) {
       console.log('Stored data:', e.target.result);
     };
   };
   ```

2. **Verificar que setFieldValue() funciona:**
   - Poner breakpoint en `setFieldValue()` (eipsi-save-continue.js línea 556)
   - Ver qué valor se intenta setear
   - Ver si el campo existe en el DOM

### Si el CSS no se aplica:

1. **Verificar en DevTools:**
   - Elements tab → Buscar `.eipsi-recovery-popup`
   - ¿El elemento existe?
   - ¿Tiene estilos aplicados?

2. **Verificar en Network:**
   - CSS filter → Buscar `eipsi-save-continue.css`
   - Si no aparece: No se enqueued correctamente

---

## ✅ CHECKLIST FINAL

Marcar cuando se complete cada test:

- [ ] TEST 1: JS se carga
- [ ] TEST 2: Script se inicializa
- [ ] TEST 3: Autosave funciona
- [ ] TEST 4: Modal aparece al recargar
- [ ] TEST 5: Botón "Continuar" funciona
- [ ] TEST 6: Botón "Empezar de nuevo" funciona
- [ ] TEST 7: BeforeUnload warning funciona
- [ ] TEST 8: Sesión se limpia al enviar
- [ ] TEST 9: Autosave periódico (30s)
- [ ] TEST 10: Dark Mode funciona
- [ ] TEST 11: Responsive en Mobile
- [ ] TEST 12: Backend AJAX funciona

---

## 📊 REPORTE DE BUGS

Si encuentras bugs durante el testing, reportar aquí:

### Bug #1
**Título:**  
**Pasos para reproducir:**  
**Resultado esperado:**  
**Resultado actual:**  
**Screenshots/Console logs:**  
**Prioridad:** [Alta / Media / Baja]

---

## 🎯 SIGUIENTE PASO

Una vez que todos los tests pasen:
- ✅ Marcar v1.3.15 como ESTABLE
- ✅ Commitear cambios con mensaje:
  ```
  feat: Recover Exit and Continue functionality (v1.3.15)
  
  - Copy eipsi-save-continue.js from src/frontend/ to assets/js/
  - Create CSS for recovery modal (eipsi-save-continue.css)
  - Add enqueue in eipsi-forms.php (lines 594-611)
  - Modal: "Continuar donde quedaste" with Continue/Restart buttons
  - WCAG 2.1 AA: 44x44px touch targets
  - Dark mode support + responsive mobile
  - Autosave every 30s + debounced input (800ms)
  - IndexedDB + MySQL sync
  - beforeUnload warning
  - Session cleanup on form submission
  
  Fixes: #[issue-number] - Exit and Continue broken
  ```

- ✅ Actualizar CHANGELOG.md
- ✅ Proceder con features priorizadas (Fase 1-4)

---

**Versión:** v1.3.15  
**Fecha de Testing:** ___________  
**Tester:** ___________  
**Resultado:** [ ] PASS | [ ] FAIL  
**Notas adicionales:**
