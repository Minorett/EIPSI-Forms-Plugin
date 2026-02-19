# Resumen de Correcciones del Esquema de Base de Datos - EIPSI Forms

## Fecha
2025-02-19

## Problemas Identificados

### 1. Errores de Claves Foráneas (Foreign Keys)
- Las funciones `eipsi_sync_survey_waves_table()` y `eipsi_sync_survey_assignments_table()` intentaban agregar claves foráneas sin verificar si las tablas referenciadas existían.
- Esto causaba errores SQL como:
  - `errno: 150` - Tabla referenciada no existe
  - `Cannot add foreign key constraint` - La columna referenciada no está indexada

### 2. Orden Incorrecto de Creación de Tablas
- La función `eipsi_maybe_create_tables()` no creaba las tablas en el orden correcto de dependencias.
- La tabla `survey_studies` no tenía una función global para su creación.

### 3. Falta de Manejo de Errores
- La función `eipsi_longitudinal_ensure_foreign_key()` no manejaba adecuadamente los errores cuando fallaba la creación de FKs.

## Cambios Realizados

### 1. Nuevas Funciones de Utilidad (línea 2328)

#### `eipsi_table_exists($table_name)`
Verifica si una tabla existe en la base de datos antes de intentar agregar claves foráneas.

#### `eipsi_column_exists_db($table_name, $column_name)`
Verifica si una columna específica existe en una tabla.

#### `eipsi_get_column_info($table_name, $column_name)`
Obtiene información detallada sobre el tipo de datos de una columna.

### 2. Función `eipsi_longitudinal_ensure_foreign_key()` Mejorada (línea 2281)

- Ahora suprime errores con `@` para evitar que el sitio se rompa si falla la creación de FKs.
- Registra mensajes de error más detallados para diagnóstico.
- Identifica patrones comunes de error (errno: 150, Cannot add foreign key).

### 3. Nueva Función `eipsi_sync_survey_studies_table()` (línea 2568)

Crea la tabla `wp_survey_studies` que es la tabla principal de estudios longitudinales.

### 4. Función `eipsi_sync_survey_waves_table()` Corregida (línea 2415)

- Ahora verifica que la tabla `survey_studies` exista antes de agregar la FK `fk_waves_study`.
- La FK a `wp_posts` se mantiene ya que WordPress garantiza su existencia.

### 5. Función `eipsi_sync_survey_assignments_table()` Corregida (línea 2483)

- Verifica existencia de `survey_studies` antes de agregar `fk_assignments_study`.
- Verifica existencia de `survey_waves` antes de agregar `fk_assignments_wave`.
- Verifica existencia de `survey_participants` antes de agregar `fk_assignments_participant`.

### 6. Función `eipsi_sync_survey_magic_links_table()` Corregida (línea 2625)

- Verifica existencia de `survey_participants` antes de agregar `fk_magic_links_participant`.

### 7. Función `eipsi_sync_survey_email_log_table()` Corregida (línea 2678)

- Verifica existencia de `survey_participants` antes de agregar `fk_email_log_participant`.

### 8. Función `eipsi_maybe_create_tables()` Corregida (línea 2601)

Ahora crea las tablas en el orden correcto de dependencias:

1. **Tablas PADRE** (sin dependencias o con FK solo a wp_posts):
   - `survey_studies` - Tabla principal
   - `survey_participants` - Depende de survey_studies

2. **Tablas HIJO Nivel 1** (dependen de tablas padre):
   - `survey_waves` - Depende de survey_studies

3. **Tablas HIJO Nivel 2** (dependen de tablas nivel 1):
   - `survey_assignments` - Depende de survey_studies, survey_waves, survey_participants
   - `survey_magic_links` - Depende de survey_participants
   - `survey_email_log` - Depende de survey_participants
   - `survey_audit_log` - Sin dependencias externas

## Beneficios de los Cambios

1. **Eliminación de Errores SQL**: Las claves foráneas solo se intentan crear cuando las tablas referenciadas existen.

2. **Mejor Diagnóstico**: Los mensajes de error en los logs ahora indican claramente qué FK se omitió y por qué.

3. **Resiliencia**: El plugin sigue funcionando incluso si algunas FKs no pueden crearse.

4. **Orden Correcto**: Las tablas se crean en el orden de dependencias, garantizando la integridad referencial.

## Archivos Modificados

- `admin/database-schema-manager.php` - Archivo principal con las correcciones

## Pruebas Recomendadas

1. Activar el plugin en una instalación limpia de WordPress.
2. Verificar que todas las tablas se creen sin errores.
3. Revisar los logs de error de WordPress (`wp-content/debug.log`) para confirmar que no hay errores de SQL.
4. Probar la creación de estudios longitudinales y verificar que las relaciones funcionen correctamente.

## Notas para Desarrolladores Futuros

- Siempre usar `eipsi_table_exists()` antes de agregar una clave foránea.
- Mantener el orden de creación de tablas en `eipsi_maybe_create_tables()`:
  1. Tablas sin FKs primero
  2. Tablas con FKs a tablas ya creadas después
- Las FKs a `wp_posts` no necesitan verificación ya que WordPress garantiza su existencia.
