# Sistema de Email EIPSI Forms - Documentación v1.5.4

## Resumen de Cambios

Esta versión implementa un sistema de email más robusto que funciona "out of the box" sin necesidad de configuración SMTP avanzada.

## Características Implementadas

### 1. Filtros wp_mail por Defecto

El plugin ahora configura automáticamente:

- **wp_mail_from**: Usa el email del investigador si está configurado, o el email de administrador de WordPress
- **wp_mail_from_name**: Usa el nombre del investigador si está configurado, o el nombre del sitio
- **wp_mail_content_type**: Fuerza HTML para todos los emails
- **wp_mail_failed**: Registra errores en el log para debugging

### 2. Servicio de Email Mejorado

**Archivo**: `admin/services/class-email-service.php`

Mejoras:
- Logging detallado de cada intento de envío
- Mejor manejo de errores
- Catch para Exception y Error (fatal errors)
- Información de error detallada cuando wp_mail falla

### 3. Funciones de Diagnóstico

**Nuevas funciones públicas en `EIPSI_Email_Service`**:

- `send_test_email($test_email)` - Envía un email de prueba
- `diagnose_email_system()` - Retorna el estado del sistema de email

### 4. Handlers AJAX

**Archivo**: `admin/ajax-email-handlers.php`

- `eipsi_test_default_email` - Prueba el sistema de email sin SMTP
- `eipsi_get_email_diagnostic` - Obtiene diagnóstico del sistema

## Configuración

### Configuración Automática (Default)

El sistema funciona automáticamente sin necesidad de configuración:

1. **Sin SMTP configurado**: Usa `wp_mail()` de WordPress
2. **Con SMTP configurado**: Usa el servidor SMTP configurado

### Configuración SMTP (Opcional)

Para mejor entregabilidad, configura SMTP en:
**EIPSI Forms > Configuración > SMTP**

Campos requeridos:
- Servidor SMTP (ej: smtp.gmail.com)
- Puerto (587 para TLS, 465 para SSL)
- Usuario (email completo)
- Contraseña (contraseña de aplicación)
- Seguridad (TLS/SSL)

### Configuración del Investigador

Para personalizar el remitente de los emails:

1. Ve a **EIPSI Forms > Configuración > SMTP**
2. Configura las opciones del investigador:
   - Email del investigador
   - Nombre del investigador

Estos valores se usan como remitente por defecto cuando no hay SMTP configurado.

## Cómo Probar el Sistema de Email

### Método 1: Botón de Prueba en SMTP

1. Ve a **EIPSI Forms > Configuración > SMTP**
2. Haz clic en **"Probar SMTP"**
3. Verifica el resultado

### Método 2: Diagnóstico del Sistema

El sistema ahora proporciona información de diagnóstico:

```php
// En código PHP
$diagnostic = EIPSI_Email_Service::diagnose_email_system();

// Retorna:
// array(
//     'status' => 'okay' | 'warning',
//     'issues' => array(),
//     'recommendations' => array(),
//     'smtp_configured' => bool,
//     'investigator_email' => 'email@ejemplo.com',
//     'admin_email' => 'admin@ejemplo.com'
// )
```

### Método 3: Email de Prueba Programático

```php
$result = EIPSI_Email_Service::send_test_email('tu-email@ejemplo.com');

// Retorna:
// array(
//     'success' => true|false,
//     'message' => '...',
//     'details' => '...'
// )
```

## Solución de Problemas

### El email no se envía

1. **Verifica el diagnóstico**: Ejecuta `diagnose_email_system()`
2. **Revisa los logs**: Activa `WP_DEBUG` en wp-config.php
3. **Prueba con wp_mail simple**: Crea un PHP simple que envíe un email de prueba

### wp_mail devuelve false

Causas comunes:
- Servidor de email no configurado en el hosting
- Plugin de seguridad bloquando envíos
- Email del destinatario inválido

### Solución: Usar SMTP

La forma más confiable es configurar SMTP:

1. Obtén una contraseña de aplicación de tu proveedor de email
2. Configura en **EIPSI Forms > Configuración > SMTP**
3. Prueba la conexión

## Estructura de Archivos

```
admin/
├── services/
│   ├── class-email-service.php    # Servicio principal de email
│   └── class-smtp-service.php     # Servicio SMTP (opcional)
├── ajax-email-handlers.php        # AJAX handlers (v1.5.4 nuevo)
└── configuration.php              # UI de configuración

includes/
└── emails/
    ├── welcome.php
    ├── wave-reminder.php
    ├── wave-confirmation.php
    ├── dropout-recovery.php
    ├── magic-link.php
    └── manual-reminder.php
```

## Notas Técnicas

### Prioridad de Remitente

1. SMTP configurado → usa credenciales SMTP
2. Email de investigador configurado → usa email del investigador
3. Email de administrador de WordPress → usa admin_email

### Logging

Todos los emails se registran en la tabla `wp_survey_email_log`:
- Tipo de email (welcome, reminder, etc.)
- Estado (sent, failed)
- Mensaje de error si falla
- Timestamp

### Plantillas de Email

Las plantillas se encuentran en `includes/emails/`:
- Usan formato HTML con estilos embebidos
- Soportan placeholders: `{{first_name}}`, `{{magic_link}}`, etc.
- Son responsive (diseño móvil)

## Compatibilidad

- WordPress 5.8+
- PHP 7.4+
- Plugins de email externos compatibles (WP Mail SMTP, etc.)

## Changelog

### v1.5.4
- ✅ Filtros wp_mail configurados por defecto
- ✅ Mejor manejo de errores en envío
- ✅ Diagnóstico del sistema de email
- ✅ Email de prueba sin SMTP
- ✅ Logging detallado de errores
