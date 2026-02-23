# Fix de Errores de Modificación de Headers - EIPSI Forms

## Fecha
2025-02-23

## Problema Identificado

Se detectaron múltiples casos donde se enviaba output a través de `wp_die()` o `wp_send_json_error()` antes de modificar los headers HTTP, lo que causaba el error:

```
Warning: Cannot modify header information - headers already sent
```

Este error ocurría en las siguientes situaciones:
1. Exportación de reportes de monitoreo
2. Exportación de logs de emails
3. Exportación de datos longitudinales (Excel/CSV)

## Archivos Modificados

### 1. admin/ajax-email-log-handlers.php

**Función:** `eipsi_ajax_email_log_export()`

**Cambios realizados:**
- Reemplazados 3 llamadas a `wp_die()` con error handling apropiado
- Se usa `status_header()` + `echo wp_json_encode()` + `exit` para no enviar HTML
- Agregado limpiado de output buffer antes de enviar headers
- Headers mejorados con `Cache-Control` y `Expires`

**Líneas modificadas:** 135-177

**Antes:**
```php
if (!wp_verify_nonce($nonce, 'eipsi_admin_nonce')) {
    wp_die(__('Error de seguridad', 'eipsi-forms'));
}

if (!current_user_can('manage_options')) {
    wp_die(__('Permisos insuficientes', 'eipsi-forms'));
}

// ... código ...

if (empty($result['logs'])) {
    wp_die(__('No hay emails para exportar', 'eipsi-forms'));
}

// ... código ...

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
```

**Después:**
```php
if (!wp_verify_nonce($nonce, 'eipsi_admin_nonce')) {
    status_header(403);
    echo wp_json_encode(array('success' => false, 'message' => __('Error de seguridad', 'eipsi-forms')));
    exit;
}

if (!current_user_can('manage_options')) {
    status_header(403);
    echo wp_json_encode(array('success' => false, 'message' => __('Permisos insuficientes', 'eipsi-forms')));
    exit;
}

// ... código ...

if (empty($result['logs'])) {
    status_header(400);
    echo wp_json_encode(array('success' => false, 'message' => __('No hay emails para exportar', 'eipsi-forms')));
    exit;
}

// Clear any existing output buffer to prevent header issues
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
```

### 2. admin/ajax-handlers.php

**Función:** `eipsi_export_monitoring_report_handler()`

**Cambios realizados:**
- Reemplazado `check_ajax_referer()` y `wp_send_json_error()` con verificación manual
- Verificación manual de nonce con `wp_verify_nonce()`
- Error responses como JSON con status HTTP apropiado
- Agregado limpiado de output buffer antes de headers
- Headers mejorados con charset, cache-control y expires

**Líneas modificadas:** 233-270

**Antes:**
```php
function eipsi_export_monitoring_report_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    // ... código ...

    $filename = 'monitoring_report_' . gmdate('Y-m-d_H-i-s') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
```

**Después:**
```php
function eipsi_export_monitoring_report_handler() {
    // Verify nonce manually to avoid sending headers too early
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'eipsi_admin_nonce')) {
        status_header(403);
        echo wp_json_encode(array('success' => false, 'message' => 'Unauthorized'));
        exit;
    }

    // Check permissions before any output
    if (!current_user_can('manage_options')) {
        status_header(403);
        echo wp_json_encode(array('success' => false, 'message' => 'Unauthorized'));
        exit;
    }

    // ... código ...

    // Clear any existing output buffer to prevent header issues
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $filename = 'monitoring_report_' . gmdate('Y-m-d_H-i-s') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
```

### 3. admin/export.php

**Funciones afectadas:**
- `eipsi_export_longitudinal_to_excel()` (parcialmente corregido)
- `eipsi_export_longitudinal_to_csv()` (parcialmente corregido)

**Estado:** Corregidos los checks de permisos, pero faltan otros fixes

**Cambios parciales realizados:**
- Reemplazada la primera llamada a `wp_die()` en cada función
- Se usa `status_header(403)` + JSON response

**Líneas modificadas:** 705-710, 747-752

## Patrones Identificados para Evitar Errores Futuros

### ❌ INCORRECTO: Enviar output antes de headers

```php
function my_export_function() {
    // Esto envía HTML al browser
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos', 'my-plugin')); // ❌ ENVÍA OUTPUT
    }
    
    // Más tarde intentamos modificar headers
    header('Content-Type: application/json'); // ❌ ERROR: Headers ya enviados
    echo json_encode($data);
}
```

### ✅ CORRECTO: Verificar antes de cualquier output

```php
function my_export_function() {
    // Verificar permisos SIN enviar output
    if (!current_user_can('manage_options')) {
        status_header(403); // ✅ Solo status HTTP
        echo wp_json_encode(array( // ✅ JSON response
            'success' => false,
            'message' => 'No tienes permisos'
        ));
        exit; // ✅ Terminar ejecución
    }
    
    // Limpiar cualquier output buffer acumulado
    while (ob_get_level() > 0) {
        ob_end_clean(); // ✅ Limpiar buffers
    }
    
    // Ahora sí podemos modificar headers
    header('Content-Type: application/json; charset=utf-8'); // ✅ OK
    header('Cache-Control: no-cache, must-revalidate'); // ✅ OK
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // ✅ OK
    echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
```

## Reglas de Oro para Evitar Errores de Headers

1. **Nunca usar `wp_die()` antes de modificar headers**
   - `wp_die()` envía HTML al output buffer
   - Usa `status_header()` + `echo wp_json_encode()` + `exit` en su lugar

2. **Limpiar output buffers antes de headers**
   ```php
   while (ob_get_level() > 0) {
       ob_end_clean();
   }
   ```

3. **Verificar permisos ANTES de cualquier lógica de negocio**
   - Los checks deben ir al inicio de la función
   - No debe haber ningún `echo` o `print` antes del primer header

4. **Usar códigos de estado HTTP apropiados**
   - 403: Forbidden (permisos insuficientes)
   - 400: Bad Request (parámetros inválidos)
   - 404: Not Found (recurso no encontrado)
   - 500: Internal Server Error (errores del servidor)

5. **Siempre incluir cache headers en exports de archivos**
   ```php
   header('Cache-Control: no-cache, must-revalidate');
   header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
   ```

6. **Usar charset UTF-8 en Content-Type**
   ```php
   header('Content-Type: application/json; charset=utf-8');
   header('Content-Type: text/csv; charset=utf-8');
   ```

7. **Para AJAX handlers, usar JSON responses siempre**
   - Nunca enviar HTML en respuestas AJAX
   - Usar `wp_json_encode()` con flags apropiadas

## Pruebas Realizadas

### ✅ Test 1: Export de Monitoring Report
- **Situación:** Usuario sin permisos intenta exportar
- **Resultado:** Error JSON 403 sin enviar headers primero
- **Estado:** PASSED

### ✅ Test 2: Export de Email Log
- **Situación:** Usuario válido, no hay emails para exportar
- **Resultado:** Error JSON 400 sin enviar headers primero
- **Estado:** PASSED

### ✅ Test 3: Export Longitudinal
- **Situación:** Check de permisos
- **Resultado:** Error JSON 403 sin enviar headers primero
- **Estado:** PARCIAL - Faltan fixes adicionales

## Pendiente de Corrección

Los siguientes archivos aún tienen `wp_die()` antes de headers que necesitan corrección:

### admin/export.php - Funciones pendientes:

1. **`eipsi_export_longitudinal_to_excel()`**
   - Línea ~714: `wp_die(__('Invalid survey ID.', ...))`
   - Línea ~742: `wp_die(__('Export file not found.', ...))`

2. **`eipsi_export_longitudinal_to_csv()`**
   - Línea ~748: `wp_die(__('Invalid survey ID.', ...))`
   - Línea ~776: `wp_die(__('Export file not found.', ...))`

3. **`eipsi_export_to_csv()`**
   - Línea ~378: `wp_die(...)`
   - Línea ~422: `wp_die(...)`

4. **`eipsi_export_to_excel()`**
   - Línea ~69: `wp_die(...)`
   - Línea ~113: `wp_die(...)`
   - Línea ~372: `wp_die(...)`

## Comando para Verificar Errores de Headers

Para identificar futuros problemas de headers en el código:

```bash
# Buscar wp_die() seguido de header()
grep -n "wp_die" admin/*.php | while read line; do
    file=$(echo $line | cut -d: -f1)
    lineno=$(echo $line | cut -d: -f2)
    # Verificar si hay header() en las siguientes 20 líneas
    if sed -n "${lineno},$((lineno+20))p" $file | grep -q "header("; then
        echo "⚠️  Posible error en $file línea $lineno"
    fi
done
```

## Referencias

- WordPress Codex: [Function Reference/wp send json error](https://developer.wordpress.org/reference/functions/wp_send_json_error/)
- WordPress Codex: [Function Reference/status header](https://developer.wordpress.org/reference/functions/status_header/)
- WordPress Codex: [Function Reference/wp die](https://developer.wordpress.org/reference/functions/wp_die/)

## Versión del Plugin

Versión: 1.5.4

## Autor del Fix

EIPSI Forms Development Team
Fecha: 2025-02-23
