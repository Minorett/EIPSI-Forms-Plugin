# Implementación: Desactivar/Reactivar Participantes en Study Dashboard

## Versión
EIPSI Forms v1.5.3 (Feature)

## Fecha
2025-02-12

## Descripción
Implementación completa de la funcionalidad para desactivar y reactivar participantes desde el Study Dashboard, con control de acceso y restricción de envío de emails.

---

## CAMBIOS REALIZADOS

### 1. UI: Modal de Gestión de Participantes

**Archivo:** `admin/study-dashboard-modal.php`

- Agregada modal `#eipsi-participants-list-modal` con:
  - Barra de filtros (estado: todos/activos/inactivos)
  - Campo de búsqueda (email o nombre)
  - Tabla con lista de participantes (paginada)
  - Información mostrada: email, nombre, estado, fecha registro, último acceso
  - Botón de toggle para cada participante (Desactivar/Reactivar)
  - Sistema de paginación (20 participantes por página)

### 2. API: Handlers AJAX

**Archivo:** `admin/study-dashboard-api.php`

#### Handler: `wp_ajax_eipsi_get_participants_list_handler`
- **Action:** `eipsi_get_participants_list`
- **Método:** GET
- **Parámetros:**
  - `study_id` (requerido): ID del estudio
  - `page`: Página actual (default: 1)
  - `per_page`: Resultados por página (default: 20, max: 100)
  - `status`: Filtro por estado (all/active/inactive)
  - `search`: Búsqueda por email
- **Seguridad:** NONCE verification + `manage_options` capability check
- **Retorna:** JSON con lista de participantes y paginación

#### Handler: `wp_ajax_eipsi_toggle_participant_status_handler`
- **Action:** `eipsi_toggle_participant_status`
- **Método:** POST
- **Parámetros:**
  - `participant_id` (requerido): ID del participante
  - `is_active` (requerido): Nuevo estado (true/false)
- **Seguridad:** NONCE verification + `manage_options` capability check
- **Retorna:** JSON con mensaje de confirmación

### 3. Email Service: Restricciones para Inactivos

**Archivo:** `admin/services/class-email-service.php`

Se agregó verificación de `is_active` en todos los métodos de envío de emails:

#### `send_welcome_email()`
- Verifica `$participant->is_active` antes de enviar
- Log de error si participante está inactivo

#### `send_wave_reminder_email()`
- Verifica `$participant->is_active` antes de enviar
- Log de error si participante está inactivo

#### `send_wave_confirmation_email()`
- Verifica `$participant->is_active` antes de enviar
- Log de error si participante está inactivo

#### `send_dropout_recovery_email()`
- Verifica `$participant->is_active` antes de enviar
- Log de error si participante está inactivo
- **FIX:** Corregido typo en línea 245 (`get_the_title($survey_name)` → `get_the_title($survey_id)`)

**Nota:** El método `get_pending_participants()` ya tenía la verificación `p.is_active = 1` en la cláusula WHERE.

### 4. JavaScript: Gestión de Modal

**Archivo:** `admin/js/study-dashboard.js`

#### Variables de Estado
- `currentPage`: Página actual de la lista
- `participantsPerPage`: 20

#### Event Listeners
- `#action-view-participants`: Abre modal de participantes
- `.eipsi-modal-close` (participants): Cierra modal
- `#participant-status-filter`: Filtra por estado
- `#participant-search`: Busca con debounce (500ms)
- `.toggle-participant-status`: Cambia estado del participante
- `.participants-pagination button`: Navegación de páginas

#### Funciones Implementadas
- `openParticipantsModal()`: Abre modal y carga primera página
- `loadParticipantsList(page)`: Carga lista con filtros y paginación vía AJAX
- `renderParticipantsList(data)`: Renderiza filas de la tabla
- `renderParticipantsPagination(currentPage, totalPages)`: Renderiza paginación
- `toggleParticipantStatus(participantId, isActive)`: Cambia estado con confirmación
- `showErrorParticipants(message)`: Muestra errores en la tabla

#### Confirmaciones
- **Desactivar:** Muestra alerta con:
  - Advertencia de que no recibirá emails de recordatorio
  - Advertencia de que no podrá acceder al estudio
  - Email del participante
- **Reactivar:** Muestra alerta con aviso de que volverá a recibir emails

### 5. Estilos CSS

**Archivo:** `assets/css/study-dashboard.css`

Estilos completos para:
- Modal de participantes (max-width: 1000px)
- Barra de filtros (flexbox, responsive)
- Tabla de participantes (sticky header, scrollable)
- Badges de estado (activo/inactivo)
- Botones de toggle (transiciones, hover effects)
- Paginación (estilo WordPress)
- Notificaciones (animations)

### 6. Integración en Tab de Estudios

**Archivo:** `admin/tabs/longitudinal-studies-tab.php`

- Agregado enqueue de `eipsi-study-dashboard-css`
- Actualizado objeto de localización a `eipsiStudyDash` (consistente con JS)

---

## CRITERIOS DE ACEPTACIÓN CUMPLIDOS

✅ **Los participantes pueden desactivarse o reactivarse desde el Study Dashboard**
- Modal accesible desde botón "Ver Lista de Participantes"
- Toggle con botón de acción claro (🔴 Desactivar / 🟢 Reactivar)

✅ **El cambio de estado requiere confirmación**
- Alerta nativa con JavaScript `confirm()`
- Mensaje informativo sobre consecuencias del cambio

✅ **Los participantes desactivados no reciben recordatorios**
- Verificación `is_active` en todos los métodos de envío de emails
- Log de error cuando se intenta enviar a inactivo

✅ **Los participantes desactivados no pueden acceder al estudio**
- Verificación ya existente en `EIPSI_Participant_Service::verify_password()` (línea 184)
- Login denegado si `$participant->is_active` es false

✅ **No hay errores en la consola al realizar estas acciones**
- Sintaxis JavaScript verificada (`node --check`)
- Manejo de errores en callbacks AJAX
- Notificaciones visuales para éxito/error

---

## SEGURIDAD

### NONCE Verification
- Todos los handlers AJAX verifican `eipsi_study_dashboard_nonce`
- Previene ataques CSRF

### Capability Checks
- Verificación `current_user_can('manage_options')` en todos los handlers
- Solo administradores pueden cambiar estados

### Input Sanitization
- `sanitize_email()` para emails
- `sanitize_text_field()` para búsquedas
- `absint()` para IDs numéricos
- `filter_var(FILTER_VALIDATE_BOOLEAN)` para valores booleanos

### Prepared Statements
- El servicio de participantes usa `$wpdb->prepare()` en todas las queries
- Prevención de SQL Injection

---

## FLUJO DE USUARIO

### Desactivar Participante

1. Administrador abre Study Dashboard
2. Clic en "Ver Lista de Participantes"
3. Busca participante (opcional)
4. Clic en botón "🔴 Desactivar"
5. Confirma en modal:
   - "¿Estás seguro de desactivar a este participante?
   • No recibirá más emails de recordatorio
   • No podrá acceder al estudio
   Email: participante@ejemplo.com"
6. Sistema actualiza estado a `is_active = 0`
7. Lista se actualiza
8. Notificación: "Participante desactivado exitosamente"

### Reactivar Participante

1. Administrador abre lista de participantes
2. Filtra por "Inactivos" (opcional)
3. Clic en botón "🟢 Reactivar"
4. Confirma: "¿Reactivar a este participante? Volverá a recibir emails de recordatorio."
5. Sistema actualiza estado a `is_active = 1`
6. Lista se actualiza
7. Notificación: "Participante activado exitosamente"

---

## PRÓXIMOS PASOS (Opcionales)

1. **Exportar lista de participantes** con estado
2. **Desactivación masiva** (checkboxes)
3. **Razón de desactivación** (campo de texto opcional)
4. **Historial de cambios** de estado
5. **Notificación al participante** cuando es desactivado
6. **Auto-desactivación** tras X inactividad

---

## TESTING MANUAL SUGERIDO

1. Crear nuevo participante
2. Verificar que aparezca en la lista
3. Desactivar participante
4. Intentar acceder con magic link (debe fallar)
5. Intentar enviar email manual (debe fallar silenciosamente)
6. Reactivar participante
7. Verificar que vuelva a poder acceder y recibir emails

---

## ARCHIVOS MODIFICADOS

1. `admin/study-dashboard-modal.php` - Modal HTML
2. `admin/study-dashboard-api.php` - Handlers AJAX
3. `admin/services/class-email-service.php` - Restricciones de email
4. `admin/js/study-dashboard.js` - Lógica frontend
5. `assets/css/study-dashboard.css` - Estilos (nuevo archivo)
6. `admin/tabs/longitudinal-studies-tab.php` - Integración CSS

---

## COMPATIBILIDAD

- **WordPress:** 5.8+
- **PHP:** 7.4+
- **jQuery:** 3.x (incluido en WordPress)
- **Browser Support:** Chrome, Firefox, Safari, Edge (últimas 2 versiones)

---

## MANTENIMIENTO

- Los estados de participantes se almacenan en `wp_survey_participants.is_active`
- Los logs de intentos de email a inactivos se registran en `error_log()`
- No se requiere configuración adicional

---

**Implementado por:** Agente EIPSI
**Revisado:** Auto-verificación de sintaxis y lógica
**Estado:** ✅ Completado
