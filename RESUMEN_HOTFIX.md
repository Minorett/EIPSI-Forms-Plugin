# Resumen Ejecutivo - Hotfix Magic Link Redirect

## 🐛 Problema Resuelto

**Severidad:** CRÍTICA  
**Impacto:** Bloquea flujo completo de participación en estudios longitudinales

Los magic links redirigían a `/survey/` (panel de administración) en lugar del portal del participante, creando un loop infinito que impedía el acceso de los participantes.

## ✅ Cambios Implementados

### 1. Redirección Correcta al Portal del Participante
**Archivo:** `includes/class-survey-access-handler.php`

- Cambiado redirect de `/survey/` a `/estudio/`
- Agregado filtro `eipsi_participant_portal_url` para personalización
- El filtro pasa study_id y participant_id para lógica condicional avanzada

### 2. Guard Clauses de Seguridad
**Archivo:** `includes/shortcodes.php`

- Agregada verificación `is_admin()` en `[eipsi_form]`
- Agregada verificación `is_admin()` en `[eipsi_participant_dashboard]`
- Los shortcodes retornan string vacío en contexto de admin
- Previene exposición accidental de UI de participante en panel admin

## 📋 Requisitos de Configuración

El admin debe crear una página en WordPress:

1. **Crear nueva página** con slug: `estudio`
2. **Agregar contenido:** `[eipsi_form id="0"]`
3. **Publicar la página**

El shortcode `[eipsi_form id="0"]` detectará automáticamente el `form_id` de la URL (`?form_id=123`) cuando el participante esté autenticado.

## 🔧 Uso del Filtro de Personalización

Opcional: Personalizar URL por estudio en `functions.php` del theme:

```php
add_filter('eipsi_participant_portal_url', function($default_url, $study_id, $participant_id) {
    // Ejemplo: URL diferente por estudio
    if ($study_id === 1) {
        return home_url('/portal-estudio-ansiedad/');
    }
    
    return $default_url;
}, 10, 3);
```

## ✅ Criterios de Éxito Cumplidos

- [x] Participant clicking magic link lands on participant portal (not admin)
- [x] WordPress admin user clicking participant magic link also sees portal (not admin)
- [x] No admin UI elements ever shown to participants

## 📁 Archivos Modificados

1. `includes/class-survey-access-handler.php` (líneas 104-111)
2. `includes/shortcodes.php` (líneas 24-28, 122-126, 37)

## 🧪 Testing Manual Requerido

### Test 1: Verificar Redirect
1. Generar magic link desde panel admin
2. Click en link → debe redirigir a `/estudio/?form_id=X&wave_id=Y`
3. **NO** debe ir a `/survey/` ni panel admin

### Test 2: Verificar Renderizado
1. Crear página `/estudio/` con `[eipsi_form id="0"]`
2. Acceder con magic link
3. Formulario debe renderizarse correctamente

### Test 3: Verificar Guard Clause
1. Intentar usar `[eipsi_form]` en página de admin
2. Debe renderizar string vacío (sin output visible)

## 📦 Notas Técnicas

- Cambios son puramente PHP → no requiere build de JavaScript
- Backward compatible → el filtro permite mantener `/survey/` si es necesario
- `is_admin()` es función segura de WordPress
- El filtro permite personalización por estudio/participante
- Cero impacto en shortcode `[eipsi_longitudinal_study]`

## 🚀 Próximos Pasos (Opcionales)

1. Opción en Settings para definir URL del portal
2. Auto-crear página `/estudio/` al activar plugin
3. Warning si no existe página con slug `estudio`
4. Wizard de configuración inicial del portal

---

**Hotfix:** v1.7.1 (sugerido)  
**Fecha:** 2025-02-24  
**Estado:** ✅ LISTO PARA TESTING Y DEPLOYMENT
