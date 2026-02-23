# Fix: Error al Agregar Participantes en Waves Manager

## Problema Identificado

El error ocurría cuando se intentaba agregar participantes en la sección "Gestión de Participantes" del Waves Manager. Los síntomas incluían:

1. **Conflicto de modales**: Al hacer clic en "Agregar Participante", se ejecutaban dos manejadores de eventos jQuery conflictivos
2. **Error de nonce**: Los handlers PHP rechazaban las solicitudes porque no se aceptaba el nonce correcto
3. **Error de permisos**: Se usaba `manage_options` en lugar de la función correcta `eipsi_user_can_manage_longitudinal()`

## Cambios Realizados

### 1. admin/js/waves-manager.js
- **Problema**: Dos manejadores de clic para el mismo botón `#eipsi-add-participant-btn`
  - Línea 1190: Abría `#eipsi-participant-modal`
  - Línea 1746: Abría `#eipsi-add-participant-multi-modal`
- **Solución**: Consolidado en un único manejador que abre el modal de métodos múltiples (más completo)

### 2. admin/ajax-handlers.php
- **Problema**: Los handlers `eipsi_add_participant_magic_link_handler` y `eipsi_add_participants_bulk_handler`:
  - Usaban `check_ajax_referer('eipsi_admin_nonce')` pero el JS enviaba otros nonces
  - Verificaban `manage_options` en lugar de `eipsi_user_can_manage_longitudinal()`
- **Solución**: 
  - Aceptan múltiples tipos de nonce: `eipsi_waves_nonce`, `eipsi_anonymize_survey_nonce`, `eipsi_admin_nonce`
  - Cambiado a `eipsi_user_can_manage_longitudinal()` para permisos correctos

### 3. admin/tabs/waves-manager-tab.php
- **Problema**: Faltaba el nonce de admin en los datos localizados
- **Solución**: Agregado `adminNonce` para compatibilidad hacia atrás

## Verificación

Para verificar que el fix funciona:
1. Ir a Waves Manager → Seleccionar un estudio
2. En "Gestión de Participantes", hacer clic en "Agregar Participante"
3. Probar los tres métodos:
   - Magic Link Individual
   - Lista CSV / Manual
   - Registro Público

## Archivos Modificados
- `/admin/js/waves-manager.js`
- `/admin/ajax-handlers.php`
- `/admin/tabs/waves-manager-tab.php`
