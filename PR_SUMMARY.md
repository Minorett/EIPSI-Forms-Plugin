# PR: Fix dbDelta Errors with Corrupt Database Indexes v2.0.1

## Descripción

Corrige errores SQL de dbDelta causados por índices corruptos con nombres vacíos en las tablas `wp_survey_waves` y `wp_survey_assignments`. Estos errores generaban SQL inválido como `ALTER TABLE wp_survey_waves ADD `` ()` que bloqueaba el acceso al admin de WordPress.

## Cambios Realizados

### Archivos Nuevos

1. **`admin/database-schema-repair.php`** (261 líneas)
   - Función `eipsi_fix_corrupt_indexes()`: Busca y elimina índices con nombres vacíos
   - Función `eipsi_repair_database_schema()`: Ejecuta reparación completa
   - Función `eipsi_manual_schema_repair()`: Permite reparación manual vía URL con nonce
   - Hook `eipsi_before_dbdelta`: Ejecuta reparación automática antes de dbDelta

2. **`admin/database-schema-migration.php`** (148 líneas)
   - Función `eipsi_migrate_fix_corrupt_indexes()`: Migración automática
   - Control de versión en `eipsi_db_schema_migration_version`
   - Admin notice con link seguro de "Run Database Migration"
   - Integración con `admin_init` para ejecutar migración bajo demanda

3. **`DB_REPAIR_INSTRUCTIONS.md`** (196 líneas)
   - Guía completa de uso y troubleshooting
   - Ejemplos de verificación
   - Pasos para diferentes escenarios de reparación

### Archivos Modificados

1. **`admin/database-schema-manager.php`**
   - Línea 4: Carga de `database-schema-repair.php` al inicio
   - Línea 1477-1498: Mejoras en `ensure_local_index()`:
     - Guard de parámetros vacíos para prevenir SQL inválido
     - Guard de tabla inexistente antes de SHOW INDEX
     - Detección de índices corruptos antes de agregar nuevos
     - Logging de errores para debugging

2. **`eipsi-forms.php`**
   - Línea 102-103: Carga de `database-schema-migration.php`
   - Línea 880-883: Ejecución de migración en activation hook
   - Migración se ejecuta antes de sincronizar tablas longitudinales

## Funcionalidad Implementada

### 1. Reparación Automática
La migración se ejecuta automáticamente en:
- Activación del plugin
- Actualización a versión nueva
- Al hacer clic en el link del admin notice

### 2. Reparación Manual
Se puede reparar manualmente sin reactivar el plugin:
```
https://tusitio.com/wp-admin/?eipsi_repair_schema=1&_wpnonce=TU_NONCE
```

### 3. Prevención de Futuros Errores
Guards implementados en `ensure_local_index()`:
- Verificación de parámetros vacíos antes de ejecutar SQL
- Verificación de tabla existente antes de SHOW INDEX
- Detección de índices corruptos en DB antes de agregar nuevos
- Logging de todos los errores para debugging

## Criterios Cumplidos

- ✅ Crea función para reparar índices corruptos en MySQL
- ✅ La función se ejecuta ANTES de dbDelta para prevenir errores
- ✅ Elimina índices con nombres vacíos (`KEY ```)
- ✅ Logging de errores en WordPress debug log
- ✅ Prevención de índices vacíos en `ensure_local_index()`
- ✅ Validación de PHP con `php -l`
- ✅ Documentación completa de uso
- ✅ Integración con hooks de activación de WordPress
- ✅ Seguridad con nonce para operaciones manuales
- ✅ Tracking de versión de migración para no ejecutar dos veces

## Capturas de Pantalla (si aplica)

N/A - Cambios en backend/base de datos

## Checklist

- [x] Los cambios están probados localmente
- [x] No hay errores de sintaxis PHP
- [x] La documentación está actualizada (DB_REPAIR_INSTRUCTIONS.md)
- [x] Se implementa prevención de futuros errores
- [x] Se incluye logging para debugging
- [x] Se respeta el código de seguridad (nonces, capability checks)

## Cambios realizados

- [x] Sistema de reparación de índices corruptos implementado
- [x] Migración automática en activación del plugin
- [x] Prevención de índices vacíos en ensure_local_index()
- [x] Documentación completa de uso y troubleshooting

## Descripción Técnica

### El Problema

dbDelta de WordPress intenta recrear índices cuando detecta diferencias entre el SQL actual y la estructura de la DB. Cuando hay índices con nombres vacíos en MySQL, dbDelta genera SQL inválido:

```sql
ALTER TABLE wp_survey_waves ADD `` ()
```

Esto causa errores SQL que bloquean el admin y generan warnings PHP.

### La Solución

El código implementado:

1. **Detecta** índices corruptos usando `INFORMATION_SCHEMA.STATISTICS`
2. **Elimina** índices con nombres vacíos o malformados
3. **Previene** creación de nuevos índices vacíos con guards
4. **Loguea** todos los errores para debugging

### Ejemplo de Reparación

```php
// Buscar índices corruptos
$indexes = $wpdb->get_results(
    "SELECT INDEX_NAME, COLUMN_NAME 
     FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = %s 
       AND TABLE_NAME = %s",
    DB_NAME,
    'wp_survey_waves'
);

// Detectar y eliminar índices con nombre vacío
foreach ($indexes as $index) {
    if (trim($index->INDEX_NAME) === '') {
        $wpdb->query("ALTER TABLE wp_survey_waves DROP INDEX (`{$index->COLUMN_NAME}`)");
    }
}
```

## Notas

- La migración es idempotente (se puede ejecutar múltiples veces sin causar daño)
- Los datos de las tablas no se modifican, solo la estructura de índices
- Se mantiene compatibilidad con todas las versiones de MySQL/MariaDB soportadas por WordPress
- La versión de migración se guarda en `eipsi_db_schema_migration_version` para prevenir ejecuciones duplicadas

## Testing

Para probar la solución:

1. Simular índice corrupto:
   ```sql
   ALTER TABLE wp_survey_waves ADD INDEX `` (`study_id`);
   ```

2. Ejecutar reparación:
   - Activar plugin o
   - Visitar admin y clic en "Run Database Migration" o
   - Ejecutar: `eipsi_repair_database_schema()`

3. Verificar:
   - Índice corrupto eliminado
   - No hay errores SQL
   - Admin dashboard carga normalmente

## Backwards Compatibility

- ✅ Compatible con versiones anteriores del plugin
- ✅ No rompe tablas existentes
- ✅ No afecta datos de usuarios
- ✅ Funciona con WordPress 5.8+ y PHP 7.4+

## Recursos

- WordPress dbDelta: https://developer.wordpress.org/reference/functions/dbdelta/
- MySQL SHOW INDEX: https://dev.mysql.com/doc/refman/8.0/en/show-index.html
- INFORMATION_SCHEMA: https://dev.mysql.com/doc/refman/8.0/en/information-schema.html
