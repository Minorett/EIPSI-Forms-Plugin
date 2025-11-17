# 📚 DOCUMENTACIÓN TÉCNICA: Session ID + Creación Automática de Tablas en BD Externa

## 📋 RESUMEN EJECUTIVO

Este documento explica el sistema de identificación de sesiones (Session ID) y el mecanismo de creación automática de tablas en bases de datos externas del plugin EIPSI Forms.

---

## 🆔 PARTE 1: SESSION ID - CONCEPTO Y FUNCIONAMIENTO

### ¿Qué es Session ID?

**Session ID NO identifica al participante.** Identifica **UNA SESIÓN** (una instancia específica de completación de formulario).

### Tipos de Identificadores

```
┌─────────────────────────────────────────┐
│ CONCEPTOS DE IDENTIFICACIÓN             │
├─────────────────────────────────────────┤
│                                         │
│ Participant ID (p-a1b2c3d4e5f6)        │
│ ↓                                       │
│ Identifica: LA PERSONA/PARTICIPANTE    │
│ Persiste: SÍ (localStorage)            │
│ Mismo para: Todos los formularios      │
│ Cambios: Limpia localStorage → nuevo   │
│                                         │
├─────────────────────────────────────────┤
│                                         │
│ Session ID (sess-1705764645000-xyz)    │
│ ↓                                       │
│ Identifica: UNA SESIÓN/ENVÍO           │
│ Persiste: NO (único cada vez)          │
│ Mismo para: Solo ese envío específico  │
│ Cambios: Nuevo cada vez que inicia     │
│                                         │
├─────────────────────────────────────────┤
│                                         │
│ Form ID (ACA-a3f1b2)                   │
│ ↓                                       │
│ Identifica: EL FORMULARIO              │
│ Persiste: SÍ (base de datos)           │
│ Mismo para: Todos los envíos al form   │
│ Cambios: Nunca (fijo)                  │
│                                         │
└─────────────────────────────────────────┘
```

### Estructura en Base de Datos

#### Tabla 1: `wp_vas_form_results`

**Almacena respuestas completas de formularios**

```sql
CREATE TABLE wp_vas_form_results (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    form_id varchar(20),
    participant_id varchar(20),
    session_id varchar(255),              -- ✅ AGREGADO
    form_name varchar(255) NOT NULL,
    created_at datetime NOT NULL,
    submitted_at datetime,
    device varchar(100),
    browser varchar(100),
    os varchar(100),
    screen_width int(11),
    duration int(11),
    duration_seconds decimal(8,3),
    start_timestamp_ms bigint(20),
    end_timestamp_ms bigint(20),
    ip_address varchar(45),
    metadata LONGTEXT,                    -- ✅ AGREGADO (JSON)
    quality_flag enum('HIGH','NORMAL','LOW') DEFAULT 'NORMAL',
    status enum('pending','submitted','error') DEFAULT 'submitted',
    form_responses longtext,
    
    PRIMARY KEY (id),
    KEY form_id (form_id),
    KEY participant_id (participant_id),
    KEY session_id (session_id),          -- ✅ ÍNDICE NUEVO
    KEY submitted_at (submitted_at),
    KEY form_participant (form_id, participant_id)
);
```

#### Tabla 2: `wp_vas_form_events`

**Almacena eventos de interacción durante el formulario**

```sql
CREATE TABLE wp_vas_form_events (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    form_id varchar(255) NOT NULL,
    session_id varchar(255) NOT NULL,     -- ✅ YA EXISTÍA
    event_type varchar(50) NOT NULL,      -- 'form_start', 'field_complete', 'page_change', 'form_submit'
    page_number int(11),
    metadata text,                        -- JSON con detalles del evento
    user_agent text,
    created_at datetime NOT NULL,
    
    PRIMARY KEY (id),
    KEY form_id (form_id),
    KEY session_id (session_id),          -- ✅ ÍNDICE para búsquedas rápidas
    KEY event_type (event_type),
    KEY created_at (created_at),
    KEY form_session (form_id, session_id)
);
```

### Flujo de Session ID

```
┌─────────────────────────────────────────────────────────────┐
│ 1. PARTICIPANTE ABRE FORMULARIO                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
        🆔 Se genera Session ID único en el frontend
        Ej: "sess-1705764645000-xyz123"
        (timestamp + random string)
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. PARTICIPANTE INTERACTÚA CON FORMULARIO                  │
│ (mientras completa campos, cambia páginas, etc.)           │
└─────────────────────────────────────────────────────────────┘
                            ↓
        💾 Se registran EVENTOS en wp_vas_form_events
        - form_start (inicio de sesión)
        - field_complete (cada campo completado)
        - page_change (cambio de página)
        - form_submit (envío final)
        
        Todos con el MISMO session_id
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. PARTICIPANTE ENVÍA FORMULARIO                           │
└─────────────────────────────────────────────────────────────┘
                            ↓
        ✅ Session ID se guarda en wp_vas_form_results
        Junto con participant_id, form_id, responses, metadata
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. SIGUIENTE ENVÍO (MISMO PARTICIPANTE)                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
        🆔 Se genera NUEVO Session ID
        Ej: "sess-1705765000000-abc456"
        (diferente al anterior)
```

### Casos de Uso

#### 1. Rastrear Múltiples Intentos

```sql
-- Ver todos los intentos de un participante
SELECT 
    participant_id,
    session_id,
    submitted_at,
    duration_seconds,
    quality_flag
FROM wp_vas_form_results
WHERE participant_id = 'p-a1b2c3d4e5f6'
ORDER BY submitted_at;

-- Resultado:
-- Participante p-a1b2c3d4e5f6 intentó 3 veces:
-- Sesión 1: sess-1705764645000-xyz (abandonó - no hay registro)
-- Sesión 2: sess-1705764700000-abc (error de validación)
-- Sesión 3: sess-1705764900000-def (exitoso)
```

#### 2. Analizar Abandonos

```sql
-- Identificar sesiones abandonadas
SELECT 
    session_id,
    form_id,
    MAX(page_number) as last_page,
    MAX(created_at) as last_interaction
FROM wp_vas_form_events
WHERE event_type IN ('field_complete', 'page_change')
GROUP BY session_id
HAVING session_id NOT IN (
    SELECT DISTINCT session_id 
    FROM wp_vas_form_results
)
ORDER BY last_interaction DESC;

-- Identifica en qué página abandonó cada sesión
```

#### 3. Cronometrar Tiempo por Sesión

```sql
-- Calcular duración de cada sesión (incluyendo no completadas)
SELECT 
    session_id,
    form_id,
    MIN(created_at) as start_time,
    MAX(created_at) as end_time,
    TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) as duration_seconds,
    COUNT(*) as event_count
FROM wp_vas_form_events
GROUP BY session_id, form_id
ORDER BY duration_seconds DESC;
```

#### 4. Detectar Sesiones Duplicadas

```sql
-- Participantes con múltiples intentos
SELECT 
    participant_id,
    COUNT(DISTINCT session_id) as attempt_count,
    GROUP_CONCAT(session_id ORDER BY submitted_at) as sessions
FROM wp_vas_form_results
GROUP BY participant_id
HAVING attempt_count > 1;
```

---

## 🗄️ PARTE 2: CREACIÓN AUTOMÁTICA DE TABLAS EN DB EXTERNA

### Problema Resuelto

**Antes:** Cuando se configuraba una base de datos externa, el plugin no verificaba ni creaba automáticamente las tablas necesarias, causando errores en los envíos.

**Ahora:** El sistema verifica y crea automáticamente todas las tablas y columnas necesarias al conectar una base de datos externa.

### Arquitectura de Solución

```
admin/
├── database-schema-manager.php (NUEVO)
│   └── EIPSI_Database_Schema_Manager
│       ├── verify_and_sync_schema()      // Verificación principal
│       ├── sync_results_table()          // Sincroniza tabla de resultados
│       ├── sync_events_table()           // Sincroniza tabla de eventos
│       ├── on_credentials_changed()      // Hook al cambiar credenciales
│       ├── periodic_verification()       // Verificación cada 24h
│       └── fallback_verification()       // Verificación en errores
│
├── database.php (MODIFICADO)
│   └── EIPSI_External_Database
│       ├── ensure_schema_ready()         // Llamado en test_connection()
│       └── insert_form_event()           // Soporte para eventos externos
│
├── configuration.php (MODIFICADO)
│   └── Agregar UI de estado de esquema
│   └── Botón "Verify & Repair Schema"
│
└── ajax-handlers.php (MODIFICADO)
    ├── eipsi_save_db_config_handler()    // Trigger schema sync
    ├── eipsi_verify_schema_handler()     // Manual verification
    └── eipsi_track_event_handler()       // External DB support
```

### Flujos de Verificación

#### Flujo 1: Al Configurar DB Externa

```
┌─────────────────────────────────────────────┐
│ ADMIN INGRESA CREDENCIALES                 │
└─────────────────────────────────────────────┘
                    ↓
            Click "Test Connection"
                    ↓
┌─────────────────────────────────────────────┐
│ test_connection()                          │
│  └── ensure_schema_ready()                │
│       ├── create_table_if_missing()       │
│       └── ensure_required_columns()       │
└─────────────────────────────────────────────┘
                    ↓
            ✅ Conexión OK
            ✅ Tablas verificadas/creadas
            ✅ Columnas sincronizadas
                    ↓
            Click "Save Configuration"
                    ↓
┌─────────────────────────────────────────────┐
│ save_db_config_handler()                   │
│  └── on_credentials_changed()             │
│       └── verify_and_sync_schema()        │
└─────────────────────────────────────────────┘
                    ↓
            Guardar timestamp verificación
            Mostrar estado en dashboard
```

#### Flujo 2: Verificación Periódica (cada 24 horas)

```
┌─────────────────────────────────────────────┐
│ HOOK: admin_init (cada carga de admin)     │
└─────────────────────────────────────────────┘
                    ↓
        periodic_verification()
                    ↓
        ¿Pasaron > 24 horas desde última verificación?
                    ↓
            SÍ → verify_and_sync_schema()
            NO → Skip (no hacer nada)
                    ↓
        Actualizar timestamp: eipsi_schema_last_verified
```

#### Flujo 3: Verificación Manual

```
┌─────────────────────────────────────────────┐
│ ADMIN CLICK "Verify & Repair Schema"       │
└─────────────────────────────────────────────┘
                    ↓
        eipsi_verify_schema_handler()
                    ↓
        verify_and_sync_schema($mysqli)
                    ↓
┌─────────────────────────────────────────────┐
│ RESULTADO:                                  │
│ • Tablas creadas: 0 o más                  │
│ • Columnas agregadas: 0 o más              │
│ • Errores: array() si hubo problemas       │
└─────────────────────────────────────────────┘
                    ↓
        Reload página para mostrar nuevo estado
```

#### Flujo 4: Fallback en Envío de Formulario

```
┌─────────────────────────────────────────────┐
│ PARTICIPANTE ENVÍA FORMULARIO              │
└─────────────────────────────────────────────┘
                    ↓
        vas_dinamico_submit_form_handler()
                    ↓
        insert_form_submission($data)
                    ↓
        ❌ ERROR (tabla no existe o columna faltante)
                    ↓
        fallback_verification()
                    ↓
        verify_and_sync_schema()
                    ↓
        REINTENTAR insert_form_submission()
                    ↓
        ✅ Éxito (guardado en DB externa)
        ❌ Fallo → Fallback a WordPress DB
```

### Métodos de Verificación

#### `verify_and_sync_schema($mysqli)`

**Propósito:** Verificar y sincronizar esquema completo en DB externa

**Proceso:**
1. Verifica tabla `wp_vas_form_results`
   - Crea si no existe
   - Agrega columnas faltantes
2. Verifica tabla `wp_vas_form_events`
   - Crea si no existe
   - Agrega columnas faltantes
3. Guarda timestamp de verificación
4. Retorna resultado detallado

**Retorno:**
```php
array(
    'success' => true/false,
    'results_table' => array(
        'exists' => true,
        'created' => false,
        'columns_added' => ['session_id', 'metadata'],
        'columns_missing' => []
    ),
    'events_table' => array(
        'exists' => true,
        'created' => false,
        'columns_added' => [],
        'columns_missing' => []
    ),
    'errors' => []
)
```

#### `on_credentials_changed()`

**Propósito:** Hook ejecutado al guardar credenciales nuevas

**Proceso:**
1. Limpia caché de verificación anterior
2. Conecta a nueva DB
3. Ejecuta `verify_and_sync_schema()`
4. Guarda resultado en `wp_options`

#### `periodic_verification()`

**Propósito:** Verificación automática cada 24 horas

**Proceso:**
1. Lee `eipsi_schema_last_verified` de `wp_options`
2. Si pasaron > 24 horas:
   - Conecta a DB externa
   - Ejecuta `verify_and_sync_schema()`
   - Actualiza timestamp

### UI de Administración

#### Estado de Esquema

Ubicación: **EIPSI Forms → Database Configuration**

**Muestra:**
- ✅ Última verificación: 2025-01-15 10:30:00
- ✅ Results Table: Exists
- ✅ Events Table: Exists (created during last sync)
- ✅ Columns Added: 3 columns synced

**Botón:** "Verify & Repair Schema"
- Ejecuta verificación manual
- Muestra progreso con spinner
- Recarga página al completar

---

## 🔧 GUÍA DE IMPLEMENTACIÓN

### Requisitos

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.2+
- Permisos: CREATE TABLE, ALTER TABLE en DB externa

### Instalación

1. **Activar Plugin**
   ```
   WP Admin → Plugins → Activate "EIPSI Forms"
   ```

2. **Configurar DB Externa**
   ```
   WP Admin → EIPSI Forms → Database Configuration
   - Ingresar credenciales
   - Click "Test Connection" (auto-crea tablas)
   - Click "Save Configuration"
   ```

3. **Verificar Estado**
   ```
   Ver sección "Database Schema Status"
   - ✅ Results Table: Exists
   - ✅ Events Table: Exists
   ```

### Migraciones

#### Migrar de WordPress DB a DB Externa

```bash
# 1. Exportar datos existentes
wp db export vas_forms_backup.sql --tables=wp_vas_form_results,wp_vas_form_events

# 2. Configurar DB externa en el plugin
# (WP Admin → Database Configuration)

# 3. Importar datos a DB externa
mysql -h [host] -u [user] -p [db_name] < vas_forms_backup.sql

# 4. Verificar importación
mysql -h [host] -u [user] -p [db_name] -e "SELECT COUNT(*) FROM wp_vas_form_results;"
```

#### Migrar de DB Externa a WordPress DB

```bash
# 1. Desactivar DB externa
# (WP Admin → Database Configuration → Disable External Database)

# 2. Exportar desde DB externa
mysqldump -h [host] -u [user] -p [db_name] wp_vas_form_results wp_vas_form_events > external_backup.sql

# 3. Importar a WordPress DB
mysql -h [wp_host] -u [wp_user] -p [wp_db] < external_backup.sql
```

---

## 📊 MONITOREO Y DEBUGGING

### Verificar Estado de Esquema

```php
// En código PHP
require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database-schema-manager.php';
$status = EIPSI_Database_Schema_Manager::get_verification_status();
print_r($status);
```

### Logs de Depuración

Activar `WP_DEBUG` en `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Revisar logs en `wp-content/debug.log`:

```bash
tail -f wp-content/debug.log | grep "EIPSI"
```

**Mensajes de log esperados:**

```
[15-Jan-2025 10:30:00 UTC] EIPSI Schema Manager: Successfully added column session_id
[15-Jan-2025 10:30:01 UTC] EIPSI Forms External DB: Attempting insert into table wp_vas_form_results
[15-Jan-2025 10:30:01 UTC] EIPSI Forms External DB: Successfully inserted record with ID 123
```

### Consultas de Diagnóstico

```sql
-- Verificar columnas en tabla de resultados
SHOW COLUMNS FROM wp_vas_form_results LIKE 'session_id';

-- Verificar columnas en tabla de eventos
SHOW COLUMNS FROM wp_vas_form_events;

-- Contar registros con session_id
SELECT COUNT(*) as total_with_session_id
FROM wp_vas_form_results
WHERE session_id IS NOT NULL;

-- Ver últimas sesiones registradas
SELECT 
    session_id,
    participant_id,
    form_id,
    submitted_at,
    duration_seconds
FROM wp_vas_form_results
ORDER BY submitted_at DESC
LIMIT 10;
```

---

## ✅ CHECKLIST DE VALIDACIÓN

### Al Configurar DB Externa

- [ ] Credenciales válidas ingresadas
- [ ] Test Connection exitoso
- [ ] Mensaje "Schema validated" visible
- [ ] Estado muestra "✅ Results Table: Exists"
- [ ] Estado muestra "✅ Events Table: Exists"
- [ ] Configuración guardada exitosamente

### Al Enviar Formulario

- [ ] Frontend genera `session_id` único
- [ ] `session_id` se envía en AJAX request
- [ ] Registro se guarda en tabla `wp_vas_form_results` con `session_id`
- [ ] Eventos se registran en `wp_vas_form_events` con mismo `session_id`
- [ ] `metadata` JSON contiene `session_id`

### Verificación Periódica

- [ ] Opción `eipsi_schema_last_verified` existe en `wp_options`
- [ ] Timestamp se actualiza cada 24 horas
- [ ] No hay errores en logs relacionados con schema

---

## 📝 NOTAS FINALES

### Ventajas del Sistema

✅ **Automatic Recovery:** Si faltan tablas/columnas, se crean automáticamente
✅ **Zero Downtime:** Verificación no bloquea operaciones normales
✅ **Backward Compatible:** Funciona con instalaciones antiguas del plugin
✅ **Transparent:** Admin ve estado claro del esquema en todo momento
✅ **Resilient:** Fallback a WordPress DB si externa falla

### Limitaciones

⚠️ **Permisos DB:** Requiere CREATE TABLE y ALTER TABLE en DB externa
⚠️ **Rendimiento:** Primera verificación puede tomar 1-2 segundos
⚠️ **Charset:** Usa charset de la conexión existente (UTF-8 recomendado)

### Soporte

Para problemas o preguntas:
- GitHub: https://github.com/roofkat/VAS-dinamico-mvp/issues
- Email: [email del autor]
- Documentación: `/docs/` en el repositorio

---

**Versión:** 1.2.1  
**Última actualización:** 2025-01-15  
**Autor:** Mathias Rojas
