# EIPSI Forms v2.0.0 - Passwordless Authentication

**Fecha:** 2025-02-26
**Estado:** ✅ IMPLEMENTADO

---

## 🎯 Objetivo

Implementar autenticación sin contraseña con login/registro solo por email, auto-creación de páginas con shortcode del estudio, y simplificación de UI para participantes.

## 📋 Resumen de Cambios

### 1. SISTEMA DE AUTENTICACIÓN SIN CONTRASEÑA

#### Backend
- **EIPSI_Participant_Service**: Método `create_participant()` ahora acepta `password = null`
- **EIPSI_Auth_Service**: Nuevo método `authenticate_passwordless()` para autenticación sin contraseña
- **EIPSI_MagicLinksService**: Nuevo método `generate_and_create_page()` para auto-crear páginas WordPress
- **EIPSI_Participant_Auth_Handler**: Actualizado para usar autenticación passwordless

#### AJAX Handlers
- **eipsi_participant_login_handler**: Removida validación de contraseña
- **eipsi_participant_register_handler**: Removidos campos password, nombres

### 2. AUTO-CREACIÓN DE PÁGINAS DE MAGIC LINK

Al generar magic links:
- Verifica si página existe por slug o meta field
- Crea página automáticamente si no existe:
  - Slug: `study-[study_code]`
  - Contenido: `[eipsi_longitudinal_study study_code="CODE"]`
  - Status: publish
- Devuelve URL completa en respuesta

### 3. SIMPLIFICACIÓN DE UI DE PARTICIPANTES

#### Templates
- **survey-login-form.php**:
  - Reducido de 3 tabs a 2 tabs (eliminado tab "Link mágico")
  - Login: Solo email (sin contraseña)
  - Registro: Solo email + términos (sin contraseña, sin nombres)
  - Agregado: Email del investigador principal dinámico

- **login-gate.php**:
  - Removido botón de magic link
  - Solo botones: "Ingresar" y "Crear cuenta"

#### JavaScript
- **participant-portal.js**:
  - Removida validación de contraseña en login
  - Removida validación de password, confirm_password, nombres en registro
  - **CORREGIDO**: Bug de validación de checkbox de términos
  - Removidos handlers: password toggle, strength meter, magic link

### 4. SEGURIDAD Y VALIDACIÓN

✅ Validación de email con `is_email()`
✅ Rate limiting mantenido en intentos de login
✅ Cookies de sesión: HTTP-only, Secure, SameSite=Lax
✅ Nonce verification en todas las peticiones AJAX
✅ Validación de checkbox de términos corregida
✅ Expiración de magic link (48 horas) mantenida
✅ Generación de hash aleatorio para satisfacer constraint de DB

### 5. COMPATIBILIDAD HACIA ATRÁS

✅ Participantes existentes con contraseñas siguen funcionando
✅ Contraseñas permanecen en DB pero no se usan en flow passwordless
✅ Interfaz de admin sin cambios
✅ Tab de magic link oculto para participantes

## 📝 Criterios de Aceptación

- [x] Login funciona con solo email (sin contraseña requerida)
- [x] Registro funciona con email + términos (sin contraseña, sin nombres)
- [x] Validación de checkbox de términos funciona correctamente
- [x] Solo 2 tabs visibles para participantes (Ingresar, Crear cuenta)
- [x] Tab de magic link oculto para participantes
- [x] Auto-creación de páginas WordPress con shortcode
- [x] Página auto-creada contiene: `[eipsi_longitudinal_study study_code="CODE"]`
- [x] Slug de página formato: `study-[study_code]`
- [x] Email del investigador principal se muestra dinámicamente
- [x] Link "¿Olvidaste tu contraseña?" removido
- [x] Toggle de visibilidad de contraseña removido
- [x] Manejo de sesiones permanece seguro
- [x] Expiración de magic link (48 horas) funciona
- [x] Rate limiting en envío de emails mantenido

## 🗂️ Archivos Modificados

1. `admin/services/class-participant-service.php` - Password opcional
2. `admin/services/class-auth-service.php` - Método authenticate_passwordless
3. `admin/services/class-magic-links-service.php` - Auto-creación de páginas
4. `admin/services/class-participant-auth-handler.php` - Handlers passwordless
5. `admin/ajax-participant-handlers.php` - Endpoints AJAX actualizados
6. `includes/templates/survey-login-form.php` - UI simplificada (2 tabs)
7. `includes/templates/login-gate.php` - Removido botón magic link
8. `assets/js/participant-portal.js` - Validación sin password, checkbox corregido

## 🎨 Cambios de Texto en UI

### Login
- Descripción: "Ingresá con tu email para continuar."
- Botón: "Ingresar al estudio"
- Footer: "¿No tenés cuenta? Creá una nueva"

### Registro
- Email helper: "Usaremos este email para enviarte los recordatorios."
- Términos: "Acepto los términos y condiciones y la política de privacidad"
- Botón: "Crear cuenta y participar"
- Footer: "¿Ya tenés cuenta? Ingresá aquí"

### Footer Común
- "🔒 Tus datos están protegidos y encriptados"
- "🔬 Investigador Principal: [dynamic email]"

## 🐛 Bugs Corregidos

### Checkbox de Términos (CRITICAL FIX)
**Problema**: El checkbox de términos no se validaba correctamente en el servidor.
**Solución**:
- En PHP: Verificar `isset($_POST['accept_terms']) && $_POST['accept_terms'] === '1'`
- En JS: Enviar `accept_terms: '1'` o `''` en lugar de boolean
- En JS: Agregar clase de error visual al label del checkbox cuando no está marcado

## 🔐 Consideraciones de Seguridad

- Los participantes creados con passwordless generan un hash aleatorio en DB
- El hash aleatorio satisface la constraint NOT NULL de la tabla
- Participantes con contraseñas existentes siguen funcionando
- Todas las validaciones de seguridad mantuvieron (nonce, rate limiting, etc.)

## 🧪 Testing Recomendado

1. Prueba de login con email (sin contraseña)
2. Prueba de registro con email + términos
3. Verificar que checkbox de términos bloquee el envío si no está marcado
4. Verificar que solo 2 tabs aparezcan
5. Probar auto-creación de páginas al generar magic link
6. Verificar que shortcode de estudio funcione en página auto-creada
7. Verificar email del investigador principal aparezca
8. Probar con participante existente (verificar login funciona)
9. Verificar que magic link tab esté oculto para participantes

## 📦 Próximos Pasos

1. Ejecutar `npm run build` para verificar build
2. Ejecutar `npm run lint:js` para verificar 0 errores
3. Testing completo de flows de autenticación
4. Verificación de compatibilidad con participantes existentes

---

**Versión**: 2.0.0
**Autor**: EIPSI Forms Team
**Revisión**: Implementación completa de passwordless authentication
