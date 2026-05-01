# 📧 AUDIT COMPLETO: Sistema de Emails y Nudges EIPSI Forms

**Fecha:** 1 Mayo 2026  
**Versión del Plugin:** 2.6.0  
**Auditor:** Cascade AI

---

## 📋 ÍNDICE

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Flujo de Emails por Tipo](#flujo-de-emails-por-tipo)
4. [Sistema de Nudges (0-4)](#sistema-de-nudges-0-4)
5. [Servicios Principales](#servicios-principales)
6. [Triggers y Hooks](#triggers-y-hooks)
7. [Problemas Detectados](#problemas-detectados)
8. [Recomendaciones](#recomendaciones)

---

## 🎯 RESUMEN EJECUTIVO

### Estado General: ✅ FUNCIONAL CON OBSERVACIONES

El sistema de emails de EIPSI Forms es **robusto y bien estructurado**, con las siguientes características:

**Fortalezas:**
- ✅ Sistema de 5 nudges (0-4) bien implementado
- ✅ Event-driven scheduling con `wp_schedule_single_event`
- ✅ Job Queue para procesamiento asíncrono
- ✅ Double opt-in con tokens seguros
- ✅ Magic links con expiración
- ✅ Logging extensivo
- ✅ Transacciones DB para evitar duplicados

**Áreas de Mejora:**
- ⚠️ Complejidad en la lógica de redistribución de nudges
- ⚠️ Múltiples puntos de entrada para envío de emails
- ⚠️ Falta documentación de flujos edge-case
- ⚠️ No hay rate limiting visible en algunos servicios

---

## 🏗️ ARQUITECTURA DEL SISTEMA

### Componentes Principales

```
┌─────────────────────────────────────────────────────────────┐
│                    SISTEMA DE EMAILS                         │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────┐    ┌──────────────────┐               │
│  │  Email Service   │◄───│  SMTP Service    │               │
│  │  (Transaccional) │    │  (Configuración) │               │
│  └────────┬─────────┘    └──────────────────┘               │
│           │                                                   │
│           ├──► Confirmation Service (Double Opt-in)          │
│           ├──► Magic Links Service                           │
│           ├──► Wave Availability Email Service (Nudge 0)     │
│           └──► Failed Email Alerts Service                   │
│                                                               │
├─────────────────────────────────────────────────────────────┤
│                   SISTEMA DE NUDGES                          │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────┐    ┌──────────────────┐               │
│  │  Nudge Service   │◄───│ Event Scheduler  │               │
│  │  (Lógica Core)   │    │ (wp_schedule)    │               │
│  └────────┬─────────┘    └──────────────────┘               │
│           │                                                   │
│           └──► Job Queue (Procesamiento Asíncrono)           │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### Capas de Abstracción

1. **Capa de Presentación:** Templates HTML en `includes/emails/`
2. **Capa de Servicio:** `admin/services/class-email-service.php`
3. **Capa de Programación:** `EIPSI_Nudge_Event_Scheduler`
4. **Capa de Ejecución:** `EIPSI_Nudge_Job_Queue`
5. **Capa de Transporte:** `EIPSI_SMTP_Service` / WordPress `wp_mail()`

---

## 📨 FLUJO DE EMAILS POR TIPO

### 1. Email de Confirmación (Double Opt-in)

**Trigger:** Registro de nuevo participante  
**Servicio:** `EIPSI_Email_Confirmation_Service`  
**Template:** `includes/emails/email-confirmation.php`

#### Flujo:
```
Participante se registra
    ↓
generate_confirmation_token()
    ↓
send_confirmation_email()
    ↓
Participante hace click en link
    ↓
validate_confirmation_token()
    ↓
mark_confirmed()
    ↓
send_welcome_after_confirmation()
    ↓
create_assignments() (Wave Service)
```

#### Características:
- ✅ Token único de 64 caracteres (32 bytes hex)
- ✅ Expiración configurable (default: 48h)
- ✅ Hash seguro con `wp_hash()`
- ✅ Limpieza automática de tokens expirados
- ✅ **FIX v1.1.0:** Removido `&email=` del URL para evitar problemas con `+` en emails

#### Código Clave:
```php
// admin/services/class-email-confirmation-service.php:43-96
public static function generate_confirmation_token($survey_id, $participant_id, $email) {
    $token_plain = bin2hex(random_bytes(32));
    $token_hash  = wp_hash($token_plain);
    $expires_at  = gmdate('Y-m-d H:i:s', 
        current_time('timestamp', true) + (EIPSI_CONFIRMATION_TOKEN_EXPIRY_HOURS * HOUR_IN_SECONDS)
    );
    // ... insert to DB
}
```

---

### 2. Email de Bienvenida (Welcome Email)

**Trigger:** Confirmación de email exitosa  
**Servicio:** `EIPSI_Email_Service::send_welcome_after_confirmation()`  
**Template:** `includes/emails/welcome.php`

#### Flujo:
```
Email confirmado
    ↓
send_welcome_after_confirmation()
    ↓
Genera magic link
    ↓
Envía email con link de acceso
    ↓
create_assignments() para todas las waves
```

#### Características:
- ✅ Incluye magic link válido por 48h
- ✅ Se envía **ANTES** de crear assignments (v2.1.5)
- ✅ Personalizado con nombre del participante
- ✅ Logging detallado

#### Código Clave:
```php
// includes/class-survey-access-handler.php:112-125
// v2.1.5 - Send welcome email FIRST, then create assignments
if (class_exists('EIPSI_Email_Service')) {
    EIPSI_Email_Service::send_welcome_after_confirmation($survey_id, $participant_id);
    error_log(sprintf('[EIPSI-DIAG-EMAIL] Welcome email sent FIRST for participant_id=%d', $participant_id));
}
```

---

### 3. Nudge 0: Email de Disponibilidad de Wave

**Trigger:** Wave se hace disponible  
**Servicio:** `EIPSI_Wave_Availability_Email_Service`  
**Template:** `includes/emails/wave-available.php`

#### Flujo:
```
Wave se hace disponible (available_at <= NOW)
    ↓
do_action('eipsi_wave_available', $assignment_id)
    ↓
EIPSI_Nudge_Event_Scheduler::schedule_nudge_sequence()
    ↓
EIPSI_Nudge_Job_Queue::execute_nudge_0()
    ↓
EIPSI_Wave_Availability_Email_Service::ensure_wave_availability_email_sent()
    ↓
Actualiza reminder_count = 1
    ↓
Programa nudges 1-4 (si follow_up_reminders_enabled)
```

#### Características:
- ✅ **SIEMPRE** se envía (no depende de `follow_up_reminders_enabled`)
- ✅ Ejecución **síncrona** antes de programar nudges 1-4
- ✅ Idempotente: verifica `reminder_count > 0` antes de enviar
- ✅ Verifica que `available_at <= NOW` antes de enviar
- ✅ **v2.1.7:** Si falla, programa reintento exacto en `available_at`

#### Código Clave:
```php
// includes/services/class-nudge-job-queue.php:386-483
public static function execute_nudge_0($payload) {
    // Verificar que aún no se haya enviado (idempotencia)
    if ($assignment->reminder_count > 0) {
        return array('success' => true, 'message' => 'Nudge 0 already sent');
    }
    
    // Verificar que la wave está disponible
    if (!empty($assignment->available_at) && strtotime($assignment->available_at) > current_time('timestamp')) {
        return array('success' => false, 'error' => 'Wave not yet available');
    }
    
    // Enviar email
    $result = EIPSI_Wave_Availability_Email_Service::ensure_wave_availability_email_sent(...);
    
    // Actualizar reminder_count y last_nudge_sent_at
    if ($result['success'] && $result['sent']) {
        $wpdb->update(
            $wpdb->prefix . 'survey_assignments',
            array(
                'reminder_count' => 1,
                'last_nudge_sent_at' => current_time('mysql')
            ),
            array('id' => $assignment_id)
        );
    }
}
```

---

### 4. Nudges 1-4: Emails de Seguimiento

**Trigger:** Programación event-driven basada en `nudge_config`  
**Servicio:** `EIPSI_Email_Service::send_wave_reminder_email()`  
**Templates:** `includes/emails/wave-nudge-{1-4}.php`

#### Flujo:
```
Nudge 0 enviado exitosamente
    ↓
EIPSI_Nudge_Event_Scheduler::schedule_nudge_sequence()
    ↓
Para cada nudge 1-4:
    ├─ Calcular timestamp: available_at + cumulative_delay
    ├─ Verificar que no sea después de due_at (T1-Anchor)
    └─ wp_schedule_single_event(timestamp, 'eipsi_scheduled_nudge_event')
    ↓
En el timestamp programado:
    ↓
execute_scheduled_nudge($args)
    ├─ START TRANSACTION
    ├─ SELECT ... FOR UPDATE (lock)
    ├─ Verificar reminder_count == expected_stage
    ├─ execute_nudge_followup()
    ├─ Enviar email
    ├─ Actualizar reminder_count++
    └─ COMMIT
```

#### Características:
- ✅ **Solo se envían si `follow_up_reminders_enabled = true`**
- ✅ Timing **acumulativo**: cada nudge suma su delay al anterior
- ✅ **Transacciones DB** con `SELECT FOR UPDATE` para evitar duplicados
- ✅ **Catch-up logic:** si un nudge se retrasa, reprograma el siguiente desde "now"
- ✅ **Intervalo mínimo:** verifica `last_nudge_sent_at` para evitar envíos consecutivos
- ✅ **Phase 5 T1-Anchor:** No programa nudges después de `due_at`

#### Configuración de Nudges:

```javascript
// Ejemplo de nudge_config en DB
{
  "nudge_1": {
    "enabled": true,
    "value": 10.47,
    "unit": "hours"
  },
  "nudge_2": {
    "enabled": true,
    "value": 28.07,
    "unit": "hours"
  },
  "nudge_3": {
    "enabled": true,
    "value": 49.42,
    "unit": "hours"
  },
  "nudge_4": {
    "enabled": true,
    "value": 63.25,
    "unit": "hours"
  },
  "manual_deadline": true,  // Si hay deadline manual
  "original_nudges": {...}, // Backup para restaurar
  "original_window_minutes": "10080",
  "redistributed": true
}
```

#### Código Clave - Scheduling:
```php
// includes/services/class-nudge-event-scheduler.php:160-258
// v2.1.1 - Los nudges son acumulativos
$cumulative_delay = 0;

for ($stage = 1; $stage <= 4; $stage++) {
    $nudge_key = "nudge_{$stage}";
    
    if (!isset($nudge_config[$nudge_key]) || empty($nudge_config[$nudge_key]['enabled'])) {
        continue;
    }
    
    $config = $nudge_config[$nudge_key];
    $value = isset($config['value']) ? floatval($config['value']) : ($stage * 24);
    $unit = isset($config['unit']) ? $config['unit'] : 'hours';
    
    // Acumular el delay del nudge anterior
    $delay_seconds = self::convert_to_seconds($value, $unit);
    $cumulative_delay += $delay_seconds;
    $scheduled_time = $available_at + $cumulative_delay;
    
    // Phase 5 T1-Anchor: No programar nudges después del deadline
    if ($due_at_timestamp !== null && $scheduled_time >= $due_at_timestamp) {
        error_log(sprintf(
            '[EIPSI EventScheduler] BLOCKED nudge %d - would occur AFTER due_at',
            $stage
        ));
        continue;
    }
    
    // Programar evento
    wp_schedule_single_event($scheduled_time, self::NUDGE_EVENT_HOOK, array($event_args));
}
```

#### Código Clave - Ejecución con Lock:
```php
// includes/services/class-nudge-event-scheduler.php:351-535
try {
    // Iniciar transacción
    $wpdb->query('START TRANSACTION');
    
    // Bloquear la fila del assignment
    $locked_assignment = $wpdb->get_row($wpdb->prepare(
        "SELECT reminder_count, status, participant_id, wave_id, study_id 
         FROM {$wpdb->prefix}survey_assignments 
         WHERE id = %d 
         FOR UPDATE",
        $assignment_id
    ));
    
    // Verificar stage correcto con el count bloqueado
    if (intval($locked_assignment->reminder_count) != $expected_reminder_count) {
        $wpdb->query('ROLLBACK');
        return;
    }
    
    // Ejecutar nudge
    $result = EIPSI_Nudge_Job_Queue::execute_nudge_followup($payload, $stage);
    
    if ($result['success']) {
        $wpdb->query('COMMIT');
    } else {
        $wpdb->query('ROLLBACK');
        // Re-encolar para reintento
        EIPSI_Nudge_Job_Queue::enqueue("send_nudge_{$stage}", $payload, 10);
    }
} catch (Exception $e) {
    if ($transaction_started) {
        $wpdb->query('ROLLBACK');
    }
}
```

---

### 5. Email de Confirmación de Toma Completada

**Trigger:** Participante completa una wave  
**Servicio:** `EIPSI_Email_Service::send_wave_confirmation_email()`  
**Template:** `includes/emails/wave-confirmation.php`

#### Flujo:
```
Participante completa formulario
    ↓
Assignment status = 'submitted'
    ↓
send_wave_confirmation_email()
    ↓
Cancela nudges programados pendientes
    ↓
Si hay next_wave, menciona en el email
```

#### Características:
- ✅ Confirma recepción de respuestas
- ✅ Informa sobre próxima wave (si existe)
- ✅ Cancela eventos programados de nudges

---

## 🔔 SISTEMA DE NUDGES (0-4)

### Tabla de Nudges

| Nudge | Nombre | Timing Default | Obligatorio | Descripción |
|-------|--------|----------------|-------------|-------------|
| **0** | Disponibilidad | Inmediato | ✅ SÍ | "Tu Toma X está lista" |
| **1** | Seguimiento | +24h | ❌ NO | "¿Ya completaste?" |
| **2** | Recordatorio | +72h | ❌ NO | "Te esperamos" |
| **3** | Ayuda | +168h (7d) | ❌ NO | "¿Necesitás ayuda?" |
| **4** | Último Llamado | +336h (14d) | ❌ NO | "Última oportunidad" |

### Estados de reminder_count

```
reminder_count = 0  →  Ningún nudge enviado
reminder_count = 1  →  Nudge 0 enviado
reminder_count = 2  →  Nudge 1 enviado
reminder_count = 3  →  Nudge 2 enviado
reminder_count = 4  →  Nudge 3 enviado
reminder_count = 5  →  Nudge 4 enviado
```

### Lógica de Redistribución (Phase 5 T1-Anchor)

Cuando se establece un deadline manual en una wave:

```php
// admin/study-dashboard-api.php:610-660
function eipsi_redistribute_nudges($original_nudges, $original_window_minutes, $new_window_minutes) {
    $redistributed = array();
    
    foreach ($original_nudges as $key => $nudge) {
        if (!$nudge || !isset($nudge['value'])) {
            $redistributed[$key] = $nudge;
            continue;
        }
        
        // Convertir a minutos
        $original_minutes = ($nudge['unit'] === 'days') 
            ? $nudge['value'] * 1440 
            : $nudge['value'] * 60;
        
        // Calcular porcentaje
        $percentage = $original_window_minutes > 0 
            ? $original_minutes / $original_window_minutes 
            : 0;
        
        // Aplicar al nuevo window
        $new_minutes = $new_window_minutes * $percentage;
        
        // Convertir de vuelta a horas
        $new_hours = round($new_minutes / 60, 2);
        
        $redistributed[$key] = array(
            'enabled' => $nudge['enabled'],
            'value' => $new_hours,
            'unit' => 'hours'
        );
    }
    
    return $redistributed;
}
```

**Ejemplo:**
- Window original: 7 días (10080 min)
- Nudge 1 original: 24h (1440 min) = 14.29% del window
- Nuevo window: 10 días (14400 min)
- Nudge 1 redistribuido: 14.29% × 14400 = 2057 min = **34.28 horas**

---

## 🔧 SERVICIOS PRINCIPALES

### 1. EIPSI_Email_Service

**Ubicación:** `admin/services/class-email-service.php`  
**Responsabilidad:** Envío de emails transaccionales

#### Métodos Públicos:

```php
// Generación de magic links
public static function generate_magic_link_url($survey_id, $participant_id)

// Emails de bienvenida
public static function send_welcome_email($survey_id, $participant_id)
public static function send_welcome_after_confirmation($survey_id, $participant_id)

// Email de confirmación
public static function send_confirmation_email($survey_id, $participant_id, $confirmation_token)

// Emails de waves
public static function send_wave_reminder_email($survey_id, $participant_id, $wave, $nudge_stage = 0)
public static function send_wave_confirmation_email($survey_id, $participant_id, $wave, $next_wave = null)

// Utilidades
private static function get_study_code($survey_id)
private static function get_participant($participant_id)
private static function get_pool_page_url_fallback($survey_id)
```

#### Características:
- ✅ Templates HTML responsivos
- ✅ Fallback a Pool page si no hay study page
- ✅ Pre-fill de email en magic links
- ✅ Logging detallado
- ✅ Manejo de errores robusto

---

### 2. EIPSI_Email_Confirmation_Service

**Ubicación:** `admin/services/class-email-confirmation-service.php`  
**Responsabilidad:** Double opt-in

#### Métodos Públicos:

```php
// Generación de tokens
public static function generate_confirmation_token($survey_id, $participant_id, $email)

// Validación
public static function validate_confirmation_token($token, $email = '')

// Confirmación
public static function mark_confirmed($token, $email = '')

// URLs
public static function generate_confirmation_url($token, $email = '')

// Estado
public static function is_confirmed($participant_id)
public static function get_pending_confirmation($participant_id)

// Reenvío
public static function resend_confirmation_email($participant_id)

// Limpieza
public static function cleanup_expired_confirmations()

// Config
public static function is_enabled()
```

#### Seguridad:
- ✅ Tokens de 64 caracteres (32 bytes hex)
- ✅ Hash con `wp_hash()` (HMAC-SHA256)
- ✅ Expiración configurable
- ✅ Limpieza automática de tokens expirados
- ✅ **FIX v1.1.0:** Lookup solo por `token_hash` (no por email)

---

### 3. EIPSI_Nudge_Service

**Ubicación:** `includes/services/class-nudge-service.php`  
**Responsabilidad:** Lógica core de nudges

#### Constantes:

```php
const NUDGE_AVAILABLE = 0;   // "Tu Toma X está lista"
const NUDGE_FOLLOW_UP = 1;   // "¿Ya completaste?"
const NUDGE_REMINDER = 2;    // "Te esperamos"
const NUDGE_URGENCY = 3;     // "¿Necesitás ayuda?"
const NUDGE_LAST_CALL = 4;   // "Última oportunidad"
```

#### Métodos Públicos:

```php
// Configuración
public static function get_nudge_config($stage, $has_due_date = false)
public static function get_all_nudge_configs($has_due_date = false)
public static function get_timeline_preview($has_due_date = false)

// Lógica de envío
public static function should_send_nudge($assignment, $wave, $current_stage, $custom_config = null)

// Utilidades
public static function convert_to_seconds($value, $unit = 'days')
public static function get_next_stage($current_stage)
public static function get_stage_description($stage, $has_due_date = false)
```

#### Lógica de `should_send_nudge`:

```php
// includes/services/class-nudge-service.php:203-319
public static function should_send_nudge($assignment, $wave, $current_stage, $custom_config = null) {
    // Stage 0 (NUDGE_AVAILABLE) is always sent immediately
    if ((int)$current_stage === self::NUDGE_AVAILABLE) {
        return true;
    }
    
    // For stages 1-4, check if follow_up_reminders_enabled
    if (empty($wave->follow_up_reminders_enabled)) {
        return false;
    }
    
    // Get config and calculate trigger timestamp
    $timing_seconds = self::convert_to_seconds($timing_value, $timing_unit);
    $available_ts = strtotime($assignment->available_at);
    $trigger_ts = $available_ts + $timing_seconds;
    
    $should_send = ($now >= $trigger_ts);
    
    // v2.5.1 - Verificar intervalo mínimo desde el último nudge enviado
    if ($should_send && $current_stage > 0 && !empty($assignment->last_nudge_sent_at)) {
        $segundos_desde_ultimo = $now - strtotime($assignment->last_nudge_sent_at);
        
        if ($segundos_desde_ultimo < $intervalo_minimo_segundos) {
            $should_send = false;
        }
    }
    
    return $should_send;
}
```

---

### 4. EIPSI_Nudge_Event_Scheduler

**Ubicación:** `includes/services/class-nudge-event-scheduler.php`  
**Responsabilidad:** Event-driven scheduling

#### Hook Principal:

```php
const NUDGE_EVENT_HOOK = 'eipsi_scheduled_nudge_event';
```

#### Métodos Públicos:

```php
// Inicialización
public static function init()

// Programación
public static function schedule_nudge_sequence($assignment_id)
public static function execute_scheduled_nudge($args)

// Cancelación
public static function cancel_scheduled_nudges($assignment_id)

// Phase 5 T1-Anchor
public static function reschedule_nudges_for_deadline($assignment_id)
public static function reschedule_all_nudges_for_participant($study_id, $participant_id)

// Debugging
public static function get_scheduled_events()
```

#### Hooks Registrados:

```php
// includes/services/class-nudge-event-scheduler.php:29-43
add_action(self::NUDGE_EVENT_HOOK, array(__CLASS__, 'execute_scheduled_nudge'), 10, 1);
add_action('eipsi_wave_available', array(__CLASS__, 'schedule_nudge_sequence'), 10, 1);
add_action('eipsi_wave_available_retry', array(__CLASS__, 'schedule_nudge_sequence'), 10, 1);
add_action('eipsi_assignment_deadline_changed', array(__CLASS__, 'reschedule_nudges_for_deadline'), 10, 1);
add_action('eipsi_t1_anchored', array(__CLASS__, 'reschedule_all_nudges_for_participant'), 10, 2);
```

---

### 5. EIPSI_Nudge_Job_Queue

**Ubicación:** `includes/services/class-nudge-job-queue.php`  
**Responsabilidad:** Procesamiento asíncrono de nudges

#### Métodos Públicos:

```php
// Enqueue
public static function enqueue($job_type, $payload, $priority = 5, $scheduled_for = null)

// Procesamiento
public static function process_queue($batch_size = 10)

// Ejecución directa
public static function execute_nudge_0($payload)
public static function execute_nudge_followup($payload, $stage)

// Limpieza
public static function cleanup_old_jobs($days = 30)
```

#### Tabla de Jobs:

```sql
CREATE TABLE wp_survey_nudge_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_type VARCHAR(50) NOT NULL,
    payload LONGTEXT NOT NULL,
    priority INT DEFAULT 5,
    status VARCHAR(20) DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    scheduled_for DATETIME NULL,
    created_at DATETIME NOT NULL,
    processed_at DATETIME NULL,
    error_message TEXT NULL,
    INDEX idx_status_priority (status, priority),
    INDEX idx_scheduled (scheduled_for)
);
```

#### Job Types:

- `send_nudge_0` - Nudge de disponibilidad
- `send_nudge_1` - Primer seguimiento
- `send_nudge_2` - Segundo seguimiento
- `send_nudge_3` - Ayuda
- `send_nudge_4` - Último llamado

---

### 6. EIPSI_Wave_Availability_Email_Service

**Ubicación:** `admin/services/class-wave-availability-email-service.php`  
**Responsabilidad:** Nudge 0 específicamente

#### Método Principal:

```php
public static function ensure_wave_availability_email_sent($assignment, $wave, $participant, $study_id)
```

#### Características:
- ✅ Idempotente: verifica si ya se envió
- ✅ Logging detallado
- ✅ Template específico para disponibilidad
- ✅ Incluye magic link

---

## 🎣 TRIGGERS Y HOOKS

### Hooks de WordPress

```php
// Eventos programados
add_action('eipsi_scheduled_nudge_event', 'EIPSI_Nudge_Event_Scheduler::execute_scheduled_nudge');

// Wave disponible
add_action('eipsi_wave_available', 'EIPSI_Nudge_Event_Scheduler::schedule_nudge_sequence');
add_action('eipsi_wave_available_retry', 'EIPSI_Nudge_Event_Scheduler::schedule_nudge_sequence');

// Phase 5 T1-Anchor
add_action('eipsi_assignment_deadline_changed', 'EIPSI_Nudge_Event_Scheduler::reschedule_nudges_for_deadline');
add_action('eipsi_t1_anchored', 'EIPSI_Nudge_Event_Scheduler::reschedule_all_nudges_for_participant');
```

### Triggers Personalizados

```php
// Cuando una wave se hace disponible
do_action('eipsi_wave_available', $assignment_id);

// Cuando cambia el deadline de un assignment
do_action('eipsi_assignment_deadline_changed', $assignment_id, $new_due_at);

// Cuando se ancla T1 (Phase 5)
do_action('eipsi_t1_anchored', $study_id, $participant_id);
```

### Cron Jobs

```php
// Procesamiento de Job Queue
wp_schedule_event(time(), 'every_minute', 'eipsi_process_nudge_queue');

// Limpieza de confirmaciones expiradas
wp_schedule_event(time(), 'daily', 'eipsi_cleanup_expired_confirmations');

// Limpieza de jobs antiguos
wp_schedule_event(time(), 'daily', 'eipsi_cleanup_old_nudge_jobs');
```

---

## ⚠️ PROBLEMAS DETECTADOS

### 1. 🔴 CRÍTICO: Múltiples Puntos de Entrada para Nudge 0

**Ubicación:** `includes/services/Wave_Service.php:281-358`

**Problema:**
Existe código legacy que envía Nudge 0 directamente desde `Wave_Service`, **además** del sistema event-driven. Esto puede causar duplicados.

```php
// Wave_Service.php:322-358
// Wave is NOW available - send email immediately (Nudge 0)
$result = EIPSI_Email_Service::send_wave_reminder_email(
    $study_id,
    $participant_id,
    $next_wave
);

if ($result) {
    // Increment reminder_count to prevent cron from sending duplicate
    $wpdb->update(
        $wpdb->prefix . 'survey_assignments',
        array('reminder_count' => 1),
        ...
    );
}
```

**Impacto:** Riesgo de emails duplicados si ambos sistemas se ejecutan.

**Recomendación:**
- Eliminar el envío directo de `Wave_Service`
- Usar **solo** el sistema event-driven con `do_action('eipsi_wave_available')`
- Mantener la verificación de `reminder_count` como safeguard

---

### 2. 🟡 MEDIO: Falta Validación de Overlapping Deadlines

**Ubicación:** `admin/study-dashboard-api.php`

**Problema:**
No hay validación que prevenga deadlines inconsistentes que causen overlapping de waves.

**Ejemplo:**
```
T1 deadline: 10/05/2026
T2 deadline: 05/05/2026  ← ANTES que T1!
```

**Recomendación:**
Agregar validación en `wp_ajax_eipsi_extend_wave_deadline_handler`:

```php
// Validar que el deadline no sea antes del deadline de la wave anterior
$previous_wave = $wpdb->get_row($wpdb->prepare(
    "SELECT due_date FROM {$wpdb->prefix}survey_waves 
     WHERE study_id = %d AND offset_minutes < %d 
     ORDER BY offset_minutes DESC LIMIT 1",
    $wave->study_id,
    $wave->offset_minutes
));

if ($previous_wave && !empty($previous_wave->due_date)) {
    $previous_deadline = strtotime($previous_wave->due_date);
    if ($deadline_timestamp < $previous_deadline) {
        wp_send_json_error('Deadline cannot be before previous wave deadline');
    }
}
```

---

### 3. 🟡 MEDIO: Redistribución Automática vs Manual Confusa

**Ubicación:** Dashboard UI + `study-dashboard-api.php`

**Problema:**
Cuando un investigador guarda nudges manualmente después de una redistribución automática, **sobrescribe** los valores redistribuidos sin advertencia.

**Flujo Actual:**
```
1. Investigador pone deadline en T1
2. Sistema redistribuye nudges automáticamente
3. Investigador abre panel de nudges y hace "Guardar"
4. ❌ Sobrescribe con valores originales (perdiendo redistribución)
```

**Recomendación:**
Implementar el botón 🔁 "Redistribuir" que ya agregamos, y:
- Mostrar indicador visual cuando hay redistribución activa
- Advertir al usuario si intenta guardar sobre una redistribución
- Opción: "Guardar y mantener redistribución" vs "Guardar valores fijos"

---

### 4. 🟢 MENOR: Falta Rate Limiting en Algunos Servicios

**Ubicación:** `admin/services/class-email-service.php`

**Problema:**
No hay rate limiting visible en `send_wave_reminder_email()` ni en otros métodos de envío.

**Riesgo:**
- Posible abuso si un investigador envía recordatorios manuales masivos
- Riesgo de ser marcado como spam por proveedores de email

**Recomendación:**
Implementar rate limiting con transients:

```php
public static function send_wave_reminder_email($survey_id, $participant_id, $wave, $nudge_stage = 0) {
    // Rate limiting: max 10 emails por participante por hora
    $rate_key = "eipsi_email_rate_{$participant_id}";
    $sent_count = get_transient($rate_key) ?: 0;
    
    if ($sent_count >= 10) {
        error_log("[EIPSI Email] Rate limit exceeded for participant {$participant_id}");
        return false;
    }
    
    // ... enviar email
    
    set_transient($rate_key, $sent_count + 1, HOUR_IN_SECONDS);
}
```

---

### 5. 🟢 MENOR: Logging Inconsistente

**Problema:**
Algunos servicios usan `error_log()` extensivamente, otros no.

**Ejemplos:**
- ✅ `EIPSI_Nudge_Event_Scheduler`: Logging excelente
- ✅ `EIPSI_Nudge_Job_Queue`: Logging detallado
- ⚠️ `EIPSI_Email_Service`: Logging mínimo
- ⚠️ `EIPSI_Wave_Availability_Email_Service`: Logging básico

**Recomendación:**
Estandarizar logging con prefijos consistentes:
```php
error_log('[EIPSI Email] Message');
error_log('[EIPSI Nudge] Message');
error_log('[EIPSI Confirmation] Message');
```

---

### 6. 🟢 MENOR: No Hay Cleanup de Eventos Programados Huérfanos

**Problema:**
Si un participante se elimina o un estudio se cierra, los eventos programados de nudges pueden quedar huérfanos en la tabla `wp_cron`.

**Recomendación:**
Agregar cleanup en:
- Eliminación de participante
- Cierre de estudio
- Cancelación de assignment

```php
// Al eliminar participante
public static function cleanup_participant_events($participant_id) {
    global $wpdb;
    
    $assignments = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}survey_assignments WHERE participant_id = %d",
        $participant_id
    ));
    
    foreach ($assignments as $assignment_id) {
        EIPSI_Nudge_Event_Scheduler::cancel_scheduled_nudges($assignment_id);
    }
}
```

---

## 💡 RECOMENDACIONES

### Prioridad ALTA

1. **✅ Eliminar código legacy de envío directo de Nudge 0**
   - Mantener solo el sistema event-driven
   - Simplifica el flujo y evita duplicados

2. **✅ Implementar validación de deadlines**
   - Prevenir overlapping de waves
   - Mejorar UX con mensajes claros

3. **✅ Mejorar UI de redistribución de nudges**
   - Indicador visual de redistribución activa
   - Advertencia antes de sobrescribir
   - Botón 🔁 "Redistribuir" ya implementado

### Prioridad MEDIA

4. **Agregar rate limiting**
   - Proteger contra abuso
   - Prevenir problemas con proveedores de email

5. **Estandarizar logging**
   - Prefijos consistentes
   - Niveles de log (DEBUG, INFO, ERROR)
   - Opción de desactivar logs verbose en producción

6. **Documentar flujos edge-case**
   - ¿Qué pasa si un participante completa T2 antes que T1?
   - ¿Qué pasa si se cambia `follow_up_reminders_enabled` después de programar nudges?
   - ¿Qué pasa si se elimina una wave con nudges programados?

### Prioridad BAJA

7. **Cleanup de eventos huérfanos**
   - Agregar hooks en eliminación de participantes/estudios
   - Cron job de limpieza semanal

8. **Dashboard de monitoreo de emails**
   - Vista de emails enviados/fallidos por estudio
   - Gráficos de engagement (open rate, click rate)
   - Alertas de emails bounced

9. **Tests automatizados**
   - Unit tests para `EIPSI_Nudge_Service::should_send_nudge()`
   - Integration tests para flujo completo de nudges
   - Tests de redistribución proporcional

---

## 📊 MÉTRICAS Y MONITOREO

### Tablas de Base de Datos

```sql
-- Confirmaciones de email
wp_survey_email_confirmations
  - token_hash (unique)
  - participant_id
  - expires_at
  - confirmed_at

-- Jobs de nudges
wp_survey_nudge_jobs
  - job_type
  - status (pending, processing, completed, failed)
  - attempts
  - scheduled_for

-- Assignments (tracking de nudges)
wp_survey_assignments
  - reminder_count (0-5)
  - last_nudge_sent_at
  - available_at
  - due_at
```

### Queries Útiles para Monitoreo

```sql
-- Nudges pendientes de envío
SELECT COUNT(*) 
FROM wp_survey_assignments 
WHERE status = 'pending' 
  AND reminder_count < 5 
  AND available_at <= NOW();

-- Jobs fallidos en las últimas 24h
SELECT * 
FROM wp_survey_nudge_jobs 
WHERE status = 'failed' 
  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Confirmaciones pendientes expiradas
SELECT COUNT(*) 
FROM wp_survey_email_confirmations 
WHERE confirmed_at IS NULL 
  AND expires_at < NOW();

-- Eventos programados de nudges
SELECT * 
FROM wp_options 
WHERE option_name = '_transient_doing_cron' 
   OR option_name LIKE '%eipsi_scheduled_nudge%';
```

---

## 🔍 CONCLUSIÓN

El sistema de emails y nudges de EIPSI Forms es **robusto y bien diseñado**, con una arquitectura event-driven moderna y mecanismos de seguridad adecuados.

**Puntos Fuertes:**
- ✅ Separación clara de responsabilidades
- ✅ Event-driven scheduling escalable
- ✅ Transacciones DB para evitar race conditions
- ✅ Logging extensivo para debugging
- ✅ Double opt-in seguro
- ✅ Magic links con expiración

**Áreas de Mejora:**
- Eliminar código legacy duplicado
- Agregar validaciones de negocio (deadlines)
- Mejorar UX de redistribución de nudges
- Implementar rate limiting
- Estandarizar logging

**Recomendación Final:**
El sistema está **listo para producción** con las mejoras de prioridad ALTA implementadas. Las mejoras de prioridad MEDIA y BAJA pueden implementarse iterativamente según necesidades del negocio.

---

**Auditor:** Cascade AI  
**Fecha:** 1 Mayo 2026  
**Versión:** 1.0
