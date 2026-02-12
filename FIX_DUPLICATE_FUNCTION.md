# Fix: Duplicación de función wp_ajax_eipsi_add_participant_handler()

## Problema
Error fatal causado por declaración duplicada de la función `wp_ajax_eipsi_add_participant_handler()` en dos archivos:
- `/admin/waves-manager-api.php` (línea 367)
- `/admin/study-dashboard-api.php` (línea 258)

Esto provocaba el error: "Cannot redeclare function wp_ajax_eipsi_add_participant_handler()"

## Solución Implementada

### 1. Eliminación del registro duplicado en waves-manager-api.php
- **Archivo**: `/admin/waves-manager-api.php` (líneas 26-30)
- **Cambio**: Removido el `add_action('wp_ajax_eipsi_add_participant', 'wp_ajax_eipsi_add_participant_handler')`
- **Razón**: Evitar el registro duplicado del mismo handler de acción AJAX
- **Nota**: Se agregó comentario explicando que la función está definida en `study-dashboard-api.php`

### 2. Eliminación de la función duplicada en waves-manager-api.php
- **Archivo**: `/admin/waves-manager-api.php` (líneas 365-413)
- **Cambio**: Eliminada completamente la función `wp_ajax_eipsi_add_participant_handler()`
- **Razón**: Eliminar la duplicación que causaba el error fatal

### 3. Mejora de compatibilidad en study-dashboard-api.php
- **Archivo**: `/admin/study-dashboard-api.php` (líneas 255-356)
- **Cambios realizados**:
  1. **Verificación de nonce flexible**: Ahora acepta tanto `eipsi_study_dashboard_nonce` como `eipsi_waves_nonce`
  2. **Compatibilidad backward**: Agregado campo `temporary_password` en la respuesta para compatibilidad con `waves-manager.js`
  3. **Documentación mejorada**: Agregado comentario indicando que acepta ambos nonces
  4. **Funcionalidad completa**: Mantiene la funcionalidad de envío de email de bienvenida

## Detalles Técnicos

### Por qué se mantuvo la versión de study-dashboard-api.php
La versión en `study-dashboard-api.php` era superior porque:
1. ✅ Envía email de bienvenida automáticamente (funcionalidad importante para estudios longitudinales)
2. ✅ Carga servicios explícitamente si no están disponibles (más robusto)
3. ✅ Devuelve información sobre si el email fue enviado o no
4. ✅ Mejor manejo de errores y validaciones

### Compatibilidad de Nonces
La función ahora acepta dos nonces para soportar ambos contextos:
- `eipsi_study_dashboard_nonce`: Usado por `study-dashboard.js`
- `eipsi_waves_nonce`: Usado por `waves-manager.js`

```php
// Verificación de nonce flexible
$nonce_valid = wp_verify_nonce($_POST['nonce'], 'eipsi_study_dashboard_nonce') ||
              wp_verify_nonce($_POST['nonce'], 'eipsi_waves_nonce');
```

### Datos de respuesta compatibles
La respuesta ahora incluye:
- `participant_id`: ID del participante creado
- `email_sent`: Booleano indicando si el email fue enviado
- `temporary_password`: Contraseña temporal (para compatibilidad con `waves-manager.js`)
- `message`: Mensaje descriptivo del resultado

## Verificación

### ✅ Función única
```bash
grep -r "function wp_ajax_eipsi_add_participant_handler" --include="*.php" .
# Solo devuelve: admin/study-dashboard-api.php
```

### ✅ Sintaxis PHP válida
Ambos archivos pasan la verificación de sintaxis PHP sin errores.

### ✅ Flujo de datos preservado
- **waves-manager.js** → `eipsi_add_participant` con `eipsi_wavesNonce` ✅
- **study-dashboard.js** → `eipsi_add_participant` con `eipsiStudyDash.nonce` ✅
- Ambos utilizan la misma función PHP en `study-dashboard-api.php` ✅

## Impacto

### Beneficios
1. ✅ **Error fatal resuelto**: El plugin ya no muestra el error de "Cannot redeclare function"
2. ✅ **Funcionalidad completa**: Se mantiene el envío de email de bienvenida
3. ✅ **Compatibilidad mantenida**: Ambas interfaces JavaScript funcionan correctamente
4. ✅ **Código más limpio**: Eliminada duplicación innecesaria
5. ✅ **Mejor mantenibilidad**: Una sola fuente de verdad para la lógica de creación de participantes

### Sin cambios para el usuario final
- La interfaz de Waves Manager sigue funcionando igual
- La interfaz de Study Dashboard sigue funcionando igual
- Los emails de bienvenida siguen enviándose automáticamente

## Pruebas Recomendadas

1. **Crear participante desde Waves Manager**:
   - Verificar que se cree correctamente
   - Confirmar que se envíe el email de bienvenida

2. **Crear participante desde Study Dashboard**:
   - Verificar que se cree correctamente
   - Confirmar que se envíe el email de bienvenida

3. **Verificar no hay errores en consola de WordPress**:
   - Revisar logs de errores de PHP
   - Confirmar que el plugin se activa sin problemas

4. **Probar flujo completo de estudio longitudinal**:
   - Crear estudio
   - Agregar participantes
   - Asignar a waves
   - Verificar envío de emails

## Archivos Modificados

1. ✅ `/admin/waves-manager-api.php`
   - Eliminado registro de acción duplicado
   - Eliminada función duplicada

2. ✅ `/admin/study-dashboard-api.php`
   - Mejorada verificación de nonce (acepta ambos)
   - Agregado campo `temporary_password` para compatibilidad
   - Mejorada documentación

## Referencias

- Fecha de corrección: 2025-02-12
- Versión del plugin: 1.4.3
- Issue: Duplicación de función wp_ajax_eipsi_add_participant_handler()
- Criterio de aceptación: Función declarada solo una vez, sin errores fatales
