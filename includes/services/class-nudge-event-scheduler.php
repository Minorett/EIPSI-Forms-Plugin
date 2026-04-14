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
    }
    
    /**
     * Programar secuencia completa de nudges cuando una wave se hace disponible
     * 
     * @param int $assignment_id ID de la asignación
     */
    public static function schedule_nudge_sequence($assignment_id) {
        global $wpdb;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[EIPSI EventScheduler] Scheduling nudge sequence for assignment %d', $assignment_id));
        }
        
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
            error_log(sprintf('[EIPSI EventScheduler] Assignment %d not found or not pending', $assignment_id));
            return false;
        }
        
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
        
        $scheduled_count = 0;
        
        // Programar cada nudge según su configuración
        for ($stage = 1; $stage <= 4; $stage++) {
            $nudge_key = "nudge_{$stage}";
            
            // Verificar si está habilitado
            if (!isset($nudge_config[$nudge_key]) || empty($nudge_config[$nudge_key]['enabled'])) {
                continue;
            }
            
            $config = $nudge_config[$nudge_key];
            $value = isset($config['value']) ? intval($config['value']) : ($stage * 24);
            $unit = isset($config['unit']) ? $config['unit'] : 'hours';
            
            // Convertir a segundos desde available_at
            $delay_seconds = self::convert_to_seconds($value, $unit);
            $scheduled_time = $available_at + $delay_seconds;
            
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
            $scheduled = wp_schedule_single_event($scheduled_time, self::NUDGE_EVENT_HOOK, array($event_args));
            
            if ($scheduled === false) {
                error_log(sprintf(
                    '[EIPSI EventScheduler] Failed to schedule nudge %d for assignment %d at %s',
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
        
        // También programar inmediatamente el Nudge 0 (disponibilidad)
        // Si hay Job Queue, se encola inmediatamente
        if (class_exists('EIPSI_Nudge_Job_Queue')) {
            EIPSI_Nudge_Job_Queue::enqueue('send_nudge_0', array(
                'assignment_id' => $assignment_id,
                'participant_id' => $assignment->participant_id,
                'wave_id' => $assignment->wave_id,
                'study_id' => $assignment->study_id
            ), 5);
            
            $scheduled_count++;
        }
        
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
        
        // Usar Job Queue si está disponible, sino enviar directamente
        if (class_exists('EIPSI_Nudge_Job_Queue')) {
            $job_type = "send_nudge_{$stage}";
            EIPSI_Nudge_Job_Queue::enqueue($job_type, array(
                'assignment_id' => $assignment_id,
                'participant_id' => $assignment->participant_id,
                'wave_id' => $assignment->wave_id,
                'study_id' => $assignment->study_id,
                'stage' => $stage
            ), 10);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[EIPSI EventScheduler] Enqueued nudge %d for assignment %d',
                    $stage,
                    $assignment_id
                ));
            }
        } else {
            // Fallback: envío directo (no recomendado)
            self::send_nudge_direct($assignment, $stage);
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
