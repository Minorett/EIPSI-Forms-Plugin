# EIPSI Forms - Double Opt-In Implementation Summary

## Overview
Actualización del sistema de "Fix Critical Magic Link Issues" para trabajar con el sistema Double Opt-In, manteniendo infraestructura esencial, removiendo fixes redundantes, y agregando requisitos específicos de Double Opt-In.

## Changes Implemented

### 1. SMTP Service Enhancement (`admin/services/class-smtp-service.php`)
**Added Methods:**
- `is_configured()` - Verifica que SMTP tenga todos los campos requeridos (host, port, user, password)
- `get_validation_errors()` - Retorna array de errores de configuración faltante/malformada
- `get_status()` - Retorna estado completo con 'configured', 'enabled', 'errors', 'can_send'

**Purpose:** Validación obligatoria de SMTP para Double Opt-In, ya que el sistema requiere envío de emails de confirmación.

### 2. Admin Configuration Warning Banner (`admin/configuration.php`)
**Added Features:**
- Banner de advertencia prominente cuando SMTP no está configurado
- Lista de errores específicos de configuración
- Indicador visual (icono de advertencia) en la pestaña SMTP
- Banner de éxito cuando SMTP está correctamente configurado

**Purpose:** Notificar al administrador que Double Opt-In requiere SMTP configurado.

### 3. Global Admin Notice (`eipsi-forms.php`)
**Added:**
- `eipsi_smtp_configuration_notice()` - Muestra notice en páginas EIPSI si SMTP no está configurado
- Solo visible para usuarios con `manage_options`
- Botón directo a configuración SMTP

**Purpose:** Asegurar que el administrador configure SMTP antes de usar Double Opt-In.

### 4. Cleanup Cron Job (`admin/cron-handlers.php`)
**Added:**
- Hook: `eipsi_cleanup_unconfirmed_participants_daily`
- Function: `eipsi_run_cleanup_unconfirmed_participants()`
  - Llama a `EIPSI_Email_Confirmation_Service::cleanup_expired_confirmations()`
  - Envía notificación al admin si se eliminan >10 participantes
  - Logging detallado del proceso

**Purpose:** Limpieza automática de participantes no confirmados y tokens expirados.

### 5. Resend Confirmation Endpoint (`admin/study-dashboard-api.php`)
**Added AJAX Handlers:**
- `wp_ajax_eipsi_resend_confirmation_handler()`
  - Reenvía email de confirmación a participante específico
  - Valida que el participante pertenezca al estudio
  - Maneja errores con mensajes localizados

- `wp_ajax_eipsi_get_pending_confirmations_handler()`
  - Lista participantes con confirmaciones pendientes
  - Incluye tiempo restante antes de expiración
  - Marca los que expiran en <24 horas

**Purpose:** Admin UI para gestionar confirmaciones pendientes.

### 6. Plugin Activation/Deactivation (`eipsi-forms.php`)
**Added:**
- Schedule: `eipsi_cleanup_unconfirmed_participants_daily` en activación
- Clear: `eipsi_cleanup_unconfirmed_participants_daily` en desactivación

**Purpose:** Asegurar que el cron de limpieza esté programado.

## Infrastructure Kept (Already Implemented)

### Email Log Table (`survey_email_log`)
- Already exists with columns: id, participant_id, survey_id, email_type, recipient_email, subject, content, status, sent_at, error_message, magic_link_used, metadata, created_at
- Used for tracking all emails including confirmation emails

### Email Confirmation Service (`class-email-confirmation-service.php`)
- Already implemented with:
  - `generate_confirmation_token()` - Genera tokens seguros
  - `validate_confirmation_token()` - Valida tokens
  - `mark_confirmed()` - Marca confirmación completada
  - `cleanup_expired_confirmations()` - Limpieza de tokens/participantes
  - `resend_confirmation_email()` - Reenvío de confirmaciones

### Database Schema (`database-schema-manager.php`)
- Table `survey_email_confirmations` already exists with:
  - id, survey_id, participant_id, email, token_hash, token_plain, expires_at, confirmed_at, created_at
  - Indices: idx_participant, idx_email, idx_token_hash, idx_expires_at, idx_confirmed_at

## Redundant Fixes Removed (Not Needed with Double Opt-In)

### MX Validation
- **Removed/Not Implemented:** Validación de registros MX del dominio de email
- **Reason:** Con Double Opt-In, los emails incorrectos simplemente no confirman y se limpian automáticamente después de 48-72 horas. No hay riesgo de seguridad.

### Rate Limiting
- **Not Needed:** Rate limiting por participante
- **Reason:** Double Opt-In envía solo 1 email de confirmación inicial. Los reenvíos son manuales por el admin. No hay riesgo de spam.

## Files Modified

1. `admin/services/class-smtp-service.php` - Validación SMTP obligatoria
2. `admin/configuration.php` - Banners de advertencia SMTP
3. `admin/cron-handlers.php` - Cron de limpieza
4. `admin/study-dashboard-api.php` - Endpoints de resend y pending confirmations
5. `eipsi-forms.php` - Cron scheduling y admin notices

## Configuration Constants (Already Defined)

```php
EIPSI_DOUBLE_OPTIN_ENABLED = true
EIPSI_CONFIRMATION_TOKEN_EXPIRY_HOURS = 48
EIPSI_CONFIRMATION_TOKEN_LENGTH = 64
EIPSI_UNCONFIRMED_PARTICIPANT_RETENTION_HOURS = 72
EIPSI_CONFIRMATION_URL_PARAM = 'eipsi_confirm'
```

## Testing Checklist

- [ ] SMTP configurado → No se muestra advertencia
- [ ] SMTP no configurado → Se muestra banner de advertencia
- [ ] SMTP no configurado → Icono de advertencia en pestaña SMTP
- [ ] SMTP no configurado → Notice global en páginas EIPSI
- [ ] Reenvío de confirmación funciona correctamente
- [ ] Lista de pendientes muestra participantes no confirmados
- [ ] Cron de limpieza se programa al activar plugin
- [ ] Cron de limpieza se ejecuta correctamente
- [ ] Participantes expirados se eliminan automáticamente

## API Endpoints Added

```
POST wp_ajax_eipsi_resend_confirmation
  - study_id (required)
  - participant_id (required)
  - nonce (required)

GET wp_ajax_eipsi_get_pending_confirmations
  - study_id (required)
  - nonce (required)
```

## Backward Compatibility

- Todos los cambios son aditivos (no rompen funcionalidad existente)
- El sistema Double Opt-In ya estaba parcialmente implementado
- Los participantes existentes no se ven afectados
- La validación SMTP es informativa (warning), no bloqueante
