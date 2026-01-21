# 🔧 Soporte Completo de Tablas RCT en Database Schema Manager

## 📋 Resumen Ejecutivo

**Versión:** v1.3.7  
**Fecha:** 2025-01-21  
**Estado:** ✅ COMPLETADO  

Se ha implementado soporte completo para las tablas de Randomization Clinical Trials (RCT) en el `Database Schema Manager`, solucionando el error "Unknown column 'config_id'" y asegurando que todas las tablas RCT funcionen correctamente.

## 🎯 Problema Resuelto

### Error Original
```
TypeError: Unknown column 'config_id' in 'where clause'
```

### Causa Raíz
1. **El código en `randomization-shortcode-handler.php` usaba `config_id`** en queries SELECT e INSERT
2. **La tabla `wp_eipsi_randomization_assignments` estaba definida con `config_id`** en `randomization-db-setup.php`
3. **PERO la tabla en la base de datos EXTERNA no tenía esa columna** - nunca se creó o la migración falló
4. **El `database-schema-manager.php` solo manejaba `vas_form_results` y `vas_form_events`**, pero NO las tablas RCT

## 🔧 Solución Implementada

### 1. **Expansión de `database-schema-manager.php`**

Se agregaron 4 nuevos métodos para manejar las tablas RCT:

#### Métodos para Base de Datos Externa (mysqli):
- `sync_randomization_configs_table($mysqli)` - Sincroniza `wp_eipsi_randomization_configs`
- `sync_randomization_assignments_table($mysqli)` - Sincroniza `wp_eipsi_randomization_assignments`

#### Métodos para Base de Datos Local (WordPress):
- `sync_local_randomization_configs_table()` - Sincroniza `wp_eipsi_randomization_configs`
- `sync_local_randomization_assignments_table()` - Sincroniza `wp_eipsi_randomization_assignments`

### 2. **Métodos de Reparación para Tablas RCT**

#### Para Reparación Local:
- `repair_local_randomization_configs_table($table_name)` - Agrega columnas faltantes a configs
- `repair_local_randomization_assignments_table($table_name)` - Agrega columnas faltantes a assignments

### 3. **Actualización de `verify_and_sync_schema()`**

Se extendió el método principal para incluir todas las tablas:

```php
public static function verify_and_sync_schema( $mysqli = null ) {
    $results = array(
        'success' => true,
        'results_table' => array( /* existente */ ),
        'events_table' => array( /* existente */ ),
        'randomization_configs_table' => array( /* NUEVO */ ),
        'randomization_assignments_table' => array( /* NUEVO */ ),
        'errors' => array(),
    );
    
    // Sincroniza todas las tablas (existentes + RCT)
    if ( $mysqli ) {
        $results_sync = self::sync_results_table( $mysqli );
        $events_sync = self::sync_events_table( $mysqli );
        $rct_configs_sync = self::sync_randomization_configs_table( $mysqli );
        $rct_assignments_sync = self::sync_randomization_assignments_table( $mysqli );
    } else {
        $results_sync = self::sync_local_results_table();
        $events_sync = self::sync_local_events_table();
        $rct_configs_sync = self::sync_local_randomization_configs_table();
        $rct_assignments_sync = self::sync_local_randomization_assignments_table();
    }
}
```

### 4. **Actualización de `configuration.php`**

Se agregó visualización del estado de las tablas RCT en la interfaz de administración:

```php
<!-- Estado de Tablas RCT -->
<div class="status-detail-row">
    <span class="detail-label"><?php echo esc_html__('RCT Configs Table:', 'eipsi-forms'); ?></span>
    <span class="detail-value">
        <?php if ($sync['randomization_configs_table']['exists']): ?>
            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
            <?php echo esc_html__('Exists', 'eipsi-forms'); ?>
        <?php else: ?>
            <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
            <?php echo esc_html__('Missing', 'eipsi-forms'); ?>
        <?php endif; ?>
    </span>
</div>
<div class="status-detail-row">
    <span class="detail-label"><?php echo esc_html__('RCT Assignments Table:', 'eipsi-forms'); ?></span>
    <span class="detail-value">
        <?php if ($sync['randomization_assignments_table']['exists']): ?>
            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
            <?php echo esc_html__('Exists', 'eipsi-forms'); ?>
        <?php else: ?>
            <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
            <?php echo esc_html__('Missing', 'eipsi-forms'); ?>
        <?php endif; ?>
    </span>
</div>
```

### 5. **Notificación Visual del Fix Crítico**

Se agregó una notificación especial cuando se detecta que la columna `config_id` fue agregada:

```php
<?php if (!empty($sync['randomization_assignments_table']['columns_added']) && in_array('config_id', $sync['randomization_assignments_table']['columns_added'])): ?>
<div class="status-detail-row" style="background-color: #e8f5e8; padding: 8px; border-radius: 4px; margin-top: 8px;">
    <span class="detail-label" style="font-weight: bold;"><?php echo esc_html__('🔧 CRITICAL FIX APPLIED:', 'eipsi-forms'); ?></span>
    <span class="detail-value">
        <?php echo esc_html__('config_id column added to RCT Assignments table - randomization queries now functional', 'eipsi-forms'); ?>
    </span>
</div>
<?php endif; ?>
```

## 📊 Especificaciones Técnicas

### Tabla: `wp_eipsi_randomization_configs`

**Columnas verificadas:**
- `id` (BIGINT) - PRIMARY KEY AUTO_INCREMENT
- `randomization_id` (VARCHAR 255) - UNIQUE
- `formularios` (LONGTEXT) - Lista JSON de formularios
- `probabilidades` (LONGTEXT) - Probabilidades JSON
- `method` (VARCHAR 20) - 'seeded' o 'pure-random'
- `manual_assignments` (LONGTEXT) - Asignaciones manuales JSON
- `show_instructions` (TINYINT) - Mostrar instrucciones
- `created_at` (DATETIME) - Timestamp de creación
- `updated_at` (DATETIME) - Timestamp de actualización

**Índices:**
- PRIMARY KEY (id)
- UNIQUE KEY randomization_id (randomization_id)
- KEY method (method)
- KEY created_at (created_at)

### Tabla: `wp_eipsi_randomization_assignments`

**Columnas verificadas (CRÍTICAS):**
- `id` (BIGINT) - PRIMARY KEY AUTO_INCREMENT
- `randomization_id` (VARCHAR 255) - ID del estudio
- `config_id` (VARCHAR 255) - **ID de configuración** ← CRÍTICA
- `user_fingerprint` (VARCHAR 255) - Fingerprint del usuario
- `assigned_form_id` (BIGINT) - Formulario asignado
- `assigned_at` (DATETIME) - Timestamp de asignación
- `last_access` (DATETIME) - Último acceso
- `access_count` (INT) - Contador de accesos

**Índices:**
- PRIMARY KEY (id)
- UNIQUE KEY unique_assignment (randomization_id, config_id, user_fingerprint)
- KEY randomization_id (randomization_id)
- KEY config_id (config_id) ← CRÍTICO para queries
- KEY user_fingerprint (user_fingerprint)
- KEY assigned_form_id (assigned_form_id)
- KEY assigned_at (assigned_at)

## 🚀 Funcionalidades Implementadas

### ✅ Verificación Automática
- Detecta si las tablas RCT existen
- Verifica que todas las columnas estén presentes
- Reporta columnas agregadas automáticamente

### ✅ Creación Automática
- Crea las tablas RCT si no existen
- Usa el charset correcto de la base de datos
- Crea todos los índices necesarios

### ✅ Reparación Automática
- Agrega columnas faltantes (especialmente `config_id`)
- Crea índices faltantes
- Agrega constraint único si falta

### ✅ Compatibilidad Doble
- **Base de datos externa** (mysqli) ✅
- **Base de datos local** (WordPress) ✅

### ✅ Interfaz de Usuario
- Estado visual de todas las tablas RCT
- Botón "Verify & Repair Schema" funciona para RCT
- Notificación especial cuando se agrega `config_id`

## 🧪 Testing y Validación

### Escenarios Probados

1. **Base de datos externa sin tablas RCT**
   - ✅ Se crean automáticamente ambas tablas
   - ✅ Todas las columnas se agregan correctamente
   - ✅ Índices se crean correctamente

2. **Base de datos externa con tabla incompleta (sin config_id)**
   - ✅ Se agrega automáticamente la columna `config_id`
   - ✅ El constraint único se crea correctamente
   - ✅ Los queries en `randomization-shortcode-handler.php` funcionan

3. **Base de datos local (WordPress)**
   - ✅ Se integran con el sistema de reparación existente
   - ✅ Funciona con `repair_local_schema()`
   - ✅ Se actualiza la versión del schema a 1.3.7

### Criterios de Éxito Cumplidos

- ✅ `database-schema-manager.php` detecta si las tablas RCT existen
- ✅ Crea las tablas RCT si no existen (ambas)
- ✅ Verifica todas las columnas definidas en el schema
- ✅ Agrega automáticamente cualquier columna faltante (especialmente `config_id`)
- ✅ Funciona para **base de datos local** (WordPress)
- ✅ Funciona para **base de datos externa** (mysqli)
- ✅ El botón "Verify & Repair Schema" en Configuration repara las tablas RCT
- ✅ Los queries de `randomization-shortcode-handler.php` funcionan sin errores
- ✅ Los INSERT y SELECT con `config_id` no generan errores "Unknown column"
- ✅ build OK: `npm run build` exitoso
- ✅ lint OK: `npm run lint:js` sin errores

## 📁 Archivos Modificados

### 1. **admin/database-schema-manager.php**
- **Líneas modificadas:** ~200 líneas agregadas
- **Nuevos métodos:** 4 métodos de sincronización + 2 métodos de reparación
- **Funcionalidad:** Soporte completo para tablas RCT en ambas bases de datos

### 2. **admin/configuration.php**
- **Líneas modificadas:** ~30 líneas agregadas
- **Funcionalidad:** Visualización del estado de tablas RCT en la interfaz

## 🔄 Flujo de Funcionamiento

### 1. **Verificación Manual**
```
Usuario hace click en "Verify & Repair Schema"
↓
Se ejecuta verify_and_sync_schema()
↓
Se sincronizan: results_table + events_table + randomization_configs_table + randomization_assignments_table
↓
Se muestra resultado en interfaz con estado de todas las tablas
```

### 2. **Verificación Automática**
```
Se carga página de configuración
↓
Se verifica schema status
↓
Si hay tablas faltantes, se muestran en rojo
↓
Usuario puede hacer click para reparar
```

### 3. **Reparación Automática**
```
Se detecta tabla faltante o columna faltante
↓
Se ejecuta ALTER TABLE para agregar columna
↓
Se verifica constraint único
↓
Se registra en logs y se notifica al usuario
```

## 🛡️ Compatibilidad y Seguridad

### Compatibilidad
- **WordPress 5.0+** ✅
- **PHP 7.4+** ✅
- **MySQL 5.7+** ✅
- **Bases de datos externas** ✅
- **Bases de datos locales** ✅

### Seguridad
- **Validación de tipos** antes de operaciones DB
- **Sanitización de inputs** en todos los queries
- **Prepared statements** en operaciones críticas
- **Error handling** robusto con logging
- **Nonces** en formularios de administración

## 📈 Beneficios de la Implementación

### Para el Usuario
1. **Zero configuration** - Las tablas se crean automáticamente
2. **Zero downtime** - No se interrumpe el servicio
3. **Zero errors** - Queries RCT funcionan sin errores
4. **Full visibility** - Estado claro de todas las tablas

### Para el Sistema
1. **Robustez** - Manejo defensivo de errores
2. **Escalabilidad** - Soporta tanto DB local como externa
3. **Mantenibilidad** - Código bien estructurado y documentado
4. **Debugging** - Logging completo para troubleshooting

## 🔍 Troubleshooting

### Si aparece "Unknown column 'config_id'"
1. Ir a **Configuration > Database**
2. Hacer click en **"Verify & Repair Schema"**
3. Verificar que se muestre **"CRITICAL FIX APPLIED"**
4. Probar formularios con aleatorización

### Si las tablas RCT no aparecen
1. Verificar que el plugin RCT esté activo
2. Revisar logs de error de PHP
3. Verificar permisos de base de datos
4. Ejecutar reparación manual

### Para Debug Avanzado
```php
// Verificar estado actual
$status = EIPSI_Database_Schema_Manager::get_verification_status();
var_dump($status);

// Reparación manual
$result = EIPSI_Database_Schema_Manager::repair_local_schema();
var_dump($result);
```

## 📚 Documentación Técnica

### Hooks Disponibles
- `eipsi_forms_activation` - Crea tablas RCT en activación
- `admin_init` - Verifica tablas RCT en cada carga de admin
- `wp_loaded` - Verificación periódica cada 24 horas

### Métodos Públicos
- `EIPSI_Database_Schema_Manager::verify_and_sync_schema($mysqli)`
- `EIPSI_Database_Schema_Manager::repair_local_schema()`
- `EIPSI_Database_Schema_Manager::get_verification_status()`

### Métodos Privados (para uso interno)
- `sync_randomization_configs_table($mysqli)`
- `sync_randomization_assignments_table($mysqli)`
- `sync_local_randomization_configs_table()`
- `sync_local_randomization_assignments_table()`
- `repair_local_randomization_configs_table($table_name)`
- `repair_local_randomization_assignments_table($table_name)`

## 🎉 Conclusión

La implementación del soporte completo de tablas RCT en el Database Schema Manager resuelve definitivamente el error "Unknown column 'config_id'" y asegura que el sistema de Randomization Clinical Trials funcione perfectamente tanto en bases de datos locales como externas.

**Resultado:** Un sistema robusto, automático y sin fricciones para la gestión de esquemas de base de datos que incluye soporte completo para RCT.

---

**Implementado por:** EIPSI Forms Development Team  
**Versión del Plugin:** v1.3.7  
**Estado:** ✅ PRODUCCIÓN READY