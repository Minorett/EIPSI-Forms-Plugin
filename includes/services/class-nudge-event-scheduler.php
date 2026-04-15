<?php
/**
 * Nudge Event Scheduler
 * 
 * Sistema Event-Driven que programa envíos exactos de nudges
 * usando wp_schedule_single_event en lugar de polling.
 * 
 * @package EIPSI_Forms
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Event Scheduler para Nudges
 */
class EIPSI_Nudge_Event_Scheduler {
    
    /**
     * Hook para eventos de nudge
     */
    const NUDGE_EVENT_HOOK = 'eipsi_scheduled_nudge_event';
    
    /**
     * Inicializar el sistema de eventos
     */
    public static function init() {
        // Registrar el hook que procesará los eventos programados
        add_action(self::NUDGE_EVENT_HOOK, array(__CLASS__, 'execute_scheduled_nudge'), 10, 1);
        
        // Hook para cuando una wave se hace disponible
        add_action('eipsi_wave_available', array(__CLASS__, 'schedule_nudge_sequence'), 10, 1);
        
        // Hook para reintento programado cuando wave no estaba disponible inicialmente
        add_action('eipsi_wave_available_retry', array(__CLASS__, 'schedule_nudge_sequence'), 10, 1);
    }
    
    /**
     * Programar secuencia completa de nudges cuando una wave se hace disponible
     * 
     * @param int $assignment_id ID de la asignación
     */
    public static function schedule_nudge_sequence($assignment_id) {
        global $wpdb;
        
        error_log(sprintf('[EIPSI EventScheduler] ========================================'));
        error_log(sprintf('[EIPSI EventScheduler] schedule_nudge_sequence CALLED for assignment %d', $assignment_id));
        error_log(sprintf('[EIPSI EventScheduler] Current time: %s', current_time('mysql')));
        
        // Obtener datos de la asignación y su configuración de nudges
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, w.nudge_config, w.follow_up_reminders_enabled, 
                    a.available_at, p.email, p.first_name
             FROM {$wpdb->prefix}survey_assignments a
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             JOIN {$wpdb->prefix}survey_participants p ON a.participant_id = p.id
             WHERE a.id = %d AND a.status = 'pending'",
            $assignment_id
        ));
        
        if (!$assignment) {
            error_log(sprintf('[EIPSI EventScheduler] Assignment %d not found or not pending - ABORTING', $assignment_id));
            return false;
        }
        
        error_log(sprintf('[EIPSI EventScheduler] Assignment found: wave_id=%d, follow_up_reminders_enabled=%s', 
            $assignment->wave_id, 
            $assignment->follow_up_reminders_enabled ? 'YES' : 'NO'
        ));
        
        // Calcular timestamp base (cuándo la wave se hizo disponible)
        $available_at = !empty($assignment->available_at) 
            ? strtotime($assignment->available_at)
            : current_time('timestamp');
        
        if (!$available_at) {
            error_log(sprintf('[EIPSI EventScheduler] Invalid available_at for assignment %d', $assignment_id));
            return false;
        }
        
        // Parsear configuración de nudges
        $nudge_config = !empty($assignment->nudge_config) 
            ? json_decode($assignment->nudge_config, true) 
            : array();
        
        error_log(sprintf('[EIPSI EventScheduler] Nudge config: %s', json_encode($nudge_config)));
        
        $scheduled_count = 0;
        
        // v2.1.7 - PRIMERO ejecutar Nudge 0, LUEGO programar nudges 1-4 solo si tuvo éxito
        // Esto evita que queden nudges programados para un assignment que nunca recibió Nudge 0
        $nudge_0_success = false;
        
        if (class_exists('EIPSI_Nudge_Job_Queue')) {
            error_log(sprintf('[EIPSI EventScheduler] STEP 1: Executing Nudge 0 SYNCHRONOUSLY for assignment %d', $assignment_id));
            $nudge_0_result = EIPSI_Nudge_Job_Queue::execute_nudge_0(array(
                'assignment_id' => $assignment_id,
                'participant_id' => $assignment->participant_id,
                'wave_id' => $assignment->wave_id,
                'study_id' => $assignment->study_id
            ));
            
            if ($nudge_0_result['success']) {
                error_log(sprintf('[EIPSI EventScheduler] Nudge 0 executed SUCCESSFULLY for assignment %d', $assignment_id));
                $scheduled_count++;
                $nudge_0_success = true;
            } else {
                $error_msg = $nudge_0_result['error'] ?? 'unknown';
                error_log(sprintf('[EIPSI EventScheduler] Nudge 0 execution FAILED for assignment %d: %s', $assignment_id, $error_msg));
                
                // v2.1.7 - Si falló porque la wave no está disponible, programar reintento exacto
                if (strpos($error_msg, 'Wave not yet available') !== false || strpos($error_msg, 'not yet available') !== false) {
                    $retry_time = !empty($assignment->available_at) ? strtotime($assignment->available_at) : time() + 60;
                    error_log(sprintf(
                        '[EIPSI EventScheduler] RESCHEDULING: Nudge 0 for assignment %d will retry at %s (when wave becomes available)',
                        $assignment_id,
                        date('Y-m-d H:i:s', $retry_time)
                    ));
                    
                    // Programar reintento exacto en available_at
                    wp_schedule_single_event($retry_time, 'eipsi_wave_available_retry', array($assignment_id));
                    
                    // También encolar en Job Queue como backup
                    EIPSI_Nudge_Job_Queue::enqueue('send_nudge_0', array(
                        'assignment_id' => $assignment_id,
                        'participant_id' => $assignment->participant_id,
                        'wave_id' => $assignment->wave_id,
                        'study_id' => $assignment->study_id
                    ), 5, date('Y-m-d H:i:s', $retry_time));
                } else {
                    // Fallback genérico: retry en 5 minutos
                    EIPSI_Nudge_Job_Queue::enqueue('send_nudge_0', array(
                        'assignment_id' => $assignment_id,
                        'participant_id' => $assignment->participant_id,
                        'wave_id' => $assignment->wave_id,
                        'study_id' => $assignment->study_id
                    ), 5);
                }
                
                // Si Nudge 0 falló, NO programar los nudges de seguimiento
                error_log(sprintf('[EIPSI EventScheduler] ABORTING: Nudge 0 failed, not scheduling follow-up nudges for assignment %d', $assignment_id));
                error_log(sprintf('[EIPSI EventScheduler] COMPLETED: Scheduled %d nudges for assignment %d', $scheduled_count, $assignment_id));
                error_log(sprintf('[EIPSI EventScheduler] ========================================'));
                return $scheduled_count;
            }
        } else {
            error_log(sprintf('[EIPSI EventScheduler] EIPSI_Nudge_JobQueue class NOT FOUND - cannot execute Nudge 0'));
            return 0;
        }
        
        // v2.1.7 - STEP 2: Solo si Nudge 0 tuvo éxito, programar nudges 1-4
        if ($nudge_0_success && !empty($assignment->follow_up_reminders_enabled)) {
            error_log(sprintf('[EIPSI EventScheduler] STEP 2: Scheduling follow-up nudges for assignment %d', $assignment_id));
            
            // v2.1.1 - Los nudges son acumulativos: cada uno empieza DESPUÉS del anterior
            $cumulative_delay = 0;
            
            for ($stage = 1; $stage <= 4; $stage++) {
                $nudge_key = "nudge_{$stage}";
                
                // Verificar si está habilitado
                if (!isset($nudge_config[$nudge_key]) || empty($nudge_config[$nudge_key]['enabled'])) {
                    continue;
                }
                
                $config = $nudge_config[$nudge_key];
                $value = isset($config['value']) ? floatval($config['value']) : ($stage * 24);
                $unit = isset($config['unit']) ? $config['unit'] : 'hours';
                
                // v2.1.1 - Acumular el delay del nudge anterior
                $delay_seconds = self::convert_to_seconds($value, $unit);
                $cumulative_delay += $delay_seconds;
                $scheduled_time = $available_at + $cumulative_delay;
                
                error_log(sprintf('[EIPSI EventScheduler] Nudge %d: +%d seconds (total: %d seconds from available)', 
                    $stage, $delay_seconds, $cumulative_delay));
                
                // No programar en el pasado
                if ($scheduled_time <= current_time('timestamp')) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log(sprintf(
                            '[EIPSI EventScheduler] Skipping nudge %d for assignment %d - time already passed',
                            $stage,
                            $assignment_id
                        ));
                    }
                    continue;
                }
                
                // Programar el evento exacto
                $event_args = array(
                    'assignment_id' => $assignment_id,
                    'stage' => $stage,
                    'scheduled_at' => $scheduled_time
                );
                
                // Crear identificador único para este evento
                $event_key = self::get_event_key($assignment_id, $stage);
                
                // Limpiar evento previo si existe (evita duplicados)
                wp_clear_scheduled_hook(self::NUDGE_EVENT_HOOK, array($event_args));
                
                // Programar nuevo evento
                error_log(sprintf('[EIPSI EventScheduler] Scheduling nudge %d at %s (delay: %d seconds)', 
                    $stage, date('Y-m-d H:i:s', $scheduled_time), $delay_seconds));
                
                $scheduled = wp_schedule_single_event($scheduled_time, self::NUDGE_EVENT_HOOK, array($event_args));
                
                if ($scheduled === false) {
                    error_log(sprintf(
                        '[EIPSI EventScheduler] FAILED to schedule nudge %d for assignment %d at %s',
                        $stage,
                        $assignment_id,
                        date('Y-m-d H:i:s', $scheduled_time)
                    ));
                } else {
                    $scheduled_count++;
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log(sprintf(
                            '[EIPSI EventScheduler] Scheduled nudge %d for assignment %d at %s (delay: %d %s)',
                            $stage,
                            $assignment_id,
                            date('Y-m-d H:i:s', $scheduled_time),
                            $value,
                            $unit
                        ));
                    }
                }
            }
        } else if (!$nudge_0_success) {
            error_log(sprintf('[EIPSI EventScheduler] SKIPPING follow-up nudges: Nudge 0 failed for assignment %d', $assignment_id));
        } else {
            error_log(sprintf('[EIPSI EventScheduler] SKIPPING follow-up nudges: disabled for assignment %d', $assignment_id));
        }
        
        error_log(sprintf('[EIPSI EventScheduler] COMPLETED: Scheduled %d nudges for assignment %d', $scheduled_count, $assignment_id));
        error_log(sprintf('[EIPSI EventScheduler] ========================================'));
        
        return $scheduled_count;
    }
    
    /**
     * Ejecutar un nudge programado
     * 
     * @param array $args Argumentos del evento: assignment_id, stage, scheduled_at
     */
    public static function execute_scheduled_nudge($args) {
        if (!is_array($args) || !isset($args['assignment_id']) || !isset($args['stage'])) {
            error_log('[EIPSI EventScheduler] Invalid event arguments');
            return;
        }
        
        $assignment_id = intval($args['assignment_id']);
        $stage = intval($args['stage']);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[EIPSI EventScheduler] Executing scheduled nudge %d for assignment %d',
                $stage,
                $assignment_id
            ));
        }
        
        // Verificar que sigue siendo válido enviar este nudge
        global $wpdb;
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, w.follow_up_reminders_enabled 
             FROM {$wpdb->prefix}survey_assignments a
             JOIN {$wpdb->prefix}survey_waves w ON a.wave_id = w.id
             WHERE a.id = %d",
            $assignment_id
        ));
        
        if (!$assignment) {
            error_log(sprintf('[EIPSI EventScheduler] Assignment %d no longer exists', $assignment_id));
            return;
        }
        
        // Si ya completó la toma, no enviar
        if ($assignment->status !== 'pending') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[EIPSI EventScheduler] Assignment %d is %s, skipping nudge %d',
                    $assignment_id,
                    $assignment->status,
                    $stage
                ));
            }
            return;
        }
        
        // Verificar que reminders estén habilitados para follow-ups
        if ($stage > 0 && empty($assignment->follow_up_reminders_enabled)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[EIPSI EventScheduler] Follow-up reminders disabled for assignment %d',
                    $assignment_id
                ));
            }
            return;
        }
        
        // Verificar stage correcto (no enviar nudge 2 si nunca se envió el 1)
        $expected_reminder_count = $stage; // Nudge 1 espera reminder_count = 1
        if (intval($assignment->reminder_count) != $expected_reminder_count) {
            error_log(sprintf(
                '[EIPSI EventScheduler] Invalid stage for assignment %d: expected reminder_count=%d, got %d',
                $assignment_id,
                $expected_reminder_count,
                $assignment->reminder_count
            ));
            return;
        }
        
        // v2.1.6 - Lock Transacción con SELECT FOR UPDATE
        // Garantiza que solo un nudge por assignment se ejecute a la vez,
        // manteniendo el timing exacto incluso con intervalos cortos (72 segundos)
        
        $transaction_started = false;
        $result = null;
        
        try {
            // Iniciar transacción
            $wpdb->query('START TRANSACTION');
            $transaction_started = true;
            
            // Bloquear la fila del assignment - ningún otro proceso puede leer/escribir hasta COMMIT/ROLLBACK
            $locked_assignment = $wpdb->get_row($wpdb->prepare(
                "SELECT reminder_count, status, participant_id, wave_id, study_id 
                 FROM {$wpdb->prefix}survey_assignments 
                 WHERE id = %d 
                 FOR UPDATE",
                $assignment_id
            ));
            
            if (!$locked_assignment) {
                $wpdb->query('ROLLBACK');
                error_log(sprintf('[EIPSI EventScheduler] Assignment %d no longer exists (locked check)', $assignment_id));
                return;
            }
            
            // Verificar que sigue pendiente
            if ($locked_assignment->status !== 'pending') {
                $wpdb->query('ROLLBACK');
                error_log(sprintf(
                    '[EIPSI EventScheduler] Assignment %d is %s (locked check), skipping nudge %d',
                    $assignment_id,
                    $locked_assignment->status,
                    $stage
                ));
                return;
            }
            
            // Verificar stage correcto con el count bloqueado
            if (intval($locked_assignment->reminder_count) != $expected_reminder_count) {
                $actual_count = intval($locked_assignment->reminder_count);
                
                // Determinar si es duplicado o el anterior aún no terminó
                if ($actual_count < $expected_reminder_count) {
                    // Nudge anterior aún no completó, re-encolar para 1 minuto (timing más cercano que 5 min)
                    error_log(sprintf(
                        '[EIPSI EventScheduler] LOCK: Nudge %d for assignment %d waiting - count is %d, expected %d. Re-enqueuing for 1 min',
                        $stage,
                        $assignment_id,
                        $actual_count,
                        $expected_reminder_count
                    ));
                    
                    EIPSI_Nudge_Job_Queue::enqueue(
                        "send_nudge_{$stage}",
                        array(
                            'assignment_id' => $assignment_id,
                            'participant_id' => $locked_assignment->participant_id,
                            'wave_id' => $locked_assignment->wave_id,
                            'study_id' => $locked_assignment->study_id,
                            'stage' => $stage
                        ),
                        10,
                        date('Y-m-d H:i:s', strtotime('+1 minute'))
                    );
                } else {
                    // Ya se envió este nudge (count > expected)
                    error_log(sprintf(
                        '[EIPSI EventScheduler] LOCK: Nudge %d for assignment %d already sent (count=%d > expected=%d)',
                        $stage,
                        $assignment_id,
                        $actual_count,
                        $expected_reminder_count
                    ));
                }
                
                $wpdb->query('ROLLBACK');
                return;
            }
            
            // TODAS LAS VERIFICACIONES PASARON - ejecutar nudge dentro de la transacción
            if (class_exists('EIPSI_Nudge_Job_Queue')) {
                $payload = array(
                    'assignment_id' => $assignment_id,
                    'participant_id' => $locked_assignment->participant_id,
                    'wave_id' => $locked_assignment->wave_id,
                    'study_id' => $locked_assignment->study_id,
                    'stage' => $stage
                );
                
                // Ejecutar - esto enviará el email y actualizará reminder_count DENTRO de la transacción
                $result = EIPSI_Nudge_Job_Queue::execute_nudge_followup($payload, $stage);
                
                if ($result['success']) {
                    $wpdb->query('COMMIT');
                    error_log(sprintf(
                        '[EIPSI EventScheduler] LOCK: Nudge %d executed and COMMITTED for assignment %d',
                        $stage,
                        $assignment_id
                    ));
                } else {
                    // Falló el envío - rollback para que se reintente
                    $wpdb->query('ROLLBACK');
                    error_log(sprintf(
                        '[EIPSI EventScheduler] LOCK: Nudge %d FAILED for assignment %d: %s - ROLLBACK',
                        $stage,
                        $assignment_id,
                        $result['error'] ?? 'unknown'
                    ));
                    
                    // Re-encolar para reintento en 1 minuto
                    EIPSI_Nudge_Job_Queue::enqueue("send_nudge_{$stage}", $payload, 10);
                }
            } else {
                // Fallback sin Job Queue
                $wpdb->query('ROLLBACK');
                self::send_nudge_direct((object)$locked_assignment, $stage);
            }
            
        } catch (Exception $e) {
            if ($transaction_started) {
                $wpdb->query('ROLLBACK');
            }
            error_log(sprintf(
                '[EIPSI EventScheduler] LOCK: EXCEPTION for assignment %d nudge %d: %s',
                $assignment_id,
                $stage,
                $e->getMessage()
            ));
        }
    }
    
    /**
     * Enviar nudge directamente (fallback sin Job Queue)
     */
    private static function send_nudge_direct($assignment, $stage) {
        // Esta función solo se usa si Job Queue no está disponible
        // Implementación básica para mantener compatibilidad
        
        if (!class_exists('EIPSI_Email_Service')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/services/class-email-service.php';
        }
        
        $result = EIPSI_Email_Service::send_wave_reminder_email(
            $assignment->study_id,
            $assignment->participant_id,
            (object) array(
                'id' => $assignment->wave_id,
                'name' => 'Wave',
                'wave_index' => 1
            ),
            $stage
        );
        
        if ($result) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'survey_assignments',
                array('reminder_count' => $stage + 1),
                array('id' => $assignment->id),
                array('%d'),
                array('%d')
            );
        }
        
        return $result;
    }
    
    /**
     * Cancelar todos los eventos programados para una asignación
     * 
     * @param int $assignment_id ID de la asignación
     */
    public static function cancel_scheduled_nudges($assignment_id) {
        for ($stage = 0; $stage <= 4; $stage++) {
            $event_args = array(
                'assignment_id' => $assignment_id,
                'stage' => $stage,
                'scheduled_at' => 0
            );
            
            wp_clear_scheduled_hook(self::NUDGE_EVENT_HOOK, array($event_args));
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[EIPSI EventScheduler] Cancelled all scheduled nudges for assignment %d',
                $assignment_id
            ));
        }
    }
    
    /**
     * Obtener lista de eventos programados para debugging
     */
    public static function get_scheduled_events() {
        $crons = _get_cron_array();
        $events = array();
        
        if (empty($crons)) {
            return $events;
        }
        
        foreach ($crons as $timestamp => $cron) {
            if (isset($cron[self::NUDGE_EVENT_HOOK])) {
                foreach ($cron[self::NUDGE_EVENT_HOOK] as $key => $event) {
                    $args = isset($event['args'][0]) ? $event['args'][0] : array();
                    $events[] = array(
                        'timestamp' => $timestamp,
                        'date' => date('Y-m-d H:i:s', $timestamp),
                        'assignment_id' => isset($args['assignment_id']) ? $args['assignment_id'] : null,
                        'stage' => isset($args['stage']) ? $args['stage'] : null
                    );
                }
            }
        }
        
        return $events;
    }
    
    /**
     * Generar clave única para evento
     */
    private static function get_event_key($assignment_id, $stage) {
        return "eipsi_nudge_{$assignment_id}_{$stage}";
    }
    
    /**
     * Convertir valor+unidad a segundos
     */
    private static function convert_to_seconds($value, $unit) {
        switch ($unit) {
            case 'minutes':
                return $value * 60;
            case 'hours':
                return $value * 3600;
            case 'days':
                return $value * 86400;
            default:
                return $value * 3600; // Default a horas
        }
    }
}

// Inicializar al cargar
add_action('init', array('EIPSI_Nudge_Event_Scheduler', 'init'));
