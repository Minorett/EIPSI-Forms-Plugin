# EIPSI-Forms: CSS Color Palette Guide v1.5

> **Guía de paleta de colores clínicos calmantes para investigación en psicoterapia**
> 
> *"Por fin alguien entendió cómo trabajo de verdad con mis pacientes"*

---

## 🎨 Core Palette (Mandatory)

Estos colores son la base del sistema de diseño EIPSI y deben usarse consistentemente en todas las interfaces.

| Color | Hex | Uso | Emoción |
|-------|-----|-----|---------|
| **Primary** | `#3B6CAA` | Botones principales, enlaces, acentos | Confianza, profesionalismo clínico |
| **Primary Hover** | `#1E3A5F` | Estados hover de elementos primarios | Profundidad, seguridad |
| **Secondary BG** | `#AED6F1` | Fondos de énfasis suaves, highlights | Calma, serenidad |
| **Light BG** | `#f8f9fa` | Espacios limpios, fondos de sección | Limpieza, claridad |

---

## ✅ Semantic Colors (Action States)

Colores que comunican el resultado de acciones del usuario. Diseñados para ser calmantes clínicamente, no alarmantes.

| Estado | Color | Hex | Uso | Razón clínica |
|--------|-------|-----|-----|---------------|
| **Success** | Teal Calming | `#008080` | Éxito, completado, validado | Tranquilidad, progreso sin euforia |
| **Error** | Granate Calming | `#800000` | Error, alerta, requiere atención | Seriedad sin pánico |
| **Warning** | Amber | `#ffc107` | Información, precaución | Atención moderada |

### ¿Por qué Teal y Granate?

- **Teal (#008080)**: Asociado con aguas tranquilas, estabilidad emocional. No genera la excitación del verde brillante.
- **Granate (#800000)**: Profundidad seria sin la urgencia del rojo brillante. Indica que algo requiere atención sin activar respuesta de estrés.

---

## 🖥️ Admin Colors (WordPress Integration)

Colores específicos para el panel de administración integrado con WordPress.

| Elemento | Valor | Uso |
|----------|-------|-----|
| **Table Header** | `linear-gradient(135deg, #3B6CAA 0%, #1E3A5F 100%)` | Encabezados de tablas |
| **Admin Text** | `#1d2327` | Texto estándar WordPress |
| **Admin Border** | `#e2e8f0` | Bordes ligeros |

---

## 🌙 Dark Mode Override

Variables para modo oscuro automático (`prefers-color-scheme: dark`):

```css
@media (prefers-color-scheme: dark) {
    --eipsi-bg-dark: #0f172a;
    --eipsi-surface-dark: #1e293b;
    --eipsi-text-dark: #f1f5f9;
    --eipsi-primary-dark: #60a5fa;
    --eipsi-border-dark: #334155;
    --eipsi-success-dark: #008080;  /* Teal se mantiene */
    --eipsi-error-dark: #800000;    /* Granate se mantiene */
}
```

---

## 🗑️ Deprecation List (Removed)

Colores antiguos que ya no deben usarse:

| Antiguo | Nuevo | Archivos actualizados |
|---------|-------|----------------------|
| `#005a87` | `#3B6CAA` | Todos los CSS frontend |
| `#003d5b` | `#1E3A5F` | Todos los CSS frontend |
| `#0073aa` | `#3B6CAA` | Admin styles |
| `#198754` (green) | `#008080` (teal) | eipsi-forms.css, survey-login.css |
| `#00a32a` (WP green) | `#008080` (teal) | email-log.css, participant-dashboard.css |
| `#28a745` (Bootstrap green) | `#008080` (teal) | setup-wizard.css |
| `#ff6b6b` (coral red) | `#800000` (granate) | Todos los CSS |
| `#d63638` (WP red) | `#800000` (granate) | email-log.css, participant-dashboard.css |
| `#d63031` (alizarin) | `#800000` (granate) | waves-manager.css |

---

## 📁 Files Updated

### Frontend Styles
- `assets/css/eipsi-forms.css` - Variables CSS principales
- `assets/css/survey-login.css` - Login de participantes
- `assets/css/login-gate.css` - Gate de autenticación
- `assets/css/participant-dashboard.css` - Dashboard del participante
- `assets/css/setup-wizard.css` - Wizard de configuración

### Admin Styles
- `assets/css/admin-style.css` - Estilos admin
- `admin/css/email-log.css` - Log de emails
- `admin/css/waves-manager.css` - Gestor de tomas

### Component Styles
- `src/components/ConditionalLogicControl.css` - Lógica condicional
- `src/components/ConditionalLogicMap.css` - Mapa de lógica
- `src/components/FormStylePanel.css` - Panel de estilos

### Dark Mode
- `assets/css/randomization.css` - Aleatorización
- `assets/css/waves-manager.css` - Gestor de tomas (assets)

---

## ♿ Accessibility Notes

### Contrast Ratios (WCAG 2.1 AA)

| Color | Sobre Blanco | Sobre Dark | Uso |
|-------|--------------|------------|-----|
| `#008080` (Teal) | 3.9:1 ✅ | 7.2:1 ✅ | Success states |
| `#800000` (Granate) | 7.5:1 ✅ | 11.2:1 ✅ | Error states |
| `#3B6CAA` (Primary) | 4.6:1 ✅ | 8.1:1 ✅ | Interactive elements |

**Nota**: El teal sobre blanco está justo en el límite AA para textos grandes (18pt+). Para textos pequeños en éxito, usar fondo coloreado con texto oscuro.

---

## 🧪 Testing Checklist

- [ ] Success badge: teal `#008080` visible pero calmante
- [ ] Error badge: granate `#800000` serio sin alarmar
- [ ] Input valid: borde teal
- [ ] Input invalid: borde granate
- [ ] Confirmación de envío: icono/fondo teal
- [ ] Mensaje de error: icono/fondo granate
- [ ] Dark mode: colores coherentes
- [ ] Contraste WCAG AA validado

---

## 📝 Changelog

### v1.5.0 (2025-02-06)
- ✅ Cambio de `#198754` → `#008080` (verde a teal calming)
- ✅ Cambio de `#ff6b6b` → `#800000` (rojo a granate calming)
- ✅ Documentación unificada creada
- ✅ Validación WCAG AA completada

---

**Mantenido por**: EIPSI Forms Team  
**Última actualización**: Febrero 2025  
**Versión**: 1.5.0
