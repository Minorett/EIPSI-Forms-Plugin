# Guía Rápida: Manejo de Headers en EIPSI Forms

## Regla #1: NUNCA usar wp_die() antes de headers

❌ **MAL:**
```php
if (!$user->can('edit')) {
    wp_die('No permission'); // Envía HTML
}
header('Content-Type: application/json'); // CRASH!
```

✅ **BIEN:**
```php
if (!$user->can('edit')) {
    status_header(403); // Solo código HTTP
    echo wp_json_encode([ // JSON response
        'success' => false,
        'message' => 'No permission'
    ]);
    exit;
}
header('Content-Type: application/json'); // OK
```

## Regla #2: Siempre limpiar output buffers

```php
// ANTES de cualquier header()
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
```

## Regla #3: Headers estándar para exports de archivos

```php
header('Content-Type: <tipo>; charset=utf-8');
header('Content-Disposition: attachment; filename="<archivo>"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
// Opcional: header('Content-Length: ' . filesize($path));
```

**Ejemplos de Content-Type:**
- JSON: `application/json`
- CSV: `text/csv`
- Excel: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`

## Regla #4: Códigos de estado HTTP

| Situación | Código | Uso |
|-----------|--------|-----|
| Permiso denegado | 403 | `status_header(403)` |
| Parámetro inválido | 400 | `status_header(400)` |
| No encontrado | 404 | `status_header(404)` |
| Error interno | 500 | `status_header(500)` |

## Regla #5: Patrón completo para funciones de export

```php
function mi_export_function() {
    // 1. Verificar permisos (sin output)
    if (!current_user_can('manage_options')) {
        status_header(403);
        echo wp_json_encode([
            'success' => false,
            'message' => 'No tienes permisos'
        ]);
        exit;
    }
    
    // 2. Validar parámetros (sin output)
    $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    if (!$id) {
        status_header(400);
        echo wp_json_encode([
            'success' => false,
            'message' => 'ID inválido'
        ]);
        exit;
    }
    
    // 3. Generar datos
    $data = get_export_data($id);
    if (empty($data)) {
        status_header(404);
        echo wp_json_encode([
            'success' => false,
            'message' => 'No hay datos para exportar'
        ]);
        exit;
    }
    
    // 4. Limpiar buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // 5. Enviar headers
    $filename = 'export-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // 6. Enviar contenido y terminar
    echo $data;
    exit;
}
```

## Checklist para funciones con headers

- [ ] No se usa `wp_die()` antes del primer header
- [ ] No se usa `wp_send_json_error()` antes del primer header
- [ ] No hay `echo` o `print` antes del primer header
- [ ] Se limpian output buffers: `while (ob_get_level() > 0) { ob_end_clean(); }`
- [ ] Headers incluyen charset UTF-8
- [ ] Headers incluyen Cache-Control apropiado
- [ ] Códigos de estado HTTP son correctos (403, 400, 404)
- [ ] Se usa `exit` después de enviar error response
- [ ] Se usa `wp_json_encode()` para responses AJAX

## Debugging de Errores de Headers

### Comando para encontrar wp_die() problemáticos:
```bash
grep -rn "wp_die" admin/ --include="*.php" | \
while read line; do
    file=$(echo "$line" | cut -d: -f1)
    lineno=$(echo "$line" | cut -d: -f2)
    # Verificar si hay header() en las siguientes 10 líneas
    if sed -n "${lineno},$((lineno+10))p" "$file" | grep -q "header("; then
        echo "⚠️  $file:$lineno - wp_die() antes de header()"
    fi
done
```

### Error común en logs:
```
Warning: Cannot modify header information - headers already sent
```
**Solución:** Buscar cualquier output antes del primer header() y eliminarlo o moverlo después.

## Referencias Rápidas

- `status_header($code)` - Set HTTP status code
- `wp_json_encode($data, $flags)` - Encode array to JSON
- `JSON_PRETTY_PRINT` - Formatear JSON (para debug)
- `JSON_UNESCAPED_UNICODE` - Mantener caracteres unicode
- `ob_get_level()` - Nivel de anidamiento de output buffer
- `ob_end_clean()` - Limpiar y cerrar buffer superior

---

**Versión:** EIPSI Forms v1.5.4+
**Última actualización:** 2025-02-23
