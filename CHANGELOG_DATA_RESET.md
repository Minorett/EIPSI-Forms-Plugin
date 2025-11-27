# EIPSI Forms - Botón de Emergencia: Borrar Datos Clínicos

## Resumen de la Implementación

Se ha implementado un **botón de emergencia** en el panel de configuración de EIPSI Forms que permite borrar de forma segura todos los datos clínicos almacenados en las tablas del plugin.

---

## Archivos Modificados

### 1. `/admin/configuration.php`
**Cambios:**
- Se agregó una nueva sección completa al final de la página: **"Advanced Tools — Data Reset"**
- Diseño con borde rojo y advertencias claras (estilo "danger zone")
- Incluye:
  - Explicación clara de qué se eliminará y qué NO se eliminará
  - Advertencias destacadas sobre irreversibilidad
  - Botón rojo "Delete All Clinical Data"
  - Hidden input con nonce para seguridad: `eipsi_delete_all_data`

**Líneas agregadas:** 387-440

### 2. `/assets/js/configuration-panel.js`
**Cambios:**
- Agregado event binding para `#eipsi-delete-all-data` en `bindEvents()`
- Nueva función `deleteAllData()`: Muestra modal de confirmación personalizado con:
  - Mensaje claro del impacto de la acción
  - Botones "Cancelar" y "Sí, borrar todos los datos"
  - Cierre al hacer clic en el fondo
- Nueva función `executeDeleteAllData()`: Ejecuta la llamada AJAX al endpoint
  - Muestra loading state en el botón
  - Recarga la página tras éxito para refrescar stats
  - Manejo completo de errores

**Líneas agregadas:** ~110 líneas

### 3. `/admin/database.php`
**Cambios:**
- Nueva función pública `delete_all_data()`:
  - Borra SIEMPRE los datos de las tablas locales de WordPress primero
  - Si hay BD externa configurada, también borra los datos allí
  - Usa `TRUNCATE` preferentemente, con fallback a `DELETE FROM`
  - Logging completo en `WP_DEBUG`
- Tres funciones auxiliares privadas:
  - `wp_table_exists()`: Verifica existencia de tabla en WordPress
  - `truncate_wp_table()`: Trunca o borra tabla de WordPress
  - `truncate_external_table()`: Trunca o borra tabla externa
- Nueva función privada `resolve_events_table_name()`: Resuelve nombre de tabla de eventos (con o sin prefijo)

**Líneas agregadas:** ~127 líneas

### 4. `/admin/ajax-handlers.php`
**Cambios:**
- Registrado nuevo action: `add_action('wp_ajax_eipsi_delete_all_data', 'eipsi_delete_all_data_handler')`
- Nueva función `eipsi_delete_all_data_handler()`:
  - Verifica nonce específico: `eipsi_delete_all_data`
  - Verifica capability: `manage_options`
  - Llama a `$db_helper->delete_all_data()`
  - Retorna respuesta JSON con éxito o error

**Líneas agregadas:** ~30 líneas

### 5. `/vas-dinamico-forms.php`
**Cambios:**
- Agregadas 6 nuevas strings en `wp_localize_script('eipsi-config-panel-script', 'eipsiConfigL10n', ...)`:
  - `confirmDeleteTitle`
  - `confirmDeleteMessage`
  - `confirmDeleteYes`
  - `confirmDeleteNo`
  - `deleteSuccess`
  - `deleteError`

**Líneas modificadas:** 268-287

---

## Funcionalidad

### Flujo de Ejecución

1. **Usuario hace clic en botón rojo "Delete All Clinical Data"**
2. **Modal de confirmación aparece** con advertencias claras
3. **Usuario confirma** haciendo clic en "Sí, borrar todos los datos"
4. **AJAX request enviada** con nonce de seguridad
5. **Backend verifica permisos** (solo administradores)
6. **Se borran las tablas en este orden:**
   - Primero: `wp_vas_form_results` (WordPress)
   - Segundo: `wp_vas_form_events` (WordPress)
   - Tercero (si aplica): Tabla de resultados en BD externa
   - Cuarto (si aplica): Tabla de eventos en BD externa
7. **Respuesta de éxito** → Mensaje verde + recarga página después de 2s
8. **Respuesta de error** → Mensaje rojo con detalles del error

### Tablas Afectadas

**SE ELIMINAN:**
- `wp_vas_form_results` (WordPress y/o BD externa)
- `wp_vas_form_events` (WordPress y/o BD externa)

**NO SE ELIMINAN:**
- Estructura de tablas (esquema)
- Definiciones de formularios (posts de WordPress)
- Configuración del plugin
- Presets de privacidad
- Credenciales de BD externa

### Seguridad

- **Nonce único:** `eipsi_delete_all_data` (diferente del nonce de configuración general)
- **Capability check:** Solo usuarios con `manage_options`
- **Modal de confirmación:** Evita clicks accidentales
- **Logging:** Registra en error_log el user ID que ejecutó la acción
- **Irreversible:** No hay papelera ni backup automático (se advierte claramente)

### Compatibilidad

- **BD WordPress:** ✅ Siempre funciona
- **BD Externa (MySQL):** ✅ Detecta y borra automáticamente
- **Sin BD externa configurada:** ✅ Solo borra WordPress
- **Fallback a DELETE FROM:** ✅ Si TRUNCATE falla por permisos

---

## Testing Checklist

### Escenario 1: Solo WordPress DB
- [ ] Botón visible en Configuration page
- [ ] Modal de confirmación aparece al hacer clic
- [ ] Cancelar cierra el modal sin borrar nada
- [ ] Confirmar borra datos de `wp_vas_form_results` y `wp_vas_form_events`
- [ ] Mensaje de éxito mostrado
- [ ] Página se recarga después de 2 segundos
- [ ] Contador de registros en dashboard muestra 0

### Escenario 2: WordPress + BD Externa
- [ ] Borra datos de ambas bases de datos
- [ ] Mensaje indica "both WordPress and external database"
- [ ] Logs en WP_DEBUG registran ambas operaciones

### Escenario 3: Errores
- [ ] Error de conexión a BD externa → Mensaje de error claro
- [ ] Usuario sin permisos → Respuesta 403 Unauthorized
- [ ] Nonce inválido → Respuesta de error

### Escenario 4: UX
- [ ] Advertencias rojas visibles y legibles
- [ ] Modal legible en mobile
- [ ] Botón se deshabilita durante operación
- [ ] No se puede hacer doble-click

---

## Uso Clínico Real

**¿Cuándo usar este botón?**

✅ **SÍ:**
- Después de hacer muchas pruebas de QA internas
- Antes de empezar a usar el plugin con pacientes reales
- Para limpiar datos de demo antes de una capacitación
- Para resetear todo al estado inicial en un entorno de desarrollo

❌ **NO:**
- En producción con datos reales de pacientes (irreversible)
- Sin hacer backup previo si los datos pueden ser valiosos
- Como forma de "exportar" datos (usar CSV/Excel Export primero)

**Recomendación:** Siempre hacer un backup de la base de datos antes de usar esta función si hay datos clínicos reales almacenados.

---

## Mensajes de Usuario

### Español (Default)
- **Título modal:** "⚠️ Delete All Clinical Data?"
- **Mensaje modal:** "This action will PERMANENTLY delete all form responses, session data, and event logs from EIPSI Forms.\n\nThis CANNOT be undone.\n\nAre you absolutely sure?"
- **Botón confirmar:** "Yes, delete all data"
- **Botón cancelar:** "Cancel"
- **Éxito:** "All clinical data has been successfully deleted from the WordPress database."
- **Éxito (BD externa):** "All clinical data has been deleted from both WordPress and the external database."
- **Error:** "Failed to delete data. Please check the error logs."

### Traducción al Español
```php
// Agregar en archivo de traducción .pot/.po:
msgid "Delete All Clinical Data"
msgstr "Borrar Todos los Datos Clínicos"

msgid "This action will PERMANENTLY delete..."
msgstr "Esta acción eliminará PERMANENTEMENTE todas las respuestas de formularios, datos de sesión y registros de eventos de EIPSI Forms.\n\nEsto NO PUEDE deshacerse.\n\n¿Estás absolutamente seguro?"

msgid "Yes, delete all data"
msgstr "Sí, borrar todos los datos"

msgid "All clinical data has been successfully deleted from the WordPress database."
msgstr "Todos los datos clínicos han sido eliminados exitosamente de la base de datos de WordPress."
```

---

## Build & Deploy

### Comandos ejecutados:
```bash
npm run lint:js      # ✅ 0 errors, 0 warnings
npm run build        # ✅ Compiled successfully in 3264 ms
```

### Bundle size:
- No impacto en bundle frontend (cambios solo en admin)
- JavaScript admin: +110 líneas (~3 KB minified)
- PHP admin: +157 líneas

---

## Notas de Implementación

1. **Decisión: Borrar siempre WordPress DB primero**
   - Razón: Fallback automático del plugin siempre usa WordPress DB
   - Garantiza limpieza completa incluso si BD externa falla

2. **TRUNCATE vs DELETE FROM**
   - Se usa `TRUNCATE` por defecto (más rápido, resetea AUTO_INCREMENT)
   - Fallback a `DELETE FROM` si usuario no tiene permisos TRUNCATE

3. **Nonce separado del general**
   - Usa nonce específico: `eipsi_delete_all_data`
   - Razón: Acción más peligrosa que config normal, merece token propio

4. **Modal custom vs. confirm() nativo**
   - Se eligió modal custom HTML/CSS
   - Razón: Mejor UX, más control, estilo consistente con WordPress

5. **Recarga página post-éxito**
   - Se recarga automáticamente después de 2 segundos
   - Razón: Refrescar contadores y stats del dashboard sin estado obsoleto

---

## Cumplimiento de Requerimientos

### ✅ Criterios de Aceptación (del ticket)

- [x] Botón rojo claramente visible en Configuration / Base Data
- [x] Modal/alerta de confirmación con "¿Estás seguro?" y Sí/No
- [x] Solo administradores con permisos adecuados pueden verlo y usarlo
- [x] Elimina todas las filas de tablas de resultados sin borrar formularios ni configuración
- [x] Mensaje de éxito tras la acción; mensaje de error en caso de fallo
- [x] `npm run build` y `npm run lint:js` pasan sin errores ni warnings

### ✅ Requerimientos Funcionales (del ticket)

1. **Ubicación:** ✅ Pestaña Configuration, sección claramente separada
2. **Apariencia:** ✅ Botón rojo con texto claro y advertencias destacadas
3. **Confirmación:** ✅ Modal con mensaje irreversible y botones Cancelar/Confirmar
4. **Borrado en BD:** ✅ TRUNCATE de ambas tablas (results + events)
5. **Feedback:** ✅ Mensaje de éxito verde / mensaje de error rojo
6. **Permisos:** ✅ Solo `administrator`, con nonce y check_admin_referer

---

## Changelog Entry (para CHANGELOG.md oficial)

```markdown
### Added
- **Emergency Data Reset Button**: New "Delete All Clinical Data" button in Configuration panel
  - Allows administrators to permanently delete all form responses and session data
  - Clears both WordPress database and external database (if configured)
  - Includes confirmation modal and clear warnings about irreversibility
  - Useful for clearing test data before starting real clinical use
  - Only accessible to users with `manage_options` capability
```

---

## Notas para Futuras Mejoras (NO implementadas)

- [ ] Opción de exportar antes de borrar (backup automático)
- [ ] Borrado selectivo por formulario
- [ ] Borrado selectivo por rango de fechas
- [ ] Papelera temporal (soft delete) con recuperación
- [ ] Confirmación doble (escribir "DELETE" para confirmar)
- [ ] Contador de registros a borrar antes de confirmar

---

**Implementado por:** CTO.new AI Agent  
**Fecha:** 2025-01-XX  
**Versión del plugin:** 1.2.2+  
**Estado:** ✅ Listo para testing en staging
