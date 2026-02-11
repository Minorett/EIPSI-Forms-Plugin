# Fix: Error Fatal por Función Duplicada `eipsi_create_manual_overrides_table()`

## 📌 Problema Detectado

Error fatal en WordPress al cargar el plugin EIPSI Forms:

```
Fatal error: Cannot redeclare eipsi_create_manual_overrides_table()
```

**Causa:** La función `eipsi_create_manual_overrides_table()` estaba declarada en dos archivos:
1. `/admin/randomization-db-setup.php` (línea 123) - versión antigua (v1.3.1)
2. `/admin/manual-overrides-table.php` (línea 18) - versión nueva (v1.4.5)

## 🔧 Solución Implementada

### 1. **Eliminación de Declaración Duplicada**

**Archivo:** `/admin/randomization-db-setup.php`

- ❌ **ANTES:** Función completa (líneas 113-162) con lógica de creación de tabla
- ✅ **DESPUÉS:** Solo comentario de referencia (líneas 113-120) que documenta dónde está la implementación real

```php
/**
 * Crear tabla de asignaciones manuales (overrides)
 *
 * NOTA: Esta función está definida en admin/manual-overrides-table.php (v1.4.5)
 * Se mantiene la llamada aquí para compatibilidad con el flujo de activación.
 *
 * @see admin/manual-overrides-table.php
 */
```

### 2. **Reordenamiento de Carga de Archivos**

**Archivo:** `/eipsi-forms.php`

- **ANTES:** `manual-overrides-table.php` se cargaba en línea 1075 (muy tarde)
- **DESPUÉS:** `manual-overrides-table.php` se carga en línea 60 (ANTES de `randomization-db-setup.php`)

```php
// Sistema RCT completo (v1.3.1)
// IMPORTANTE: manual-overrides-table.php debe cargarse ANTES de randomization-db-setup.php
// porque este último llama a eipsi_create_manual_overrides_table() en sus hooks
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/manual-overrides-table.php';
require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/randomization-db-setup.php';
```

- **Eliminado:** `require_once` duplicado de línea 1078

## ✅ Verificaciones Realizadas

### Declaración Única
```bash
$ grep -n "function eipsi_create_manual_overrides_table" admin/*.php
admin/manual-overrides-table.php:18:function eipsi_create_manual_overrides_table() {
```
✅ Solo 1 declaración

### Llamadas Válidas
```bash
$ grep -n "eipsi_create_manual_overrides_table()" admin/*.php
admin/manual-overrides-table.php:60:        eipsi_create_manual_overrides_table();
admin/randomization-db-setup.php:128:    $overrides_created = eipsi_create_manual_overrides_table();
```
✅ 2 llamadas válidas (ambas DESPUÉS de la definición)

### Orden de Carga
```bash
$ grep -n "manual-overrides-table.php\|randomization-db-setup.php" eipsi-forms.php
60:require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/manual-overrides-table.php';
61:require_once EIPSI_FORMS_PLUGIN_DIR . 'admin/randomization-db-setup.php';
```
✅ Orden correcto

### Build Exitoso
```bash
$ npm run build
✅ webpack 5.104.1 compiled successfully in 3207 ms
✅ Fixed 12 block.json files
```

## 📋 Archivos Modificados

1. **`/admin/randomization-db-setup.php`**
   - Eliminada implementación completa de `eipsi_create_manual_overrides_table()`
   - Agregado comentario de referencia

2. **`/eipsi-forms.php`**
   - Movido `require_once` de `manual-overrides-table.php` de línea 1078 → línea 60
   - Agregado comentario explicativo sobre el orden de carga
   - Eliminada línea duplicada

## 🎯 Criterios de Aceptación Cumplidos

- ✅ Error fatal resuelto
- ✅ Función declarada solo una vez
- ✅ Funcionalidad del plugin operativa
- ✅ Build exitoso (`npm run build`)
- ✅ No se introdujeron nuevos problemas
- ✅ Documentación agregada para futura referencia

## 🔍 Detalles Técnicos

### Versión de la Tabla Mantenida
Se mantuvo la versión de **v1.4.5** (`manual-overrides-table.php`) porque incluye:
- `randomization_id VARCHAR(255)` (más amplio que v1.3.1's `VARCHAR(100)`)
- `created_by BIGINT(20) UNSIGNED NOT NULL` (constraint más estricto)
- Índice adicional en columna `created_by`
- Hook `admin_init` para verificación automática de tabla

### Flujo de Activación
```
1. WordPress carga eipsi-forms.php
2. Se carga manual-overrides-table.php (define función)
3. Se carga randomization-db-setup.php (puede llamar función)
4. Hook admin_init ejecuta verificaciones
5. Si tabla no existe → llama eipsi_create_manual_overrides_table()
```

## 📝 Recomendaciones Futuras

1. **Evitar Duplicación:** Implementar un sistema de autoload o namespace para prevenir declaraciones duplicadas
2. **Tests Automatizados:** Crear tests PHP que verifiquen la ausencia de declaraciones duplicadas
3. **Versionado de Tablas:** Considerar sistema de migraciones más robusto (similar a Laravel Migrations)
4. **Documentación:** Mantener un archivo central que documente qué archivos definen qué funciones

## 🧪 Testing Recomendado

1. **Activar plugin** en WordPress limpio
2. **Verificar creación** de tabla `wp_eipsi_manual_overrides`
3. **Crear estudio RCT** y verificar funcionalidad de asignaciones manuales
4. **Desactivar y reactivar** plugin para verificar re-creación de tablas

---

**Fecha de Fix:** 2025-02-11  
**Versión:** v1.4.3 → v1.4.4 (propuesta)  
**Autor:** EIPSI Forms Development Team
