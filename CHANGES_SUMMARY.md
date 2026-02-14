# EIPSI Forms - Fix Reminders Section & Add Delete Study Functionality

## Fecha
2025-02-05

## Cambios Implementados

### 1. **Reminders Section - Link to Studies** ✅

**Problema:** La sección de Reminders mostraba "No hay estudios disponibles" incluso cuando existían estudios, porque estaba buscando posts con `post_type => 'eipsi_form'` en lugar de buscar en la tabla `wp_survey_studies`.

**Archivos Modificados:**
- `/admin/tabs/cron-reminders-tab.php`

**Cambios:**
- Línea 14-21: Reemplazada la consulta de `get_posts()` por una query directa a la tabla `wp_survey_studies`
- Línea 23-49: Actualizada la lógica de carga de configuración para leer desde el campo `config` (JSON) en lugar de post meta
- Línea 62-79: Actualizado el selector de estudios para usar `$studies` en lugar de `$surveys`
- Línea 86: Actualizado el ID del hidden input de `selected_survey_id` a `selected_study_id`
- Línea 240-250: Actualizado el JavaScript para usar `study_id` en la URL

### 2. **AJAX Handler para Guardar Configuración de Cron Reminders** ✅

**Problema:** El handler existente `eipsi_ajax_save_cron_reminders_config` intentaba guardar en post meta en lugar de en el campo `config` de `wp_survey_studies`.

**Archivos Creados:**
- `/admin/cron-reminders-handler.php`

**Cambios:**
- Creado nuevo handler que guarda la configuración en el JSON del campo `config`
- Utiliza transacciones de base de datos
- Valida que el estudio exista en `wp_survey_studies`
- Guarda la configuración de cron reminders en el config JSON del estudio

### 3. **Delete Study Button** ✅

**Problema:** No existía funcionalidad para eliminar estudios completamente de la base de datos.

**Archivos Modificados:**
- `/admin/study-dashboard-modal.php` - Línea 98: Agregado botón "Eliminar Estudio"
- `/admin/js/study-dashboard.js` - Línea 210-228: Agregado handler de click para el botón delete
- `/admin/js/study-dashboard.js` - Línea 337-366: Agregada función `deleteStudy()`

**Archivos Creados:**
- `/admin/delete-study-handler.php` - Handler AJAX para eliminar estudio

**Cambios:**
- Agregado botón rojo "🗑️ Eliminar Estudio" en el modal de dashboard
- Implementada confirmación doble con advertencia clara de que la acción es irreversible
- La función `deleteStudy()` hace llamada AJAX al handler
- El handler elimina en cascada:
  - Email logs
  - Assignments
  - Waves
  - Magic Links
  - Sessions
  - Participants
  - Study
- Utiliza transacción de base de datos para asegurar integridad
- Redirección a la lista de estudios después de eliminar exitosamente

### 4. **Carga de Handlers** ✅

**Archivos Modificados:**
- `/eipsi-forms.php` - Línea 48-49

**Cambios:**
- Agregado `require_once` para `/admin/cron-reminders-handler.php`
- Agregado `require_once` para `/admin/delete-study-handler.php`

## Testing Requerido

1. **Sección de Reminders:**
   - [ ] Crear un estudio longitudinal
   - [ ] Navegar a la pestaña "Recordatorios"
   - [ ] Verificar que el estudio aparece en el dropdown
   - [ ] Configurar recordatorios y guardar
   - [ ] Verificar que la configuración se guarda correctamente

2. **Delete Study:**
   - [ ] Crear un estudio de prueba
   - [ ] Abrir el modal de detalles del estudio
   - [ ] Hacer clic en "Eliminar Estudio"
   - [ ] Verificar que aparezca la confirmación doble
   - [ ] Confirmar la eliminación
   - [ ] Verificar que el estudio desaparece de la lista
   - [ ] Verificar que todos los datos relacionados se eliminan (participants, waves, etc.)

3. **Linting:**
   - [ ] `npm run lint:js` debe pasar sin errores
   - [ ] `npm run build` debe completarse exitosamente

## Notas Técnicas

### Tablas Afectadas
- `wp_survey_studies` - Configuración de estudios
- `wp_survey_email_log` - Logs de emails
- `wp_survey_assignments` - Asignaciones de waves a participantes
- `wp_survey_waves` - Waves/tomas
- `wp_survey_magic_links` - Links mágicos
- `wp_survey_sessions` - Sesiones de participantes
- `wp_survey_participants` - Participantes

### Seguridad
- ✅ Todos los handlers verifican NONCE
- ✅ Verificación de capacidades `manage_options`
- ✅ Confirmación doble antes de eliminar
- ✅ Validación de datos de entrada
- ✅ Transacciones de base de datos para integridad

## Versión del Plugin
1.5.0 → 1.5.3 (recomendado bump de versión)
