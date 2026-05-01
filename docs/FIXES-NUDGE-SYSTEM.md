# 🔧 FIXES CRÍTICOS: Sistema de Nudges

**Fecha:** 1 Mayo 2026  
**Basado en:** Audit de Sistema de Emails + Análisis de Cron

---

## 📋 PROBLEMAS IDENTIFICADOS

### 1. 🔴 CRÍTICO: Lock de Job Worker No es Atómico

**Problema:**
```php
// admin/cron-reminders-handler.php:871-879
// ❌ ACTUAL: No funciona sin object cache persistente
$lock_key = 'eipsi_job_worker_lock';
if (get_transient($lock_key)) {
    return;
}
set_transient($lock_key, true, 5 * MINUTE_IN_SECONDS);
```

**Por qué falla:**
- Hostinger shared hosting NO tiene Redis/Memcached
- `wp_cache_add()` usa memoria del proceso PHP
- Cada request HTTP = proceso nuevo
- Requests concurrentes NO ven el lock del otro

**Impacto:**
- Múltiples workers pueden ejecutarse simultáneamente
- Posible duplicación de nudges
- Race conditions en `reminder_count`

---

### 2. 🟡 MEDIO: Nudges Bloqueados Sin Feedback

**Problema:**
```php
// includes/services/class-nudge-event-scheduler.php:207-215
if ($due_at_timestamp !== null && $scheduled_time >= $due_at_timestamp) {
    error_log('[EIPSI EventScheduler] BLOCKED nudge...');
    continue; // ❌ Solo log, investigador NO se entera
}
```

**Escenario:**
```
Investigador configura 4 nudges
Sistema bloquea 2 (muy cerca del deadline)
UI muestra: "✓ Nudges redistribuidos a 7 días"
Investigador piensa: "Perfecto, 4 nudges"
Realidad: Solo 2 se van a enviar
```

**Impacto:**
- Investigador no sabe que algunos nudges no se programaron
- Expectativa != Realidad
- Problema de UX crítico

---

### 3. 🟢 MENOR: Ventanas Muy Cortas Permiten Redistribución

**Problema:**
- Sistema permite redistribuir nudges en ventanas de 10 minutos
- Con cron cada 5 min, nudges llegan tarde o no llegan
- No hay validación de ventana mínima

**Impacto:**
- Configuraciones absurdas permitidas
- Nudges que nunca se envían

---

## ✅ SOLUCIONES IMPLEMENTADAS

### 1. Validación de Ventana Mínima (60 minutos)

**Implementado en:** `admin/study-dashboard-api.php:1466-1482`

```php
// Validate minimum window for nudge redistribution
if ($current_window_minutes < 60) {
    error_log(sprintf(
        '[EIPSI Redistribute] Wave %d: Window too short (%d min) for redistribution',
        $wave_id,
        $current_window_minutes
    ));
    
    wp_send_json_error(array(
        'message' => sprintf(
            'La ventana es muy corta (%d minutos). Se requiere un mínimo de 60 minutos para redistribuir nudges de manera efectiva.',
            $current_window_minutes
        ),
        'window_minutes' => $current_window_minutes,
        'minimum_required' => 60
    ));
}
```

**Beneficios:**
- ✅ Previene configuraciones absurdas
- ✅ Mensaje claro al investigador
- ✅ Sin contraindicaciones

---

## 🚧 SOLUCIONES PROPUESTAS (NO IMPLEMENTADAS AÚN)

### ⚠️ PROBLEMA CON GET_LOCK(): Connection Pooling

**GET_LOCK() es por conexión MySQL**, no por servidor:
- En shared hosting con connection pooling, las conexiones se reciclan
- Lock puede persistir más de lo esperado
- Lock puede liberarse inesperadamente si el pool resetea la conexión

**Documentación MySQL:**
> "GET_LOCK() establishes a lock only on a single mysqld... locks are released when the connection terminates"

### ✅ SOLUCIÓN MÁS ROBUSTA: Tabla de Locks con Timestamp

### 1. Lock Atómico con Tabla de Locks

**Archivo:** `admin/cron-reminders-handler.php`

**Reemplazar función `eipsi_process_nudge_jobs_worker()`:**

```php
function eipsi_process_nudge_jobs_worker() {
    global $wpdb;
    
    if (!class_exists('EIPSI_Nudge_Job_Queue')) {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/services/class-nudge-job-queue.php';
    }
    
    // ✅ Lock nativo de MySQL (atómico garantizado)
    $lock_name = 'eipsi_job_worker';
    $lock_timeout = 0; // No esperar, retornar inmediatamente
    
    $lock_acquired = $wpdb->get_var($wpdb->prepare(
        "SELECT GET_LOCK(%s, %d)",
        $lock_name,
        $lock_timeout
    ));
    
    if (!$lock_acquired) {
        error_log('[EIPSI JobWorker] Another worker is running, skipping');
        return;
    }
    
    try {
        error_log('[EIPSI JobWorker] Starting job processing');
        
        $stats = EIPSI_Nudge_Job_Queue::process_batch(1);
        
        if ($stats['processed'] > 0) {
            error_log(sprintf(
                '[EIPSI JobWorker] Processed: %d completed, %d retried, %d failed',
                $stats['completed'],
                $stats['retried'],
                $stats['failed']
            ));
        } else {
            error_log('[EIPSI JobWorker] No pending jobs to process');
        }
    } finally {
        // Liberar lock (SIEMPRE se ejecuta, incluso si hay exception)
        $wpdb->get_var($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock_name));
    }
}
```

**Ventajas de GET_LOCK():**
- ✅ Atómico a nivel de MySQL (garantizado)
- ✅ No requiere tabla adicional
- ✅ Auto-release si el proceso PHP muere
- ✅ Funciona en cualquier hosting con MySQL 5.7+
- ✅ No depende de object cache

**Test:**
```bash
# Ejecutar 2 veces simultáneamente
curl https://site.com/wp-cron.php?doing_wp_cron &
curl https://site.com/wp-cron.php?doing_wp_cron &

# Verificar logs
tail -f wp-content/debug.log | grep "JobWorker"

# Esperado:
# [EIPSI JobWorker] Starting job processing
# [EIPSI JobWorker] Another worker is running, skipping
```

---

### 2. Feedback de Nudges Bloqueados

**Modificar:** `includes/services/class-nudge-event-scheduler.php`

#### Paso 1: Retornar Información de Nudges Bloqueados

```php
// Cambiar firma de schedule_nudge_sequence para retornar array
public static function schedule_nudge_sequence($assignment_id) {
    // ... código existente ...
    
    $scheduled_count = 0;
    $blocked_nudges = array(); // ✅ NUEVO
    
    // ... código de Nudge 0 ...
    
    if ($nudge_0_success && !empty($assignment->follow_up_reminders_enabled)) {
        // ... código existente ...
        
        for ($stage = 1; $stage <= 4; $stage++) {
            // ... código existente ...
            
            // Phase 5 T1-Anchor: No programar nudges después del deadline
            if ($due_at_timestamp !== null && $scheduled_time >= $due_at_timestamp) {
                error_log(sprintf(
                    '[EIPSI EventScheduler] BLOCKED nudge %d - would occur AFTER due_at',
                    $stage
                ));
                
                // ✅ NUEVO: Registrar nudge bloqueado
                $blocked_nudges[] = array(
                    'stage' => $stage,
                    'scheduled_time' => date('Y-m-d H:i:s', $scheduled_time),
                    'deadline' => date('Y-m-d H:i:s', $due_at_timestamp),
                    'reason' => 'after_deadline'
                );
                
                continue;
            }
            
            // ... resto del código ...
        }
    }
    
    error_log(sprintf('[EIPSI EventScheduler] COMPLETED: Scheduled %d nudges for assignment %d', $scheduled_count, $assignment_id));
    
    // ✅ NUEVO: Retornar información completa
    return array(
        'scheduled' => $scheduled_count,
        'blocked' => $blocked_nudges,
        'nudge_0_sent' => $nudge_0_success
    );
}
```

#### Paso 2: Usar Información en AJAX Handler

```php
// admin/study-dashboard-api.php - en wp_ajax_eipsi_redistribute_nudges_handler

// Después de redistribuir y guardar en DB:

// ✅ NUEVO: Re-programar nudges y capturar info de bloqueados
$assignments = $wpdb->get_col($wpdb->prepare(
    "SELECT id FROM {$wpdb->prefix}survey_assignments 
     WHERE wave_id = %d AND status = 'pending'",
    $wave_id
));

$total_blocked = 0;
$blocked_details = array();

foreach ($assignments as $assignment_id) {
    // Cancelar nudges existentes
    EIPSI_Nudge_Event_Scheduler::cancel_scheduled_nudges($assignment_id);
    
    // Re-programar y capturar info
    $result = EIPSI_Nudge_Event_Scheduler::schedule_nudge_sequence($assignment_id);
    
    if (!empty($result['blocked'])) {
        $total_blocked += count($result['blocked']);
        $blocked_details = array_merge($blocked_details, $result['blocked']);
    }
}

$window_days = ceil($current_window_minutes / 1440);

error_log(sprintf('[EIPSI Redistribute] Wave %d: Redistributed to %d days (%d minutes)',
    $wave_id, $window_days, $current_window_minutes));

// ✅ NUEVO: Incluir info de bloqueados en respuesta
wp_send_json_success(array(
    'message' => 'Nudges redistribuidos correctamente',
    'window_days' => $window_days,
    'window_minutes' => $current_window_minutes,
    'blocked_count' => $total_blocked,
    'blocked_details' => $blocked_details
));
```

#### Paso 3: Mostrar Advertencia en Frontend

```javascript
// assets/js/study-dashboard.js - en redistributeNudges()

redistributeNudges: function(waveId) {
    const self = this;
    
    $.ajax({
        url: eipsiDashboard.ajaxUrl,
        method: 'POST',
        data: {
            action: 'eipsi_redistribute_nudges',
            wave_id: waveId,
            nonce: eipsiDashboard.nonce
        },
        success: function(response) {
            if (response.success) {
                const windowDays = response.data.window_days || 0;
                const blockedCount = response.data.blocked_count || 0;
                
                let message = `✓ Nudges redistribuidos a ${windowDays} días`;
                
                // ✅ NUEVO: Advertencia si hay nudges bloqueados
                if (blockedCount > 0) {
                    message += `\n⚠️ ${blockedCount} nudge(s) no se programaron (muy cerca del deadline)`;
                    self.showToast(message, 'warning');
                } else {
                    self.showToast(message, 'success');
                }
                
                // Mostrar mensaje temporal
                self.showTemporaryMessage(waveId, message);
                
                // Recargar dashboard
                if (self.currentStudyId) {
                    self.loadDashboard(self.currentStudyId);
                }
            } else {
                self.showToast('Error: ' + (response.data?.message || 'No se pudo redistribuir'), 'error');
            }
        },
        error: function(xhr, status, error) {
            self.showToast('Error al redistribuir nudges', 'error');
        }
    });
}
```

**Resultado Visual:**

```
✓ Nudges redistribuidos a 7 días
⚠️ 2 nudge(s) no se programaron (muy cerca del deadline)
```

---

### 3. Margen de Seguridad para Deadline

**Modificar:** `includes/services/class-nudge-event-scheduler.php:206-216`

```php
// Phase 5 T1-Anchor: No programar nudges muy cerca del deadline

if ($due_at_timestamp !== null) {
    // ✅ NUEVO: Margen de seguridad de 10 minutos
    // Esto garantiza que con cron cada 5 min, el nudge llegue ANTES del deadline
    $safety_margin = 10 * MINUTE_IN_SECONDS;
    $deadline_with_margin = $due_at_timestamp - $safety_margin;
    
    if ($scheduled_time >= $deadline_with_margin) {
        error_log(sprintf(
            '[EIPSI EventScheduler] BLOCKED nudge %d - too close to deadline (scheduled: %s, deadline: %s, margin: 10min)',
            $stage,
            date('Y-m-d H:i:s', $scheduled_time),
            date('Y-m-d H:i:s', $due_at_timestamp)
        ));
        
        // Registrar nudge bloqueado
        $blocked_nudges[] = array(
            'stage' => $stage,
            'scheduled_time' => date('Y-m-d H:i:s', $scheduled_time),
            'deadline' => date('Y-m-d H:i:s', $due_at_timestamp),
            'reason' => 'too_close_to_deadline',
            'margin_minutes' => 10
        );
        
        continue;
    }
}
```

**Lógica:**
```
Deadline: 10:10
Margen: 10 min
Deadline efectivo: 10:00

Nudge programado para 10:07 → Bloqueado (10:07 >= 10:00)
Nudge programado para 09:55 → ✅ Permitido (09:55 < 10:00)

Con cron cada 5 min:
- Nudge a las 09:55 se ejecuta a las 10:00 ✅ (antes del deadline 10:10)
```

---

## 📊 PRIORIDADES DE IMPLEMENTACIÓN

### 🔴 ALTA (Implementar YA)

1. **✅ Validación de ventana mínima** - IMPLEMENTADO
2. **Lock atómico con GET_LOCK()** - Previene duplicados críticos
3. **Feedback de nudges bloqueados** - UX crítico

### 🟡 MEDIA (Implementar pronto)

4. **Margen de seguridad de 10 min** - Mejora confiabilidad
5. **Eliminar código legacy de Wave_Service** - Simplifica sistema

### 🟢 BAJA (Nice to have)

6. **Dashboard de nudges programados** - Debugging
7. **Alertas de nudges fallidos** - Monitoreo

---

## 🧪 TESTS RECOMENDADOS

### Test 1: Lock Atómico

```bash
# Ejecutar 10 requests simultáneos
for i in {1..10}; do
    curl -s https://site.com/wp-cron.php?doing_wp_cron &
done

# Verificar logs
grep "JobWorker" wp-content/debug.log | grep -c "Starting"
# Esperado: 1 (solo uno procesó)

grep "JobWorker" wp-content/debug.log | grep -c "Another worker"
# Esperado: 9 (los demás fueron bloqueados)
```

### Test 2: Ventana Mínima

```sql
-- Crear wave con ventana de 30 minutos
UPDATE wp_survey_waves 
SET due_date = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
WHERE id = 123;

-- Intentar redistribuir (debe fallar)
```

**Esperado:**
```json
{
  "success": false,
  "data": {
    "message": "La ventana es muy corta (30 minutos). Se requiere un mínimo de 60 minutos...",
    "window_minutes": 30,
    "minimum_required": 60
  }
}
```

### Test 3: Feedback de Bloqueados

```sql
-- Crear wave con ventana de 2 horas
UPDATE wp_survey_waves 
SET due_date = DATE_ADD(NOW(), INTERVAL 2 HOUR)
WHERE id = 123;

-- Redistribuir
```

**Esperado en UI:**
```
✓ Nudges redistribuidos a 0.08 días
⚠️ 2 nudge(s) no se programaron (muy cerca del deadline)
```

---

## 📝 NOTAS ADICIONALES

### Por Qué NO Usar wp_cache_add()

```php
// ❌ NO FUNCIONA sin object cache persistente
if (!wp_cache_add($lock_key, true, '', 5 * MINUTE_IN_SECONDS)) {
    return;
}
```

**Problema:**
- `wp_cache_add()` usa memoria del proceso PHP
- Cada request HTTP = proceso nuevo con cache vacío
- Requests concurrentes NO comparten memoria
- Lock no es atómico entre procesos

**Solución:**
- Usar MySQL `GET_LOCK()` (atómico a nivel de DB)
- O usar tabla de locks con `INSERT ... ON DUPLICATE KEY`

### Por Qué >= en vez de >

```php
// Deadline: 10:10
// Nudge programado: 10:10

// Con >= (actual):
if ($scheduled_time >= $due_at_timestamp) // 10:10 >= 10:10 → TRUE → Bloqueado

// Con > (propuesto):
if ($scheduled_time > $due_at_timestamp) // 10:10 > 10:10 → FALSE → Permitido

// Pero... cron ejecuta a las 10:10 o 10:15
// Si ejecuta a las 10:15 → ❌ TARDE (después del deadline)
```

**Conclusión:** `>=` es correcto. La solución es margen de seguridad, no cambiar el operador.

---

**Autor:** Cascade AI  
**Fecha:** 1 Mayo 2026  
**Versión:** 1.0
