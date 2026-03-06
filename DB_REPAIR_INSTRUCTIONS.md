# EIPSI Forms - Database Schema Repair v2.0.1

## Problema

El sitio WordPress muestra errores SQL de dbDelta con índices corruptos:

```
ALTER TABLE wp_survey_waves ADD `` ()
ALTER TABLE wp_survey_assignments ADD `` ()
```

Estos errores bloquean el acceso al admin y generan warnings PHP.

## Causa

Índices de base de datos con nombres vacíos o malformados que dbDelta intenta recrear, generando SQL inválido.

## Solución Implementada

### Archivos Creados

1. **`admin/database-schema-repair.php`**
   - Función `eipsi_fix_corrupt_indexes()` - Busca y elimina índices corruptos
   - Función `eipsi_repair_database_schema()` - Ejecuta reparación completa
   - Función `eipsi_manual_schema_repair()` - Reparación manual vía admin URL
   - Hook `eipsi_before_dbdelta` - Reparación automática antes de dbDelta

2. **`admin/database-schema-migration.php`**
   - Función `eipsi_migrate_fix_corrupt_indexes()` - Migración automática
   - Control de versión de migración en `eipsi_db_schema_migration_version`
   - Admin notice cuando se necesita migración
   - Link de "Run Database Migration" con nonce de seguridad

3. **`admin/database-schema-manager.php`**
   - Carga de `database-schema-repair.php` al inicio
   - Guards mejorados en `ensure_local_index()` para prevenir índices vacíos
   - Logging de errores para debugging

## Cómo Usar

### Opción 1: Reparación Automática (Recomendado)

La migración se ejecuta automáticamente al:
- Activar el plugin
- Actualizar a una versión nueva
- Hacer clic en el link de admin notice

Pasos:
1. Desactivar EIPSI Forms
2. Reactivar EIPSI Forms
3. El sistema detectará índices corruptos y mostrará un notice
4. Hacer clic en "Run Database Migration"
5. Verificar que la reparación fue exitosa

### Opción 2: Reparación Manual con URL

Para reparar manualmente sin reactivar el plugin:

1. Ir a URL:
   ```
   https://tusitio.com/wp-admin/?eipsi_repair_schema=1&_wpnonce=TU_NONCE
   ```

2. Ver los resultados de reparación
3. Volver al admin dashboard

### Opción 3: Reparación desde Código

Para ejecutar reparación programáticamente:

```php
if (function_exists('eipsi_repair_database_schema')) {
    $results = eipsi_repair_database_schema();
    
    if ($results['success']) {
        echo "Reparación exitosa: {$results['total_fixed']} índices corregidos";
    } else {
        echo "Errores: " . implode(', ', $results['errors']);
    }
}
```

## Verificación de Reparación

### 1. Verificar Errores en Log

```bash
grep "EIPSI Schema Repair" /var/log/apache2/error.log
```

Debería ver algo como:
```
[EIPSI Schema Repair] Completed: 16 tables checked, 2 indexes dropped
```

### 2. Verificar Versión de Migración

```php
$version = get_option('eipsi_db_schema_migration_version', '0.0.0');
echo "Migración versión: {$version}";
```

Debe mostrar `2.0.1` o superior.

### 3. Verificar que No Hay Índices Vacíos

```php
global $wpdb;
$tables = array(
    $wpdb->prefix . 'survey_waves',
    $wpdb->prefix . 'survey_assignments'
);

foreach ($tables as $table) {
    $indexes = $wpdb->get_results("SHOW INDEX FROM `{$table}`");
    foreach ($indexes as $idx) {
        if (empty($idx->Key_name) || trim($idx->Key_name) === '') {
            echo "Índice corrupto encontrado en {$table}\n";
        }
    }
}
```

No debería mostrar output si todo está correcto.

## Prevención de Futuros Errores

### Guards Implementados

1. **Guard de parámetros vacíos** en `ensure_local_index()`:
   ```php
   if (empty($table) || empty($column)) {
       return;
   }
   ```

2. **Guard de tabla inexistente**:
   ```php
   if (empty($table_exists)) {
       return;
   }
   ```

3. **Detección de índices corruptos** antes de agregar nuevos:
   - Busca nombres de índices vacíos
   - Busca nombres con backticks vacíos (``)
   - Intenta eliminarlos antes de crear nuevos

### Logging de Errores

Cada índice corrupto se loguea con detalles:
```php
error_log("[EIPSI Schema Manager] Found corrupt index with empty name on table {$table}");
```

## Troubleshooting

### Problema: La reparación no se ejecuta

**Solución:** Verificar que `database-schema-repair.php` se carga antes de usar las funciones:
```php
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/database-schema-repair.php';
```

### Problema: Errores de permisos al eliminar índices

**Solución:** Verificar que el usuario de base de datos tiene privilegios ALTER:
```sql
SHOW GRANTS FOR CURRENT_USER();
```

Debe incluir:
- ALTER
- CREATE
- DROP INDEX

### Problema: Índices corruptos persisten

**Solución:** Eliminar manualmente desde MySQL:
```sql
-- Identificar índices corruptos
SHOW INDEX FROM wp_survey_waves;
SHOW INDEX FROM wp_survey_assignments;

-- Eliminar índices con nombre vacío
ALTER TABLE wp_survey_waves DROP INDEX (`columna1`);
ALTER TABLE wp_survey_assignments DROP INDEX (`columna2`);
```

## Checklist de Validación

- [ ] No hay errores de dbDelta en debug.log
- [ ] No hay warnings PHP relacionados con base de datos
- [ ] Opción `eipsi_db_schema_migration_version` es `2.0.1`+
- [ ] Admin dashboard carga sin errores
- [ ] Funcionalidad de Wave Manager funciona
- [ ] Asignación de participantes funciona
- [ ] Formularios se guardan correctamente

## Soporte

Si el problema persiste después de ejecutar la reparación:

1. Revisar logs de error de WordPress: `/wp-content/debug.log`
2. Revisar logs de MySQL: `/var/log/mysql/error.log`
3. Exportar estructura de tablas afectadas:
   ```bash
   mysqldump -u usuario -p --no-data nombre_db wp_survey_waves > structure.sql
   ```
4. Crear issue con información detallada del error

## Versión

- **Versión del patch:** v2.0.1
- **Fecha:** 2025-02-05
- **Compatibilidad:** WordPress 5.8+, PHP 7.4+
