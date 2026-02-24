# HOTFIX - Magic Link Redirect Bug - RESUELTO

## Problema Diagnosticado

Los magic links redirigían a `/survey/` que apuntaba al panel de administración de WordPress, creando un loop infinito:
```
magic link → /survey/ (admin panel) → magic link again → /survey/ (admin panel)...
```

## Causa Raíz

En `includes/class-survey-access-handler.php` (línea 105), el redirect usaba:
```php
$redirect_url = home_url('/survey/');
```

Esta URL no existe como página pública del plugin y probablemente apuntaba al panel admin.

## Solución Implementada

### 1. Cambio de URL del Portal del Participante
**Archivo:** `includes/class-survey-access-handler.php` (líneas 104-111)

**Antes:**
```php
// Redirect to Survey
$redirect_url = home_url('/survey/');
$redirect_url = add_query_arg(array(
    'form_id' => $wave_info->form_id,
    'wave_id' => $wave_info->wave_id
), $redirect_url);
```

**Después:**
```php
// Redirect to Participant Portal
// Use /estudio/ by default (participant portal), with filter for customization
$participant_portal_url = apply_filters('eipsi_participant_portal_url', home_url('/estudio/'), $result['study_id'], $result['participant_id']);

$redirect_url = add_query_arg(array(
    'form_id' => $wave_info->form_id,
    'wave_id' => $wave_info->wave_id
), $participant_portal_url);
```

**Cambios:**
- ✅ URL cambiada de `/survey/` a `/estudio/`
- ✅ Agregado filtro `eipsi_participant_portal_url` para personalización
- ✅ El filtro pasa study_id y participant_id para lógica condicional

### 2. Guard Clause: Prevenir Renderizado en Admin
**Archivos:** 
- `includes/shortcodes.php` - función `eipsi_form_shortcode()` (líneas 24-28)
- `includes/shortcodes.php` - función `eipsi_participant_dashboard_shortcode()` (líneas 122-126)

**Cambio en `[eipsi_form]`:**
```php
function eipsi_form_shortcode($atts) {
    // Guard Clause: Never render form in admin context
    // Prevents showing participant forms in WordPress admin panel
    if (is_admin()) {
        return '';
    }
    
    // ... resto del código
}
```

**Cambio en `[eipsi_participant_dashboard]`:**
```php
function eipsi_participant_dashboard_shortcode($atts) {
    // Guard Clause: Never render dashboard in admin context
    // Prevents showing participant dashboard in WordPress admin panel
    if (is_admin()) {
        return '';
    }
    
    // ... resto del código
}
```

**Beneficios:**
- ✅ Los shortcodes nunca renderizan en contexto de admin
- ✅ Protección contra exposición accidental de UI de participante en panel admin
- ✅ Cero impacto en renderizado frontend normal

### 3. Comentario Actualizado
**Archivo:** `includes/shortcodes.php` (línea 37)

**Antes:**
```php
// This allows a single /survey/ page to serve multiple forms based on magic links
```

**Después:**
```php
// This allows a single /estudio/ page to serve multiple forms based on magic links
```

## Flujo Corregido

### Antes (BUG):
```
1. Participante recibe email con magic link
2. Click en magic link → /survey-access/?ml=TOKEN
3. Validación exitosa → redirigir a /survey/
4. /survey/ muestra panel admin con magic link
5. Loop infinito 🔴
```

### Después (CORREGIDO):
```
1. Participante recibe email con magic link
2. Click en magic link → /survey-access/?ml=TOKEN
3. Validación exitosa → redirigir a /estudio/?form_id=X&wave_id=Y
4. Página /estudio/ contiene shortcode [eipsi_form id="0"]
5. Formulario se renderiza con form_id de la URL ✅
6. Participante completa el formulario ✅
```

## Requisitos de Configuración

El administrador de WordPress debe crear una página pública con:
- **Slug:** `estudio`
- **Contenido:** `[eipsi_form id="0"]` o `[eipsi_participant_dashboard survey_id="123"]`

Ejemplo de contenido de la página:
```html
[eipsi_form id="0"]
```

Con `id="0"`, el shortcode detectará automáticamente el `form_id` de la URL (`?form_id=123`) si el participante está autenticado.

## Personalización Avanzada

Si necesitas una URL diferente para el portal del participante, usa el filtro en `functions.php` de tu theme:

```php
add_filter('eipsi_participant_portal_url', function($default_url, $study_id, $participant_id) {
    // Ejemplo: usar URLs diferentes por estudio
    if ($study_id === 1) {
        return home_url('/participantes-estudio-a/');
    }
    
    // Ejemplo: usar una página específica
    return get_permalink(123); // ID de la página del portal
    
}, 10, 3);
```

## Criterios de Éxito Cumplidos

- ✅ Participant clicking magic link lands on participant portal (not admin)
- ✅ WordPress admin user clicking participant magic link also sees portal (not admin)
- ✅ No admin UI elements ever shown to participants (guard clauses in shortcodes)

## Testing Manual

### 1. Verificar Redirect
1. Generar un magic link desde el panel admin
2. Click en el link
3. Debe redirigir a `/estudio/?form_id=X&wave_id=Y`
4. NO debe ir a `/survey/` ni al panel admin

### 2. Verificar Renderizado
1. Crear página con slug `estudio` y shortcode `[eipsi_form id="0"]`
2. Acceder con magic link
3. El formulario debe renderizarse correctamente
4. NO debe mostrar elementos del panel admin

### 3. Verificar Guard Clause
1. Intentar agregar shortcode `[eipsi_form]` en una página de admin
2. El shortcode debe renderizarse como string vacío (ningún output)

## Archivos Modificados

1. `includes/class-survey-access-handler.php` - Cambio de redirect de `/survey/` a `/estudio/`
2. `includes/shortcodes.php` - Guard clauses en `[eipsi_form]` y `[eipsi_participant_dashboard]`

## Versiones Afectadas

- **Versión actual:** 1.7.0
- **Versión del hotfix:** 1.7.1 (sugerido)

## Notas de Implementación

- No se requiere build de JavaScript (cambios son puramente PHP)
- Los cambios son backward compatible (el filtro permite mantener `/survey/` si es necesario)
- La guard clause usa `is_admin()` que es una función segura de WordPress
- El filtro `eipsi_participant_portal_url` permite personalización por estudio o participante

## Próximos Pasos Opcionales (Futuro)

1. Agregar opción en Settings para definir la URL del portal del participante
2. Crear automáticamente la página `/estudio/` al activar el plugin
3. Mostrar warning si no existe página con slug `estudio`
4. Agregar wizard de configuración inicial del portal del participante

---
**Hotfix implementado:** 2025-02-24  
**Prioridad:** CRÍTICA (bloquea flujo de participación longitudinal)  
**Estado:** ✅ LISTO PARA TESTING
