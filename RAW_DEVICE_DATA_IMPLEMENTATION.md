# RAW Device Data Implementation - v2.1.0

## Resumen de Cambios

Esta implementación cambia la filosofía del fingerprint de:
- **ANTES**: Hash procesado (`user_fingerprint`) que identifica dispositivos
- **AHORA**: Datos RAW individuales que el investigador decide cómo usar

## Archivos Modificados/Creados

### 1. ✅ JavaScript - Captura RAW
**Archivo**: `assets/js/eipsi-fingerprint.js`

- Eliminado hash SHA-256
- Función `captureDeviceData()` captura todos los datos crudos:
  - `canvas_fingerprint` - Data URL truncado (no hash)
  - `webgl_renderer` - GPU vendor + renderer
  - `screen_resolution` - ej: "1920x1080"
  - `screen_depth` - Color depth (24/32 bits)
  - `pixel_ratio` - Device pixel ratio
  - `timezone` - ej: "America/Argentina/Buenos_Aires"
  - `language` - ej: "es-AR"
  - `cpu_cores` - Navigator hardwareConcurrency
  - `ram` - Navigator deviceMemory (si disponible)
  - `do_not_track` - Configuración de privacidad
  - `cookies_enabled` - true/false
  - `plugins` - Lista de plugins
  - `user_agent` - Navigator string
  - `platform` - ej: "Win32"
  - `touch_support` - true/false
  - `max_touch_points` - Número de touch points

### 2. ✅ Database Schema - Nueva Tabla
**Archivo**: `admin/database-schema-manager.php`

Nueva tabla `wp_eipsi_device_data`:
```sql
CREATE TABLE wp_eipsi_device_data (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    submission_id BIGINT(20) UNSIGNED NULL,
    participant_id BIGINT(20) UNSIGNED NULL,
    canvas_fingerprint VARCHAR(255) NULL,
    webgl_renderer VARCHAR(255) NULL,
    screen_resolution VARCHAR(50) NULL,
    screen_depth INT NULL,
    pixel_ratio DECIMAL(4,2) NULL,
    timezone VARCHAR(100) NULL,
    timezone_offset INT NULL,
    language VARCHAR(50) NULL,
    languages VARCHAR(255) NULL,
    cpu_cores INT NULL,
    ram INT NULL,
    do_not_track VARCHAR(20) NULL,
    cookies_enabled VARCHAR(10) NULL,
    plugins TEXT NULL,
    user_agent TEXT NULL,
    platform VARCHAR(100) NULL,
    touch_support VARCHAR(10) NULL,
    max_touch_points INT NULL,
    captured_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_submission_id (submission_id),
    INDEX idx_participant_id (participant_id),
    INDEX idx_captured_at (captured_at)
);
```

### 3. ✅ Device Data Service
**Archivo**: `admin/services/class-device-data-service.php`

Métodos implementados:
- `save_device_data($submission_id, $device_data)` - Guarda datos RAW
- `get_device_data($submission_id)` - Obtiene datos de una submission
- `get_device_data_batch($submission_ids)` - Obtiene datos en lote
- `get_export_columns()` - Columnas disponibles para export
- `get_export_column_groups()` - Grupos para UI de export:
  - **Fingerprint Completo** (activado por defecto)
  - **Fingerprint Liviano** (desactivado por defecto)
  - **Solo Tamaño de Pantalla** (opcional)
- `delete_device_data($submission_id)` - Elimina datos

## Pendiente para Completar

### 4. ⏳ AJAX Handlers
**Archivo**: `admin/ajax-handlers.php`

Modificar para:
- Recibir `eipsi_device_data` desde el frontend
- Guardar en tabla `wp_eipsi_device_data` usando el servicio

### 5. ⏳ Export UI
**Archivo**: `admin/export.php`

Agregar UI con checkboxes:
```
📊 Datos de Dispositivo (opcionales)

🖥️ Fingerprint Completo del Dispositivo
✅ ACTIVADO POR DEFECTO
☐ Generar datos completos del dispositivo

🖥️ Fingerprint Liviano del Dispositivo  
⚠️ DESACTIVADO POR DEFECTO
☐ Capturar navegador y sistema operativo
☐ Capturar tamaño de pantalla
```

## Nota Importante

La columna `user_fingerprint` se mantiene en la tabla principal por:
1. **Backward compatibility** - Exportaciones existentes
2. **Randomización RCT** - Usa fingerprint para asignación

En una futura versión (v3.0.0), se puede migrar completamente y eliminar.

## Filosofía

- El plugin NO genera un ID único
- El plugin NO determina calidad de datos
- El investigador recibe datos crudos y decide cómo usarlos
- Los datos son opcionales para respetar privacidad
