# Quality Flag Relocation – Draft 2 ✅

**Fecha:** 25 de noviembre de 2024  
**Versión base:** v1.2.2  
**Ticket:** Admin Panel – Mover "Quality Flag" a sección de trazabilidad (opcional, ON por defecto)

---

## 🎯 **OBJETIVO**

Reubicar y clarificar la configuración de Quality Flag en el Admin Panel, tratándolo como un metadato de trazabilidad opcional (similar a Device Type / IP), con toggle configurable pero **ON por defecto**.

---

## ✅ **CAMBIOS REALIZADOS**

### 1. **UI Admin Panel** (`admin/privacy-dashboard.php`)

**ANTES:**
```php
<!-- SEGURIDAD BÁSICA (OBLIGATORIO) -->
<div class="eipsi-toggle-group">
    <h3>🔐 Seguridad Básica</h3>
    <label>
        <input type="checkbox" checked disabled> 
        <strong>Form ID</strong>
        ...
    </label>
    <label>
        <input type="checkbox" checked disabled> 
        <strong>Participant ID</strong>
        ...
    </label>
    <label>
        <input type="checkbox" checked disabled> 
        <strong>Quality Flag</strong>  ← AQUÍ ESTABA (disabled, siempre ON)
        <span class="eipsi-tooltip">(Control automático: HIGH/NORMAL/LOW)</span>
    </label>
</div>
```

**DESPUÉS:**
```php
<!-- SEGURIDAD BÁSICA (OBLIGATORIO) -->
<div class="eipsi-toggle-group">
    <h3>🔐 Seguridad Básica</h3>
    <label>
        <input type="checkbox" checked disabled> 
        <strong>Form ID</strong>
        ...
    </label>
    <label>
        <input type="checkbox" checked disabled> 
        <strong>Participant ID</strong>
        ...
    </label>
    <!-- Quality Flag eliminado de aquí -->
</div>

<!-- TRAZABILIDAD -->
<div class="eipsi-toggle-group">
    <h3>📋 Trazabilidad</h3>
    <label>
        <input type="checkbox" name="device_type" ...>
        <strong>Device Type</strong>
        ...
    </label>
    <label>
        <input type="checkbox" name="ip_address" ...>
        <strong>IP Address</strong>
        ...
    </label>
    <label>
        <input type="checkbox" name="quality_flag" <?php checked($privacy_config['quality_flag'] ?? true); ?>>
        <strong>Quality Flag</strong>  ← AHORA AQUÍ (configurable, default ON)
        <span class="eipsi-tooltip">(Control automático: HIGH/NORMAL/LOW)</span>
    </label>
</div>
```

**Cambios clave:**
- ✅ Quality Flag eliminado de "Seguridad Básica"
- ✅ Quality Flag agregado a "Trazabilidad"
- ✅ Toggle activo (NO disabled)
- ✅ Default ON (`?? true`)

---

### 2. **Configuración permitida** (`admin/privacy-config.php`)

**ANTES:**
```php
$allowed_toggles = array(
    'therapeutic_engagement',
    'clinical_consistency',
    'avoidance_patterns',
    'device_type',
    'browser',
    'os',
    'screen_width',
    'ip_address'
    // quality_flag NO estaba aquí
);
```

**DESPUÉS:**
```php
$allowed_toggles = array(
    'therapeutic_engagement',
    'clinical_consistency',
    'avoidance_patterns',
    'device_type',
    'browser',
    'os',
    'screen_width',
    'ip_address',
    'quality_flag'  ← AGREGADO
);
```

**Cambios clave:**
- ✅ `quality_flag` ahora puede ser guardado/configurado

---

### 3. **Handler AJAX de guardado** (`admin/ajax-handlers.php`)

**ANTES:**
```php
$config = array(
    'therapeutic_engagement' => isset($_POST['therapeutic_engagement']),
    'clinical_consistency' => isset($_POST['clinical_consistency']),
    'avoidance_patterns' => isset($_POST['avoidance_patterns']),
    'device_type' => isset($_POST['device_type'])
    // Faltaban: browser, os, screen_width, ip_address, quality_flag
);
```

**DESPUÉS:**
```php
$config = array(
    'therapeutic_engagement' => isset($_POST['therapeutic_engagement']),
    'clinical_consistency' => isset($_POST['clinical_consistency']),
    'avoidance_patterns' => isset($_POST['avoidance_patterns']),
    'device_type' => isset($_POST['device_type']),
    'browser' => isset($_POST['browser']),
    'os' => isset($_POST['os']),
    'screen_width' => isset($_POST['screen_width']),
    'ip_address' => isset($_POST['ip_address']),
    'quality_flag' => isset($_POST['quality_flag'])  ← AGREGADO
);
```

**Cambios clave:**
- ✅ Agregados todos los toggles faltantes al config que se guarda
- ✅ `quality_flag` ahora se guarda correctamente

---

### 4. **Lógica de captura condicional** (`admin/ajax-handlers.php`)

**ANTES:**
```php
// QUALITY METRICS (SIEMPRE)
$quality_flag = eipsi_calculate_quality_flag($form_responses, $duration_seconds, $estimated_total_pages);
$metadata['quality_metrics'] = array(
    'quality_flag' => $quality_flag,
    'completion_rate' => 1.0
);
```

**DESPUÉS:**
```php
// QUALITY METRICS (según privacy config)
$quality_flag = null;
if ($privacy_config['quality_flag'] ?? true) {
    $quality_flag = eipsi_calculate_quality_flag($form_responses, $duration_seconds, $estimated_total_pages);
    $metadata['quality_metrics'] = array(
        'quality_flag' => $quality_flag,
        'completion_rate' => 1.0
    );
} else {
    $metadata['quality_metrics'] = array(
        'completion_rate' => 1.0
    );
}
```

**Cambios clave:**
- ✅ **Si toggle ON:** Calcula y guarda quality_flag (HIGH/NORMAL/LOW)
- ✅ **Si toggle OFF:** `$quality_flag = null`, no se agrega a metadata, no se guarda en BD

---

### 5. **Script AJAX inline** (`admin/privacy-dashboard.php`)

**AGREGADO:**
```javascript
<script>
jQuery(document).ready(function($) {
    $('#eipsi-privacy-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var $submitButton = $(this).find('button[type="submit"]');
        var originalText = $submitButton.text();
        
        $submitButton.prop('disabled', true).text('💾 Guardando...');
        
        $('.eipsi-message').remove();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData + '&action=eipsi_save_privacy_config',
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                } else {
                    showMessage('error', response.data.message || 'Error al guardar la configuración.');
                }
            },
            error: function() {
                showMessage('error', 'Error al guardar la configuración. Por favor, inténtelo de nuevo.');
            },
            complete: function() {
                $submitButton.prop('disabled', false).text(originalText);
            }
        });
    });
    
    function showMessage(type, message) {
        var $message = $('<div>')
            .addClass('eipsi-message notice is-dismissible')
            .addClass(type === 'success' ? 'notice-success' : 'notice-error')
            .html('<p>' + message + '</p>');
        
        $('#eipsi-privacy-form').before($message);
        
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
});
</script>
```

**Cambios clave:**
- ✅ Manejo completo de submit via AJAX
- ✅ Feedback visual (spinner, mensajes)
- ✅ Auto-dismiss después de 3 segundos

---

## 🔐 **RETROCOMPATIBILIDAD**

### ✅ **Default ON en nuevas instalaciones**
```php
<?php checked($privacy_config['quality_flag'] ?? true); ?>
```
- Si no hay configuración guardada → **ON por defecto**

### ✅ **Instalaciones existentes**
- Si ya tenían quality_flag activo → sigue activo
- Si no tenían configuración → defaultea a ON

### ✅ **Datos históricos**
- **No se tocan** - siguen mostrando su quality_flag anterior
- Solo aplica a **nuevos envíos**

### ✅ **Schema de BD**
- Columna `quality_flag` sigue siendo `enum('HIGH','NORMAL','LOW') DEFAULT 'NORMAL'`
- Si el toggle está OFF, se guarda `NULL` en vez de calcular

---

## ✅ **ACCEPTANCE CRITERIA - CUMPLIMIENTO**

| Criterio | Estado | Verificado |
|----------|--------|------------|
| Quality Flag aparece en "Trazabilidad", NO en "Seguridad Básica" | ✅ | Líneas 81-85 `privacy-dashboard.php` |
| Quality Flag tiene toggle ON/OFF | ✅ | `<input type="checkbox" name="quality_flag" ...>` |
| Default ON en instalaciones nuevas | ✅ | `?? true` |
| Toggle OFF → nuevos envíos no guardan quality_flag | ✅ | Líneas 401-412 `ajax-handlers.php` |
| Toggle ON → comportamiento actual (HIGH/NORMAL/LOW) | ✅ | Líneas 403-407 `ajax-handlers.php` |
| Form ID y Participant ID en "Seguridad Básica", sin toggles | ✅ | Líneas 29-39 `privacy-dashboard.php` |

---

## 🛠️ **BUILD & LINT**

```bash
npm run build
# ✅ webpack 5.103.0 compiled successfully in 4602 ms

npm run lint:js
# ✅ 0 errors, 0 warnings
```

---

## 📋 **ARCHIVOS MODIFICADOS**

1. **`admin/privacy-dashboard.php`** (4 cambios):
   - Eliminado Quality Flag de "Seguridad Básica"
   - Agregado Quality Flag a "Trazabilidad" con toggle
   - Agregado script AJAX inline

2. **`admin/privacy-config.php`** (1 cambio):
   - Agregado `'quality_flag'` a `$allowed_toggles`

3. **`admin/ajax-handlers.php`** (2 cambios):
   - Agregado `'quality_flag'` al config que se guarda
   - Modificada lógica de captura condicional

**Total:** 3 archivos, 7 cambios

---

## 🧪 **TESTING SUGERIDO**

### **Paso 1: Verificar UI**
1. Ir a Admin Panel → Results & Experience → Privacy & Metadata
2. ✅ Confirmar que "Quality Flag" aparece en "Trazabilidad"
3. ✅ Confirmar que tiene un toggle activo (no disabled)
4. ✅ Confirmar que está checkeado por defecto

### **Paso 2: Probar guardado**
1. Cambiar el toggle de Quality Flag (ON → OFF)
2. Guardar configuración
3. ✅ Debe aparecer mensaje: "✅ Configuración guardada correctamente"
4. Recargar página
5. ✅ El toggle debe mantener el estado OFF

### **Paso 3: Probar captura**
1. **Con toggle ON:**
   - Enviar un formulario
   - Verificar en BD que `quality_flag` tiene valor (HIGH/NORMAL/LOW)
   
2. **Con toggle OFF:**
   - Enviar otro formulario
   - Verificar en BD que `quality_flag` es `NULL`

---

## 📌 **NOTAS CLÍNICAS**

### **¿Por qué mover Quality Flag a Trazabilidad?**

**ANTES (problema conceptual):**
- Quality Flag estaba en "Seguridad Básica" → implicaba que es **obligatorio** y **no configurable**
- Realidad: Quality Flag es un **indicador derivado** (HIGH/NORMAL/LOW basado en engagement, consistency, duration)
- No es esencial para la **seguridad** del formulario (Form ID y Participant ID sí lo son)

**AHORA (más coherente):**
- Quality Flag en "Trazabilidad" → es un **metadato opcional** para análisis posterior
- Similar a Device Type, IP Address → capturan contexto, pero **no son obligatorios**
- El investigador puede decidir si necesita esta métrica o no

### **Default ON:**
- Mantiene la experiencia actual para instalaciones existentes
- Clínicos que usan quality_flag activamente → no se les rompe nada
- Nuevos usuarios → lo tienen activo por defecto (recomendado)

---

## ✅ **CONCLUSIÓN**

Quality Flag ahora es un **metadato de trazabilidad opcional**, configurable por el investigador, con **default ON** para mantener retrocompatibilidad y no romper flujos existentes.

**Estado:** ✅ **Completado** - Listo para merge
**Build:** ✅ Exitoso (webpack 5.103.0, < 5s)  
**Lint:** ✅ 0 errors, 0 warnings  
**Retrocompatibilidad:** ✅ Preservada  
**Acceptance Criteria:** ✅ 6/6 cumplidos

---

**Mathias Rojas**  
EIPSI Forms – v1.2.2  
*Por fin alguien entendió cómo trabajo de verdad con mis pacientes.*
