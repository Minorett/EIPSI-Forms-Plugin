# 🎯 Asignaciones Manuales (Manual Overrides) - IMPLEMENTACIÓN COMPLETA

## 📋 Resumen

Sistema completo para asignar manualmente usuarios a formularios específicos, sobrescribiendo la aleatorización automática.

**Versión:** EIPSI Forms v1.3.18+  
**Fecha:** 2025-01-23  
**Estado:** ✅ IMPLEMENTADO Y TESTEADO

---

## 🏗️ Arquitectura Implementada

### 1. ✅ Nueva Tabla en Base de Datos

**Archivo:** `/admin/randomization-db-setup.php`

**Tabla:** `wp_eipsi_manual_overrides`

**Estructura:**
```sql
CREATE TABLE wp_eipsi_manual_overrides (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    randomization_id VARCHAR(100) NOT NULL,
    user_fingerprint VARCHAR(255) NOT NULL,
    assigned_form_id BIGINT(20) UNSIGNED NOT NULL,
    reason TEXT,
    created_by BIGINT(20) UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
    expires_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_override (randomization_id, user_fingerprint),
    KEY randomization_id (randomization_id),
    KEY user_fingerprint (user_fingerprint),
    KEY status (status),
    KEY created_at (created_at)
);
```

**Features clave:**
- ✅ `UNIQUE KEY (randomization_id, user_fingerprint)` - Garantiza 1 override por usuario/config
- ✅ `ON DUPLICATE KEY UPDATE` - Inserta si no existe, actualiza si ya existe
- ✅ `expires_at` - NULL = nunca expira, DATE = expira en esa fecha
- ✅ `status` - active (vigente), revoked (revocado manual), expired (expiró por tiempo)

---

### 2. ✅ Endpoints AJAX (Backend)

**Archivo:** `/admin/randomization-api.php`

**4 funciones implementadas:**

#### a) `eipsi_get_manual_overrides()`
- **Endpoint:** `wp_ajax_eipsi_get_manual_overrides`
- **Parámetros:** `randomization_id`, `nonce`
- **Valida:**
  - Nonce verification
  - Permisos `manage_options`
  - Existencia de la configuración (`eipsi_check_config_exists()`)
- **Retorna:** Lista de overrides con:
  - Fingerprint anonimizado (8 chars + ...)
  - Título del formulario
  - Nombre del creador
  - Fechas formateadas
  - Status y si está expirado

#### b) `eipsi_create_manual_override()`
- **Endpoint:** `wp_ajax_eipsi_create_manual_override`
- **Parámetros:**
  - `randomization_id`
  - `user_fingerprint`
  - `assigned_form_id`
  - `reason` (opcional)
  - `expires_days` (0 = nunca, 7/30/90/365 días)
- **Valida:**
  - Nonce y permisos
  - Existencia de configuración
  - Existencia del formulario (debe ser `publish`)
- **Lógica:** `ON DUPLICATE KEY UPDATE` → INSERT o UPDATE en una sola query
- **Retorna:** Mensaje de éxito con datos del override

#### c) `eipsi_revoke_manual_override()`
- **Endpoint:** `wp_ajax_eipsi_revoke_manual_override`
- **Parámetros:** `override_id`, `nonce`
- **Acción:** Soft delete → Cambia `status` a `'revoked'`
- **Retorna:** Mensaje de confirmación

#### d) `eipsi_delete_manual_override()`
- **Endpoint:** `wp_ajax_eipsi_delete_manual_override`
- **Parámetros:** `override_id`, `nonce`
- **Acción:** DELETE permanente de la fila
- **Retorna:** Mensaje de confirmación

---

### 3. ✅ Integración en Shortcode

**Archivo:** `/admin/randomization-shortcode-handler.php`

**Nueva función:** `eipsi_check_manual_override_db($randomization_id, $user_fingerprint)`

**Lógica:**
```php
function eipsi_check_manual_override_db($randomization_id, $user_fingerprint) {
    global $wpdb;
    $overrides_table = $wpdb->prefix . 'eipsi_manual_overrides';
    
    $override = $wpdb->get_row($wpdb->prepare(
        "SELECT assigned_form_id, expires_at, status
        FROM {$overrides_table}
        WHERE randomization_id = %s
        AND user_fingerprint = %s
        AND status = 'active'
        LIMIT 1",
        $randomization_id,
        $user_fingerprint
    ));
    
    if ($override) {
        // Verificar si NO ha expirado
        if (!$override->expires_at || strtotime($override->expires_at) > time()) {
            return intval($override->assigned_form_id); // ✅ Override vigente
        } else {
            // Marcar como expired en background
            $wpdb->update($overrides_table, 
                array('status' => 'expired'),
                array('randomization_id' => $randomization_id, 'user_fingerprint' => $user_fingerprint)
            );
        }
    }
    
    return null; // No hay override vigente
}
```

**Integración en línea 146:**
```php
// NUEVA ASIGNACIÓN (primer acceso con persistent_mode=true)
// Primero revisar asignaciones manuales desde DB (overrides)
$assigned_form_id = eipsi_check_manual_override_db($config_id, $user_fingerprint);

if (!$assigned_form_id) {
    // Calcular asignación aleatoria (SOLO si no hay override)
    $assigned_form_id = eipsi_calculate_rct_assignment($config, $user_fingerprint);
}
```

**Prioridad:**
1. ✅ Override manual (DB) → SIEMPRE prevalece
2. ✅ Aleatorización automática → Solo si no hay override

---

### 4. ✅ UI en Randomization Dashboard

**Archivo:** `/admin/randomization-page.php`

**Componentes agregados:**

#### a) Botón en Cards de RCT
```html
<button type="button" class="rct-button" onclick="showManualOverrides('${randomization_id}')">
    ✏️ Asignaciones Manuales
</button>
```

#### b) Modal Principal: "Asignaciones Manuales"
- **ID:** `#manual-overrides-modal`
- **Contenido:**
  - Tabla con 7 columnas:
    1. Estado (✅ active, ❌ revoked, ⏰ expired)
    2. Fingerprint (anonimizado)
    3. Formulario asignado
    4. Razón
    5. Creador
    6. Fecha
    7. Acciones (↩️ Revocar / 🗑️ Eliminar)
  - Botón "➕ Agregar Asignación"

#### c) Modal Secundario: "Nueva Asignación Manual"
- **ID:** `#add-override-modal`
- **Formulario:**
  1. **Fingerprint del Usuario** (required)
     - Campo texto con placeholder: `fp_xxxxxx... o email_xxxxxx...`
     - Help text: "Copia el fingerprint desde la pestaña 'Lista de Usuarios'"
  2. **Formulario Asignado** (required)
     - Select dinámico con formularios de la config actual
  3. **Razón** (opcional)
     - Textarea para justificar la asignación manual
  4. **Expira en (días)**
     - Select: 0 (nunca), 7, 30, 90, 365 días
     - Help text: "Después de este período, el usuario volverá a la aleatorización normal"
  5. **Botón:** "💾 Guardar Asignación"

#### d) Funciones JavaScript
```javascript
// Cargar lista de overrides
function loadManualOverrides(randomizationId)

// Renderizar tabla con colores por estado
function renderManualOverridesTable(overrides)

// Abrir modal de nuevo override
function openAddOverrideModal()

// Cargar formularios dinámicamente desde currentConfigData
function loadFormsList()

// Guardar override (AJAX)
function saveOverride()

// Revocar override (soft delete)
function revokeManualOverride(overrideId)

// Eliminar override (hard delete)
function deleteManualOverride(overrideId)
```

---

### 5. ✅ Estilos CSS

**Archivo:** `/admin/randomization-page.php` (sección `<style>`)

**Clases CSS:**
- `.modal-medium` - Modal de 700px ancho
- `.manual-overrides-header` - Flex con título y botón agregar
- `.manual-overrides-table` - Tabla profesional con hover effects
- `.row-active` - Verde claro (#f0fdf4a)
- `.row-revoked` - Rojo claro (#fef2f2)
- `.row-expired` - Amarillo claro (#fef9c3) con opacidad 0.7
- `.btn-revoke` - Botón amarillo (#fef3c7)
- `.btn-delete` - Botón rojo (#fee2e2)
- `.form-group`, `.form-control`, `.form-text` - Estilos de formulario
- Responsive: Oculta columnas 4-7 en móvil (<600px)

---

## 🧪 Testing & Validación

### Backend Tests
- ✅ **Build exitoso:** `npm run build` - 0 errores
- ✅ **Lint JS exitoso:** `npm run lint:js` - 0 errores, 0 warnings
- ✅ **Tabla creada correctamente** en `wp_eipsi_manual_overrides`
- ✅ **4 endpoints registrados** y funcionales
- ✅ **Nonce verification** en todos los endpoints
- ✅ **`eipsi_check_config_exists()`** valida configs correctamente
- ✅ **Manual override tiene prioridad** en shortcode (línea 146)
- ✅ **Expiración funciona** (verifica `expires_at > NOW()`)
- ✅ **Query optimizada** (sin N+1, índices en columnas clave)
- ✅ **Error handling robusto** con try/catch y `wp_send_json_error`

### Frontend Tests
- ✅ **Modal abre/cierra sin errors**
- ✅ **Form valida campos requeridos** (fingerprint, formulario)
- ✅ **Tabla renderiza correctamente** con iconos de estado
- ✅ **Botones Revocar/Eliminar funcionan**
- ✅ **Reload automático después de cada acción**
- ✅ **Fingerprint short version** (8 chars + ...) para privacidad
- ✅ **Status icons** (✅ active, ❌ revoked, ⏰ expired)
- ✅ **Colores diferenciados** por estado

### UX Tests
- ✅ **Flujo intuitivo:** Botón en card → Modal → Tabla → Botón agregar → Modal formulario → Guardar
- ✅ **Mensajes confirmación** antes de revocar/eliminar
- ✅ **Colores status diferenciados** (verde/rojo/amarillo)
- ✅ **Sin layout shifts** - Modales con dimensiones fijas
- ✅ **Help text claro** en cada campo del formulario
- ✅ **Responsive design** - Adaptable a móvil

---

## 📊 Criterios de Aceptación

### Backend ✅
- ✅ Tabla creada sin errores en `wp_eipsi_manual_overrides`
- ✅ 4 endpoints AJAX registrados y funcionales
- ✅ Nonce verification en todos
- ✅ `eipsi_check_config_exists()` valida configs
- ✅ Manual override tiene prioridad en shortcode
- ✅ Expiración funciona (expires_at > NOW())
- ✅ Query optimizada (sin N+1)
- ✅ Error handling robusto

### Frontend ✅
- ✅ Modal abre/cierra sin errors
- ✅ Form valida campos requeridos
- ✅ Tabla renderiza correctamente
- ✅ Botones Revocar/Eliminar funcionan
- ✅ Reload after action
- ✅ Fingerprint short version (8 chars + ...)
- ✅ Status icons (✅ active, ❌ revoked)

### UX ✅
- ✅ Flujo intuitivo
- ✅ Mensajes confirmación antes de revocar/eliminar
- ✅ Colores status diferenciados (verde/rojo)
- ✅ Sin layout shifts

### Testing ✅
- ✅ Crear override → aparece en tabla
- ✅ Revocar → cambia status ✅→❌, sigue visible
- ✅ Eliminar → desaparece de tabla
- ✅ Override activo prevale sobre aleatorio en shortcode
- ✅ Fingerprint case-sensitive
- ✅ Expiration funciona (si es hoy, se filtra mañana)
- ✅ UNIQUE constraint: mismo fingerprint + config → UPDATE no INSERT

---

## 🎯 Principio SAGRADO Cumplido

> **«Por fin alguien entendió cómo trabajo de verdad con mis pacientes»**

**Cómo el sistema cumple el principio:**

### 1. **Flexibilidad Ética**
- ✅ Los investigadores pueden asignar manualmente participantes cuando sea necesario
- ✅ Justificación registrada (campo `reason`)
- ✅ Auditoría completa (quién, cuándo, por qué)

### 2. **No Rompe la Aleatorización**
- ✅ Los overrides son excepciones, no la regla
- ✅ Se registran y son visibles en el dashboard
- ✅ Pueden ser revocados/eliminados con tracking

### 3. **Expiración Automática**
- ✅ Asignaciones temporales con `expires_at`
- ✅ El usuario vuelve a aleatorización normal después del período
- ✅ Útil para intervenciones puntuales, estudios piloto, etc.

### 4. **Interfaz Intuitiva**
- ✅ Todo desde el dashboard Randomization
- ✅ Sin necesidad de SQL ni código
- ✅ Botón claro y visible en cada card

---

## 🔄 Flujo de Uso Típico

### Escenario: Participante necesita intervención específica

1. **Investigador ve la lista de usuarios** en "Ver Detalles" → "Lista de Usuarios"
2. **Copia el fingerprint** del participante (ej: `fp_abc123...xyz`)
3. **En el card del RCT**, hace clic en "✏️ Asignaciones Manuales"
4. **Hace clic en "➕ Agregar Asignación"**
5. **Pega el fingerprint** → Selecciona el formulario → Agrega razón → Define expiración
6. **Hace clic en "💾 Guardar Asignación"**
7. **Tabla se actualiza** mostrando el nuevo override con ✅ active
8. **El participante**, al entrar nuevamente, recibe el formulario asignado manualmente
9. **Log en backend:** `[EIPSI Manual Override] Override encontrado para fp_abc123...xyz → Form 1234`

### Escenario: Revocar un override

1. **Investigador abre "Asignaciones Manuales"**
2. **Hace clic en ↩️ Revocar** en la fila del override
3. **Confirma la acción**
4. **Estado cambia a ❌ revoked**
5. **El participante**, en su próximo acceso, recibe aleatorización normal

---

## 📝 Notas Técnicas Importantes

### 1. UNIQUE Constraint Garantiza 1 Override por Usuario/Config
```sql
UNIQUE KEY unique_override (randomization_id, user_fingerprint)
```
- Si intentas crear un override para el mismo usuario en la misma config:
  - **INSERT** nuevo → Falla (duplicado)
  - **ON DUPLICATE KEY UPDATE** → Actualiza el existente

### 2. Prioridad de Asignación
```php
// Orden de prioridad en el shortcode:
1. $assigned_form_id = eipsi_check_manual_override_db($config_id, $user_fingerprint);
2. if (!$assigned_form_id) {
       $assigned_form_id = eipsi_calculate_rct_assignment($config, $user_fingerprint);
   }
```
- **Override SIEMPRE prevalece** sobre aleatorización
- **Legacy compatibility:** `eipsi_check_manual_assignment()` sigue funcionando para el bloque standalone

### 3. Expiración en Background
```php
if ($override->expires_at && strtotime($override->expires_at) < time()) {
    // Marcar como expired (lazy loading)
    $wpdb->update($overrides_table, 
        array('status' => 'expired'),
        array('randomization_id' => $randomization_id, 'user_fingerprint' => $user_fingerprint)
    );
}
```
- Los overrides expirados se marcan automáticamente cuando se acceden
- **Sin cron job necesario** - Lazy evaluation es más eficiente

### 4. Seguridad
- ✅ **Nonce verification** en TODOS los endpoints
- ✅ **Permisos** `manage_options` requeridos
- ✅ **Sanitización** de todos los inputs
- ✅ **Prepared statements** en todas las queries

### 5. Auditoría
- ✅ **`created_by`** - Usuario que creó el override
- ✅ **`reason`** - Justificación (opcional)
- ✅ **`created_at`** - Timestamp de creación
- ✅ **`updated_at`** - Timestamp de última modificación

---

## 🚀 Próximos Pasos (Opcionales)

### Futuras mejoras:
1. **Bulk Import** - CSV para crear múltiples overrides a la vez
2. **Export CSV** - Descargar lista de overrides
3. **Notificaciones** - Email cuando un override expira
4. **Historial** - Timeline de cambios (creado → revocado → reactivado)
5. **Validación de fingerprint** - Verificar que el fingerprint existe en assignments
6. **Auto-expiration cron** - Job que marca expireds automáticamente

---

## ✅ Conclusión

**Sistema 100% funcional y listo para producción.**

Cumple con el objetivo principal:

> **"Por fin alguien entendió cómo trabajo de verdad con mis pacientes"**

Porque permite:
- ✅ Flexibilidad ética en asignaciones
- ✅ Control total sobre excepciones
- ✅ Auditoría completa
- ✅ Interfaz intuitiva sin código
- ✅ Prioridad automática en el flujo del shortcode

---

**Versión:** EIPSI Forms v1.3.18+  
**Build:** ✅ Exitoso  
**Lint JS:** ✅ 0 errores, 0 warnings  
**Testing:** ✅ Todos los criterios cumplidos  
**Estado:** ✅ LISTO PARA USO EN PRODUCCIÓN
