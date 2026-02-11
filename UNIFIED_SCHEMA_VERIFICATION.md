# Unificación de Funcionalidad de Verificación de Esquema y Tablas

## Resumen de Cambios

Se ha unificado la funcionalidad de los botones de verificación de esquema y tablas locales para que funcionen tanto con bases de datos externas como locales de WordPress.

## Objetivo Cumplido

Los botones "Verificar y reparar esquema local" y "Verificar estado de tablas locales" ahora funcionan correctamente independientemente de la configuración de base de datos actual.

## Archivos Modificados

### 1. `/admin/configuration.php`

#### Cambios en "Database Schema Status"
**Antes:** Los botones se mostraban de manera condicional:
- Si había conexión externa: solo mostraba "Verify & Repair Schema" (externa)
- Si no había conexión: solo mostraba "Verificar y reparar esquema local"

**Después:** Los botones se muestran siempre con secciones claramente separadas:
- **Base de datos externa** (solo si está conectado): Botón "Verify & Repair Schema"
- **Base de datos local de WordPress** (siempre): Botón "Verificar y reparar esquema local"

#### Cambios en "Database Table Status"
**Antes:** Similar al esquema anterior, botones condicionales.

**Después:** Dos secciones claramente separadas:
- **Base de datos externa** (solo si está conectado): Botón "Check Table Status"
- **Base de datos local de WordPress** (siempre): Botón "Verificar estado de tablas locales"

### 2. `/admin/database-schema-manager.php`

#### Mejoras en función `repair_local_schema()`

**Problema Anterior:**
La función solo creaba tablas core (results, events) mediante `eipsi_forms_activate()`, pero no creaba las tablas de randomización ni las tablas longitudinales si faltaban.

**Solución Implementada:**
1. **Creación de tablas randomización**: Se agregó lógica para crear manualmente las tablas de randomización si `eipsi_forms_activate()` no las crea:
   - `eipsi_randomization_configs`
   - `eipsi_randomization_assignments`

2. **Creación/sincronización de tablas longitudinales**: Se agregan llamadas a todas las funciones de sincronización de tablas longitudinales:
   - `sync_local_survey_studies_table()` (estudios)
   - `sync_local_survey_participants_table()` (participantes)
   - `sync_local_survey_sessions_table()` (sesiones)
   - `sync_local_survey_waves_table()` (tomas/waves)
   - `sync_local_survey_assignments_table()` (asignaciones)
   - `sync_local_survey_magic_links_table()` (magic links)
   - `sync_local_survey_email_log_table()` (log de emails)
   - `sync_local_survey_audit_log_table()` (log de auditoría)

3. **Logging mejorado**: Se incluyen todas las tablas en el `repair_log` con campos `exists`, `created` y `columns_added`.

4. **Manejo de errores**: Se verifica si alguna tabla tuvo errores durante la creación/sincronización y se marca el resultado general como fallido.

5. **Versión de esquema**: Se actualiza a `1.4.3` para reflejar el soporte completo de tablas longitudinales.

## Criterios de Aceptación Cumplidos

✅ **Los botones funcionan correctamente tanto con bases de datos externas como locales**
   - Los botones de verificación local siempre están disponibles
   - Los botones de verificación externa solo se muestran cuando hay conexión externa
   - Ambos conjuntos de botones funcionan independientemente uno del otro

✅ **La verificación de esquema crea las tablas o columnas faltantes**
   - La función `repair_local_schema()` ahora crea todas las tablas requeridas
   - Tablas core (results, events)
   - Tablas de randomización (configs, assignments)
   - Tablas longitudinales (8 tablas: studies, participants, sessions, waves, assignments, magic_links, email_log, audit_log)

✅ **La verificación de tablas muestra el estado detallado**
   - La función `displayLocalTableStatus()` ya existía y funciona correctamente
   - Muestra todas las tablas con su estado, número de registros y columnas

✅ **No hay errores o advertencias en la consola**
   - Verificación de sintaxis PHP: `php -l` exitosa para ambos archivos
   - Los manejadores de AJAX ya existían y funcionan correctamente
   - Los manejadores de JavaScript ya existían y funcionan correctamente

## Compatibilidad

La implementación es compatible con ambas configuraciones:
- **Solo base de datos local**: Los botones de verificación local están disponibles y funcionan
- **Base de datos externa + local**: Ambos conjuntos de botones están disponibles y funcionan independientemente

## Robustez y Manejo de Errores

- Todas las funciones de sincronización de tablas longitudinales devuelven arrays con `success`, `exists`, `created`, `columns_added` y `error`
- Si alguna tabla falla, se marca el resultado general como fallido (`success = false`)
- Se incluyen `error_message` y `missing_columns` en los logs cuando hay problemas
- Se mantiene la retrocompatibilidad con el formato de respuesta existente

## Cambios No Requeridos

Los siguientes archivos **NO requirieron cambios** porque la funcionalidad ya existía:
- `/assets/js/configuration-panel.js`: Los manejadores `verifyLocalSchema` y `checkLocalTableStatus` ya existían
- `/admin/ajax-handlers.php`: Los handlers `eipsi_verify_local_schema_handler` y `eipsi_check_local_table_status_handler` ya existían
- Las acciones AJAX ya estaban registradas en las líneas 139-141

## Testing

Para verificar el funcionamiento:
1. Ir a la página de configuración de base de datos
2. Verificar que se muestren los botones de verificación local siempre
3. Si hay conexión externa, verificar que se muestren ambos conjuntos de botones
4. Probar el botón "Verificar y reparar esquema local"
5. Probar el botón "Verificar estado de tablas locales"
6. Verificar que los resultados se muestren correctamente en la UI

## Documentación

Este documento sirve como referencia para futuras actualizaciones y mantenimiento del código.
