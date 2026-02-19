# EIPSI Forms v1.5.5 - Login System Integration

## Estado: ✅ IMPLEMENTADO

**Fecha:** 2025-02-19

---

## Objetivo

Integrar completamente el sistema de login con las características de estudios longitudinales y magic links para permitir que los participantes se registren, inicien sesión y accedan a sus formularios asignados.

---

## Cambios Realizados

### 1. Handlers AJAX para Participantes (`admin/ajax-participant-handlers.php`)

**Archivo nuevo creado con los siguientes handlers:**

| Handler | Descripción |
|---------|-------------|
| `eipsi_participant_register` | Registra un nuevo participante en un estudio longitudinal |
| `eipsi_participant_login` | Autentica un participante y crea sesión |
| `eipsi_participant_logout` | Destruye la sesión actual |
| `eipsi_participant_info` | Retorna información del participante autenticado |
| `eipsi_request_magic_link` | Envía un magic link al email del participante |
| `eipsi_validate_magic_link_token` | Valida un token de magic link |

**Características:**
- Nonce verification con `eipsi_participant_auth`
- Validación robusta de inputs
- Mensajes de error en español
- Auto-login después del registro
- Integración con `EIPSI_Auth_Service`, `EIPSI_Participant_Service` y `EIPSI_MagicLinksService`

### 2. Integración en Plugin Principal (`eipsi-forms.php`)

- **Versión actualizada:** 1.5.4 → 1.5.5
- **Constante agregada:** `EIPSI_SESSION_COOKIE_NAME = 'eipsi_session_token'`
- **Include agregado:** `admin/ajax-participant-handlers.php`

### 3. Shortcode de Login Mejorado (`includes/shortcodes.php`)

**Función `eipsi_render_survey_login_form()` actualizada para:**
- Encolar CSS: `assets/css/survey-login.css`
- Encolar JS: `assets/js/survey-login.js`
- Encolar JS: `assets/js/participant-auth.js`
- Localizar script con objeto `eipsiAuth` (ajaxUrl, nonce, strings)

### 4. CSS para Formulario de Login (`assets/css/survey-login.css`)

**Archivo nuevo con estilos para:**
- Contenedor del formulario
- Tabs de Login/Registro
- Campos de formulario con validación visual
- Toggle de contraseña
- Botones primarios
- Mensajes de error/éxito
- Soporte para Dark Mode
- Diseño responsive

---

## Flujo de Autenticación

### Registro de Participante
```
1. Usuario completa formulario [eipsi_survey_login]
2. JS envía POST a eipsi_participant_register
3. EIPSI_Participant_Service::create_participant() crea registro
4. EIPSI_Auth_Service::create_session() crea sesión
5. Usuario redirigido a dashboard/formulario
```

### Login de Participante
```
1. Usuario ingresa email y password
2. JS envía POST a eipsi_participant_login
3. EIPSI_Auth_Service::authenticate() valida credenciales
4. EIPSI_Auth_Service::create_session() crea sesión
5. Usuario redirigido a dashboard/formulario
```

### Magic Link
```
1. Usuario solicita magic link (opción "olvidé contraseña")
2. EIPSI_MagicLinksService::generate_magic_link() crea token
3. EIPSI_Email_Service envía email con link
4. Usuario accede a /survey-access/?ml=TOKEN
5. EIPSI_Survey_Access_Handler valida y crea sesión
6. Usuario redirigido a formulario asignado
```

---

## Integración con Estudios Longitudinales

### Asignación de Waves

El sistema de login está integrado con:

- **`wp_survey_participants`**: Tabla de participantes
- **`wp_survey_sessions`**: Sesiones de autenticación
- **`wp_survey_magic_links`**: Tokens de acceso sin password
- **`wp_survey_waves`**: Tomas del estudio longitudinal
- **`wp_survey_assignments`**: Asignaciones participante-wave

### Redirección Post-Login

La función `eipsi_get_participant_redirect_url()` determina la URL de redirección:

1. URL personalizada en parámetro `redirect_url`
2. Página con shortcode `[eipsi_participant_dashboard]`
3. Página asociada al survey_id
4. Home URL como fallback

---

## Helper Functions Disponibles

```php
// Verificar si hay participante logueado
eipsi_is_participant_logged_in(): bool

// Obtener ID del participante actual
eipsi_get_current_participant_id(): ?int

// Obtener survey_id de la sesión actual
eipsi_get_current_survey_id(): ?int
```

---

## Shortcodes Relacionados

| Shortcode | Propósito |
|-----------|-----------|
| `[eipsi_survey_login survey_id="123"]` | Formulario de login/registro |
| `[eipsi_participant_dashboard survey_id="123"]` | Dashboard del participante |
| `[eipsi_form id="123"]` | Formulario (soporta contexto de sesión) |

---

## Seguridad

- ✅ Nonce verification en todos los handlers AJAX
- ✅ Input sanitization con `sanitize_email()`, `sanitize_text_field()`
- ✅ Password mínimo 8 caracteres
- ✅ Sesiones con cookies HTTP-only, Secure, SameSite=Lax
- ✅ Magic links con expiración de 48 horas
- ✅ Magic links de un solo uso (marcados como used)

---

## Criterios de Aceptación

| Criterio | Estado |
|----------|--------|
| Sistema de login funcional | ✅ Implementado |
| Integración con estudios longitudinales | ✅ Implementado |
| Participantes pueden registrarse | ✅ Implementado |
| Participantes pueden loguearse | ✅ Implementado |
| Magic links funcionan correctamente | ✅ Implementado |
| Redirección a formulario correcto | ✅ Implementado |
| Sin errores de consola relacionados | ⏳ Pendiente de testing |

---

## Archivos Modificados/Creados

1. **Creado:** `admin/ajax-participant-handlers.php`
2. **Creado:** `assets/css/survey-login.css`
3. **Modificado:** `eipsi-forms.php` (versión, constante, include)
4. **Modificado:** `includes/shortcodes.php` (enqueue de assets)

---

## Próximos Pasos

1. Testing manual del flujo completo
2. Verificar que las tablas de BD se crean correctamente
3. Probar magic links en entorno real
4. Validar redirecciones post-login
5. Verificar integración con Wave Manager

---

## Notas

- El build de npm tiene problemas con dependencias de @wordpress/vips que deben resolverse
- El código PHP está sintácticamente correcto
- Los handlers AJAX usan los servicios existentes (Auth_Service, Participant_Service, MagicLinksService)
